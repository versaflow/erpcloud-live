<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateIspSmsApiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('isp_sms_api', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 50)->nullable();
            $table->string('description')->nullable();
            $table->string('method')->nullable();
            $table->string('url')->nullable();
            $table->string('params', 1000)->nullable();
            $table->string('response', 8000)->nullable();
            $table->string('example_request')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('isp_sms_api');
    }
}
