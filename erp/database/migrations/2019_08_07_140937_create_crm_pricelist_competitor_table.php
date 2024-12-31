<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmPricelistCompetitorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_product_competitors', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('type', 50)->nullable();
            $table->string('competitor')->nullable();
            $table->integer('product_id')->nullable();
            $table->float('price', 10, 0)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_product_competitors');
    }
}
