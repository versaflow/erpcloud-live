<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpFlowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_flows', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('workflow')->nullable();
            $table->integer('sort')->nullable();
            $table->integer('backend_id')->nullable();
            $table->integer('user_type')->nullable();
            $table->string('task', 1000)->nullable();
            $table->string('policy', 1000)->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_flows');
    }
}
