<?php
/**
 * public/opcache_reset.php
 * ล้าง PHP OPcache + Laravel bootstrap cache (route/config) สำหรับ deploy บน Plesk แบบไม่มี SSH
 * ยิง URL นี้หลัง Git Deploy ทุกครั้งที่แก้ routes/config เพื่อกัน opcache ค้างของเก่า
 * (ปลอดภัย: bootstrap/cache ทุกไฟล์ Laravel สร้างใหม่อัตโนมัติจาก .env/web.php)
 */
header('Content-Type: text/plain; charset=utf-8');

echo "== OPcache ==\n";
if (function_exists('opcache_reset')) {
    echo opcache_reset() ? "OPcache reset OK\n" : "opcache_reset() returned false\n";
} else {
    echo "OPcache not available\n";
}
if (function_exists('opcache_get_status')) {
    $s = @opcache_get_status(false);
    echo 'enabled: ' . ((!empty($s['opcache_enabled'])) ? 'yes' : 'no') . "\n";
}

echo "\n== Laravel bootstrap cache ==\n";
$cacheDir = __DIR__ . '/../bootstrap/cache';
foreach (['config.php', 'routes-v7.php', 'routes.php', 'packages.php', 'services.php', 'events.php'] as $f) {
    $path = $cacheDir . '/' . $f;
    if (is_file($path)) {
        echo (@unlink($path) ? 'cleared ' : 'FAILED  ') . $f . "\n";
    }
}

clearstatcache(true);
echo "\ndone\n";
