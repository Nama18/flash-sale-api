<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

// Products CRUD
Route::apiResource('products', ProductController::class);

// Orders CRUD
Route::apiResource('orders', OrderController::class);
