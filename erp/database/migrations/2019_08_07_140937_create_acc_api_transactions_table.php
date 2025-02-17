<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccApiTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acc_register_payment_gateways', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('docdate')->nullable();
            $table->integer('account_id')->nullable();
            $table->integer('payment_id')->nullable();
            $table->float('amount', 10, 0)->default(0);
            $table->string('status')->nullable();
            $table->text('reference', 65535)->nullable();
            $table->string('provider')->nullable();
            $table->text('params', 65535)->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('acc_register_payment_gateways');
    }
}
