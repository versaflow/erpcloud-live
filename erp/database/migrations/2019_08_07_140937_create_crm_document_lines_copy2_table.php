<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmDocumentLinesCopy2Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_document_lines_copy2', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('document_id')->index('fkey_pmgelwyuks');
            $table->integer('product_id')->index('fkey_bmnjivqoux');
            $table->integer('qty')->nullable();
            $table->float('price', 10, 0)->nullable();
            $table->string('description')->nullable();
            $table->float('full_price', 10, 0)->nullable();
            $table->float('service_price', 10, 0)->nullable();
            $table->float('service_full_price', 10, 0)->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_document_lines_copy2');
    }
}
