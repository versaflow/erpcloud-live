<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmPricelistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_pricelists', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('partner_id');
            $table->string('name')->nullable();
            $table->boolean('default_pricelist')->default(0);
            $table->integer('default_markup')->default(15);
            $table->string('type', 50)->default('retail');
            $table->string('currency', 10)->default('ZAR');
            $table->float('exchange_rate', 10, 0)->default(0);
            $table->integer('internation_call_markup')->default(15);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_pricelists');
    }
}
