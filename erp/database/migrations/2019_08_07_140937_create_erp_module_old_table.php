<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpModuleOldTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_cruds_old', function (Blueprint $table) {
            $table->integer('module_id', true);
            $table->string('module_name', 100)->nullable();
            $table->string('package', 10)->nullable();
            $table->string('module_title', 100)->nullable();
            $table->string('module_level')->default('Admin');
            $table->string('module_permission_template', 100)->nullable();
            $table->string('module_note', 8000)->nullable();
            $table->text('form_note', 65535)->nullable();
            $table->dateTime('module_created')->nullable();
            $table->string('module_db')->nullable();
            $table->string('ledger_module')->nullable();
            $table->string('module_db_key', 100)->nullable();
            $table->string('module_type')->nullable();
            $table->binary('module_config')->nullable();
            $table->text('module_lang', 65535)->nullable();
            $table->string('formjs', 3000);
            $table->string('gridjs', 3000);
            $table->string('button1_title');
            $table->string('button1_function');
            $table->string('button2_title');
            $table->string('button2_function');
            $table->integer('hide_inactive')->default(0);
            $table->boolean('disabledelete')->default(0);
            $table->string('schedule1_command')->nullable();
            $table->string('schedule1_frequency')->nullable();
            $table->string('schedule2_command')->nullable();
            $table->string('schedule2_frequency')->nullable();
            $table->binary('beforesave')->nullable();
            $table->binary('aftersave')->nullable();
            $table->binary('beforedelete')->nullable();
            $table->binary('afterdelete')->nullable();
            $table->binary('indextop')->nullable();
            $table->binary('customfunction')->nullable();
            $table->boolean('build_controller')->nullable()->default(1);
            $table->boolean('build_model')->nullable()->default(1);
            $table->boolean('build_grid')->nullable()->default(1);
            $table->boolean('build_form')->nullable()->default(1);
            $table->boolean('build_view')->nullable()->default(1);
            $table->boolean('build_permissions')->nullable()->default(1);
            $table->string('customer_table')->nullable();
            $table->string('customer_id_field')->nullable();
            $table->string('transaction_type', 100)->nullable()->default('0');
            $table->boolean('add_supplier_import_fields')->nullable()->default(0);
            $table->boolean('disableedit')->default(0);
            $table->boolean('disablecreate')->default(0);
            $table->boolean('lock_rebuild')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_cruds_old');
    }
}
