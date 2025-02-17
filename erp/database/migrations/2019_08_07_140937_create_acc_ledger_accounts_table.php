<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccLedgerAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acc_ledger_accounts', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('ledger_account_category_id')->nullable()->index('fkey_xsbxwgevvu');
            $table->string('name')->nullable();
            $table->integer('taxable')->default(0);
            $table->integer('allow_payments')->nullable()->default(1);
            $table->float('target', 10, 0)->default(0);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('acc_ledger_accounts');
    }
}
