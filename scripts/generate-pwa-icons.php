<?php
// สร้างไอคอน PWA (เกรเดียนม่วง→ส้ม + ถุงช้อปปิ้งสีขาว)
// รัน: php scripts/generate-pwa-icons.php

function makeIcon(int $size, string $out): void
{
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);

    // เกรเดียนทแยงม่วง (#7c3aed) → ส้ม (#fb923c)
    [$r1, $g1, $b1] = [124, 58, 237];
    [$r2, $g2, $b2] = [251, 146, 60];
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            $t = ($x + $y) / (2 * $size);
            $r = (int) ($r1 + ($r2 - $r1) * $t);
            $g = (int) ($g1 + ($g2 - $g1) * $t);
            $b = (int) ($b1 + ($b2 - $b1) * $t);
            imagesetpixel($img, $x, $y, imagecolorallocate($img, $r, $g, $b));
        }
    }

    $white = imagecolorallocate($img, 255, 255, 255);

    // ถุงช้อปปิ้ง (สัดส่วนตาม size)
    $s = $size / 512;
    $bx1 = (int) (170 * $s); $by1 = (int) (210 * $s);
    $bx2 = (int) (342 * $s); $by2 = (int) (380 * $s);
    // ตัวถุง (มุมโค้งง่ายๆ)
    imagefilledrectangle($img, $bx1, $by1, $bx2, $by2, $white);
    // หูจับ (เส้นโค้งหนา)
    imagesetthickness($img, max(2, (int) (16 * $s)));
    $cx = (int) (($bx1 + $bx2) / 2);
    $hw = (int) (70 * $s);
    imagearc($img, $cx, $by1, $hw, (int) (90 * $s), 180, 360, $white);

    imagepng($img, $out);
    imagedestroy($img);
    echo "wrote $out\n";
}

$dir = __DIR__ . '/../public/icons';
@mkdir($dir, 0755, true);
makeIcon(192, "$dir/icon-192.png");
makeIcon(512, "$dir/icon-512.png");
echo "done\n";
