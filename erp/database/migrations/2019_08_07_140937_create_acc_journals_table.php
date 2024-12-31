<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccJournalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acc_general_journals', function (Blueprint $table) {
            $table->integer('id', true);
            $table->dateTime('date')->nullable();
            $table->integer('debit_account_id')->nullable();
            $table->integer('credit_account_id')->nullable();
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
        Schema::drop('acc_general_journals');
    }
}
