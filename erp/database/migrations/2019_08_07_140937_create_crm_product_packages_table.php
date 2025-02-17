<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmProductPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_product_packages', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('product_package_id')->nullable();
            $table->integer('product_id')->nullable();
            $table->integer('qty')->default(0);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_product_packages');
    }
}
