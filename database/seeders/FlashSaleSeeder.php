<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class FlashSaleSeeder extends Seeder
{
    public function run(): void
    {
        Product::updateOrCreate(
            ['name' => 'Sepatu Nike Flash Sale'],
            [
                'price' => 1_200_000,
                'flash_sale_price' => 299_000,
                'stock' => 10,
            ]
        );

        Product::updateOrCreate(
            ['name' => 'Kaos Polos Premium'],
            [
                'price' => 150_000,
                'flash_sale_price' => null,
                'stock' => 100,
            ]
        );
    }
}
