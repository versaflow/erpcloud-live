<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmMarketingChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_marketing_channels', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('responsible')->nullable();
            $table->string('channel')->nullable();
            $table->string('market')->nullable();
            $table->string('stage')->nullable();
            $table->string('notes', 2000)->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_marketing_channels');
    }
}
