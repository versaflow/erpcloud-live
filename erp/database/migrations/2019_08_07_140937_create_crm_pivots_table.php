<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmPivotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_reports', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name')->nullable();
            $table->integer('module_id')->nullable();
            $table->text('settings', 65535)->nullable();
            $table->text('sql_query', 65535)->nullable();
            $table->text('staging_query', 65535)->nullable();
            $table->text('staging_tables', 65535)->nullable();
            $table->string('connection')->default('mysql');
            $table->text('month_filter_query', 65535)->nullable();
            $table->text('staging_columns', 65535)->nullable();
            $table->string('month_filter_field')->nullable();
            $table->text('concat_columns', 65535)->nullable();
            $table->integer('report_model_id')->nullable();
            $table->text('query_data', 65535)->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_reports');
    }
}
