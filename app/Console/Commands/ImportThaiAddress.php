<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImportThaiAddress extends Command
{
    protected $signature   = 'address:import';
    protected $description = 'ดาวน์โหลดฐานข้อมูลที่อยู่ไทยและบันทึกลง storage';

    public function handle(): int
    {
        $dest = storage_path('app/thai-address.json');

        if (file_exists($dest)) {
            if (!$this->confirm('มีไฟล์ thai-address.json อยู่แล้ว ดาวน์โหลดใหม่?', false)) {
                $this->info('ข้ามการดาวน์โหลด');
                return self::SUCCESS;
            }
        }

        $urls = [
            'https://raw.githubusercontent.com/kongvut/thai-province-data/master/api_tambon.json',
            'https://raw.githubusercontent.com/Mekter/ThailandData/master/address.json',
        ];

        $this->info('กำลังดาวน์โหลดฐานข้อมูลที่อยู่ไทย...');

        foreach ($urls as $url) {
            try {
                $response = Http::timeout(30)->get($url);
                if ($response->successful()) {
                    $raw = $response->json();
                    // แปลงเป็น format มาตรฐาน: [subdistrict, district, province, zipcode]
                    $normalized = $this->normalize($raw, $url);
                    if (count($normalized) > 100) {
                        file_put_contents($dest, json_encode($normalized, JSON_UNESCAPED_UNICODE));
                        $this->info('✓ บันทึกแล้ว: ' . count($normalized) . ' ตำบล/แขวง');
                        return self::SUCCESS;
                    }
                }
            } catch (\Exception $e) {
                $this->warn('ไม่สำเร็จจาก ' . $url . ': ' . $e->getMessage());
            }
        }

        $this->error('ดาวน์โหลดไม่สำเร็จ — ใช้ข้อมูลตัวอย่างนครราชสีมาแทน');
        file_put_contents($dest, json_encode($this->fallbackData(), JSON_UNESCAPED_UNICODE));
        $this->info('บันทึกข้อมูลตัวอย่างแล้ว');
        return self::SUCCESS;
    }

    private function normalize(array $raw, string $url): array
    {
        $out = [];
        // รองรับหลาย format
        foreach ($raw as $item) {
            if (isset($item['name_th'], $item['amphure']['name_th'], $item['amphure']['province']['name_th'])) {
                // format: kongvut/thai-province-data
                $out[] = [
                    'subdistrict' => $item['name_th'],
                    'district'    => $item['amphure']['name_th'],
                    'province'    => $item['amphure']['province']['name_th'],
                    'zipcode'     => $item['zip_code'] ?? '',
                ];
            } elseif (isset($item['tambon'], $item['amphoe'], $item['province'])) {
                $out[] = [
                    'subdistrict' => $item['tambon'],
                    'district'    => $item['amphoe'],
                    'province'    => $item['province'],
                    'zipcode'     => $item['zipcode'] ?? $item['zip_code'] ?? '',
                ];
            }
        }
        return $out;
    }

    private function fallbackData(): array
    {
        // ข้อมูลนครราชสีมาสำหรับใช้งานขั้นต้น
        return require __DIR__ . '/../../Data/ThaiAddressFallback.php';
    }
}
