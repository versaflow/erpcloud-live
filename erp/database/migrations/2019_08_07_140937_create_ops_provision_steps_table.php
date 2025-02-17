<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOpsProvisionStepsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_activation_steps', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('provision_id')->nullable();
            $table->integer('provision_plan_id')->nullable();
            $table->text('input', 65535)->nullable();
            $table->string('result')->nullable();
            $table->boolean('completed');
            $table->timestamps();
            $table->string('subscription_detail')->nullable();
            $table->text('subscription_info', 65535)->nullable();
            $table->text('email_data', 65535)->nullable();
            $table->text('table_data', 65535)->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sub_activation_steps');
    }
}
