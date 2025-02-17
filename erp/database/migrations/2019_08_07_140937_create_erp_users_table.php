<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_users', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('full_name', 100)->nullable();
            $table->integer('account_id')->default(7);
            $table->string('role_ids')->nullable();
            $table->boolean('active')->default(1);
            $table->string('username')->unique('username');
            $table->string('password');
            $table->string('email')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->string('activation_reset', 100)->nullable();
            $table->dateTime('last_login')->nullable();
            $table->timestamps();
            $table->string('pbx_extension', 50)->nullable();
            $table->string('api_token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_users');
    }
}
