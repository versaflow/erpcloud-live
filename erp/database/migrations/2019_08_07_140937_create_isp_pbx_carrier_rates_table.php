<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateIspPbxCarrierRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('isp_voice_international_rates', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->string('destination')->nullable();
            $table->float('admin_rate', 10, 0)->unsigned();
            $table->float('wholesale_rate', 10, 0)->nullable();
            $table->float('retail_rate', 10, 0)->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('isp_voice_international_rates');
    }
}
