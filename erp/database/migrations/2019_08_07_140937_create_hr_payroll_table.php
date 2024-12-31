<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateHrPayrollTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hr_payroll', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('employee_id')->nullable();
            $table->date('docdate')->nullable();
            $table->float('gross_salary', 10, 0)->nullable();
            $table->string('deduction_1')->nullable();
            $table->float('deduction_1_amount', 10, 0)->nullable();
            $table->string('deduction_2')->nullable();
            $table->float('deduction_2_amount', 10, 0)->nullable();
            $table->float('paye', 10, 0)->nullable();
            $table->float('uif_employee', 10, 0)->nullable();
            $table->float('uif_company', 10, 0)->nullable();
            $table->float('net_salary', 10, 0)->nullable();
            $table->string('status', 50)->nullable()->default('Draft');
            $table->string('addition_1')->nullable();
            $table->float('addition_1_amount', 10, 0)->nullable();
            $table->string('addition_2')->nullable();
            $table->float('addition_2_amount', 10, 0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hr_payroll');
    }
}
