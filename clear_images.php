<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$db = app('db');

// ลบ product_images ของ products 1-12 เพื่อ re-seed ใหม่
$ids = [1,2,3,4,5,6,7,8,9,10,11,12];
foreach ($ids as $id) {
    $count = $db->table('product_images')->where('product_id', $id)->count();
    if ($count > 0) {
        $db->table('product_images')->where('product_id', $id)->delete();
        echo "Cleared product $id ($count imgs removed)\n";
    }
}
echo "Done\n";
