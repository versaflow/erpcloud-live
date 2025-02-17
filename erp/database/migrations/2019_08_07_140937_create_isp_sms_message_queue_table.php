<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateIspSmsMessageQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('isp_sms_message_queue', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('isp_sms_messages_id');
            $table->dateTime('time_queued')->nullable();
            $table->string('number', 100)->default('');
            $table->string('status', 100)->default('0');
            $table->string('error_description', 100)->nullable();
            $table->integer('account_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('isp_sms_message_queue');
    }
}
