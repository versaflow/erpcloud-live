<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_services', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('account_id')->default(0);
            $table->integer('product_id')->default(0)->index('fkey_xmrynkekna');
            $table->string('provision_type', 50)->default('other');
            $table->string('detail')->nullable();
            $table->string('status', 50)->default('Enabled');
            $table->integer('migrate_product_id')->nullable();
            $table->date('created_at')->nullable();
            $table->date('deleted_at')->nullable();
            $table->string('notify')->nullable();
            $table->decimal('last_usage', 10)->default(0.00);
            $table->decimal('current_usage', 10)->default(0.00);
            $table->string('usage_type')->default('');
            $table->float('usage_allocation', 10, 0)->default(0);
            $table->text('tracking', 65535)->nullable();
            $table->boolean('to_migrate')->default(0);
            $table->boolean('to_cancel')->default(0);
            $table->string('description')->nullable();
            $table->string('class_id')->nullable();
            $table->integer('request_id')->nullable();
            $table->text('tracking_summary', 65535)->nullable();
            $table->date('contract_expiry_date')->nullable();
            $table->date('backup_date')->nullable();
            $table->unique(['account_id', 'product_id', 'detail', 'status'], 'unique_line');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sub_services');
    }
}
