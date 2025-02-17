<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpMenuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_menu', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('parent_id')->nullable()->default(0);
            $table->integer('module_id')->default(0);
            $table->string('menu_name')->nullable();
            $table->string('menu_type', 100)->nullable();
            $table->string('menu_icon', 100)->nullable();
            $table->text('url', 65535)->nullable();
            $table->string('favicon')->nullable();
            $table->integer('ordering')->nullable();
            $table->integer('grid_sort')->nullable();
            $table->boolean('active')->nullable()->default(1);
            $table->string('slug')->nullable();
            $table->boolean('new_tab')->nullable()->default(0);
            $table->string('panel_menu')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_menu');
    }
}
