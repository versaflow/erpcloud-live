<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmNewslettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_email_manager', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('group_id')->nullable();
            $table->string('name');
            $table->binary('gjs_editor')->nullable();
            $table->integer('partner_id')->default(1);
            $table->integer('product_category_id')->nullable();
            $table->integer('attach_pricelist_id')->nullable();
            $table->string('role_ids')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_email_manager');
    }
}
