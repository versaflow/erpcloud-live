<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccDebitOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acc_debit_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->nullable();
            $table->string('bank_name', 30)->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('bank_branch_code', 6)->nullable();
            $table->string('bank_account_type', 50)->nullable();
            $table->string('bank_account_number', 11)->nullable();
            $table->timestamps();
            $table->boolean('validated')->default(0);
            $table->string('validate_message')->nullable();
            $table->text('debit_order_mandate', 65535)->nullable();
            $table->text('id_file', 65535)->nullable();
            $table->text('company_registration_file', 65535)->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('acc_debit_orders');
    }
}
