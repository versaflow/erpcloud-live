<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateIspHostZacrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('isp_host_zacr', function (Blueprint $table) {
            $table->string('id', 30);
            $table->string('qdate');
            $table->string('msg')->default('');
            $table->string('domain_name')->nullable();
            $table->string('domain_trstatus')->nullable();
            $table->string('domain_reid')->nullable();
            $table->dateTime('domain_redate')->nullable();
            $table->string('domain_acid')->nullable();
            $table->dateTime('domain_acdate')->nullable();
            $table->integer('acknowledged')->nullable()->default(0);
            $table->integer('account_id')->nullable()->default(0);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('isp_host_zacr');
    }
}
