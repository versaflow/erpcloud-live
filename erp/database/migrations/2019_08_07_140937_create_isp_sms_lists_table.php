<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateIspSmsListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('isp_sms_lists', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 100)->default('');
            $table->integer('account_id')->default(0)->index('uid_4');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('isp_sms_lists');
    }
}
