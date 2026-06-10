<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test race condition pada flash sale.
 *
 * Skenario : 50 request order untuk produk dengan stok 10.
 * Harapan  : tepat 10 order berhasil, 40 gagal, stok akhir = 0.
 *
 * Jalankan : php artisan test --filter=FlashSaleRaceConditionTest
 */
class FlashSaleRaceConditionTest extends TestCase
{
    use RefreshDatabase;

    private const INITIAL_STOCK     = 10;
    private const CONCURRENT_ORDERS = 50;

    public function test_concurrent_orders_do_not_oversell(): void
    {
        // Arrange: produk flash sale stok terbatas
        $product = Product::create([
            'name'             => 'Flash Sale Product',
            'price'            => 100_000,
            'flash_sale_price' => 10_000,
            'stock'            => self::INITIAL_STOCK,
        ]);

        $successCount = 0;
        $failCount    = 0;

        // Simulasi 50 request order bersamaan
        for ($i = 1; $i <= self::CONCURRENT_ORDERS; $i++) {
            $response = $this->postJson('/api/orders', [
                'customer_name' => "Customer {$i}",
                'items'         => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

            $response->status() === 201 ? $successCount++ : $failCount++;
        }

        $finalStock = $product->fresh()->stock;

        // Assert: tidak boleh oversell
        $this->assertSame(self::INITIAL_STOCK, $successCount,
            "Harusnya tepat 10 order berhasil, dapat: {$successCount}");

        $this->assertSame(self::CONCURRENT_ORDERS - self::INITIAL_STOCK, $failCount,
            "Harusnya 40 order gagal, dapat: {$failCount}");

        $this->assertSame(0, $finalStock,
            "Stok akhir harus 0, dapat: {$finalStock}");
    }
}
