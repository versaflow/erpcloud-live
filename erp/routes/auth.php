<?php

/* All routes on Auth Controller */

/* Auth & Profile */
Route::get('user/profile', 'CoreController@getProfile');

Route::get('user/login', 'CoreController@getLogin');
Route::get('user/admin_login', 'CoreController@adminLogin');
Route::get('user/register', 'CoreController@getRegister');

Route::get('user/logout', 'CoreController@getLogout');
Route::any('logout', 'CoreController@getLogout')->name('logout');
Route::get('user/loginas/{any0?}/{any1?}', 'CoreController@getLoginAs');
Route::any('autologin', 'CoreController@autoLogin');

Route::get('user/loginas_voicebasic', function () {
    if (! is_superadmin()) {
        return false;
    }
    $account_id = \DB::connection('pbx')->table('v_domains')->where('pbx_type', 'Phone Line')
        ->pluck('account_id')->first();

    return redirect()->to('user/loginas/'.$account_id);
});

Route::get('user/reset', 'CoreController@getReset');
Route::get('user/reset/{any?}', 'CoreController@getReset');
Route::post('user/reset', 'CoreController@postReset');
Route::get('user/activation', 'CoreController@getActivation');
Route::get('user/converttoPartner/', 'CoreController@getconverttoPartner');

Route::get('user/profile', 'CoreController@getProfile');

Route::get('phpinfo', 'CoreController@getPHPInfo');
// Social Login
Route::get('user/socialize/{any?}', 'CoreController@getSocialize');
Route::get('user/socialize/{any?}', 'CoreController@getAutosocial');

Route::post('user/signin', 'CoreController@postSignin');
Route::any('user/create', 'CoreController@postCreate');
Route::post('user/saveprofile', 'CoreController@postSaveprofile');
Route::post('user/savepassword', 'CoreController@postSavepassword');
Route::post('user/doreset/{any?}', 'CoreController@postDoreset');
Route::any('user/upload', 'CoreController@summernote_upload_image');

Route::get('user/updatepricelists', 'CoreController@getUpdatePricelists');
Route::get('user/updateUsage', 'CustomController@updateUsage');

Route::get('helper/{function}', 'CoreController@runHelper');
Route::get('helper/{function}/{var1}', 'CoreController@runHelper');
Route::get('helper/{function}/{var1}/{var2}', 'CoreController@runHelper');
Route::any('/user/kendomail/{email}', 'CoreController@postkendomail');
Route::any('/user/paygateresult', 'CoreController@paygateresult');

// token login
Route::any('api/user/getusertoken', 'CoreController@getUserToken');
Route::any('api/user/signintoken', 'CoreController@postSigninToken');
Route::any('api/user/validatetoken', 'CoreController@postValidateToken');
