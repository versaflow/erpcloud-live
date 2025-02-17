<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOpsProvisionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_activations', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('account_id');
            $table->integer('partner_id');
            $table->integer('user_id')->nullable();
            $table->integer('invoice_id');
            $table->integer('product_id');
            $table->date('start_date')->nullable();
            $table->text('notes', 65535);
            $table->integer('step')->default(1);
            $table->string('status')->default('New');
            $table->text('tracking', 65535)->nullable();
            $table->text('subscription_detail', 65535)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->integer('provision_plan_id')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sub_activations');
    }
}
