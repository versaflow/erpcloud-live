<?php

function menu_cache_clear()
{
    $module_ids = \DB::connection('default')->table('erp_cruds')->pluck('id')->toArray();
    $role_ids = \DB::connection('default')->table('erp_user_roles')->pluck('id')->toArray();
    $locations = \DB::connection('default')->table('erp_menu')->pluck('location')->toArray();

    foreach ($module_ids as $module_id) {
        foreach ($role_ids as $role_id) {
            foreach ($locations as $location) {
                Cache::forget('events_menus'.$module_id.$location.$role_id);
                Cache::forget('linkmenus'.$module_id.$location.$role_id);
            }
        }
    }
    foreach ($role_ids as $role_id) {
        foreach ($locations as $location) {
            Cache::forget('events_menus'.$location.$role_id);
            Cache::forget('linkmenus'.$location.$role_id);
        }
    }
}

function cache_clear()
{

    \Artisan::call('cache:clear');
}

function schedule_cache_clear()
{

    \Artisan::call('cache:clear');
}
