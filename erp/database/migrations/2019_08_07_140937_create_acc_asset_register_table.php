<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccAssetRegisterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acc_asset_register', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('type')->nullable();
            $table->string('name', 50)->nullable();
            $table->string('details', 1000)->nullable();
            $table->float('cost_value', 10, 0)->nullable();
            $table->string('taken_by_name')->nullable();
            $table->date('taken_by_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('acc_asset_register');
    }
}
