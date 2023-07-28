<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderLogError extends Model
{
    use HasFactory;

    protected $fillable = [
        'orderId',
        'type',
        'symbol',
        'data',
    ];
}
