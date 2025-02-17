<?php

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
Route::get('api_sms_balances', 'IntegrationsController@getsms_balances');
Route::get('api_sms_report', 'IntegrationsController@getsms_report');
Route::get('api_sms_send', 'IntegrationsController@getsms_send');

// auth
Route::any('/api/postsignup', 'PbxAppController@postSignup');
Route::get('/api/getlogin', 'PbxAppController@getLogin');
Route::any('/api/postsigninextension', 'PbxAppController@postSignInExtension');
Route::any('/api/postsmstoken', 'PbxAppController@postSMSToken');
Route::any('/api/postfeedback', 'PbxAppController@postFeedback');
//account
Route::any('/api/postcancel', 'PbxAppController@postCancel');
Route::any('/api/postundocancel', 'PbxAppController@postUndoCancel');

// data
Route::get('/api/getextension', 'PbxAppController@getExtension');
Route::get('/api/getextensionlist', 'PbxAppController@getExtensionList');
Route::get('/api/getstatement', 'PbxAppController@getStatement');
Route::get('/api/getsubscriptions', 'PbxAppController@getSubscriptions');
Route::any('/api/postrecording', 'PbxAppController@postRecording');
Route::any('/api/postextension', 'PbxAppController@postExtension');
Route::get('/api/getbalance', 'PbxAppController@getBalance');
Route::get('/api/getaccount', 'PbxAppController@getAccount');

Route::any('/api/getaccountpost', 'PbxAppController@getAccountPost');
Route::get('/api/getpartner', 'PbxAppController@getPartner');
Route::get('/api/getvoicemailcount', 'PbxAppController@getVoicemailCount');
Route::any('/api/postairtime', 'PbxAppController@postAirtime');
Route::any('/api/postordernumber', 'PbxAppController@postOrderNumber');
Route::get('/api/getcallerids', 'PbxAppController@getCallerIds');
Route::any('/api/postcallerid', 'PbxAppController@postCallerId');
Route::any('/api/postcallforward', 'PbxAppController@postCallForward');
Route::get('/api/getrouting', 'PbxAppController@getRouting');
Route::get('/api/getroutingoptions', 'PbxAppController@getRoutingOptions');
Route::any('/api/postrouting', 'PbxAppController@postRouting');
Route::any('/api/postreferral', 'PbxAppController@postReferral');


Route::get('/api/getdomain', 'PbxAppController@getDomain');
Route::any('/api/postdomainsettings', 'PbxAppController@postDomainSettings');

// webviews
Route::any('/api/dashboard', 'PbxAppController@getDashboard');
Route::get('/api/getorder', 'PbxAppController@getOrder');
Route::any('/api/getpaynowlink', 'PbxAppController@getPayNowLink');

Route::get('/api/getrates', 'PbxAppController@getRates');
Route::any('/api/getratessearch', 'PbxAppController@getRatesSearch');
Route::get('/api/gethelpdesk', 'PbxAppController@getHelpdesk');
Route::get('/api/gethelpdeskdata', 'PbxAppController@getHelpdeskData');
Route::get('/api/getcontact', 'PbxAppController@getContact');
Route::get('/api/getcdr', 'PbxAppController@getCdr');

// webviews submit
Route::any('/api/postorder', 'PbxAppController@postOrder');

Route::any('/api/postcontactform', 'PbxAppController@postContactForm');
// UNLIMITED MOBILE END

// ERP START
Route::any('/erp_api/postsignup', 'Api\ErpApiController@postSignup');
Route::get('/erp_api/getlogin', 'Api\ErpApiController@getLogin');
Route::any('/erp_api/postsmstoken', 'Api\ErpApiController@postSMSToken');
Route::any('/erp_api/postvehicledbinvoice', 'Api\ErpApiController@postVehicledbInvoice');
Route::any('/erp_api/postvehicledbcreditsused', 'Api\ErpApiController@postVehicledbCreditsUsed');
Route::any('/erp_api/getvehicledbvehicles', 'Api\ErpApiController@getVehicledbVehicles');
Route::any('/erp_api/getvehicledbdropdowns', 'Api\ErpApiController@getVehicledbDropdowns');
Route::any('/erp_api/postcontactform', 'Api\ErpApiController@postContactForm');
Route::any('/erp_api/postCreditOnEvaluationFail', 'Api\ErpApiController@postCreditOnEvaluationFail');


Route::any('/erp_api/getaccount', 'Api\ErpApiController@getAccount');
Route::any('/erp_api/getapproutes', 'Api\ErpApiController@getAppRoutes');
Route::get('/erp_api/getwebviewsession', 'Api\ErpApiController@getWebviewSession');
// ERP END

// BULKHUB START
Route::any('/bulkhub_api/postsignup', 'Api\BulkhubApiController@postSignup');
Route::get('/bulkhub_api/getlogin', 'Api\BulkhubApiController@getLogin');
Route::any('/bulkhub_api/postsmstoken', 'Api\BulkhubApiController@postSMSToken');
Route::any('/bulkhub_api/getproducts', 'Api\BulkhubApiController@getProducts');
Route::any('/bulkhub_api/getaccount', 'Api\BulkhubApiController@getAccount');
Route::any('/bulkhub_api/getapproutes', 'Api\BulkhubApiController@getAppRoutes');
Route::get('/bulkhub_api/getwebviewsession', 'Api\BulkhubApiController@getWebviewSession');
// BULKHUB END


// RESTAPI START
Route::prefix('rest_api')->group(function () {
    Route::get('endpoints', 'Api\RestApiController@endpoints'); // endpoints
    Route::post('register', 'Api\RestApiController@register'); // register
    Route::any('auth', 'Api\RestApiController@postLogin'); // auth token
    Route::any('getaccount', 'Api\RestApiController@getAccount'); // auth token
    Route::get('{module}', 'Api\RestApiController@index'); // get all
    Route::get('{module}/{id?}', 'Api\RestApiController@show'); // get one
    Route::post('{module}', 'Api\RestApiController@store'); // insert
    Route::put('{module}/{id?}', 'Api\RestApiController@update'); // update
    Route::delete('{module}/{id?}', 'Api\RestApiController@destroy'); // delete
});
// RESTAPI END
