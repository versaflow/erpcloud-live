<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpModuleGridsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_grids', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('alias')->nullable();
            $table->string('field')->nullable();
            $table->string('label')->nullable();
            $table->integer('module_id')->nullable();
            $table->boolean('visible')->default(1);
            $table->boolean('picker_visible')->default(0);
            $table->string('filter')->nullable();
            $table->string('access')->nullable();
            $table->integer('sort_order')->nullable();
            $table->string('align', 50)->nullable();
            $table->string('orderby')->nullable();
            $table->string('type')->nullable();
            $table->string('format_value')->nullable();
            $table->boolean('groupby')->default(0);
            $table->text('conf', 65535)->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_grids');
    }
}
