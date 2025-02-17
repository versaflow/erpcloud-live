<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpGroupsAccessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_menu_role_access', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('menu_id')->nullable();
            $table->integer('role_id')->nullable();
            $table->boolean('is_menu');
            $table->boolean('is_view')->default(0);
            $table->boolean('is_view')->default(0);
            $table->boolean('is_add')->default(0);
            $table->boolean('is_edit')->default(0);
            $table->boolean('is_add')->default(0);
            $table->boolean('is_delete')->default(0);
            $table->boolean('is_export')->default(0);
            $table->boolean('is_disabled')->default(0);
            $table->boolean('record_type_all')->default(0);
            $table->boolean('record_type_partner')->default(0);
            $table->boolean('record_type_account')->default(0);
            $table->boolean('record_type_group')->default(0);
            $table->unique(['menu_id','role_id'], 'menu_id');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_menu_role_access');
    }
}
