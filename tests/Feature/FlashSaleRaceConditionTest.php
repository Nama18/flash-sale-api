<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashSaleRaceConditionTest extends TestCase
{
    use RefreshDatabase;

    public function test_concurrent_orders_do_not_oversell(): void
    {
        $product = Product::create([
            'name' => 'Flash Sale Product',
            'price' => 100_000,
            'flash_sale_price' => 10_000,
            'stock' => 10,
        ]);

        $success = 0;
        $fail = 0;

        for ($i = 1; $i <= 50; $i++) {
            $res = $this->postJson('/api/orders', [
                'customer_name' => "Customer {$i}",
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
            ]);
            $res->status() === 201 ? $success++ : $fail++;
        }

        $this->assertSame(10, $success);
        $this->assertSame(40, $fail);
        $this->assertSame(0, $product->fresh()->stock);
    }
}
