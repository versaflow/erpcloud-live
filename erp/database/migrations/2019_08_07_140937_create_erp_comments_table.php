<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_comments', function (Blueprint $table) {
            $table->integer('commentID', true);
            $table->integer('pageID')->nullable();
            $table->integer('userID')->nullable();
            $table->text('comments')->nullable();
            $table->dateTime('posted')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_comments');
    }
}
