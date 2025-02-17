<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_user_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 20)->nullable();
            $table->string('level', 12)->default('Customer');
            $table->integer('default_module')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_user_roles');
    }
}
