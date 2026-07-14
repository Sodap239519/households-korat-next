<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$products = App\Models\Product::select('id','name','slug','seller_group_id')->get();
foreach ($products as $p) {
    $c = App\Models\ProductImage::where('product_id', $p->id)->count();
    echo $p->id . ': ' . $p->name . ' (group:'.$p->seller_group_id.') [' . $c . ' imgs]' . PHP_EOL;
}
