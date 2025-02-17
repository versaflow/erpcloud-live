<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccLedgerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acc_ledgers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->date('date')->nullable();
            $table->integer('ledger_account_id')->nullable()->index('fkey_nbiripqfea');
            $table->float('amount', 10, 0)->nullable();
            $table->string('description')->nullable();
            $table->integer('account_id')->nullable()->index('fkey_ohoxzlpkxf');
            $table->integer('product_id')->nullable();
            $table->float('product_qty', 10, 0)->nullable()->default(0);
            $table->float('product_cost', 10, 0)->nullable()->default(0);
            $table->string('transaction_table')->nullable();
            $table->integer('transaction_id')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('acc_ledgers');
    }
}
