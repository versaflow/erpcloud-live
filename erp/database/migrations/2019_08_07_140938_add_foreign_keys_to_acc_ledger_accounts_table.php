<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToAccLedgerAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('acc_ledger_accounts', function (Blueprint $table) {
            $table->foreign('ledger_account_category_id', 'fkey_xsbxwgevvu')->references('id')->on('acc_ledger_account_categories')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('acc_ledger_accounts', function (Blueprint $table) {
            $table->dropForeign('fkey_xsbxwgevvu');
        });
    }
}
