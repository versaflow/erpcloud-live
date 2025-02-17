<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmDocumentsCopy2Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_documents_copy2', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('partner_document_id')->nullable();
            $table->integer('account_id')->nullable()->index('fkey_dvokmrrfqw');
            $table->date('docdate')->nullable();
            $table->string('doctype')->nullable()->default('Tax Invoice');
            $table->float('discount', 10, 0)->default(0);
            $table->float('delivery_fee', 10, 0)->default(0);
            $table->float('tax', 10, 0)->default(0);
            $table->float('total', 10, 0)->default(0);
            $table->string('status');
            $table->string('notes')->nullable();
            $table->string('reference')->nullable();
            $table->dateTime('duedate')->nullable();
            $table->string('stage')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('reversal_id')->default(0);
            $table->string('delivery')->default('Collection');
            $table->boolean('monthly_billing')->default(0);
            $table->integer('store_order')->default(0);
            $table->float('exchange_rate', 10, 0)->nullable();
            $table->boolean('applied')->default(0);
            $table->integer('reseller_user')->nullable()->index('fkey_jvoysklgxq');
            $table->string('provision_status')->default('');
            $table->string('payment_status')->default('');
            $table->float('service_tax', 10, 0)->nullable();
            $table->float('service_total', 10, 0)->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_documents_copy2');
    }
}
