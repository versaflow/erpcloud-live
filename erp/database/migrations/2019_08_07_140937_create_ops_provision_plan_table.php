<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOpsProvisionPlanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_activation_plans', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('department', 100)->nullable();
            $table->string('name', 100)->nullable();
            $table->string('type', 50)->nullable();
            $table->string('step', 11)->nullable();
            $table->text('step_email', 65535)->nullable();
            $table->text('step_checklist', 65535)->nullable();
            $table->string('status', 50)->nullable()->default('Enabled');
            $table->boolean('automated')->default(0);
            $table->boolean('add_subscription')->default(0);
            $table->boolean('repeatable')->default(0);
            $table->boolean('admin_only')->default(0);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sub_activation_plans');
    }
}
