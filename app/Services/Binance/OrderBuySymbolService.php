<?php

namespace App\Services\Binance;

use App\Models\Orders\OrderBuySymbol;

use function collect;

class OrderBuySymbolService extends BaseService
{
    public function updateRules(): void
    {
        $info = collect($this->exchangeInfo()['symbols'])->map(function ($data) {
                return [
                    'quoteAsset' => $data['quoteAsset'],
                    'symbol' => $data['symbol'],
                    'minPrice' => $this->getMinNotionalFromInfo($data['filters']),
                    'lotSize' => $this->strLen($this->getLotSizeFromInfo($data['filters'])),
                    'priceFilter' => $this->getPriceFilterFromInfo($data['filters']),
                    'isWorking' => $data['isSpotTradingAllowed'] ?? false,
                ];
            });

        foreach ($info as $item) {
            $orderBuySymbol = OrderBuySymbol::query()->where('symbol', $item['symbol'])->first();

            $data = [
                'minPrice' => $item['minPrice'],
                'lotSize' => $item['lotSize'],
                'priceFilter' => $item['priceFilter'],
                'isWorking' => $item['isWorking'],
            ];

            if ($orderBuySymbol) {
                $orderBuySymbol->update($data);
            } else {
                OrderBuySymbol::query()
                    ->updateOrCreate(
                        array_merge(
                            [
                                'symbol' => $item['symbol'],
                                'currency' => $item['quoteAsset'],
                                'margin' => 20,
                                'profit' => 5,
                                'limit' => 10,
                                'status' => 0,
                            ],
                            $data
                        )
                    );
            }
        }
    }

    public function getMinNotionalFromInfo(array $data): float
    {
        return (float)(collect($data)->filter(function ($val) {
                return $val['filterType'] === 'TRAILING_DELTA';
            })->first()['minTrailingAboveDelta'] ?? 0);
    }

    public function getLotSizeFromInfo(array $data): string
    {
        return collect($data)->filter(function ($val) {
            return $val['filterType'] === 'LOT_SIZE';
        })->first()['minQty'];
    }

    public function getPriceFilterFromInfo(array $data): int
    {
        return $this->strLen(
            collect($data)->filter(function ($val) {
                return $val['filterType'] === 'PRICE_FILTER';
            })->first()['minPrice']
        );
    }
}
