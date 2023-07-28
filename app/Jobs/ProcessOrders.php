<?php

namespace App\Jobs;

use App\Models\Orders\OrderBuySymbol;
use App\Repositories\Binance\OrderLogErrorRepository;
use App\Services\Binance\OrderService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class ProcessOrders implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        sleep(1);

        try {
            $symbols = OrderBuySymbol::query()->where('status', 1)->get();

            $prices = collect($this->orderService()->prices());

            foreach ($symbols as $symbol) {
                $this->orderService()->sellOrder(
                    $symbol,
                    $this->orderService()->filterPrice($prices, $symbol->symbol)
                );
            }

            foreach ($symbols as $symbol) {
                $price = $this->orderService()->filterPrice($prices, $symbol->symbol);

                if ($this->orderService()->getBalance($symbol->currency) > $this->orderService()->getMinPrice($symbol, $price)) {
                    $this->orderService()->buyOrder($symbol, $price);
                }
            }
        } catch (Exception $e) {
            OrderLogErrorRepository::logError(["ProcessOrders: " . $e->getMessage()]);
        }

        Artisan::call('run:orders');
    }

    protected function orderService(): OrderService
    {
        return app(OrderService::class);
    }
}
