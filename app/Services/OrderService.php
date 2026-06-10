<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Buat order baru.
     *
     * Menggunakan DB transaction + pessimistic locking (lockForUpdate)
     * agar tidak terjadi race condition saat flash sale.
     *
     * @param  string  $customerName
     * @param  array   $items  [ ['product_id' => int, 'quantity' => int], ... ]
     * @return Order
     *
     * @throws \Throwable
     */
    public function createOrder(string $customerName, array $items): Order
    {
        return DB::transaction(function () use ($customerName, $items) {
            $totalPrice = 0;
            $orderLines = [];

            foreach ($items as $item) {
                /*
                 * lockForUpdate() = pessimistic lock.
                 * Row di-lock selama transaksi berjalan sehingga
                 * request lain harus menunggu — mencegah overselling.
                 */
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                // Cek stok mencukupi
                if ($product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'stock' => "Stok produk '{$product->name}' tidak mencukupi. "
                                 . "Tersedia: {$product->stock}, diminta: {$item['quantity']}.",
                    ]);
                }

                // Kurangi stok
                $product->decrement('stock', $item['quantity']);

                $unitPrice   = $product->effective_price;
                $totalPrice += $unitPrice * $item['quantity'];

                $orderLines[] = [
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $unitPrice,
                ];
            }

            // Buat header order
            $order = Order::create([
                'customer_name' => $customerName,
                'total_price'   => $totalPrice,
                'status'        => 'confirmed',
            ]);

            // Bulk insert order items
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

    /**
     * Update order yang sudah ada.
     *
     * Stok dikembalikan dulu (restock) lalu dipotong ulang (deduct).
     *
     * @param  Order  $order
     * @param  string $customerName
     * @param  array  $items  [ ['product_id' => int, 'quantity' => int], ... ]
     * @return Order
     *
     * @throws \Throwable
     */
    public function updateOrder(Order $order, string $customerName, array $items): Order
    {
        return DB::transaction(function () use ($order, $customerName, $items) {

            // 1. Kembalikan stok dari order items lama
            foreach ($order->items as $oldItem) {
                Product::lockForUpdate()->find($oldItem->product_id)
                    ?->increment('stock', $oldItem->quantity);
            }

            // 2. Hapus order items lama
            $order->items()->delete();

            // 3. Proses items baru (sama seperti createOrder)
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

            // 4. Update header order
            $order->update([
                'customer_name' => $customerName,
                'total_price'   => $totalPrice,
            ]);

            // 5. Insert order items baru
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

    /**
     * Hapus order dan kembalikan stok.
     */
    public function deleteOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            // Kembalikan stok
            foreach ($order->items as $item) {
                Product::lockForUpdate()->find($item->product_id)
                    ?->increment('stock', $item->quantity);
            }

            // Hapus items lalu order
            $order->items()->delete();
            $order->delete();
        });
    }
}
