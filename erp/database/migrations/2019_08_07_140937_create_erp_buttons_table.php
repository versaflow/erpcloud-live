<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpButtonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_grid_buttons', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('button_group', 11)->default('');
            $table->integer('module_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('name', 100)->nullable();
            $table->string('icon')->nullable();
            $table->string('type', 50)->nullable();
            $table->string('modal_size', 50)->nullable();
            $table->string('url')->nullable();
            $table->string('confirm')->nullable();
            $table->string('access')->nullable();
            $table->text('grid_access', 65535)->nullable();
            $table->boolean('require_grid_id')->default(0);
            $table->integer('link_module_id')->default(0);
            $table->integer('context_menu')->nullable()->default(0);
            $table->boolean('in_iframe')->default(0);
            $table->text('button_definition', 65535)->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_grid_buttons');
    }
}
