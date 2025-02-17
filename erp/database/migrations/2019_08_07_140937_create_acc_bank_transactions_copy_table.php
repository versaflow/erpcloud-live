<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccBankTransactionsCopyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acc_register_bank_copy', function (Blueprint $table) {
            $table->date('docdate')->nullable();
            $table->integer('id', true);
            $table->string('reference')->nullable();
            $table->float('tax', 10, 0)->default(0);
            $table->float('amount', 10, 0)->nullable();
            $table->float('balance', 10, 0)->nullable()->default(0);
            $table->integer('account_id')->default(0);
            $table->integer('supplier_id')->default(0);
            $table->integer('ledger_account_id')->default(0);
            $table->text('payment_id', 65535)->nullable();
            $table->text('invoice_file', 65535)->nullable();
            $table->text('statement_file', 65535)->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('acc_register_bank_copy');
    }
}
