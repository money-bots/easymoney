<?php

namespace App\Services\Binance;

use App\Models\Orders\OrderBuySymbol;
use App\Models\Settings\Setting;
use App\Repositories\Binance\OrderLogErrorRepository;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

use function collect;
use function config;

class BaseService
{
    public function balanceBy(string $asse): float
    {
        return collect(
                $this->httpGetWithSignature("account")->json('balances')
            )->filter(function ($val) use ($asse) {
                return $val['asset'] === $asse;
            })->first()['free'] ?? 0;
    }

    public function httpGetWithSignature(string $url, array $data = []): Response
    {
        return $this->baseGetWithSignature($this->getUrl($url), $data);
    }

    public function baseGetWithSignature(string $url, array $data = []): Response
    {
        return Http::withHeaders([
            'X-MBX-APIKEY' => config('binance.key')
        ])->get($url, array_merge($this->getSignature($data), $data));
    }

    public function getSignature(array $data): array
    {
        $time = floor(microtime(true) * 1000);

        $data['timestamp'] = $time;
        $data['signature'] = hash_hmac('sha256', http_build_query($data), config('binance.secret'));

        return $data;
    }

    public function getUrl(string $url): string
    {
        return rtrim(config('binance.url'), '/') . '/' . ltrim($url, '/');
    }

    public function updateBalanceSettings(): void
    {
        $data = $this->httpGetWithSignature("account")->json();

        foreach ($data['balances'] as $val) {
            Setting::query()->updateOrCreate([
                'key' => $val['asset']
            ], [
                'value' => $val['free']
            ]);
        }

        Setting::query()->updateOrCreate(
            ['key' => 'commissionRateMaker'],
            ['value' => (float)$data['commissionRates']['maker']]
        );

        Setting::query()->updateOrCreate(
            ['key' => 'commissionRateTaker'],
            ['value' => (float)$data['commissionRates']['taker']]
        );

        $this->updateTradeFee();
    }

    public function updateTradeFee(): void
    {
        $data = $this->baseGetWithSignature(
            $this->getBaseUrl('/sapi/v1/asset/tradeFee')
        )->json();

        foreach ($data as $val) {
            OrderBuySymbol::query()->where('symbol', $val['symbol'])->update([
                'makerCommission' => $val['makerCommission'],
                'takerCommission' => $val['takerCommission'],
            ]);
        }
    }

    public function getBaseUrl(string $url): string
    {
        return rtrim(config('binance.baseUrl'), '/') . '/' . ltrim($url, '/');
    }

    public function price(string $symbol): ?float
    {
        $price = Http::get($this->getUrl("ticker/price?symbol=$symbol"))->json();

        if (! empty($price['code'])) {
            OrderLogErrorRepository::logError($price, type: 'PRICE');

            return null;
        }

        return $price['price'];
    }

    public function exchangeInfo(): array
    {
        return Http::get($this->getUrl("exchangeInfo"))->json();
    }

    public function prices(string $symbols = ''): array
    {
        return Http::get($this->getUrl("ticker/price" . (empty($symbols) ? '' : '?symbols=' . $symbols)))->json();
    }

    public function strLen(string $string): int
    {
        $val = explode('.', $string);

        if (empty($val[1])) {
            return 0;
        }

        $string = rtrim($val[1], '0');

        return strlen($string);
    }

    public function balance(): Collection
    {
        return collect($this->httpGetWithSignature("account")->json('balances'));
    }

    public function numberFormat(?float $value, int $size): float
    {
        if (empty($size)) {
            return (int)$value;
        }

        return preg_replace('/\.\d{' . $size . '}\K.+/', '', $value);
    }

    public function filterPrice(Collection $collection, string $asset): ?float
    {
        return $collection->filter(function ($val) use ($asset) {
            return $val['symbol'] === $asset;
        })->first()['price'];
    }

    public function httpPostWithSignature(string $url, array $data = []): Response
    {
        return $this->basePostWithSignature($this->getUrl($url), $data);
    }

    public function basePostWithSignature(string $url, array $data = []): Response
    {
        $url .= "?" . http_build_query(
                array_merge(
                    $data,
                    $this->getSignature($data)
                )
            );

        return Http::withHeaders([
            'X-MBX-APIKEY' => config('binance.key'),
        ])->send("POST", $url);
    }
}
