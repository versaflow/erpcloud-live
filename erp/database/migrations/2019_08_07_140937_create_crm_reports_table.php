<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_reports', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('group_ids')->nullable();
            $table->integer('sort')->nullable();
            $table->string('name')->nullable();
            $table->string('report_title', 50);
            $table->text('report_subtitle', 65535);
            $table->text('report_settings', 65535)->nullable();
            $table->string('report_layout', 50)->default('Portrait');
            $table->string('show_date_filter', 50)->nullable();
            $table->boolean('show_totals_only')->default(0);
            $table->boolean('totals_inline')->default(0);
            $table->string('status', 1000)->nullable();
            $table->dateTime('date_generated')->nullable();
            $table->boolean('to_update')->default(0);
            $table->string('date_filter_column')->nullable();
            $table->text('virtual_table', 65535)->nullable();
            $table->boolean('line_column')->default(0);
            $table->boolean('disable_view_groups')->default(0);
            $table->text('data_url', 65535)->nullable();
            $table->binary('pivot_settings')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_reports');
    }
}
