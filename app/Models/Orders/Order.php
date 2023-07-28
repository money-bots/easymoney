<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'orderId',
        'symbol',
        'isWorking',
        'income',
        'amountBuy',
        'amountSell',
        'quantityBuy',
        'quantitySell',
        'priceMin',
        'priceBuy',
        'priceSell',
        'priceBuyCommission',
        'priceSellCommission',
        'commissionBuyCurrency',
        'commissionSellCurrency',
        'commissionRateMaker',
        'commissionRateTaker',
        'makerCommission',
        'takerCommission',
    ];

    public function orderBuySymbol(): HasOne
    {
        return $this->hasOne(OrderBuySymbol::class, 'symbol', 'symbol');
    }
}
