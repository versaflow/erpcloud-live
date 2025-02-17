<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateErpEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_form_events', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('module_id');
            $table->integer('sort')->default(0);
            $table->string('type')->nullable();
            $table->string('name');
            $table->string('frequency')->nullable();
            $table->integer('frequency_unit')->nullable();
            $table->dateTime('last_run')->nullable();
            $table->text('email_subject', 65535)->nullable();
            $table->binary('email_message')->nullable();
            $table->string('email_from_email')->nullable();
            $table->string('email_to_email')->nullable();
            $table->boolean('email_cc_partner')->nullable();
            $table->boolean('email_bcc_partner')->nullable();
            $table->integer('email_attachment')->nullable();
            $table->boolean('active')->default(1);
            $table->boolean('system_trigger')->default(0);
            $table->text('system_tables', 65535)->nullable();
            $table->text('function_definition', 65535)->nullable();
            $table->text('function_name', 65535)->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_form_events');
    }
}
