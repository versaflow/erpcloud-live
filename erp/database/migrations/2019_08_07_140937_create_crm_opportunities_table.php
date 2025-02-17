<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_deals', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id')->default(1);
            $table->integer('partner_id')->nullable();
            $table->integer('account_id')->nullable();
            $table->string('company')->nullable();
            $table->string('contact')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->integer('department')->default(0);
            $table->string('interest', 1000)->nullable();
            $table->string('status')->default('New');
            $table->integer('channel_id')->default(0);
            $table->string('landline')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('suburb')->nullable();
            $table->string('province')->nullable();
            $table->string('vat_number', 50)->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_deals');
    }
}
