<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmInternalTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_internal_tasks', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('product_id')->nullable();
            $table->date('start_date')->nullable();
            $table->text('task', 65535)->nullable();
            $table->string('type', 50)->nullable();
            $table->text('notes', 65535);
            $table->string('support_status')->nullable();
            $table->string('status')->default('New');
            $table->boolean('update_dates')->default(0);
            $table->string('escalation_contact', 50)->nullable();
            $table->string('escalation_ref', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_internal_tasks');
    }
}
