<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCrmDocumentLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crm_document_lines', function (Blueprint $table) {
            $table->foreign('product_id', 'fkey_bmnjivqoux')->references('id')->on('crm_products')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('document_id', 'fkey_pmgelwyuks')->references('id')->on('crm_documents')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('crm_document_lines', function (Blueprint $table) {
            $table->dropForeign('fkey_bmnjivqoux');
            $table->dropForeign('fkey_pmgelwyuks');
        });
    }
}
