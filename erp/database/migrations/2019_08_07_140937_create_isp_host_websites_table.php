<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateIspHostWebsitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('isp_host_websites', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('account_id')->nullable()->default(9);
            $table->string('domain', 128)->default('')->unique('domain');
            $table->string('package');
            $table->string('website_type')->nullable();
            $table->integer('hosted')->nullable()->default(0);
            $table->date('domain_expiry')->nullable();
            $table->dateTime('last_sync')->nullable();
            $table->boolean('auto_renew')->default(0);
            $table->integer('to_update_nameservers')->default(0);
            $table->integer('to_update_contact')->default(0);
            $table->integer('to_register')->default(0);
            $table->integer('transfer_in')->default(0);
            $table->integer('transfer_out')->default(0);
            $table->integer('to_delete')->default(0);
            $table->boolean('sitebuilder')->default(0);
            $table->string('epp_key')->nullable();
            $table->string('username')->default('helpdesk@telecloud.co.za');
            $table->string('password')->default('superlicious');
            $table->integer('srs_id')->nullable();
            $table->string('srs_status')->nullable();
            $table->integer('product_id')->nullable();
            $table->string('provider', 50)->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->string('provider_status')->nullable();
            $table->text('provider_status_comment', 65535)->nullable();
            $table->string('backup_schedule')->nullable();
            $table->string('server', 50)->nullable();
            $table->integer('subscription_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('isp_host_websites');
    }
}
