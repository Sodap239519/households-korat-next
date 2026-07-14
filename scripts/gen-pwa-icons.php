<?php
/**
 * สร้างไอคอน PWA จากโลโก้ตลาดชุมชนโคราช (แบบ full-bleed square — พื้นม่วงเต็มขอบ)
 * ต้นฉบับ: public/icons/logo-src.png (ควรเป็นสี่เหลี่ยมจัตุรัส ดีไซน์เป็นไอคอนมาแล้ว)
 * รัน: C:\xampp\php\php.exe scripts/gen-pwa-icons.php
 * ได้: icon-192/512 (any), icon-maskable-192/512, apple-touch-icon(180)
 */

$src = __DIR__ . '/../public/icons/logo-src.png';
$outDir = __DIR__ . '/../public/icons';

if (!is_file($src)) { fwrite(STDERR, "ไม่พบ public/icons/logo-src.png\n"); exit(1); }

$logo = @imagecreatefromstring(file_get_contents($src));
if (!$logo) { fwrite(STDERR, "อ่านรูปไม่ได้\n"); exit(1); }

$lw = imagesx($logo);
$lh = imagesy($logo);

/** ย่อทั้งภาพให้เต็มผืน target แบบ cover (คงสัดส่วน crop กึ่งกลางถ้าไม่จัตุรัส) */
function render($logo, $lw, $lh, $size, $out) {
    $canvas = imagecreatetruecolor($size, $size);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);

    $scale = max($size / $lw, $size / $lh);          // cover
    $sw = (int) round($size / $scale);                // พื้นที่ต้นฉบับที่จะใช้
    $sh = (int) round($size / $scale);
    $sx = (int) round(($lw - $sw) / 2);
    $sy = (int) round(($lh - $sh) / 2);

    imagecopyresampled($canvas, $logo, 0, 0, $sx, $sy, $size, $size, $sw, $sh);
    imagepng($canvas, $out, 9);
    imagedestroy($canvas);
    echo "  ✓ " . basename($out) . " ({$size}x{$size})\n";
}

echo "สร้างไอคอนจาก logo-src.png ({$lw}x{$lh}):\n";
render($logo, $lw, $lh, 192, "$outDir/icon-192.png");
render($logo, $lw, $lh, 512, "$outDir/icon-512.png");
render($logo, $lw, $lh, 192, "$outDir/icon-maskable-192.png");
render($logo, $lw, $lh, 512, "$outDir/icon-maskable-512.png");
render($logo, $lw, $lh, 180, "$outDir/apple-touch-icon.png");
imagedestroy($logo);
echo "เสร็จ!\n";
