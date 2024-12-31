<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateIspPbxExtensionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('isp_voice_extensions', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('account_id')->default(0);
            $table->string('extension_uuid')->nullable();
            $table->string('domain_uuid')->nullable();
            $table->string('extension')->nullable();
            $table->string('description')->nullable();
            $table->string('password')->nullable();
            $table->string('user_context')->nullable();
            $table->boolean('toll_allow')->default(0);
            $table->string('extension_type', 50)->nullable();
            $table->string('forward_all_destination', 50)->nullable();
            $table->boolean('forward_all_enabled')->default(0);
            $table->string('forward_busy_destination', 50)->nullable();
            $table->boolean('forward_busy_enabled')->default(0);
            $table->string('forward_no_answer_destination', 50)->nullable();
            $table->boolean('forward_no_answer_enabled')->default(0);
            $table->string('forward_user_not_registered_destination', 50)->nullable();
            $table->boolean('forward_user_not_registered_enabled')->default(0);
            $table->string('outbound_caller_id_number', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('isp_voice_extensions');
    }
}
