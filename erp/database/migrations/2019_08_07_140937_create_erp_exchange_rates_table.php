<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpExchangeRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_exchange_rates', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('base_currency', 10)->nullable();
            $table->string('currency', 10)->nullable();
            $table->float('rate', 10, 0)->nullable();
            $table->date('exchange_date')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_exchange_rates');
    }
}
