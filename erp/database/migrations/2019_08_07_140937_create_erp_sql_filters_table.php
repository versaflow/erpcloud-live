<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpSqlFiltersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_sql_filters', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('levels', 50)->nullable();
            $table->string('option_name')->nullable();
            $table->text('option_value', 65535)->nullable();
            $table->string('required_column', 50)->nullable();
            $table->integer('num_modules')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_sql_filters');
    }
}
