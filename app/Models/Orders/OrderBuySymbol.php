<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderBuySymbol extends Model
{
    use HasFactory;

        protected $fillable = [
        'symbol',
        'currency',
        'isWorking',
        'status',
        'margin',
        'profit',
        'limit',
        'minPrice',
        'lotSize',
        'priceFilter',
        'makerCommission',
        'takerCommission',
    ];
}
