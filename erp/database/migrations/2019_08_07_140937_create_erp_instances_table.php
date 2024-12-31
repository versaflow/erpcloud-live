<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_instances', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->string('domain_name')->nullable();
            $table->string('type', 50)->nullable();
            $table->string('db_connection', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_instances');
    }
}
