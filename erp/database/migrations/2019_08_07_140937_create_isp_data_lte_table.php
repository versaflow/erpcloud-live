<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateIspDataLteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('isp_data_ltes', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('username', 50)->nullable();
            $table->integer('account_id')->nullable();
            $table->integer('subscription_id')->nullable();
            $table->integer('product_id')->nullable();
            $table->integer('request_id')->nullable();
            $table->string('lte_class', 50)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('lte_status', 11)->nullable();
            $table->integer('lte_status_id')->nullable();
            $table->text('account_info', 65535)->nullable();
            $table->text('sim_info', 65535)->nullable();
            $table->text('subscription_info', 65535)->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('isp_data_ltes');
    }
}
