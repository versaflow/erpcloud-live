<?php

Route::get('clear_cache_migrations', function () {
    $erp = new ErpMigrations();
    $erp->clearCache();
});

Route::get('coverage_maps', function () {
    return view('__app.components.pages.coverage_maps', ['menu_name' => 'Coverage Maps']);
});
