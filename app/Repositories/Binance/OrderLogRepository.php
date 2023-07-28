<?php

namespace App\Repositories\Binance;

use App\Models\Orders\Order;
use App\Models\Orders\OrderLog;

class OrderLogRepository
{
    public static function create(Order $order, array $data, string $type): void
    {
        OrderLog::query()
            ->create([
                'orderId' => $order->id,
                'type' => $type,
                'symbol' => $order->symbol,
                'data' => json_encode($data, JSON_THROW_ON_ERROR),
            ]);
    }
}
