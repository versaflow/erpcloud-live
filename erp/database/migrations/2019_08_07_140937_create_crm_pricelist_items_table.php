<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmPricelistItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_pricelist_items', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('pricelist_id');
            $table->integer('product_id')->nullable()->index('fkey_blbpjsetmy');
            $table->float('cost_price', 10)->unsigned()->default(0.00);
            $table->float('cost_price_tax', 10)->unsigned()->default(0.00);
            $table->integer('markup')->unsigned()->default(1);
            $table->float('price', 10)->unsigned()->default(0.00);
            $table->float('price_tax', 10)->unsigned()->default(0.00);
            $table->float('price_exchange', 10)->unsigned()->default(0.00);
            $table->float('retail_avg', 10)->unsigned()->default(0.00);
            $table->float('wholesale_avg', 10)->unsigned()->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_pricelist_items');
    }
}
