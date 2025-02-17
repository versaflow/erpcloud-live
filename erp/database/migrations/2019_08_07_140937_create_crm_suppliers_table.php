<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_suppliers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('company');
            $table->string('contact');
            $table->string('terms', 100)->nullable();
            $table->string('email');
            $table->string('landline');
            $table->string('mobile');
            $table->string('address');
            $table->string('vat_number', 50);
            $table->string('notes', 250);
            $table->string('status', 50)->default('Enabled');
            $table->timestamps();
            $table->dateTime('deleted_at');
            $table->integer('user_id')->default(1);
            $table->float('balance', 10, 0)->default(0);
            $table->boolean('taxable')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_suppliers');
    }
}
