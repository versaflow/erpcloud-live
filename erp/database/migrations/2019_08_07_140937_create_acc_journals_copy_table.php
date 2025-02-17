<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccJournalsCopyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acc_general_journals_copy', function (Blueprint $table) {
            $table->integer('id', true);
            $table->dateTime('docdate')->nullable();
            $table->integer('debit_account_id')->nullable();
            $table->integer('credit_account_id')->nullable();
            $table->integer('account_id')->nullable();
            $table->float('amount', 10, 0)->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('acc_general_journals_copy');
    }
}
