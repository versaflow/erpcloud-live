<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmAccountPartnerSettingsCopy3Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_account_partner_settings_copy3', function (Blueprint $table) {
            $table->integer('account_id')->index('fkey_innnubobqy');
            $table->boolean('bill_customers')->default(1);
            $table->boolean('vat_enabled')->default(0);
            $table->string('whitelabel_domain');
            $table->string('logo');
            $table->text('bank_details', 65535);
            $table->text('invoice_footer', 65535);
            $table->text('quote_footer', 65535);
            $table->boolean('payfast_enabled');
            $table->string('payfast_id');
            $table->float('voice_prepaid_profit', 10, 0)->default(0);
            $table->float('carrier_voice', 10, 0)->nullable();
            $table->float('carrier_sms', 10, 0)->nullable();
            $table->binary('email_template')->nullable();
            $table->string('smtp_host')->nullable();
            $table->string('smtp_port')->nullable();
            $table->string('smtp_username')->nullable();
            $table->string('smtp_password')->nullable();
            $table->string('smtp_encryption')->nullable();
            $table->string('sales_email')->nullable();
            $table->string('accounts_email')->nullable();
            $table->string('ofx_file_name')->nullable();
            $table->boolean('enable_client_invoice_creation')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_account_partner_settings_copy3');
    }
}
