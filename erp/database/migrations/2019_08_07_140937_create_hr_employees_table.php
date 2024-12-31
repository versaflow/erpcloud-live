<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateHrEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hr_employees', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name')->nullable();
            $table->string('id_number')->nullable();
            $table->integer('income_tax_no')->nullable();
            $table->string('mobile')->nullable();
            $table->string('bank_details')->nullable();
            $table->string('address')->nullable();
            $table->string('spouse_name')->nullable();
            $table->string('spouse_mobile')->nullable();
            $table->string('position')->nullable();
            $table->float('gross_salary', 10, 0)->nullable();
            $table->string('commission')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('notes', 1000)->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->float('daily_rate', 10)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hr_employees');
    }
}
