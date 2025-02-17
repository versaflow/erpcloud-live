<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateIspPbxNumbersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('isp_voice_numbers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->bigInteger('number')->nullable()->unique('number');
            $table->integer('account_id')->default(0);
            $table->string('status')->nullable()->default('Enabled');
            $table->string('domain_uuid')->nullable();
            $table->string('number_uuid')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('isp_voice_numbers');
    }
}
