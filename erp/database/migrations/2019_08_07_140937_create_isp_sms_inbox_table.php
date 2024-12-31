<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateIspSmsInboxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('isp_sms_inbox', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('account_id')->default(0);
            $table->dateTime('created_date')->nullable();
            $table->string('sender', 200)->default('');
            $table->string('message', 1000)->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('isp_sms_inbox');
    }
}
