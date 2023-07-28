<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('orderId')->index();
            $table->string('symbol')->index();

            $table->boolean('isWorking')->nullable();

            $table->double('income')->nullable();
            $table->double('amountBuy')->nullable();
            $table->double('amountSell')->nullable();

            $table->double('quantityBuy')->nullable();
            $table->double('quantitySell')->nullable();

            $table->double('priceMin')->nullable();
            $table->double('priceBuy')->nullable();
            $table->double('priceSell')->nullable();

            $table->double('priceBuyCommission')->nullable();
            $table->double('priceSellCommission')->nullable();

            $table->string('commissionSellCurrency')->nullable();
            $table->string('commissionBuyCurrency')->nullable();

            $table->double('commissionRateMaker')->nullable();
            $table->double('commissionRateTaker')->nullable();

            $table->double('makerCommission')->nullable();
            $table->double('takerCommission')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
