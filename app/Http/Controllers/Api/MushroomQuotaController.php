<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MushroomQuotaDistrict;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MushroomQuotaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MushroomQuotaDistrict::query();

        if ($year = $request->input('year')) {
            $query->where('year', $year);
        }
        if ($district = $request->input('district')) {
            $query->where('district', $district);
        }
        if ($request->has('active')) {
            $query->where('is_active', (bool) $request->input('active'));
        }

        $quotas = $query->withCount('allocations')
            ->withSum('allocations', 'bags')
            ->orderBy('year', 'desc')
            ->orderBy('round')
            ->orderBy('district')
            ->paginate($request->input('per_page', 20));

        return response()->json($quotas);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'district'   => ['required', 'string', 'max:100'],
            'province'   => ['nullable', 'string', 'max:100'],
            'year'       => ['required', 'integer', 'min:2500', 'max:2600'],
            'round'      => ['required', 'integer', 'min:1', 'max:10'],
            'quota_bags' => ['required', 'integer', 'min:1'],
            'is_active'  => ['boolean'],
            'note'       => ['nullable', 'string'],
        ]);

        $quota = MushroomQuotaDistrict::create($validated);

        return response()->json($quota, 201);
    }

    public function show(MushroomQuotaDistrict $mushroomQuotaDistrict): JsonResponse
    {
        $mushroomQuotaDistrict->loadCount('allocations');
        $mushroomQuotaDistrict->loadSum('allocations', 'bags');

        return response()->json($mushroomQuotaDistrict);
    }

    public function update(Request $request, MushroomQuotaDistrict $mushroomQuotaDistrict): JsonResponse
    {
        $validated = $request->validate([
            'district'   => ['sometimes', 'string', 'max:100'],
            'province'   => ['nullable', 'string', 'max:100'],
            'year'       => ['sometimes', 'integer', 'min:2500', 'max:2600'],
            'round'      => ['sometimes', 'integer', 'min:1', 'max:10'],
            'quota_bags' => ['sometimes', 'integer', 'min:1'],
            'is_active'  => ['boolean'],
            'note'       => ['nullable', 'string'],
        ]);

        $mushroomQuotaDistrict->update($validated);

        return response()->json($mushroomQuotaDistrict);
    }

    public function destroy(MushroomQuotaDistrict $mushroomQuotaDistrict): JsonResponse
    {
        if ($mushroomQuotaDistrict->allocations()->exists()) {
            return response()->json(['message' => 'ไม่สามารถลบได้ มีการจัดสรรแล้ว'], 422);
        }

        $mushroomQuotaDistrict->delete();

        return response()->json(['message' => 'ลบโควต้าสำเร็จ']);
    }
}
