<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acc_payments', function (Blueprint $table) {
            $table->integer('id', true);
            $table->date('docdate')->nullable();
            $table->integer('account_id')->nullable();
            $table->float('total', 10, 0)->nullable()->default(0);
            $table->string('reference')->nullable();
            $table->integer('user_id')->nullable();
            $table->boolean('reconciled')->default(0);
            $table->integer('bank_id')->nullable();
            $table->integer('cash_id')->nullable();
            $table->integer('api_id')->nullable();
            $table->string('source')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('acc_payments');
    }
}
