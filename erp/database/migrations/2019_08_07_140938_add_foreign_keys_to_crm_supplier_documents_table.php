<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCrmSupplierDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crm_supplier_documents', function (Blueprint $table) {
            $table->foreign('supplier_id', 'fkey_sygnxyhzht')->references('id')->on('crm_suppliers')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('crm_supplier_documents', function (Blueprint $table) {
            $table->dropForeign('fkey_sygnxyhzht');
        });
    }
}
