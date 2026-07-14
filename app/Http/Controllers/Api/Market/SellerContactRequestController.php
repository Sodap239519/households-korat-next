<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Models\SellerContactRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerContactRequestController extends Controller
{
    /** GET /market/seller-requests — แอดมินดูรายการ (scope ตามกลุ่ม) */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์');

        $query = SellerContactRequest::with([
            'seller:id,name,email',
            'sellerGroup:id,name',
            'handler:id,name',
        ])->latest();

        if ($scope = $user->sellerGroupScope()) {
            $query->where('seller_group_id', $scope);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    /** POST /market/seller-requests — ผู้ขายส่งคำขอ */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->seller_group_id, 403, 'คุณยังไม่ได้สังกัดกลุ่มผู้ขาย');

        // ป้องกัน spam: ยังมี pending อยู่หัวข้อเดียวกัน
        $duplicate = SellerContactRequest::where('seller_user_id', $user->id)
            ->where('topic', $request->input('topic'))
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();
        abort_if($duplicate, 422, 'คุณมีคำขอหัวข้อนี้ที่ยังรอดำเนินการอยู่ — กรุณารอผลก่อน');

        $validated = $request->validate([
            'topic'  => ['required', 'in:' . implode(',', array_keys(SellerContactRequest::TOPICS))],
            'detail' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $req = SellerContactRequest::create([
            'seller_user_id' => $user->id,
            'seller_group_id' => $user->seller_group_id,
            'topic'  => $validated['topic'],
            'detail' => $validated['detail'],
        ]);

        return response()->json([
            'message' => 'ส่งคำขอเรียบร้อยแล้ว ผู้ดูแลระบบจะดำเนินการภายใน 1-3 วันทำการ',
            'id'      => $req->id,
        ], 201);
    }

    /** GET /market/seller-requests/my — ผู้ขายดูคำขอของตัวเอง */
    public function myRequests(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = SellerContactRequest::where('seller_user_id', $user->id)
            ->latest()
            ->get(['id', 'topic', 'detail', 'status', 'admin_note', 'resolved_at', 'created_at']);

        return response()->json($items->map(fn ($r) => [
            'id'          => $r->id,
            'topic'       => $r->topic,
            'topic_label' => $r->topic_label,
            'detail'      => $r->detail,
            'status'      => $r->status,
            'status_label' => $r->status_label,
            'admin_note'  => $r->admin_note,
            'resolved_at' => $r->resolved_at?->toDateString(),
            'created_at'  => $r->created_at->toDateString(),
        ]));
    }

    /** PUT /market/seller-requests/{id}/resolve — แอดมินดำเนินการ */
    public function resolve(Request $request, SellerContactRequest $sellerContactRequest): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์');

        $validated = $request->validate([
            'status'     => ['required', 'in:in_progress,resolved,rejected'],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $sellerContactRequest->update([
            'status'      => $validated['status'],
            'admin_note'  => $validated['admin_note'] ?? null,
            'handled_by'  => $user->id,
            'resolved_at' => in_array($validated['status'], ['resolved', 'rejected']) ? now() : null,
        ]);

        return response()->json(['message' => 'อัปเดตสถานะแล้ว']);
    }
}
