<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('order_buy_symbols', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->index();
            $table->string('currency')->index();

            $table->boolean('isWorking')->nullable();
            $table->boolean('status')->nullable();

            $table->integer('margin')->nullable();
            $table->float('profit')->nullable();
            $table->integer('limit')->nullable();
            
            $table->double('minPrice')->nullable();
            $table->integer('lotSize')->nullable();
            $table->integer('priceFilter')->nullable();

            $table->double('makerCommission')->nullable();
            $table->double('takerCommission')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_buy_symbols');
    }
};
