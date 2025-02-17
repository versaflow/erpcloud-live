<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_notifications', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('account_id')->nullable();
            $table->string('type', 50)->nullable();
            $table->string('title')->nullable();
            $table->string('message')->nullable();
            $table->string('link', 50)->nullable();
            $table->boolean('read')->default(0);
            $table->timestamp('created_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_notifications');
    }
}
