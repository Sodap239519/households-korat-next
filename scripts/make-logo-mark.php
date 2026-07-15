<?php
/**
 * สร้างโลโก้ "เส้นขาวพื้นโปร่งใส" (logo-mark.png) จาก icon-512.png (เส้นขาวบนพื้นม่วง)
 * วิธี: คำนวณ alpha จากความ "ขาว" ของแต่ละพิกเซล → ได้ขอบเนียน (anti-alias) ไม่มีคราบม่วง
 * รัน: C:\xampp\php\php.exe scripts/make-logo-mark.php
 */

$src = __DIR__ . '/../public/icons/icon-512.png';
$out = __DIR__ . '/../resources/js/spa/assets/logo-mark.png';

$im = @imagecreatefrompng($src);
if (!$im) { fwrite(STDERR, "อ่าน icon-512.png ไม่ได้\n"); exit(1); }

$w = imagesx($im);
$h = imagesy($im);

// สีพื้นหลัง = พิกเซลมุมซ้ายบน
$bg   = imagecolorsforindex($im, imagecolorat($im, 0, 0));
$bgLum = 0.2126 * $bg['red'] + 0.7152 * $bg['green'] + 0.0722 * $bg['blue'];

$dst = imagecreatetruecolor($w, $h);
imagealphablending($dst, false);
imagesavealpha($dst, true);
imagefilledrectangle($dst, 0, 0, $w, $h, imagecolorallocatealpha($dst, 255, 255, 255, 127)); // โปร่งใสทั้งหมด

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $c = imagecolorsforindex($im, imagecolorat($im, $x, $y));
        $lum = 0.2126 * $c['red'] + 0.7152 * $c['green'] + 0.0722 * $c['blue'];

        // ยิ่งสว่างกว่าพื้น = ยิ่งทึบ (เป็นเส้นโลโก้)
        $t = (255.0 - $bgLum) > 0 ? ($lum - $bgLum) / (255.0 - $bgLum) : 0;
        $t = max(0.0, min(1.0, $t));

        $alpha = (int) round(127 * (1 - $t));   // GD: 0=ทึบ, 127=โปร่งใส
        imagesetpixel($dst, $x, $y, imagecolorallocatealpha($dst, 255, 255, 255, $alpha));
    }
}

imagepng($dst, $out, 9);
echo "สร้าง " . basename($out) . " ({$w}x{$h}) จากพื้นหลัง rgb({$bg['red']},{$bg['green']},{$bg['blue']})\n";
imagedestroy($im);
imagedestroy($dst);
