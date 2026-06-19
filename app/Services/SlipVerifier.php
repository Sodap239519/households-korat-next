<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ตรวจสอบสลิปโอนเงิน
 *
 * แนวทาง:
 *  - ถ้าตั้งค่าบริการตรวจสลิป (เช่น SlipOK / Slip2Go) ใน config('services.slip')
 *    จะอ่าน QR บนสลิป แล้วเทียบ จำนวนเงิน + ชื่อผู้รับ กับออเดอร์/บัญชีกลุ่ม
 *  - ถ้ายังไม่ตั้งค่า → ข้ามการตรวจอัตโนมัติ (status = skipped) ให้แอดมินตรวจเอง
 *    แต่ยังเทียบ "จำนวนเงินที่ลูกค้าแจ้ง" กับยอดออเดอร์ให้เป็นข้อมูลเบื้องต้น
 *
 * ผลลัพธ์ถูกบันทึกลง payment.verify_status + payment.verify_detail
 */
class SlipVerifier
{
    public static function verify(Payment $payment): void
    {
        $payment->loadMissing('order.sellerGroup');
        $order = $payment->order;
        $group = $order->sellerGroup;

        $detail = [
            'order_total'     => (float) $order->total,
            'declared_amount' => (float) $payment->amount,
            'amount_match'    => abs((float) $payment->amount - (float) $order->total) < 0.01,
            'checked_at'      => now()->toDateTimeString(),
        ];

        $provider = config('services.slip.provider');

        // ยังไม่ได้ตั้งค่าบริการตรวจสลิป → ข้าม (ให้แอดมินตรวจเอง)
        if (empty($provider) || empty(config('services.slip.token'))) {
            $detail['note'] = 'ยังไม่ได้เปิดใช้บริการตรวจสลิปอัตโนมัติ — รอแอดมินตรวจสอบ';
            $payment->update(['verify_status' => 'skipped', 'verify_detail' => $detail]);
            return;
        }

        try {
            $result = self::callProvider($provider, $payment);
            $detail = array_merge($detail, $result);

            // เกณฑ์ผ่าน: จำนวนเงินตรงกับยอดออเดอร์ + (ถ้ามีชื่อบัญชีกลุ่ม) ชื่อผู้รับตรง
            $amountOk   = isset($result['slip_amount']) && abs((float) $result['slip_amount'] - (float) $order->total) < 0.01;
            $receiverOk = empty($group?->bank_account_name)
                || (isset($result['receiver_name']) && self::nameMatches($result['receiver_name'], $group->bank_account_name));

            $detail['amount_ok']   = $amountOk;
            $detail['receiver_ok'] = $receiverOk;

            $status = ($amountOk && $receiverOk) ? 'passed' : 'failed';
            $payment->update(['verify_status' => $status, 'verify_detail' => $detail]);
        } catch (\Throwable $e) {
            Log::warning('Slip verify error', ['error' => $e->getMessage(), 'payment' => $payment->id]);
            $detail['error'] = 'ตรวจสลิปอัตโนมัติไม่สำเร็จ — รอแอดมินตรวจสอบ';
            $payment->update(['verify_status' => 'skipped', 'verify_detail' => $detail]);
        }
    }

    /** เรียกบริการตรวจสลิปภายนอก (SlipOK เป็นตัวอย่าง — ปรับ endpoint/field ตามผู้ให้บริการจริง) */
    protected static function callProvider(string $provider, Payment $payment): array
    {
        $token = config('services.slip.token');
        $url   = config('services.slip.url');

        // ส่งรูปสลิปไปให้บริการอ่าน QR/ข้อมูล
        $absolute = Storage::disk('public')->path($payment->slip_path);

        $response = Http::withToken($token)
            ->timeout(15)
            ->attach('files', file_get_contents($absolute), basename($payment->slip_path))
            ->post($url);

        $response->throw();
        $data = $response->json('data', $response->json());

        // map ฟิลด์ผลลัพธ์ (ชื่อ field ขึ้นกับผู้ให้บริการ)
        return [
            'provider'      => $provider,
            'slip_amount'   => $data['amount']           ?? null,
            'receiver_name' => $data['receiver']['name'] ?? ($data['receiver_name'] ?? null),
            'sender_name'   => $data['sender']['name']   ?? ($data['sender_name'] ?? null),
            'trans_ref'     => $data['transRef']         ?? ($data['ref'] ?? null),
            'trans_date'    => $data['transDate']        ?? null,
        ];
    }

    /** เทียบชื่อแบบยืดหยุ่น (ตัดช่องว่าง/คำนำหน้า) */
    protected static function nameMatches(string $a, string $b): bool
    {
        $norm = fn ($s) => preg_replace('/\s+/u', '', preg_replace('/(นาย|นาง|นางสาว|น\.ส\.|mr|mrs|ms)\.?/iu', '', mb_strtolower(trim($s))));
        $na = $norm($a);
        $nb = $norm($b);
        return $na !== '' && (str_contains($na, $nb) || str_contains($nb, $na));
    }
}
