<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpModuleFormsCopyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_forms_copy', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('module_id')->nullable();
            $table->string('tab')->nullable();
            $table->string('type')->nullable();
            $table->string('field')->nullable();
            $table->string('label')->nullable();
            $table->boolean('required')->default(0);
            $table->boolean('readonly')->default(0);
            $table->boolean('readonly_edit')->default(0);
            $table->boolean('add')->default(0);
            $table->boolean('edit')->default(0);
            $table->boolean('view')->default(0);
            $table->string('default_value', 50)->nullable();
            $table->integer('sort_order')->nullable();
            $table->string('access')->nullable();
            $table->string('tooltip')->nullable();
            $table->text('conf', 65535)->nullable();
            $table->text('display_logic', 65535)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_forms_copy');
    }
}
