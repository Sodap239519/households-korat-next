<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Models\ShopBanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ShopBannerController extends Controller
{
    // GET /market/banners
    public function index(): JsonResponse
    {
        $banners = ShopBanner::orderBy('sort_order')->orderBy('id')->get();
        return response()->json($banners);
    }

    // POST /market/banners
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'เฉพาะ admin เท่านั้น');

        $data = $request->validate([
            'tag'        => 'nullable|string|max:80',
            'title'      => 'nullable|string|max:255',
            'subtitle'   => 'nullable|string|max:500',
            'gradient'   => 'required|string|max:255',
            'cta_label'  => 'nullable|string|max:80',
            'cta_icon'   => 'nullable|string|max:100',
            'cta_color'  => 'nullable|string|max:255',
            'link_type'  => 'nullable|in:product,category,group,url,none',
            'link_value' => 'nullable|string|max:255',
            'emojis'     => 'nullable|array',
            'emojis.*'   => 'string|max:10',
            'sort_order'   => 'nullable|integer|min:0',
            'is_active'    => 'boolean',
            'aspect_ratio' => 'nullable|string|max:10',
        ]);

        $banner = ShopBanner::create($data);
        return response()->json($banner, 201);
    }

    // PUT /market/banners/{id}
    public function update(Request $request, ShopBanner $shopBanner): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'เฉพาะ admin เท่านั้น');

        $data = $request->validate([
            'tag'        => 'nullable|string|max:80',
            'title'      => 'nullable|string|max:255',
            'subtitle'   => 'nullable|string|max:500',
            'gradient'   => 'required|string|max:255',
            'cta_label'  => 'nullable|string|max:80',
            'cta_icon'   => 'nullable|string|max:100',
            'cta_color'  => 'nullable|string|max:255',
            'link_type'  => 'nullable|in:product,category,group,url,none',
            'link_value' => 'nullable|string|max:255',
            'emojis'     => 'nullable|array',
            'emojis.*'   => 'string|max:10',
            'sort_order'   => 'nullable|integer|min:0',
            'is_active'    => 'boolean',
            'aspect_ratio' => 'nullable|string|max:10',
        ]);

        $shopBanner->update($data);
        return response()->json($shopBanner->fresh());
    }

    // POST /market/banners/{id}/image — อัปโหลดรูป hero
    public function uploadImage(Request $request, ShopBanner $shopBanner): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'เฉพาะ admin เท่านั้น');

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,webp,gif', 'max:4096'],
        ]);

        if ($shopBanner->image_path) {
            Storage::disk('public')->delete($shopBanner->image_path);
        }

        $path = $request->file('image')->store('banners', 'public');
        $shopBanner->update(['image_path' => $path]);

        return response()->json($shopBanner->fresh());
    }

    // POST /market/banners/reorder — บันทึกลำดับใหม่จาก drag
    public function reorder(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'เฉพาะ admin เท่านั้น');

        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:shop_banners,id',
        ]);

        foreach ($request->ids as $order => $id) {
            ShopBanner::where('id', $id)->update(['sort_order' => $order]);
        }

        return response()->json(['ok' => true]);
    }

    // DELETE /market/banners/{id}
    public function destroy(Request $request, ShopBanner $shopBanner): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'เฉพาะ admin เท่านั้น');
        if ($shopBanner->image_path) {
            Storage::disk('public')->delete($shopBanner->image_path);
        }
        $shopBanner->delete();
        return response()->json(null, 204);
    }

    // PATCH /market/banners/{id}/toggle
    public function toggle(Request $request, ShopBanner $shopBanner): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'เฉพาะ admin เท่านั้น');
        $shopBanner->update(['is_active' => !$shopBanner->is_active]);
        return response()->json($shopBanner->fresh());
    }

    // GET /shop/banners — public
    public function public(): JsonResponse
    {
        $banners = ShopBanner::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        return response()->json($banners);
    }
}
