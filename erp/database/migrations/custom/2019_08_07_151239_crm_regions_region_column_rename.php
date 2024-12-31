<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrmRegionsRegionColumnRename extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('crm_regions')) {
            if (Schema::hasColumn('crm_regions', 'region')
            && ! Schema::hasColumn('crm_regions', 'province')) {
                Schema::table('crm_regions', function (Blueprint $table) {
                    $table->renameColumn('region', 'province');
                });
            }
        }
    }
}
