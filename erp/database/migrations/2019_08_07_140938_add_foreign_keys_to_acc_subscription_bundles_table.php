<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToAccSubscriptionBundlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sub_contracts', function (Blueprint $table) {
            $table->foreign('account_id', 'fkey_rwiohjwpba')->references('id')->on('crm_accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('product_id', 'fkey_swsectaeyq')->references('id')->on('crm_products')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sub_contracts', function (Blueprint $table) {
            $table->dropForeign('fkey_rwiohjwpba');
            $table->dropForeign('fkey_swsectaeyq');
        });
    }
}
