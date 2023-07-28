<?php

namespace App\Repositories\Binance;

use App\Models\Orders\Order;
use App\Models\Orders\OrderLogError;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrderLogErrorRepository
{
    public static function logError(
        array $data,
        Order|Model|null $order = null,
        ?string $symbol = null,
        ?string $type = null
    ): void {
        try {
            $data = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            $data = $e->getMessage();
        }

        OrderLogError::query()->updateOrCreate([
            'orderId' => $order->id ?? 0,
            'type' => $type,
            'symbol' => $symbol,
            'data' => $data
        ], [
            'count' => DB::raw('count+1'),
        ]);
    }
}
