<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccSubscriptionBundlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_contracts', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('account_id')->nullable()->index('fkey_rwiohjwpba');
            $table->integer('product_id')->nullable()->index('fkey_swsectaeyq');
            $table->integer('invoice_id')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->softDeletes();
            $table->string('status')->nullable()->default('Pending');
            $table->date('expiry_date')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sub_contracts');
    }
}
