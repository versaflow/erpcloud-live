<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmRepairsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_repairs', function (Blueprint $table) {
            $table->integer('id', true);
            $table->date('date')->nullable();
            $table->integer('account_id')->nullable();
            $table->string('item_details', 1000)->nullable();
            $table->string('fault_description', 50)->nullable();
            $table->date('invoice_date')->nullable();
            $table->integer('invoice_no')->nullable();
            $table->integer('supplier_id')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 50)->nullable();
            $table->text('supplier_reference', 65535)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_repairs');
    }
}
