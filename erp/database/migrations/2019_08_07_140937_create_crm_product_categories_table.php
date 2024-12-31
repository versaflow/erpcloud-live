<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmProductCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_product_categories', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('website_id')->nullable();
            $table->string('name');
            $table->string('type', 50)->default('products');
            $table->integer('sort_order')->nullable();
            $table->string('slogan', 50)->nullable();
            $table->string('benefits_message', 800)->nullable();
            $table->string('target_markets', 1000)->nullable();
            $table->string('product_keywords', 100)->nullable();
            $table->string('adwords_ad_1', 500)->nullable();
            $table->string('adwords_ad_2', 500)->nullable();
            $table->string('adwords_ad_3', 500)->nullable();
            $table->string('brochure')->nullable();
            $table->string('banner_ad')->nullable();
            $table->string('video_ad')->nullable();
            $table->integer('is_forsale')->default(0);
            $table->string('department', 50)->nullable();
            $table->integer('department_website_id')->nullable();
            $table->string('status', 50)->default('Enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_product_categories');
    }
}
