<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmAccountsCopyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_accounts_copy', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id')->default(1);
            $table->integer('partner_id')->default(1);
            $table->string('type', 50);
            $table->string('company');
            $table->string('contact');
            $table->string('email');
            $table->string('cc_email')->nullable();
            $table->string('landline');
            $table->string('mobile');
            $table->text('address', 65535);
            $table->integer('region_id')->default(1);
            $table->string('vat_number', 50);
            $table->text('notes', 65535);
            $table->boolean('cancelled')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->float('subscriptions', 10, 0)->default(0);
            $table->integer('call_ext')->nullable();
            $table->integer('call_duration')->nullable();
            $table->date('call_time')->nullable();
            $table->date('email_date')->nullable();
            $table->integer('email_notification_id')->nullable();
            $table->date('last_invoice_date')->nullable();
            $table->float('sales6', 10, 0)->default(0);
            $table->float('sales12', 10, 0)->default(0);
            $table->string('sales_status', 50)->nullable();
            $table->float('current', 10, 0)->default(0);
            $table->float('days30', 10, 0)->default(0);
            $table->float('days60', 10, 0)->default(0);
            $table->float('days90', 10, 0)->default(0);
            $table->float('days120', 10, 0)->default(0);
            $table->float('balance', 10, 0)->default(0);
            $table->date('debtor_date')->nullable();
            $table->boolean('invalid_email')->default(0);
            $table->integer('customer_qty');
            $table->integer('website_id')->nullable();
            $table->dateTime('last_login')->nullable();
            $table->string('api_key');
            $table->boolean('system_disabled')->default(0);
            $table->string('status', 50)->default('Enabled');
            $table->boolean('debit_order')->default(0);
            $table->boolean('for_export')->default(0);
            $table->integer('pricelist_id')->nullable();
            $table->integer('aging')->default(0);
            $table->date('commitment_date')->nullable();
            $table->string('bank_reference')->nullable();
            $table->boolean('bank_allocate_airtime')->nullable();
            $table->float('airtime_contract', 10, 0)->nullable();
            $table->float('airtime_prepaid', 10, 0)->nullable();
            $table->float('airtime_contract_limit', 10, 0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_accounts_copy');
    }
}
