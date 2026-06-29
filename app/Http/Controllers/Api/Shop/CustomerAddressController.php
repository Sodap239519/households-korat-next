<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerAddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = CustomerAddress::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')->orderByDesc('id')->get();
        return response()->json($addresses);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label'        => ['nullable', 'string', 'max:50'],
            'name'         => ['required', 'string', 'max:255'],
            'phone'        => ['required', 'string', 'max:30'],
            'address'      => ['required', 'string', 'max:500'],
            'sub_district' => ['nullable', 'string', 'max:100'],
            'district'     => ['nullable', 'string', 'max:100'],
            'province'     => ['nullable', 'string', 'max:100'],
            'postal_code'  => ['nullable', 'string', 'max:10'],
            'is_default'   => ['boolean'],
        ]);

        $userId = $request->user()->id;
        if (!empty($data['is_default'])) {
            CustomerAddress::where('user_id', $userId)->update(['is_default' => false]);
        }
        // ถ้ายังไม่มีที่อยู่ใดเลย → ตั้งเป็น default อัตโนมัติ
        $isFirst = CustomerAddress::where('user_id', $userId)->doesntExist();

        $address = CustomerAddress::create(array_merge($data, [
            'user_id'    => $userId,
            'is_default' => $data['is_default'] ?? $isFirst,
        ]));

        return response()->json($address, 201);
    }

    public function update(Request $request, CustomerAddress $customerAddress): JsonResponse
    {
        abort_unless($customerAddress->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'label'        => ['nullable', 'string', 'max:50'],
            'name'         => ['required', 'string', 'max:255'],
            'phone'        => ['required', 'string', 'max:30'],
            'address'      => ['required', 'string', 'max:500'],
            'sub_district' => ['nullable', 'string', 'max:100'],
            'district'     => ['nullable', 'string', 'max:100'],
            'province'     => ['nullable', 'string', 'max:100'],
            'postal_code'  => ['nullable', 'string', 'max:10'],
            'is_default'   => ['boolean'],
        ]);

        if (!empty($data['is_default'])) {
            CustomerAddress::where('user_id', $request->user()->id)->update(['is_default' => false]);
        }

        $customerAddress->update($data);
        return response()->json($customerAddress->fresh());
    }

    public function destroy(Request $request, CustomerAddress $customerAddress): JsonResponse
    {
        abort_unless($customerAddress->user_id === $request->user()->id, 403);
        $wasDefault = $customerAddress->is_default;
        $customerAddress->delete();

        // ถ้าลบที่อยู่ default → ตั้งตัวล่าสุดเป็น default แทน
        if ($wasDefault) {
            CustomerAddress::where('user_id', $request->user()->id)
                ->latest()->first()?->update(['is_default' => true]);
        }

        return response()->json(['ok' => true]);
    }

    public function setDefault(Request $request, CustomerAddress $customerAddress): JsonResponse
    {
        abort_unless($customerAddress->user_id === $request->user()->id, 403);
        CustomerAddress::where('user_id', $request->user()->id)->update(['is_default' => false]);
        $customerAddress->update(['is_default' => true]);
        return response()->json($customerAddress->fresh());
    }
}
