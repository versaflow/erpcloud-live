<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccCostAdjustmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acc_adjust_cost', function (Blueprint $table) {
            $table->integer('id', true);
            $table->date('docdate')->nullable();
            $table->integer('product_id')->default(0);
            $table->float('new_cost', 10, 0);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('acc_adjust_cost');
    }
}
