<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_log_errors', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('orderId')->index();
            $table->string('type')->nullable();
            $table->string('symbol')->nullable();
            $table->jsonb('data')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_log_errors');
    }
};
