<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmSupplierDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_supplier_documents', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('supplier_id')->index('fkey_sygnxyhzht');
            $table->date('docdate')->nullable();
            $table->string('doctype')->nullable()->default('Tax Invoice');
            $table->float('discount', 10, 0)->default(0);
            $table->float('tax', 10, 0)->default(0);
            $table->float('total', 10, 0)->default(0);
            $table->string('status');
            $table->string('notes')->nullable();
            $table->string('reference')->nullable();
            $table->dateTime('duedate')->nullable();
            $table->string('stage')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('reversal_id')->default(0);
            $table->integer('prov_user_id')->nullable();
            $table->string('prov_priority')->nullable();
            $table->string('prov_notes')->nullable();
            $table->text('invoice_file', 65535)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_supplier_documents');
    }
}
