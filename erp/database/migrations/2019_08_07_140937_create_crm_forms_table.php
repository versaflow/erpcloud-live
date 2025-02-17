<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_forms', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name')->nullable();
            $table->string('method', 50)->nullable()->default('table');
            $table->string('tablename', 50)->nullable();
            $table->string('email', 225)->nullable();
            $table->text('configuration')->nullable();
            $table->text('success', 65535)->nullable();
            $table->text('failed', 65535)->nullable();
            $table->text('redirect', 65535)->nullable();
            $table->binary('form_json')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_forms');
    }
}
