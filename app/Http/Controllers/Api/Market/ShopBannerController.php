<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Models\ShopBanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ShopBannerController extends Controller
{
    // GET /market/banners — รายการแบนเนอร์ทั้งหมด (admin)
    public function index(): JsonResponse
    {
        $banners = ShopBanner::orderBy('sort_order')->orderBy('id')->get();
        return response()->json($banners);
    }

    // POST /market/banners — สร้างแบนเนอร์ใหม่
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'เฉพาะ admin เท่านั้น');

        $data = $request->validate([
            'tag'        => 'nullable|string|max:80',
            'title'      => 'required|string|max:255',
            'subtitle'   => 'nullable|string|max:500',
            'gradient'   => 'required|string|max:255',
            'cta_label'  => 'required|string|max:80',
            'cta_icon'   => 'required|string|max:100',
            'cta_color'  => 'required|string|max:255',
            'link_type'  => 'required|in:product,category,group,url',
            'link_value' => 'nullable|string|max:255',
            'emojis'     => 'nullable|array',
            'emojis.*'   => 'string|max:10',
            'sort_order' => 'nullable|integer|min:0|max:255',
            'is_active'  => 'boolean',
        ]);

        $banner = ShopBanner::create($data);
        return response()->json($banner, 201);
    }

    // PUT /market/banners/{id} — อัปเดตแบนเนอร์
    public function update(Request $request, ShopBanner $shopBanner): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'เฉพาะ admin เท่านั้น');

        $data = $request->validate([
            'tag'        => 'nullable|string|max:80',
            'title'      => 'required|string|max:255',
            'subtitle'   => 'nullable|string|max:500',
            'gradient'   => 'required|string|max:255',
            'cta_label'  => 'required|string|max:80',
            'cta_icon'   => 'required|string|max:100',
            'cta_color'  => 'required|string|max:255',
            'link_type'  => 'required|in:product,category,group,url',
            'link_value' => 'nullable|string|max:255',
            'emojis'     => 'nullable|array',
            'emojis.*'   => 'string|max:10',
            'sort_order' => 'nullable|integer|min:0|max:255',
            'is_active'  => 'boolean',
        ]);

        $shopBanner->update($data);
        return response()->json($shopBanner->fresh());
    }

    // DELETE /market/banners/{id} — ลบแบนเนอร์
    public function destroy(Request $request, ShopBanner $shopBanner): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'เฉพาะ admin เท่านั้น');
        $shopBanner->delete();
        return response()->json(null, 204);
    }

    // PATCH /market/banners/{id}/toggle — เปิด/ปิด
    public function toggle(Request $request, ShopBanner $shopBanner): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'เฉพาะ admin เท่านั้น');
        $shopBanner->update(['is_active' => !$shopBanner->is_active]);
        return response()->json($shopBanner->fresh());
    }

    // GET /shop/banners — แบนเนอร์ที่ active (public)
    public function public(): JsonResponse
    {
        $banners = ShopBanner::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        return response()->json($banners);
    }
}
