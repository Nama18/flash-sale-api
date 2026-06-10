<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function createProduct(array $data): Product
    {
        return Product::create($data);
    }

    public function updateProduct(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh();
    }

    public function deleteProduct(Product $product): void
    {
        $activeOrders = $product->orderItems()
            ->whereHas('order', fn($q) => $q->where('status', '!=', 'failed'))
            ->exists();

        if ($activeOrders) {
            throw ValidationException::withMessages([
                'product' => "Produk '{$product->name}' tidak bisa dihapus karena masih ada order aktif.",
            ]);
        }

        $product->delete();
    }
}
