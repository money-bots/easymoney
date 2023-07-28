<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Orders\OrderBuySymbol;
use App\Models\Settings\Setting;
use App\Services\Binance\OrderService;
use App\Services\Binance\OrderBuySymbolService;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);

        $balances = $orderService->balance()->filter(function ($val) {
            return in_array(
                $val['asset'],
                OrderBuySymbol::query()
                    ->select('currency')
                    ->where('status', 1)
                    ->groupBy('currency')
                    ->get()
                    ->pluck('currency')
                    ->toArray()
            );
        });

        foreach ($balances as $balance) {
            Setting::query()->updateOrCreate(['key' => $balance['asset']], ['value' => $balance['free']]);
        }

        /** @var OrderBuySymbolService $orderBuySymbolService */
        $orderBuySymbolService = app(OrderBuySymbolService::class);
        $orderBuySymbolService->updateRules();
    }
}
