<?php

use App\Http\Controllers\CoreController;
use App\Http\Controllers\CustomController;
use Illuminate\Support\Facades\Route;

/* All routes on Auth Controller */

/* Auth & Profile */
Route::get('user/profile', [CoreController::class, 'getProfile']);

Route::get('user/login', [CoreController::class, 'getLogin']);
Route::get('user/admin_login', [CoreController::class, 'adminLogin']);
Route::get('user/register', [CoreController::class, 'getRegister']);

Route::get('user/logout', [CoreController::class, 'getLogout']);
Route::any('logout', [CoreController::class, 'getLogout'])->name('logout');
Route::get('user/loginas/{any0?}/{any1?}', [CoreController::class, 'getLoginAs']);
Route::any('autologin', [CoreController::class, 'autoLogin']);

Route::get('user/loginas_voicebasic', function () {
    if (! is_superadmin()) {
        return false;
    }
    $account_id = \DB::connection('pbx')->table('v_domains')->where('pbx_type', 'Phone Line')
        ->pluck('account_id')->first();

    return redirect()->to('user/loginas/'.$account_id);
});

Route::get('user/reset', [CoreController::class, 'getReset']);
Route::get('user/reset/{any?}', [CoreController::class, 'getReset']);
Route::post('user/reset', [CoreController::class, 'postReset']);
Route::get('user/activation', [CoreController::class, 'getActivation']);
Route::get('user/converttoPartner/', [CoreController::class, 'getconverttoPartner']);

Route::get('user/profile', [CoreController::class, 'getProfile']);

Route::get('phpinfo', [CoreController::class, 'getPHPInfo']);
// Social Login
Route::get('user/socialize/{any?}', [CoreController::class, 'getSocialize']);
Route::get('user/socialize/{any?}', [CoreController::class, 'getAutosocial']);

Route::post('user/signin', [CoreController::class, 'postSignin']);
Route::any('user/create', [CoreController::class, 'postCreate']);
Route::post('user/saveprofile', [CoreController::class, 'postSaveprofile']);
Route::post('user/savepassword', [CoreController::class, 'postSavepassword']);
Route::post('user/doreset/{any?}', [CoreController::class, 'postDoreset']);
Route::any('user/upload', [CoreController::class, 'summernote_upload_image']);

Route::get('user/updatepricelists', [CoreController::class, 'getUpdatePricelists']);
Route::get('user/updateUsage', [CustomController::class, 'updateUsage']);

Route::get('helper/{function}', [CoreController::class, 'runHelper']);
Route::get('helper/{function}/{var1}', [CoreController::class, 'runHelper']);
Route::get('helper/{function}/{var1}/{var2}', [CoreController::class, 'runHelper']);
Route::any('/user/kendomail/{email}', [CoreController::class, 'postkendomail']);
Route::any('/user/paygateresult', [CoreController::class, 'paygateresult']);

// token login
Route::any('api/user/getusertoken', [CoreController::class, 'getUserToken']);
Route::any('api/user/signintoken', [CoreController::class, 'postSigninToken']);
Route::any('api/user/validatetoken', [CoreController::class, 'postValidateToken']);
