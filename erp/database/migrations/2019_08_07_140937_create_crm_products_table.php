<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_products', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('product_category_id')->default(0)->index('fkey_vcjzmldozy');
            $table->string('type')->default('Service');
            $table->string('code', 256);
            $table->string('name', 256);
            $table->string('description', 5000);
            $table->string('frequency', 256);
            $table->float('cost_price', 10)->default(0.00);
            $table->integer('qty_on_hand')->default(0);
            $table->boolean('active')->default(1);
            $table->integer('sort_order')->nullable()->default(0);
            $table->string('upload_file', 200);
            $table->integer('provision_plan_id')->nullable();
            $table->string('provision_package')->nullable();
            $table->text('cost_calculation', 65535)->nullable();
            $table->string('prorata_delay')->nullable();
            $table->text('included_products', 65535)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_products');
    }
}
