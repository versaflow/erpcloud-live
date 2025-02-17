<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToAccLedgerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('acc_ledgers', function (Blueprint $table) {
            $table->foreign('ledger_account_id', 'fkey_nbiripqfea')->references('id')->on('acc_ledger_accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('account_id', 'fkey_ohoxzlpkxf')->references('id')->on('crm_accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('acc_ledgers', function (Blueprint $table) {
            $table->dropForeign('fkey_nbiripqfea');
            $table->dropForeign('fkey_ohoxzlpkxf');
        });
    }
}
