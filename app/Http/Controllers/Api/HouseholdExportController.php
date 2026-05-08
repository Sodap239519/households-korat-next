<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Household;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HouseholdExportController extends Controller
{
    public function csv(Request $request): StreamedResponse
    {
        $query = Household::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('household_code', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name',  'like', "%{$search}%")
                  ->orWhere('id_card',    'like', "%{$search}%");
            });
        }
        if ($d = $request->input('district')) $query->where('district', $d);
        if ($p = $request->input('priority')) $query->where('priority', $p);
        if ($request->has('passed') && $request->input('passed') !== '') {
            $query->where('passed', (int) $request->input('passed'));
        }

        $filename = 'households_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'รหัสบ้าน','คำนำหน้า','ชื่อ','นามสกุล','บัตรประชาชน',
            'เพศ','อายุ','การศึกษา','สุขภาพ','เบอร์โทร',
            'จังหวัด','อำเภอ','ตำบล','หมู่ที่','หมู่บ้าน','บ้านเลขที่',
            'อาชีพหลัก','อาชีพเสริม','รายได้/เดือน','รายจ่าย/เดือน','หนี้สิน','แหล่งกู้',
            'พื้นที่เพาะเห็ด (ตร.ม.)','เคยเพาะเห็ด','เคยทำเกษตร',
            'คะแนนรวม','Priority','สถานะ','วันที่สำรวจ','ผู้สำรวจ',
        ];

        $columns = [
            'household_code','prefix','first_name','last_name','id_card',
            'gender','age','education','health','phone',
            'province','district','sub_district','moo_number','village','house_number',
            'main_occupation','secondary_occupation','income_month','expense_month','debt_amount','debt_source',
            'mushroom_area_size','ever_mushroom','ever_agriculture',
            'total_score','priority',null,'survey_date','surveyor',
        ];

        $callback = function () use ($query, $headers, $columns) {
            $out = fopen('php://output', 'w');
            // BOM for Excel UTF-8 compatibility
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);

            $query->orderBy('household_code')->chunk(500, function ($rows) use ($out, $columns) {
                foreach ($rows as $r) {
                    $line = [];
                    foreach ($columns as $col) {
                        if ($col === null) {
                            // status column
                            $line[] = $r->passed ? 'ผ่าน' : 'ไม่ผ่าน';
                        } elseif (in_array($col, ['ever_mushroom', 'ever_agriculture'])) {
                            $line[] = $r->{$col} ? 'ใช่' : 'ไม่';
                        } else {
                            $line[] = (string) ($r->{$col} ?? '');
                        }
                    }
                    fputcsv($out, $line);
                }
            });

            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
