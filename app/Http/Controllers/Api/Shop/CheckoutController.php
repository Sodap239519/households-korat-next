<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerShippingOption;
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
            // per-group shipping (ใหม่) — ถ้าไม่ส่งจะ fallback ไป shipping_option_id เดิม
            'group_shippings'                       => ['nullable', 'array'],
            'group_shippings.*.group_id'            => ['required_with:group_shippings', 'integer'],
            'group_shippings.*.shipping_option_id'  => ['nullable', 'integer', 'exists:seller_shipping_options,id'],
            // legacy fallback
            'shipping_option_id' => ['nullable', 'integer', 'exists:seller_shipping_options,id'],
            'payment_method'     => ['nullable', 'string', 'in:online,cod'],
        ], [], [
            'shipping_name'    => 'ชื่อผู้รับ',
            'shipping_phone'   => 'เบอร์โทร',
            'shipping_address' => 'ที่อยู่',
        ]);

        $user    = $request->user();
        $qtyById = collect($data['items'])->pluck('qty', 'product_id');

        // สร้าง map {group_id → SellerShippingOption} จาก group_shippings หรือ legacy shipping_option_id
        $groupShippingMap = [];   // group_id (int) → SellerShippingOption|null

        if (!empty($data['group_shippings'])) {
            $optIds = array_filter(array_column($data['group_shippings'], 'shipping_option_id'));
            $opts   = SellerShippingOption::whereIn('id', $optIds)->where('is_active', true)->get()->keyBy('id');
            foreach ($data['group_shippings'] as $gs) {
                $gid = (int) $gs['group_id'];
                $groupShippingMap[$gid] = $opts->get($gs['shipping_option_id'] ?? 0);
            }
        }

        // legacy: ถ้าไม่ส่ง group_shippings ให้ใช้ shipping_option_id เดิมเป็น fallback ทุกกลุ่ม
        $legacyOpt = null;
        if (empty($data['group_shippings']) && !empty($data['shipping_option_id'])) {
            $legacyOpt = SellerShippingOption::where('id', $data['shipping_option_id'])
                ->where('is_active', true)->first();
        }

        // คำนวณ allowedPaymentMethods = intersection ของทุกกลุ่ม
        $allAllowed = [];
        foreach ($groupShippingMap as $opt) {
            $allAllowed[] = $opt ? ($opt->allowed_payment_methods ?: ['online']) : ['online'];
        }
        if (empty($allAllowed) && $legacyOpt) {
            $allAllowed[] = $legacyOpt->allowed_payment_methods ?: ['online'];
        }
        $allowedPaymentMethods = empty($allAllowed)
            ? ['online', 'cod']
            : array_values(array_intersect(...$allAllowed) ?: ['online']);

        $paymentMethod = $data['payment_method'] ?? 'online';
        if (!in_array($paymentMethod, $allowedPaymentMethods)) {
            $paymentMethod = $allowedPaymentMethods[0];
        }

        // โหลดสินค้าที่เผยแพร่จริง พร้อมล็อกแถวกันสต็อกชน
        $created = DB::transaction(function () use ($qtyById, $data, $user, $groupShippingMap, $legacyOpt, $paymentMethod) {
            $products = Product::where('status', Product::STATUS_PUBLISHED)
                ->whereIn('id', $qtyById->keys())
                ->lockForUpdate()
                ->get();

            abort_if($products->isEmpty(), 422, 'ไม่พบสินค้าในตะกร้า');

            $orders = [];
            // แตกตามกลุ่มผู้ขาย
            foreach ($products->groupBy('seller_group_id') as $groupId => $groupProducts) {
                // หา shipping option ของกลุ่มนี้ (per-group ก่อน → legacy fallback)
                $shipOpt = $groupShippingMap[(int) $groupId] ?? $legacyOpt;

                // COD: ข้ามขั้นตอนชำระเงินออนไลน์ → เริ่มต้นที่ confirmed ทันที
                $initialStatus = $paymentMethod === 'cod'
                    ? Order::STATUS_CONFIRMED
                    : Order::STATUS_PENDING_PAYMENT;

                $order = new Order([
                    'order_no'              => self::makeOrderNo(),
                    'user_id'               => $user->id,
                    'seller_group_id'       => $groupId,
                    'status'                => $initialStatus,
                    'payment_method'        => $paymentMethod,
                    'shipping_name'         => $data['shipping_name'],
                    'shipping_phone'        => $data['shipping_phone'],
                    'shipping_address'      => $data['shipping_address'],
                    'shipping_sub_district' => $data['shipping_sub_district'] ?? null,
                    'shipping_district'     => $data['shipping_district'] ?? null,
                    'shipping_province'     => $data['shipping_province'] ?? null,
                    'shipping_zipcode'      => $data['shipping_zipcode'] ?? null,
                    'shipping_note'         => $data['shipping_note'] ?? null,
                    'shipping_option_id'    => $shipOpt?->id,
                    'shipping_method'       => $shipOpt?->name,
                    'shipping_carrier'      => $shipOpt?->carrier,
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

                // คำนวณส่วนลดรวม (จาก effective_price vs price ของแต่ละสินค้า)
                $discount = 0;
                foreach ($groupProducts as $product) {
                    $qty = (int) $qtyById[$product->id];
                    $discount += max(0, ((float)$product->price - (float)($product->sale_price ?? $product->price)) * $qty);
                }

                $shippingFee = $shipOpt ? (float) $shipOpt->fee : 0;

                $order->update([
                    'subtotal'     => $subtotal,
                    'shipping_fee' => $shippingFee,
                    'discount'     => $discount,
                    'total'        => $subtotal + $shippingFee - $discount,
                ]);

                $statusNote = $paymentMethod === 'cod'
                    ? 'สร้างคำสั่งซื้อ (ชำระเงินปลายทาง)'
                    : 'สร้างคำสั่งซื้อ';
                $order->statusHistories()->create([
                    'status'     => $initialStatus,
                    'note'       => $statusNote,
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
