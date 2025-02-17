<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCrmDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crm_documents', function (Blueprint $table) {
            $table->foreign('account_id', 'fkey_dvokmrrfqw')->references('id')->on('crm_accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('crm_documents', function (Blueprint $table) {
            $table->dropForeign('fkey_dvokmrrfqw');
        });
    }
}
