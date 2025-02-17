<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpCommunicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_communication_lines', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('account_id')->nullable();
            $table->integer('lead_id')->nullable();
            $table->dateTime('date_sent')->nullable();
            $table->string('email_address')->nullable();
            $table->string('subject')->nullable();
            $table->text('message', 65535)->nullable();
            $table->boolean('success')->default(0);
            $table->string('error')->nullable();
            $table->string('phone_number')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_communication_lines');
    }
}
