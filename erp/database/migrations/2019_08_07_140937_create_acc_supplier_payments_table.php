<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccSupplierPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acc_payment_suppliers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->dateTime('docdate')->nullable();
            $table->integer('supplier_id')->nullable();
            $table->float('total', 10, 0)->nullable()->default(0);
            $table->string('reference')->nullable()->default('');
            $table->string('payment_method')->nullable()->default('');
            $table->string('status')->nullable()->default('Complete');
            $table->integer('voided')->default(0);
            $table->integer('cash_id')->nullable();
            $table->integer('bank_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('acc_payment_suppliers');
    }
}
