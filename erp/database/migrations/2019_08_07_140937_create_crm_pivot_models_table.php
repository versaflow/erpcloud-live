<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmPivotModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_report_models', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('model_id')->nullable();
            $table->string('model_name')->nullable();
            $table->text('model_xml', 65535)->nullable();
            $table->text('model_json', 65535)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_report_models');
    }
}
