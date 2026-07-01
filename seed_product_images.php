<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$db = app('db');
$src = 'C:/Users/jetsa/Downloads/';

// ภาพต้นทาง => path สัมพัทธ์ใน Downloads
$imgs = [
    'veg3'    => $src . 'colorful-assortment-fresh-vegetables-vector-illustration/1545b27c-2212-4674-b913-eb92bb008f1a.jpg',
    'food1'   => $src . 'colorful-healthy-food-background-template/2177227.jpg',
    'food2'   => $src . 'colorful-healthy-food-background-template/2177228.jpg',
    'food3'   => $src . 'colorful-healthy-food-background-template/83363b37-d07b-4c51-be65-783a9f31997a.jpg',
    'cuke1'   => $src . 'cucumber-vegetable-organic-icon-isolated/83363b37-d07b-4c51-be65-783a9f31997a.jpg',
    'agri'    => $src . 'agriculture.png',
    'market'  => $src . 'LINE_ALBUM_ตลาดแก้จน_250718_1.jpg',
];

// [product_id => [img_key, ...]]
$assign = [
    1  => ['food1', 'food2', 'veg3'],
    2  => ['food3', 'food1'],
    3  => ['food3', 'food2'],
    4  => ['cuke1', 'food1'],
    5  => ['food2', 'market'],
    6  => ['cuke1', 'veg3'],
    7  => ['food2', 'food3'],
    8  => ['food1', 'market'],
    9  => ['agri', 'market'],
    10 => ['food3', 'veg3'],
    11 => ['market', 'agri'],
    12 => ['food1', 'veg3'],
];

$destDir = storage_path('app/public/products');
if (!is_dir($destDir)) mkdir($destDir, 0755, true);

$now = date('Y-m-d H:i:s');

foreach ($assign as $productId => $keys) {
    // skip if already has images
    $existing = $db->table('product_images')->where('product_id', $productId)->count();
    if ($existing > 0) {
        echo "Product $productId: already has $existing images, skipping\n";
        continue;
    }

    $isPrimary = true;
    $sort = 1;
    foreach ($keys as $key) {
        $srcPath = $imgs[$key];
        if (!file_exists($srcPath)) {
            echo "  MISSING: $srcPath\n";
            continue;
        }
        $ext  = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
        $name = "p{$productId}_" . $key . '.' . $ext;
        $dest = $destDir . '/' . $name;
        copy($srcPath, $dest);

        $db->table('product_images')->insert([
            'product_id' => $productId,
            'path'       => 'products/' . $name,
            'sort_order' => $sort,
            'is_primary' => $isPrimary ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        echo "  OK: product $productId <= $name (primary=" . ($isPrimary ? 'Y' : 'N') . ")\n";
        $isPrimary = false;
        $sort++;
    }
}
echo "Done!\n";
