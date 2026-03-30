<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Household;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HouseholdApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Household::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('household_code', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('id_card', 'like', "%{$search}%");
            });
        }

        if ($district = $request->input('district')) {
            $query->where('district', $district);
        }

        $households = $query->orderBy('household_code')->paginate($request->input('per_page', 20));

        return response()->json($households);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'household_code' => ['required', 'string', 'max:50', 'unique:households'],
            'prefix'         => ['nullable', 'string', 'max:20'],
            'first_name'     => ['required', 'string', 'max:100'],
            'last_name'      => ['required', 'string', 'max:100'],
            'id_card'        => ['nullable', 'string', 'max:13', 'unique:households'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'village'        => ['nullable', 'string', 'max:100'],
            'sub_district'   => ['nullable', 'string', 'max:100'],
            'district'       => ['nullable', 'string', 'max:100'],
            'province'       => ['nullable', 'string', 'max:100'],
            'postal_code'    => ['nullable', 'string', 'max:10'],
            'is_active'      => ['boolean'],
            'note'           => ['nullable', 'string'],
        ]);

        $household = Household::create($validated);

        return response()->json($household, 201);
    }

    public function show(Household $household): JsonResponse
    {
        return response()->json($household);
    }

    public function update(Request $request, Household $household): JsonResponse
    {
        $validated = $request->validate([
            'household_code' => ['sometimes', 'string', 'max:50', 'unique:households,household_code,' . $household->id],
            'prefix'         => ['nullable', 'string', 'max:20'],
            'first_name'     => ['sometimes', 'string', 'max:100'],
            'last_name'      => ['sometimes', 'string', 'max:100'],
            'id_card'        => ['nullable', 'string', 'max:13', 'unique:households,id_card,' . $household->id],
            'phone'          => ['nullable', 'string', 'max:20'],
            'village'        => ['nullable', 'string', 'max:100'],
            'sub_district'   => ['nullable', 'string', 'max:100'],
            'district'       => ['nullable', 'string', 'max:100'],
            'province'       => ['nullable', 'string', 'max:100'],
            'postal_code'    => ['nullable', 'string', 'max:10'],
            'is_active'      => ['boolean'],
            'note'           => ['nullable', 'string'],
        ]);

        $household->update($validated);

        return response()->json($household);
    }

    public function destroy(Household $household): JsonResponse
    {
        $household->delete();

        return response()->json(['message' => 'ลบข้อมูลครัวเรือนสำเร็จ']);
    }
}
