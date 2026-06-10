<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['name', 'price', 'flash_sale_price', 'stock'];

    // flash sale price takes priority
    public function getEffectivePriceAttribute(): int
    {
        return $this->flash_sale_price ?? $this->price;
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
