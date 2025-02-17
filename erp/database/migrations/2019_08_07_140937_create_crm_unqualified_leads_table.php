<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCrmUnqualifiedLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_lead_records', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id')->nullable();
            $table->string('market')->nullable();
            $table->string('search_term')->nullable();
            $table->string('source')->nullable();
            $table->string('company')->nullable();
            $table->string('contact')->nullable();
            $table->string('landline', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('address', 1000)->nullable();
            $table->string('tower_address', 500)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('notes')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->integer('last_notification_id')->default(0);
            $table->boolean('invalid_email')->default(0);
            $table->date('ldate')->nullable();
            $table->time('ltime')->nullable();
            $table->integer('ldur')->nullable();
            $table->integer('lext')->nullable();
            $table->date('lemail')->nullable();
            $table->integer('website_id')->nullable();
            $table->integer('partner_id')->default(1);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('crm_lead_records');
    }
}
