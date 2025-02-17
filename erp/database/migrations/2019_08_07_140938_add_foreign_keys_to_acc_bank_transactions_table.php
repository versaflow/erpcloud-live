<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToAccBankTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('acc_register_bank', function (Blueprint $table) {
            $table->foreign('account_id', 'fkey_dhqybzelrj')->references('id')->on('crm_accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('supplier_id', 'fkey_khfgipgnyj')->references('id')->on('crm_suppliers')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('ledger_account_id', 'fkey_qajtxuwfpn')->references('id')->on('acc_ledger_accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('acc_register_bank', function (Blueprint $table) {
            $table->dropForeign('fkey_dhqybzelrj');
            $table->dropForeign('fkey_khfgipgnyj');
            $table->dropForeign('fkey_qajtxuwfpn');
        });
    }
}
