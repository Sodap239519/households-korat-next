<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\AdminNotificationService;
use App\Services\LineNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Checkout — สร้างคำสั่งซื้อจากตะกร้า
 * multi-vendor: ตะกร้าข้ามกลุ่ม → แตกเป็น 1 ออเดอร์ต่อ 1 กลุ่มผู้ขาย
 */
class CheckoutController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'integer'],
            'items.*.qty'            => ['required', 'integer', 'min:1'],
            'shipping_name'          => ['required', 'string', 'max:255'],
            'shipping_phone'         => ['required', 'string', 'max:30'],
            'shipping_address'       => ['required', 'string', 'max:255'],
            'shipping_sub_district'  => ['nullable', 'string', 'max:100'],
            'shipping_district'      => ['nullable', 'string', 'max:100'],
            'shipping_province'      => ['nullable', 'string', 'max:100'],
            'shipping_zipcode'       => ['nullable', 'string', 'max:10'],
            'shipping_note'          => ['nullable', 'string', 'max:500'],
        ], [], [
            'shipping_name'    => 'ชื่อผู้รับ',
            'shipping_phone'   => 'เบอร์โทร',
            'shipping_address' => 'ที่อยู่',
        ]);

        $user = $request->user();
        $qtyById = collect($data['items'])->pluck('qty', 'product_id');

        // โหลดสินค้าที่เผยแพร่จริง พร้อมล็อกแถวกันสต็อกชน
        $created = DB::transaction(function () use ($qtyById, $data, $user) {
            $products = Product::where('status', Product::STATUS_PUBLISHED)
                ->whereIn('id', $qtyById->keys())
                ->lockForUpdate()
                ->get();

            abort_if($products->isEmpty(), 422, 'ไม่พบสินค้าในตะกร้า');

            $orders = [];
            // แตกตามกลุ่มผู้ขาย
            foreach ($products->groupBy('seller_group_id') as $groupId => $groupProducts) {
                $order = new Order([
                    'order_no'              => self::makeOrderNo(),
                    'user_id'               => $user->id,
                    'seller_group_id'       => $groupId,
                    'status'                => Order::STATUS_PENDING_PAYMENT,
                    'shipping_name'         => $data['shipping_name'],
                    'shipping_phone'        => $data['shipping_phone'],
                    'shipping_address'      => $data['shipping_address'],
                    'shipping_sub_district' => $data['shipping_sub_district'] ?? null,
                    'shipping_district'     => $data['shipping_district'] ?? null,
                    'shipping_province'     => $data['shipping_province'] ?? null,
                    'shipping_zipcode'      => $data['shipping_zipcode'] ?? null,
                    'shipping_note'         => $data['shipping_note'] ?? null,
                ]);
                $order->save();

                $subtotal = 0;
                foreach ($groupProducts as $product) {
                    $qty = (int) $qtyById[$product->id];
                    abort_if($product->stock_qty < $qty, 422, "สินค้า \"{$product->name}\" คงเหลือไม่พอ (เหลือ {$product->stock_qty})");

                    $unit = (float) ($product->sale_price ?? $product->price);
                    $order->items()->create([
                        'product_id'   => $product->id,
                        'product_name' => $product->name,
                        'unit_price'   => $unit,
                        'qty'          => $qty,
                    ]);
                    $subtotal += $unit * $qty;

                    $product->decrement('stock_qty', $qty);
                }

                $order->update([
                    'subtotal'     => $subtotal,
                    'shipping_fee' => 0,
                    'discount'     => 0,
                    'total'        => $subtotal,
                ]);

                $order->statusHistories()->create([
                    'status'     => Order::STATUS_PENDING_PAYMENT,
                    'note'       => 'สร้างคำสั่งซื้อ',
                    'changed_by' => $user->id,
                    'created_at' => now(),
                ]);

                $orders[] = $order;
            }

            return $orders;
        });

        // แจ้งเตือน LINE + admin notification ให้กลุ่มผู้ขาย (นอก transaction)
        foreach ($created as $order) {
            LineNotifier::orderPlaced($order);
            AdminNotificationService::orderPlaced($order);
        }

        return response()->json([
            'message'    => 'สร้างคำสั่งซื้อสำเร็จ',
            'orders'     => collect($created)->map(fn ($o) => [
                'order_no'        => $o->order_no,
                'seller_group_id' => $o->seller_group_id,
                'total'           => (float) $o->total,
            ])->values(),
            'order_nos'  => collect($created)->pluck('order_no')->values(),
        ], 201);
    }

    private static function makeOrderNo(): string
    {
        do {
            $no = 'OR' . now()->format('ymd') . strtoupper(Str::random(4));
        } while (Order::where('order_no', $no)->exists());
        return $no;
    }
}
