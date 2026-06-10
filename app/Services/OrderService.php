<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function createOrder(string $customerName, array $items): Order
    {
        return DB::transaction(function () use ($customerName, $items) {
            $totalPrice = 0;
            $orderLines = [];

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                if ($product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'stock' => "Stok produk '{$product->name}' tidak mencukupi. "
                                 . "Tersedia: {$product->stock}, diminta: {$item['quantity']}.",
                    ]);
                }
                $product->decrement('stock', $item['quantity']);

                $unitPrice   = $product->effective_price;
                $totalPrice += $unitPrice * $item['quantity'];

                $orderLines[] = [
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $unitPrice,
                ];
            }
            $order = Order::create([
                'customer_name' => $customerName,
                'total_price'   => $totalPrice,
                'status'        => 'confirmed',
            ]);
            $now = now();
            $order->items()->createMany(
                array_map(fn($line) => array_merge($line, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]), $orderLines)
            );

            return $order->load('items.product');
        });
    }

    public function updateOrder(Order $order, string $customerName, array $items): Order
    {
        return DB::transaction(function () use ($order, $customerName, $items) {

            foreach ($order->items as $oldItem) {
                Product::lockForUpdate()->find($oldItem->product_id)
                    ?->increment('stock', $oldItem->quantity);
            }
            $order->items()->delete();
            $totalPrice = 0;
            $orderLines = [];
            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'stock' => "Stok produk '{$product->name}' tidak mencukupi. "
                                . "Tersedia: {$product->stock}, diminta: {$item['quantity']}.",
                    ]);
                }

                $product->decrement('stock', $item['quantity']);

                $unitPrice   = $product->effective_price;
                $totalPrice += $unitPrice * $item['quantity'];

                $orderLines[] = [
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $unitPrice,
                ];
            }
            $order->update([
                'customer_name' => $customerName,
                'total_price'   => $totalPrice,
            ]);
            $now = now();
            $order->items()->createMany(
                array_map(fn($line) => array_merge($line, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]), $orderLines)
            );

            return $order->load('items.product');
        });
    }

    public function deleteOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                Product::lockForUpdate()->find($item->product_id)
                    ?->increment('stock', $item->quantity);
            }
            $order->items()->delete();
            $order->delete();
        });
    }
}
