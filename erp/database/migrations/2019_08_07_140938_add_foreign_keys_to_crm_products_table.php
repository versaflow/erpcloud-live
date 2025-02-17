<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCrmProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crm_products', function (Blueprint $table) {
            $table->foreign('product_category_id', 'fkey_vcjzmldozy')->references('id')->on('crm_product_categories')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('crm_products', function (Blueprint $table) {
            $table->dropForeign('fkey_vcjzmldozy');
        });
    }
}
