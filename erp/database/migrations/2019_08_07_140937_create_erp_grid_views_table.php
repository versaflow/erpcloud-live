<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpGridViewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_grid_views', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('module_id')->nullable();
            $table->integer('role_ids')->nullable();
            $table->string('name')->nullable();
            $table->text('settings', 65535)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_grid_views');
    }
}
