<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToAccSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sub_services', function (Blueprint $table) {
            $table->foreign('account_id', 'fkey_hvrveukoqs')->references('id')->on('crm_accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('product_id', 'fkey_xmrynkekna')->references('id')->on('crm_products')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sub_services', function (Blueprint $table) {
            $table->dropForeign('fkey_hvrveukoqs');
            $table->dropForeign('fkey_xmrynkekna');
        });
    }
}
