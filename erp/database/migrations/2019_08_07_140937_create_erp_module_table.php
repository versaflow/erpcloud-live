<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpModuleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_cruds', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name')->nullable();
            $table->string('title')->nullable();
            $table->string('controller')->nullable();
            $table->integer('grid_function_id')->nullable();
            $table->text('grid_note', 65535)->nullable();
            $table->text('form_note', 65535)->nullable();
            $table->string('form_method', 50)->nullable();
            $table->string('view_method', 50)->nullable();
            $table->string('db_table')->nullable();
            $table->string('db_key')->nullable();
            $table->text('db_sql', 65535)->nullable();
            $table->text('db_where', 65535)->nullable();
            $table->boolean('disabledelete')->default(0);
            $table->boolean('disableedit')->default(0);
            $table->boolean('disablecreate')->default(0);
            $table->timestamps();
            $table->string('connection')->default('mysql');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_cruds');
    }
}
