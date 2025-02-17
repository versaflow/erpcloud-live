<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmSupplierDocumentLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_supplier_document_lines', function (Blueprint $table) {
            $table->integer('document_id')->nullable()->index('fkey_pumvmhxcqe');
            $table->integer('product_id')->nullable()->index('fkey_ajenygejeq');
            $table->integer('qty')->nullable();
            $table->float('price', 10, 0)->nullable();
            $table->string('description')->nullable();
            $table->string('provision_input');
            $table->text('provision_checklist', 65535)->nullable();
            $table->boolean('provision_complete')->default(1);
            $table->integer('qty_to_provision')->default(0);
            $table->float('full_price', 10, 0)->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_supplier_document_lines');
    }
}
