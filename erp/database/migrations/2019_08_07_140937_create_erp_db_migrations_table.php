<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDBEventMigrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_instance_migrations', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('table_name')->nullable();
            $table->string('field_name')->nullable();
            $table->string('action')->nullable();
            $table->string('action_value')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->boolean('processed')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('erp_instance_migrations');
    }
}
