<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateIspSmsMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('isp_sms_messages', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->text('sms_template_id', 65535)->nullable();
            $table->bigInteger('sms_list_id')->nullable();
            $table->integer('account_id')->nullable();
            $table->dateTime('queuetime')->nullable();
            $table->string('numbers', 2000)->nullable();
            $table->string('message', 1000)->nullable();
            $table->dateTime('schedule')->nullable();
            $table->integer('charactercount')->default(0);
            $table->integer('size')->default(1);
            $table->integer('quantity');
            $table->integer('total_qty')->default(0);
            $table->integer('delivered_qty')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('isp_sms_messages');
    }
}
