<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateIspDataLteTopupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('isp_data_ltes_topups', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('lte_account_id');
            $table->string('lte_account_username');
            $table->integer('TopUpID');
            $table->string('TopupClassID')->nullable();
            $table->dateTime('AssignedDate')->nullable();
            $table->dateTime('ActiveFrom')->nullable();
            $table->string('Status')->nullable();
            $table->string('ByteCounter')->nullable();
            $table->integer('account_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('isp_data_ltes_topups');
    }
}
