<?php

use App\Http\Controllers\Api;
use App\Http\Controllers\IntegrationsController;
use App\Http\Controllers\PbxAppController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/// UNLIMITED MOBILE START
Route::get('api_sms_balances', [IntegrationsController::class, 'getsms_balances']);
Route::get('api_sms_report', [IntegrationsController::class, 'getsms_report']);
Route::get('api_sms_send', [IntegrationsController::class, 'getsms_send']);

// auth
Route::any('/api/postsignup', [PbxAppController::class, 'postSignup']);
Route::get('/api/getlogin', [PbxAppController::class, 'getLogin']);
Route::any('/api/postsigninextension', [PbxAppController::class, 'postSignInExtension']);
Route::any('/api/postsmstoken', [PbxAppController::class, 'postSMSToken']);
Route::any('/api/postfeedback', [PbxAppController::class, 'postFeedback']);
//account
Route::any('/api/postcancel', [PbxAppController::class, 'postCancel']);
Route::any('/api/postundocancel', [PbxAppController::class, 'postUndoCancel']);

// data
Route::get('/api/getextension', [PbxAppController::class, 'getExtension']);
Route::get('/api/getextensionlist', [PbxAppController::class, 'getExtensionList']);
Route::get('/api/getstatement', [PbxAppController::class, 'getStatement']);
Route::get('/api/getsubscriptions', [PbxAppController::class, 'getSubscriptions']);
Route::any('/api/postrecording', [PbxAppController::class, 'postRecording']);
Route::any('/api/postextension', [PbxAppController::class, 'postExtension']);
Route::get('/api/getbalance', [PbxAppController::class, 'getBalance']);
Route::get('/api/getaccount', [PbxAppController::class, 'getAccount']);

Route::any('/api/getaccountpost', [PbxAppController::class, 'getAccountPost']);
Route::get('/api/getpartner', [PbxAppController::class, 'getPartner']);
Route::get('/api/getvoicemailcount', [PbxAppController::class, 'getVoicemailCount']);
Route::any('/api/postairtime', [PbxAppController::class, 'postAirtime']);
Route::any('/api/postordernumber', [PbxAppController::class, 'postOrderNumber']);
Route::get('/api/getcallerids', [PbxAppController::class, 'getCallerIds']);
Route::any('/api/postcallerid', [PbxAppController::class, 'postCallerId']);
Route::any('/api/postcallforward', [PbxAppController::class, 'postCallForward']);
Route::get('/api/getrouting', [PbxAppController::class, 'getRouting']);
Route::get('/api/getroutingoptions', [PbxAppController::class, 'getRoutingOptions']);
Route::any('/api/postrouting', [PbxAppController::class, 'postRouting']);
Route::any('/api/postreferral', [PbxAppController::class, 'postReferral']);

Route::get('/api/getdomain', [PbxAppController::class, 'getDomain']);
Route::any('/api/postdomainsettings', [PbxAppController::class, 'postDomainSettings']);

// webviews
Route::any('/api/dashboard', [PbxAppController::class, 'getDashboard']);
Route::get('/api/getorder', [PbxAppController::class, 'getOrder']);
Route::any('/api/getpaynowlink', [PbxAppController::class, 'getPayNowLink']);

Route::get('/api/getrates', [PbxAppController::class, 'getRates']);
Route::any('/api/getratessearch', [PbxAppController::class, 'getRatesSearch']);
Route::get('/api/gethelpdesk', [PbxAppController::class, 'getHelpdesk']);
Route::get('/api/gethelpdeskdata', [PbxAppController::class, 'getHelpdeskData']);
Route::get('/api/getcontact', [PbxAppController::class, 'getContact']);
Route::get('/api/getcdr', [PbxAppController::class, 'getCdr']);

// webviews submit
Route::any('/api/postorder', [PbxAppController::class, 'postOrder']);

Route::any('/api/postcontactform', [PbxAppController::class, 'postContactForm']);
// UNLIMITED MOBILE END

// ERP START
Route::any('/erp_api/postsignup', [Api\ErpApiController::class, 'postSignup']);
Route::get('/erp_api/getlogin', [Api\ErpApiController::class, 'getLogin']);
Route::any('/erp_api/postsmstoken', [Api\ErpApiController::class, 'postSMSToken']);
Route::any('/erp_api/postvehicledbinvoice', [Api\ErpApiController::class, 'postVehicledbInvoice']);
Route::any('/erp_api/postvehicledbcreditsused', [Api\ErpApiController::class, 'postVehicledbCreditsUsed']);
Route::any('/erp_api/getvehicledbvehicles', [Api\ErpApiController::class, 'getVehicledbVehicles']);
Route::any('/erp_api/getvehicledbdropdowns', [Api\ErpApiController::class, 'getVehicledbDropdowns']);
Route::any('/erp_api/postcontactform', [Api\ErpApiController::class, 'postContactForm']);
Route::any('/erp_api/postCreditOnEvaluationFail', [Api\ErpApiController::class, 'postCreditOnEvaluationFail']);

Route::any('/erp_api/getaccount', [Api\ErpApiController::class, 'getAccount']);
Route::any('/erp_api/getapproutes', [Api\ErpApiController::class, 'getAppRoutes']);
Route::get('/erp_api/getwebviewsession', [Api\ErpApiController::class, 'getWebviewSession']);
// ERP END

// BULKHUB START
Route::any('/bulkhub_api/postsignup', [Api\BulkhubApiController::class, 'postSignup']);
Route::get('/bulkhub_api/getlogin', [Api\BulkhubApiController::class, 'getLogin']);
Route::any('/bulkhub_api/postsmstoken', [Api\BulkhubApiController::class, 'postSMSToken']);
Route::any('/bulkhub_api/getproducts', [Api\BulkhubApiController::class, 'getProducts']);
Route::any('/bulkhub_api/getaccount', [Api\BulkhubApiController::class, 'getAccount']);
Route::any('/bulkhub_api/getapproutes', [Api\BulkhubApiController::class, 'getAppRoutes']);
Route::get('/bulkhub_api/getwebviewsession', [Api\BulkhubApiController::class, 'getWebviewSession']);
// BULKHUB END

// RESTAPI START
Route::prefix('rest_api')->group(function () {
    Route::get('endpoints', [Api\RestApiController::class, 'endpoints']); // endpoints
    Route::post('register', [Api\RestApiController::class, 'register']); // register
    Route::any('auth', [Api\RestApiController::class, 'postLogin']); // auth token
    Route::any('getaccount', [Api\RestApiController::class, 'getAccount']); // auth token
    Route::get('{module}', [Api\RestApiController::class, 'index']); // get all
    Route::get('{module}/{id?}', [Api\RestApiController::class, 'show']); // get one
    Route::post('{module}', [Api\RestApiController::class, 'store']); // insert
    Route::put('{module}/{id?}', [Api\RestApiController::class, 'update']); // update
    Route::delete('{module}/{id?}', [Api\RestApiController::class, 'destroy']); // delete
});
// RESTAPI END
