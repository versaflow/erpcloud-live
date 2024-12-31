<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpDoctypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acc_doctypes', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('doctype', 50);
            $table->string('prefix', 50);
            $table->string('doctable', 50);
            $table->boolean('customer_access')->default(0);
            $table->boolean('suppliers_only')->default(0);
            $table->boolean('form_show')->default(0);
            $table->integer('sort')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('acc_doctypes');
    }
}
