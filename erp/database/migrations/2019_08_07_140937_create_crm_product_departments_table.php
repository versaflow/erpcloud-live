<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmProductDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_product_departments', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->integer('active')->default(0);
            $table->integer('sort_order')->default(0);
            $table->integer('website_id')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_product_departments');
    }
}
