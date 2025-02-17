<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccDebitOrderBatchCopyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acc_debit_order_batch_copy', function (Blueprint $table) {
            $table->integer('id', true);
            $table->dateTime('action_date')->nullable();
            $table->text('batch', 65535)->nullable();
            $table->string('batch_file')->nullable();
            $table->text('result', 65535)->nullable();
            $table->string('result_token')->nullable();
            $table->string('result_file')->nullable();
            $table->boolean('uploaded')->default(0);
            $table->dateTime('created_at')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('acc_debit_order_batch_copy');
    }
}
