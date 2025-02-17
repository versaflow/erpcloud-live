<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmServerManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_server_management', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('category')->nullable();
            $table->string('topic')->nullable();
            $table->text('article')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_server_management');
    }
}
