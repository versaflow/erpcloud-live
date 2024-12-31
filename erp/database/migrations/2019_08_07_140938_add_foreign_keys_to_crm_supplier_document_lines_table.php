<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCrmSupplierDocumentLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crm_supplier_document_lines', function (Blueprint $table) {
            $table->foreign('product_id', 'fkey_ajenygejeq')->references('id')->on('crm_products')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('document_id', 'fkey_pumvmhxcqe')->references('id')->on('crm_supplier_documents')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('crm_supplier_document_lines', function (Blueprint $table) {
            $table->dropForeign('fkey_ajenygejeq');
            $table->dropForeign('fkey_pumvmhxcqe');
        });
    }
}
