<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AddressController extends Controller
{
    private static array $cache = [];

    public function search(Request $request): JsonResponse
    {
        $q    = trim($request->query('q', ''));
        $type = $request->query('type', 'subdistrict'); // subdistrict|district|province

        $data = $this->loadData();

        if ($type === 'province') {
            $results = $this->searchProvinces($data, $q);
        } elseif ($type === 'district') {
            $province = trim($request->query('province', ''));
            $results  = $this->searchDistricts($data, $q, $province);
        } else {
            $district = trim($request->query('district', ''));
            $province = trim($request->query('province', ''));
            $results  = $this->searchSubdistricts($data, $q, $district, $province);
        }

        return response()->json($results);
    }

    private function loadData(): array
    {
        if (!empty(self::$cache)) {
            return self::$cache;
        }

        $path = storage_path('app/thai-address.json');
        if (file_exists($path)) {
            self::$cache = json_decode(file_get_contents($path), true) ?? [];
        } else {
            self::$cache = require app_path('Data/ThaiAddressFallback.php');
        }

        return self::$cache;
    }

    private function searchSubdistricts(array $data, string $q, string $district, string $province): array
    {
        $q        = mb_strtolower($q);
        $district = mb_strtolower($district);
        $province = mb_strtolower($province);

        $results = [];
        foreach ($data as $row) {
            if ($q && mb_strpos(mb_strtolower($row['subdistrict'] ?? ''), $q) === false) continue;
            if ($district && mb_strpos(mb_strtolower($row['district']    ?? ''), $district) === false) continue;
            if ($province && mb_strpos(mb_strtolower($row['province']    ?? ''), $province) === false) continue;
            $results[] = $row;
            if (count($results) >= 30) break;
        }

        return $results;
    }

    private function searchDistricts(array $data, string $q, string $province): array
    {
        $q        = mb_strtolower($q);
        $province = mb_strtolower($province);

        $seen    = [];
        $results = [];
        foreach ($data as $row) {
            $key = $row['district'] . '|' . $row['province'];
            if (isset($seen[$key])) continue;
            if ($q && mb_strpos(mb_strtolower($row['district'] ?? ''), $q) === false) continue;
            if ($province && mb_strpos(mb_strtolower($row['province'] ?? ''), $province) === false) continue;
            $seen[$key] = true;
            $results[] = ['district' => $row['district'], 'province' => $row['province']];
            if (count($results) >= 30) break;
        }

        return $results;
    }

    private function searchProvinces(array $data, string $q): array
    {
        $q    = mb_strtolower($q);
        $seen = [];
        foreach ($data as $row) {
            $p = $row['province'] ?? '';
            if (isset($seen[$p])) continue;
            if ($q && mb_strpos(mb_strtolower($p), $q) === false) continue;
            $seen[$p] = true;
        }

        $provinces = array_keys($seen);
        sort($provinces);
        return array_map(fn($p) => ['province' => $p], $provinces);
    }
}
