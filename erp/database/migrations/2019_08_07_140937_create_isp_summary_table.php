<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateIspSummaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_dashboards', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('account_id')->default(0);
            $table->string('pabx_domain');
            $table->string('pabx_type', 50);
            $table->boolean('pabx_registered')->default(0);
            $table->integer('extensions')->default(0);
            $table->integer('extension_recording')->default(0);
            $table->integer('numbers')->default(0);
            $table->integer('websites')->default(0);
            $table->integer('lte_accounts')->default(0);
            $table->integer('fibre_accounts')->default(0);
            $table->integer('fibre_addons')->default(0);
            $table->boolean('sub_data')->default(0);
            $table->boolean('sub_telecoms')->default(0);
            $table->boolean('sub_hosting')->default(0);
            $table->boolean('sub_sms')->default(0);
            $table->string('voice_contract', 50);
            $table->string('directadmin_user', 50)->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_dashboards');
    }
}
