<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpMappingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_mapping', function (Blueprint $table) {
            $table->string('table_1', 50)->nullable();
            $table->string('table_2', 50)->nullable();
            $table->string('field_1', 50)->nullable();
            $table->string('field_2', 50)->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_mapping');
    }
}
