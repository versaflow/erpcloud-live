<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class ErpConfigServiceProvider extends ServiceProvider
{
    public function register()
    {
        
        $configTables = [
            'modules' => DB::table('erp_cruds')->get(),
            'menus' => DB::table('erp_menu')->get(),
            'module_fields' => DB::table('erp_module_fields')->get(),
            'users' => DB::table('erp_users')->get(),
            'roles' => DB::table('erp_user_roles')->get(),
            'forms' => DB::table('erp_forms')->get(),
            'menu_access' => DB::table('erp_menu_role_access')->get(),
            'layouts' => DB::table('erp_grid_views')->get(),
            'grid_styles' => DB::table('erp_grid_styles')->get(),
            // Add more config tables as needed
        ];
        
        $this->app->instance('erp_config', $configTables);
    }
}