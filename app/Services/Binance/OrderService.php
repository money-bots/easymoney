<?php

namespace App\Services\Binance;

use App\Models\Orders\Order;
use App\Models\Orders\OrderBuySymbol;
use App\Models\Settings\Setting;
use App\Repositories\Binance\OrderLogErrorRepository;
use App\Repositories\Binance\OrderLogRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrderService extends BaseService
{
    public function sellOrder(OrderBuySymbol $symbol, ?float $price): array
    {
        $order = $this->getOrderIfPriceReached($symbol, $price);

        if (! $order) {
            return [];
        }

        $order = $this->getOrderIfPriceReached($symbol, $this->price($symbol->symbol));

        if (! $order) {
            return [];
        }

        return $this->sellByOrder($order);
    }

    public function getOrderIfPriceReached(OrderBuySymbol $symbol, ?float $price): Order|Model|null
    {
        if (is_null($price)) {
            return null;
        }

        $order = Order::query()->where('symbol', $symbol->symbol)->where('isWorking', 1);

        if (! $order->exists()) {
            return null;
        }

        $order = $order->where('priceSell', '<', $price)->first();

        if (! $order) {
            return null;
        }

        return $order;
    }

    public function sellByOrder(Order $order): array
    {
        $data = $this->httpPostWithSignature('order', [
            'side' => 'SELL',
            'symbol' => $order->symbol,
            'newClientOrderId' => 'sell-' . $order->id,
            'type' => 'MARKET',
            'quantity' => $order->quantityBuy,
            'newOrderRespType' => "FULL",
        ])->json();

        Setting::saveValue($order->orderBuySymbol->currency, $this->balanceBy($order->orderBuySymbol->currency));

        if (! empty($data['code'])) {
            OrderLogErrorRepository::logError($data, $order, $order->symbol, 'SELL');

            return [];
        }

        $fills = collect($data['fills']);

        $order->update([
            'orderId' => $data['orderId'],
            'isWorking' => false,
            'quantitySell' => (float)$data['origQty'],
            'priceSellCommission' => (float)$fills->sum('commission'),
            'commissionSellCurrency' => $fills->first()['commissionAsset'],
            'priceSell' => (float)$fills->sortByDesc('price')->first()['price'],
            'amountSell' => $this->numberFormat($data['cummulativeQuoteQty'], 4),
            'commissionRateTaker' => (float)$order->orderBuySymbol->takerCommission,
            'takerCommission' => (float)$order->orderBuySymbol->takerCommission,
        ]);

        $this->income($order);

        OrderLogRepository::create($order, $data, 'SELL');

        return $data;
    }

    public function income(Order $order): void
    {
        $order = $order->refresh();

        $income = $this->numberFormat(
            $order->amountSell - $order->amountBuy,
            4
        );

        DB::table('orders')->where('id', $order->id)->update(['income' => $income]);
    }

    public function getBalance(string $key): float
    {
        return (float)(Setting::getValue($key) ?? 0);
    }

    public function buyOrder(OrderBuySymbol $symbol, ?float $price): array
    {
        if ($this->isLimit($symbol) || $this->isOrder($symbol, $price)) {
            return [];
        }

        $order = Order::query()->create(['symbol' => $symbol->symbol, 'isWorking' => true, 'orderId' => 1]);

        $data = $this->httpPostWithSignature('order', [
            'side' => 'BUY',
            'symbol' => $symbol->symbol,
            'newClientOrderId' => 'buy-' . $order->id,
            'type' => 'MARKET',
            'quantity' => $this->getQuantityWithMargin($symbol, $price),
            'newOrderRespType' => "FULL",
        ])->json();

        Setting::saveValue($order->orderBuySymbol->currency, $this->balanceBy($order->orderBuySymbol->currency));

        if (! empty($data['code'])) {
            OrderLogErrorRepository::logError($data, $order, $order->symbol, 'BUY');
            $order->delete();

            return [];
        }

        $fills = collect($data['fills']);
        $priceBuy = (float)$fills->sortByDesc('price')->first()['price'];
        $commission = (float)$fills->sum('commission');
        $quantityBuy = (float)$fills->sum('qty');

        $order->update([
            'orderId' => $data['orderId'],
            'quantityBuy' => $this->numberFormat($quantityBuy, $symbol->lotSize),
            'priceBuy' => $priceBuy,
            'priceBuyCommission' => $commission,
            'commissionBuyCurrency' => $fills->first()['commissionAsset'],
            'priceSell' => $this->getPriceSell($priceBuy, $symbol),
            'priceMin' => $this->getPriceMin($priceBuy, $symbol),
            'amountBuy' => $this->numberFormat($data['cummulativeQuoteQty'], 4),
            'commissionRateMaker' => (float)$symbol->makerCommission,
            'makerCommission' => (float)$symbol->makerCommission,
        ]);

        OrderLogRepository::create($order, $data, 'BUY');

        return $data;
    }

    public function isLimit(OrderBuySymbol $symbol): bool
    {
        return Order::query()
                ->where('symbol', $symbol->symbol)
                ->where('isWorking', true)
                ->count('id') >= $symbol->limit;
    }

    public function isOrder(OrderBuySymbol $symbol, float $price): bool
    {
        return Order::query()
            ->where('symbol', $symbol->symbol)
            ->where('isWorking', true)
            ->where(
                function ($q) use ($price) {
                    $q->where('priceMin', '<=', $price);
                    $q->where('priceSell', '>=', $price);
                }
            )->exists();
    }

    public function getQuantityWithMargin(OrderBuySymbol $symbol, float $price): float
    {
        $quantity = $symbol->minPrice / $price;
        $margin = $symbol->margin / 100;
        $quantityWithMargin = $quantity + ($quantity * $margin);

        return $this->numberFormat($quantityWithMargin, $symbol->lotSize);
    }

    public function getPriceSell(float $price, OrderBuySymbol $symbol): float
    {
        return $this->numberFormat($price + ($price * ($symbol->profit / 100)), $symbol->priceFilter);
    }

    public function getPriceMin(float $price, OrderBuySymbol $symbol): float
    {
        return $this->numberFormat($price - ($price * ($symbol->profit / 100)), $symbol->priceFilter);
    }

    public function getMinPrice(OrderBuySymbol $symbol, float $price): float
    {
        return $this->getQuantityWithMargin($symbol, $price) * $price;
    }
}
