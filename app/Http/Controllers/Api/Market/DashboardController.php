<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductComment;
use App\Models\ProductReview;
use App\Models\ReturnRequest;
use App\Models\SellerApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * หลังบ้าน — แดชบอร์ดยอดขาย (scope ตามกลุ่ม)
 */
class DashboardController extends Controller
{
    /** สถานะที่ถือว่าขายได้แล้ว (ยืนยันชำระเงินเป็นต้นไป) */
    private const PAID = ['confirmed', 'processing', 'shipped', 'delivered', 'completed'];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');

        $personal = $user->sellerPersonalScope();
        $scope    = $user->sellerGroupScope();
        $groupId  = ($personal === null && $scope === null) ? $request->input('group_id') : null;

        // staff ทั่วไปเห็นเฉพาะข้อมูลตัวเอง, admin เห็นทั้งกลุ่ม, superadmin เห็นทั้งหมด
        $orderScope = function ($q) use ($personal, $scope, $groupId) {
            if ($personal !== null) {
                $q->whereHas('items.product', fn ($p) => $p->where('seller_user_id', $personal));
            } elseif ($scope !== null) {
                $q->where('seller_group_id', $scope);
            } elseif ($groupId) {
                $q->where('seller_group_id', $groupId);
            }
        };
        $productScope = function ($q) use ($personal, $scope, $groupId) {
            if ($personal !== null) {
                $q->where('seller_user_id', $personal);
            } elseif ($scope !== null) {
                $q->where('seller_group_id', $scope);
            } elseif ($groupId) {
                $q->where('seller_group_id', $groupId);
            }
        };

        // ===== KPI =====
        $salesTotal    = Order::query()->tap($orderScope)->whereIn('status', self::PAID)->sum('total');
        $ordersTotal   = Order::query()->tap($orderScope)->count();
        $awaiting      = Order::query()->tap($orderScope)->where('status', 'awaiting_confirm')->count();
        $toShip        = Order::query()->tap($orderScope)->whereIn('status', ['confirmed', 'processing'])->count();
        $productsCount = Product::query()->tap($productScope)->count();
        $lowStock      = Product::query()->tap($productScope)->where('stock_qty', '<', 10)->count();

        // ===== สถานะออเดอร์ (สำหรับ donut) =====
        $statusCounts = Order::query()->tap($orderScope)
            ->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')->pluck('c', 'status');

        // ===== ยอดขายตามช่วงวัน =====
        if ($request->filled('from_date') && $request->filled('to_date')) {
            // กำหนดเองจาก date picker
            $from = Carbon::parse($request->input('from_date'))->startOfDay();
            $to   = Carbon::parse($request->input('to_date'))->endOfDay();
            $days = max(1, min(180, (int) $from->diffInDays($to) + 1));
        } else {
            $days = max(7, min(90, (int) $request->input('days', 14)));
            $from = Carbon::today()->subDays($days - 1)->startOfDay();
            $to   = Carbon::today()->endOfDay();
        }

        $rows = Order::query()->tap($orderScope)
            ->whereIn('status', self::PAID)
            ->whereBetween('created_at', [$from, $to])
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('SUM(total) as t'))
            ->groupBy('d')->pluck('t', 'd');

        $salesByDay = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->startOfDay()->addDays($i);
            $key = $day->toDateString();
            $salesByDay[] = [
                'date'  => $day->format('d/m'),
                'total' => (float) ($rows[$key] ?? 0),
            ];
        }

        // ===== สินค้าขายดี (top 5 ตามจำนวนที่ขาย) =====
        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->when($personal !== null, fn ($q) => $q
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->where('products.seller_user_id', $personal))
            ->when($personal === null && $scope !== null, fn ($q) => $q->where('orders.seller_group_id', $scope))
            ->when($personal === null && $scope === null && $groupId, fn ($q) => $q->where('orders.seller_group_id', $groupId))
            ->whereIn('orders.status', self::PAID)
            ->select('order_items.product_name', DB::raw('SUM(order_items.qty) as qty'), DB::raw('SUM(order_items.line_total) as revenue'))
            ->groupBy('order_items.product_name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        return response()->json([
            'summary' => [
                'sales_total'    => (float) $salesTotal,
                'orders_total'   => $ordersTotal,
                'awaiting'       => $awaiting,
                'to_ship'        => $toShip,
                'products_count' => $productsCount,
                'low_stock'      => $lowStock,
            ],
            'status_counts' => $statusCounts,
            'sales_by_day'  => $salesByDay,
            'top_products'  => $topProducts,
        ]);
    }

    /** GET /market/dashboard/badges — นับรายการที่ต้องดำเนินการ (lightweight, poll ได้บ่อย) */
    public function badges(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');

        $personal = $user->sellerPersonalScope();
        $scope    = $user->sellerGroupScope();

        $orderScope = function ($q) use ($personal, $scope) {
            if ($personal !== null) {
                $q->whereHas('items.product', fn ($p) => $p->where('seller_user_id', $personal));
            } elseif ($scope !== null) {
                $q->where('seller_group_id', $scope);
            }
        };
        $productScope = function ($q) use ($personal, $scope) {
            if ($personal !== null) {
                $q->where('seller_user_id', $personal);
            } elseif ($scope !== null) {
                $q->where('seller_group_id', $scope);
            }
        };

        $orders = Order::query()->tap($orderScope)
            ->whereIn('status', ['pending_payment', 'awaiting_confirm'])->count();

        $payments = Payment::query()
            ->whereHas('order', $orderScope)->where('status', 'pending')->count();

        $shipments = Order::query()->tap($orderScope)
            ->whereIn('status', ['confirmed', 'processing'])->count();

        $returns = ReturnRequest::query()
            ->whereHas('order', $orderScope)->where('status', 'pending')->count();

        $chat = Conversation::query()
            ->when($scope, fn ($q) => $q->where('seller_group_id', $scope))
            ->where('is_read_by_seller', false)->count();

        $reviews = ProductReview::query()
            ->whereHas('product', $productScope)
            ->where('status', 'published')->whereNull('reply')->count();

        $comments = ProductComment::query()
            ->whereHas('product', $productScope)
            ->where('status', 'pending')->count();

        // คำขอสมัครขายที่รอดำเนินการ (superadmin เห็นทุกสถานะที่รอ, admin เห็นเฉพาะ pending)
        $sellerAppStatuses = $user->isSuperAdmin()
            ? ['pending', 'admin_approved', 'escalated']
            : ['pending'];
        $seller_apps = $user->isAdmin()
            ? SellerApplication::whereIn('status', $sellerAppStatuses)->count()
            : 0;

        return response()->json(compact('orders', 'payments', 'shipments', 'returns', 'chat', 'reviews', 'comments', 'seller_apps'));
    }
}
