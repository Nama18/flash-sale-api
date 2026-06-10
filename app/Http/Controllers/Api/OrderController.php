<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    /**
     * POST /api/orders
     *
     * Body JSON:
     * {
     *   "customer_name": "Budi",
     *   "items": [
     *     { "product_id": 1, "quantity": 2 }
     *   ]
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name'      => 'required|string|max:255',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        try {
            $order = $this->orderService->createOrder(
                $validated['customer_name'],
                $validated['items']
            );

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat.',
                'data'    => $order,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order gagal.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /** GET /api/orders/{order} */
    public function show(Order $order): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $order->load('items.product'),
        ]);
    }
}
