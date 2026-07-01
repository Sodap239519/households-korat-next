<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Models\FlashSaleEvent;
use App\Models\FlashSaleEventItem;
use App\Models\Product;
use App\Services\CustomerNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FlashSaleEventController extends Controller
{
    /** GET /market/flash-sale-events */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');

        $events = FlashSaleEvent::withCount('items')
            ->with('creator:id,name')
            ->orderByDesc('starts_at')
            ->get();

        return response()->json($events);
    }

    /** POST /market/flash-sale-events */
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'เฉพาะ superadmin เท่านั้น');

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'starts_at'   => ['required', 'date'],
            'ends_at'     => ['required', 'date', 'after:starts_at'],
            'is_active'   => ['boolean'],
        ]);

        $this->assertNoOverlap($data['starts_at'], $data['ends_at']);

        $event = FlashSaleEvent::create([...$data, 'created_by' => $request->user()->id]);

        return response()->json($event->loadCount('items'), 201);
    }

    /** GET /market/flash-sale-events/{event} */
    public function show(Request $request, FlashSaleEvent $flashSaleEvent): JsonResponse
    {
        abort_unless($request->user()->isMarketStaff(), 403);

        $flashSaleEvent->loadCount('items');

        return response()->json($flashSaleEvent);
    }

    /** PUT /market/flash-sale-events/{event} */
    public function update(Request $request, FlashSaleEvent $flashSaleEvent): JsonResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'เฉพาะ superadmin เท่านั้น');

        $data = $request->validate([
            'title'       => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'starts_at'   => ['sometimes', 'required', 'date'],
            'ends_at'     => ['sometimes', 'required', 'date', 'after:starts_at'],
            'is_active'   => ['boolean'],
        ]);

        $startsAt = $data['starts_at'] ?? $flashSaleEvent->starts_at;
        $endsAt   = $data['ends_at']   ?? $flashSaleEvent->ends_at;
        $this->assertNoOverlap($startsAt, $endsAt, $flashSaleEvent->id);

        $wasInactive = ! $flashSaleEvent->is_currently_active;
        $flashSaleEvent->update($data);
        $flashSaleEvent->refresh();

        // แจ้งเตือนลูกค้าเมื่อ event เพิ่งถูกเปิดใช้งาน (is_active → true) และอยู่ในช่วงเวลา
        if ($wasInactive && $flashSaleEvent->is_currently_active) {
            $productIds = $flashSaleEvent->items()->pluck('product_id')->all();
            if ($productIds) {
                CustomerNotificationService::cartFlashSale($flashSaleEvent, $productIds);
            }
        }

        return response()->json($flashSaleEvent->loadCount('items'));
    }

    /** DELETE /market/flash-sale-events/{event} */
    public function destroy(Request $request, FlashSaleEvent $flashSaleEvent): JsonResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'เฉพาะ superadmin เท่านั้น');

        $flashSaleEvent->delete();

        return response()->json(['message' => 'ลบ event แล้ว']);
    }

    /** GET /market/flash-sale-events/{event}/items */
    public function items(Request $request, FlashSaleEvent $flashSaleEvent): JsonResponse
    {
        abort_unless($request->user()->isMarketStaff(), 403);

        $query = $flashSaleEvent->items()->with([
            'product' => fn($q) => $q->select('id', 'name', 'price', 'stock_qty', 'seller_group_id')
                                     ->with(['images' => fn($i) => $i->where('is_primary', true)->select('product_id', 'path')]),
            'sellerGroup:id,name',
        ]);

        // เจ้าหน้าที่กลุ่ม: เห็นเฉพาะสินค้ากลุ่มตัวเอง
        if ($scope = $request->user()->sellerGroupScope()) {
            $query->where('seller_group_id', $scope);
        }

        return response()->json($query->orderBy('sort_order')->get());
    }

    /** POST /market/flash-sale-events/{event}/items */
    public function addItems(Request $request, FlashSaleEvent $flashSaleEvent): JsonResponse
    {
        abort_unless($request->user()->isMarketStaff(), 403);

        $data = $request->validate([
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.sale_price' => ['required', 'numeric', 'min:0'],
            'items.*.stock_limit'=> ['nullable', 'integer', 'min:1'],
        ]);

        $user            = $request->user();
        $scope           = $user->sellerGroupScope();
        $added           = 0;
        $addedProductIds = [];

        foreach ($data['items'] as $item) {
            $product = Product::find($item['product_id']);
            if (! $product) continue;

            // เจ้าหน้าที่กลุ่ม: ต้องเป็นสินค้ากลุ่มตัวเอง
            if ($scope && $product->seller_group_id !== $scope) continue;

            FlashSaleEventItem::updateOrCreate(
                ['event_id' => $flashSaleEvent->id, 'product_id' => $product->id],
                [
                    'seller_group_id' => $product->seller_group_id,
                    'sale_price'      => $item['sale_price'],
                    'stock_limit'     => $item['stock_limit'] ?? null,
                ]
            );
            $added++;
            $addedProductIds[] = $product->id;
        }

        // แจ้งเตือนลูกค้าที่มีสินค้าเหล่านี้ในตะกร้า (เฉพาะ event ที่กำลังดำเนินการ)
        if ($added > 0 && $flashSaleEvent->is_currently_active) {
            CustomerNotificationService::cartFlashSale($flashSaleEvent, $addedProductIds);
        }

        return response()->json(['message' => "เพิ่ม {$added} สินค้าใน event แล้ว", 'added' => $added]);
    }

    private function assertNoOverlap(string $startsAt, string $endsAt, ?int $excludeId = null): void
    {
        $overlap = FlashSaleEvent::where('is_active', true)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'starts_at' => ['ช่วงเวลานี้ซ้ำกับ Flash Sale Event ที่มีอยู่แล้ว'],
            ]);
        }
    }

    /** DELETE /market/flash-sale-events/{event}/items/{item} */
    public function removeItem(Request $request, FlashSaleEvent $flashSaleEvent, FlashSaleEventItem $item): JsonResponse
    {
        abort_unless($request->user()->isMarketStaff(), 403);
        abort_unless($item->event_id === $flashSaleEvent->id, 404);

        $scope = $request->user()->sellerGroupScope();
        if ($scope && $item->seller_group_id !== $scope) {
            abort(403, 'ไม่มีสิทธิ์ลบสินค้ากลุ่มอื่น');
        }

        $item->delete();

        return response()->json(['message' => 'ลบสินค้าออกจาก event แล้ว']);
    }
}
