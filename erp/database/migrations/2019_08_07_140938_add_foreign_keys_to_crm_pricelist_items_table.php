<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCrmPricelistItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crm_pricelist_items', function (Blueprint $table) {
            $table->foreign('product_id', 'fkey_blbpjsetmy')->references('id')->on('crm_products')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('crm_pricelist_items', function (Blueprint $table) {
            $table->dropForeign('fkey_blbpjsetmy');
        });
    }
}
