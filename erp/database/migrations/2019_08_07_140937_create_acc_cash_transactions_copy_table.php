<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccCashTransactionsCopyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acc_register_cash_copy', function (Blueprint $table) {
            $table->integer('id', true);
            $table->date('docdate')->nullable();
            $table->float('tax', 10, 0)->default(0);
            $table->float('amount', 10, 0)->nullable();
            $table->float('balance', 10, 0)->nullable()->default(0);
            $table->float('actual_balance', 10, 0)->nullable();
            $table->integer('ledger_account_id')->default(0);
            $table->string('reference')->nullable();
            $table->integer('bank_id')->nullable();
            $table->integer('payment_id')->nullable();
            $table->integer('account_id')->nullable();
            $table->integer('supplier_id')->nullable();
            $table->text('invoice_file', 65535)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('acc_register_cash_copy');
    }
}
