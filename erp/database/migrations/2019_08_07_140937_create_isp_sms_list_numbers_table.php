<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateIspSmsListNumbersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('isp_sms_list_numbers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('sms_list_id')->default(0);
            $table->string('number', 100)->default('')->index('mobile_4');
            $table->string('name', 250)->default('');
            $table->integer('account_id')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('isp_sms_list_numbers');
    }
}
