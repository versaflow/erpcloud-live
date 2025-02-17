<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

// use Illuminate\Support\Facades\Mail;

class PbxAppController extends BaseController
{
    protected $request; // request as an attribute of the controllers
    protected $token;
    protected $account;
    protected $debug_numbers;

    public function __construct(Request $request)
    {
        $this->debug_numbers = ['0813334444'];
        $this->request = $request; // Request becomes available for all the controller functions that call $this->request

        // LOG INCOMMING Reques

        $this->middleware(function ($request, $next) {
            if ('App\\Http\\Controllers\\Api\\ErpApiController@documentation' != \Route::getCurrentRoute()->getActionName()) {
                $appkeys = ['$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O'];
                if (empty($request->key) || !in_array($request->key, $appkeys)) {
                    return api_error('Invalid API Key');
                }

                $currentRoute = \Route::getCurrentRoute()->getActionName();

                if ('App\\Http\\Controllers\\PbxAppController@postSignInExtension' != \Route::getCurrentRoute()->getActionName()
                && 'App\\Http\\Controllers\\Api\\PbxAppController@getLogin' != \Route::getCurrentRoute()->getActionName()
                && 'App\\Http\\Controllers\\PbxAppController@postSignup' != \Route::getCurrentRoute()->getActionName()
                && 'App\\Http\\Controllers\\PbxAppController@postFeedback' != \Route::getCurrentRoute()->getActionName()
                && 'App\\Http\\Controllers\\PbxAppController@postSMSToken' != \Route::getCurrentRoute()->getActionName()) {
                    $validation = $this->validateToken();
                    if (true !== $validation) {
                        return api_error($validation);
                    }

                    $this->token = $request->api_token;
                    $this->key = $request->key;
                }
            }

            return $next($request);
        });
    }

    /**
     * @apiDefine DomainDNSError
     *
     * @apiError (HTTP 400) DomainDNS
     *
     * @apiErrorExample Global-Validation-Error:
     *     HTTP/1.1 400 Bad Request
     *     {
     *       "status": "FAILURE",
     *       "message": "Domain DNS not propagated"
     *     }
     */

    /**
     * @apiDefine AccountNotFoundError
     *
     * @apiError (HTTP 400) AccountNotFound
     *
     * @apiErrorExample Global-Validation-Error:
     *     HTTP/1.1 400 Bad Request
     *     {
     *       "status": "FAILURE",
     *       "message": "Account does not exists, create a new account"
     *     }
     */

    /**
     * @apiDefine AccountTypeError
     *
     * @apiError (HTTP 400) AccountType
     *
     * @apiErrorExample Global-Validation-Error:
     *     HTTP/1.1 400 Bad Request
     *     {
     *       "status": "FAILURE",
     *       "message": "Invalid account type"
     *     }
     */

    /**
     * @apiDefine PBXDomainError
     *
     * @apiError (HTTP 400) PBXDomain
     *
     * @apiErrorExample Global-Validation-Error:
     *     HTTP/1.1 400 Bad Request
     *     {
     *       "status": "FAILURE",
     *       "message": "Invalid pbx domain"
     *     }
     */

    /**
     * @apiDefine AccountStatusDisabledError
     *
     * @apiError (HTTP 400) AccountStatusDisabled
     *
     * @apiErrorExample Global-Validation-Error:
     *     HTTP/1.1 400 Bad Request
     *     {
     *       "status": "FAILURE",
     *       "message": "Account Disabled"
     *     }
     */

    /**
     * @apiDefine AccountStatusDeletedError
     *
     * @apiError (HTTP 400) AccountStatusDeleted
     *
     * @apiErrorExample Global-Validation-Error:
     *     HTTP/1.1 400 Bad Request
     *     {
     *       "status": "FAILURE",
     *       "message": "Account Deleted"
     *     }
     */

    //   public function postFeedback() {

    //     try {

    //         $request = $this->request;

    //         $message = '';
    //         if ($request->name)
    //             $message .= 'Name:'.$request->name . "<br>";

    //         if ($request->phone)
    //             $message .= 'Phone:'.$request->phone . "<br>";

    //         if ($request->email)
    //             $message .= 'Email:'.$request->email . "<br>";

    //         if ($request->feedback)
    //             $message .= 'Feedback:'.$request->feedback . "<br>";

    //         $result = directmail('ahmed@telecloud.co.za', 'Telecloud App Feedback', $message);

    //         return $result;
    //     } catch (\Throwable $ex) {  exception_log($ex);
    //         exception_email($ex, 'API error token '.date('Y-m-d H:i'));
    //         return api_abort('Error exception');
    //     }
    // }

    public function postFeedback()
    {
        try {
            $request = $this->request;

            $name = $request->input('name');
            $phone = $request->input('phone');
            $email = $request->input('email');
            $feedback = $request->input('feedback');

            $message = '';
            if ($name) {
                $message .= 'Name: '.$name.'<br>';
            }
            if ($phone) {
                $message .= 'Phone: '.$phone.'<br>';
            }
            if ($email) {
                $message .= 'Email: '.$email.'<br>';
            }
            if ($feedback) {
                $message .= 'Feedback: '.$feedback.'<br>';
            }

            $result = directmail('helpdesk@telecloud.co.za', 'Telecloud Mobile App Feedback', $message);
            // a($result);

            //  Mail::send([], [], function ($mail) use ($message) {
            //     $mail->to('ahmed@telecloud.co.za')
            //         ->subject('Telecloud App Feedback')
            //         ->setBody($message, 'text/html'); // Set content type to HTML
            // });

            return $result;
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error token '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    // private function postFeedback() {
    //     try {
    //         $request = $this->request;

    //         $message = '';
    //         if ($request->name)
    //             $message .= 'Name:'.$request->name . "<br>";

    //         if ($request->phone)
    //             $message .= 'Phone:'.$request->phone . "<br>";

    //         if ($request->email)
    //             $message .= 'Email:'.$request->email . "<br>";

    //         if ($request->feedback)
    //             $message .= 'Feedback:'.$request->feedback . "<br>";

    //         $result = directmail('ahmed@telecloud.co.za', 'Telecloud App Feedback', $message);

    //         return $result;
    //     } catch (\Throwable $ex) {  exception_log($ex);
    //         exception_email($ex, 'API error token '.date('Y-m-d H:i'));
    //         return api_abort('Error exception');
    //     }
    // }

    public function getLogin()
    {
        // 105 - $2y$10$CRQmxS.4BoSi2qc9.vCUreGcKz7Hl26.7pkH5N.5PAhQJYOCOJ0.a
        //http://cloudtools.versaflow.io/api/getlogin?key=$2y10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O&mobile_number=0812223333
        /*
        * @api {get} api/login getLogin
        * @apiVersion 1.0.0
        * @apiName getLogin
        * @apiGroup Auth
        *
        * @apiParam {String} key appkey
        * @apiParam {Number} mobile_number valid za mobile number
        * @apiSampleRequest http://cloudtools.versaflow.io/api/getlogin?key=$2y10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O&mobile_number=0812223333
        * @apiSuccess (HTTP 200) {String} message
        * @apiSuccess (HTTP 200) {String} status
        *
        *
        * @apiSuccessExample Success-Response Verification:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Account Exists. SMS Sent."
        *     }
        *
        * @apiError (HTTP 400) InvalidPhoneNumber invalid ZA phone number
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Invalid phone number"
        *     }
        *
        * @apiError (HTTP 400) InvalidPhoneNumber fixed line phone number
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "You can not use a fixed line number"
        *     }
        *
        * @apiError (HTTP 400) InvalidPhoneNumber lookup failed
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Phone number not found"
        *     }
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Verification SMS could not be sent."
        *     }
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        */

        try {
            $phone = $this->request->mobile_number;
            $hashkey = $this->request->hashkey;
            $number = phone($phone, ['ZA', 'US', 'Auto']);

            if (strlen($phone) != 10) {
                //  return api_error('Invalid phone number.');
            }
            if ($number->isOfType('fixed_line')) {
                return api_error('You can not use a fixed line number.');
            }

            $number = $number->formatForMobileDialingInCountry('ZA');
        } catch (\Throwable $ex) {
            exception_log($ex);
            $post_arr = (array) $this->request->all();
            exception_email($ex, 'API error token phone number '.date('Y-m-d H:i'), $post_arr);

            return api_error('Invalid phone number.');
        }

        try {
            $extension = \DB::connection('pbx')->table('v_extensions')->where('mobile_app_number', $number)->get()->first();
            $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $extension->domain_uuid)->pluck('account_id')->first();
            if (!$extension->mobile_app_number) {
                return api_error('Phone number not found. Already have an account? Call us to link your number to your account.');
            }
            $this->setManagerExtension($extension);
            $account = dbgetaccount($account_id);
            $validation = $this->validateAccount($account);
            if ($validation !== true) {
                return api_error($validation);
            }
            aa('this is the account details');

            aa($account);
            aa('this is the account details');

            // if (!$mobile_number->verified) {
            $result = $this->sendPhoneNumberVerification($extension, $hashkey);

            if ($result) {
                return api_success('Account exists.');
            } else {
                return api_abort('Verification SMS could not be sent.');
            }
            // }

            return api_success('Account exists.');
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error token '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function postSignInExtension()
    {
        /*
        * @api {post} api/postsigninextension postSignInExtension
        * @apiVersion 1.0.0
        * @apiName postSignup
        * @apiGroup Auth
        *
        * @apiParam {String} key appkey
        * @apiParam {String} extension (required)
        * @apiParam {String} password (required)
        * @apiParam {String} domain_name (required)
        * @apiSampleRequest http://cloudtools.versaflow.io/api/postsigninextension
        * @apiSuccess (HTTP 200) {String} message
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Token Sent.",
        *       "api_token": "Token"
        *     }
        *
        * @apiError (HTTP 400) FieldRequired
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Extension required."
        *     }
        * @apiError (HTTP 400) FieldRequired
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Password required."
        *     }
        *
        * @apiError (HTTP 400) FieldRequired
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Domain name required."
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        */

        try {
            if (empty($this->request->extension)) {
                return api_error('Extension required.');
            }
            if (empty($this->request->password)) {
                return api_error('Password required.');
            }
            if (empty($this->request->domain_name)) {
                return api_error('Domain name required.');
            }
            $extension = $this->request->extension;
            $password = $this->request->password;
            $domain_name = $this->request->domain_name;

            $domain = \DB::connection('pbx')->table('v_domains')->where('domain_name', $domain_name)->get()->first();
            if (empty($domain)) {
                return api_error('Domain not found.');
            }

            $ext = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain->domain_uuid)->where('extension', $extension)->get()->first();
            if (empty($ext)) {
                return api_error('Extension not found.');
            }

            if ($ext->password != $password) {
                return api_error('Incorrect password.');
            }

            $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $ext->domain_uuid)->pluck('account_id')->first();

            $token = \Hash::make($domain->domain_name.$ext->extension);
            \DB::connection('pbx')->table('v_extensions')->where('id', $ext->id)->update(['verification_code' => null, 'api_token' => $token, 'verified' => 1]);

            return api_success('Token sent', ['api_token' => $token, 'account_id' => $account_id]);
        } catch (\Throwable $ex) {
            exception_log($ex);
            $post_arr = (array) $this->request->all();
            exception_email($ex, 'API error postSignInExtension '.date('Y-m-d H:i'), $post_arr);

            return api_error('Invalid credentials.');
        }
    }

    // public function postSignup()
    // {
    //     /**
    //     * @api {post} api/postsignup postSignup
    //     * @apiVersion 1.0.0
    //     * @apiName postSignup
    //     * @apiGroup Auth
    //     *
    //     * @apiParam {String} key appkey
    //     * @apiParam {Number} mobile_number valid za mobile number (required)0
    //     * @apiParam {String} name (required)
    //     * @apiParam {String} email (required)
    //     * @apiParam {String} company (optional)
    //     * @apiParam {String} reseller_code (optional)
    //     * @apiSampleRequest http://cloudtools.versaflow.io/api/postsignup
    //     * @apiSuccess (HTTP 200) {String} message
    //     *
    //     * @apiSuccessExample Success-Response:
    //     *     HTTP/1.1 200 OK
    //     *     {
    //     *       "status": "SUCCESS",
    //     *       "message": "Account Created. SMS Sent.",
    //     *       "sip": {username: '101', password: '1234', server: 'pbx.cloudtools.co.za'}
    //     *     }
    //     *
    //     * @apiError (HTTP 400) FieldRequired
    //     *
    //     * @apiErrorExample Error-Response:
    //     *     HTTP/1.1 400 Bad Request
    //     *     {
    //     *       "status": "FAILURE",
    //     *       "message": "Name required."
    //     *     }
    //     * @apiError (HTTP 400) FieldRequired
    //     *
    //     * @apiErrorExample Error-Response:
    //     *     HTTP/1.1 400 Bad Request
    //     *     {
    //     *       "status": "FAILURE",
    //     *       "message": "Phone required."
    //     *     }
    //     *
    //     * @apiError (HTTP 400) FieldRequired
    //     *
    //     * @apiErrorExample Error-Response:
    //     *     HTTP/1.1 400 Bad Request
    //     *     {
    //     *       "status": "FAILURE",
    //     *       "message": "Email required."
    //     *     }
    //     *
    //     * @apiError (HTTP 400) InvalidPhoneNumber invalid ZA phone number
    //     *
    //     * @apiErrorExample Error-Response:
    //     *     HTTP/1.1 400 Bad Request
    //     *     {
    //     *       "status": "FAILURE",
    //     *       "message": "Invalid phone number"
    //     *     }
    //     *
    //     * @apiError (HTTP 400) InvalidPhoneNumber phone number in use
    //     *
    //     * @apiErrorExample Error-Response:
    //     *     HTTP/1.1 400 Bad Request
    //     *     {
    //     *       "status": "FAILURE",
    //     *       "message": "Phone number already exists."
    //     *     }
    //     *
    //     *
    //     * @apiError (HTTP 400) InvalidPhoneNumber fixed line phone number
    //     *
    //     * @apiErrorExample Error-Response:
    //     *     HTTP/1.1 400 Bad Request
    //     *     {
    //     *       "status": "FAILURE",
    //     *       "message": "You can not use a fixed line number"
    //     *     }
    //     *
    //     * @apiError (HTTP 500) Exception-Response
    //     *
    //     * @apiErrorExample Exception-Response:
    //     *     HTTP/1.1 500 Internal Error
    //     *     {
    //     *       "status": "FAILURE",
    //     *       "message": "Account could not be created."
    //     *     }
    //     * @apiError (HTTP 500) Exception-Response
    //     *
    //     * @apiErrorExample Exception-Response:
    //     *     HTTP/1.1 500 Internal Error
    //     *     {
    //     *       "status": "FAILURE",
    //     *       "message": "User could not be created."
    //     *     }
    //     *
    //     * @apiError (HTTP 500) Exception-Response
    //     *
    //     * @apiErrorExample Exception-Response:
    //     *     HTTP/1.1 500 Internal Error
    //     *     {
    //     *       "status": "FAILURE",
    //     *       "message": "PBX add failed. DNS create failed"
    //     *     }
    //     *
    //     * @apiError (HTTP 500) Exception-Response
    //     *
    //     * @apiErrorExample Exception-Response:
    //     *     HTTP/1.1 500 Internal Error
    //     *     {
    //     *       "status": "FAILURE",
    //     *       "message": "Extension could not be created"
    //     *     }
    //     *
    //     * @apiError (HTTP 500) Exception-Response
    //     *
    //     * @apiErrorExample Exception-Response:
    //     *     HTTP/1.1 500 Internal Error
    //     *     {
    //     *       "status": "FAILURE",
    //     *       "message": "Error exception."
    //     *     }
    //     */

    //     $test_numbers = ['0743141193'];
    //     if (!empty($this->request->mobile_number)) {
    //         try {
    //             $phone = $this->request->mobile_number;
    //             $number = phone($phone, ['ZA','US','Auto']);

    //             if (strlen($phone) != 10) {
    //                 //  return api_error('Invalid phone number.');
    //             }

    //             if ($number->isOfType('fixed_line')) {
    //                 return api_error('You can not use a fixed line number.');
    //             }

    //             $number = $number->formatForMobileDialingInCountry('ZA');
    //         } catch (\Throwable $ex) {  exception_log($ex);
    //             $post_arr = (array) $this->request->all();
    //             if (!str_contains($ex->getMessage(), 'Number does not match the provided countries')) {
    //                 exception_email($ex, 'API error token phone number '.date('Y-m-d H:i'), $post_arr);
    //             }

    //             return api_error('Invalid phone number.');
    //         }
    //     }

    //     if (empty($this->request->name)) {
    //         return api_error('Name required.');
    //     }

    //     if (empty($this->request->mobile_number)) {
    //         return api_error('Phone required.');
    //     }

    //     if (empty($this->request->email)) {
    //         return api_error('Email required.');
    //     }
    //     if (in_array($this->request->mobile_number, $test_numbers)) {
    //         \DB::connection('pbx')->table('v_extensions')->where('mobile_app_number', $this->request->mobile_number)->update(['mobile_app_number' => null]);
    //         \DB::connection('pbx')->table('p_app_verification')->where('mobile_number', $this->request->mobile_number)->delete();
    //     }
    //     $number_used = \DB::connection('pbx')->table('v_extensions')->where('mobile_app_number', $this->request->mobile_number)->count();

    //     if ($number_used) {
    //         return api_error('Phone number already exists.');
    //     }

    //     try {
    //         $post_data = (object) $this->request->all();

    //         $insert_data = [];
    //         $verification_code =  mt_rand(100000, 999999);
    //         if (in_array($post_data->mobile_number, $this->debug_numbers)) {
    //             $verification_code = '12345';
    //         }
    //         $token = \Hash::make($post_data->mobile_number);
    //         $insert_data = [
    //             'created_at' => date('Y-m-d H:i:s'),
    //             'mobile_number' => $post_data->mobile_number,
    //             'code' => $verification_code,
    //             'verified' => 0,
    //             'api_token' => $token,
    //             'signup_data' => json_encode($post_data),
    //         ];
    //         \DB::connection('pbx')->table('p_app_verification')->insert($insert_data);
    //         if (!in_array($post_data->mobile_number, $this->debug_numbers)) {
    //             $result = queue_sms(12, $post_data->mobile_number, 'Unlimited Mobile Verification Code - '.$verification_code, 1, 1);
    //         }
    //         return api_success('OTP Sent.');
    //     } catch (\Throwable $ex) {  exception_log($ex);
    //         exception_email($ex, 'API error signup '.date('Y-m-d H:i'));
    //         return api_abort('Error exception');
    //     }
    // }

    public function postSignup()
    {
        /**
         * @api {post} api/postsignup postSignup
         * @apiVersion 1.0.0
         * @apiName postSignup
         * @apiGroup Auth
         *
         * @apiParam {String} key appkey
         * @apiParam {Number} mobile_number valid za mobile number (required)
         * @apiParam {String} name (required)
         * @apiParam {String} email (required)
         * @apiParam {String} company (optional)
         * @apiParam {String} reseller_code (optional)
         * @apiSampleRequest http://cloudtools.versaflow.io/api/postsignup
         * @apiSuccess (HTTP 200) {String} message
         *
         * @apiSuccessExample Success-Response:
         *     HTTP/1.1 200 OK
         *     {
         *       "status": "SUCCESS",
         *       "message": "Account Created. SMS Sent.",
         *       "sip": {username: '101', password: '1234', server: 'pbx.cloudtools.co.za'}
         *     }
         *
         * @apiError (HTTP 400) FieldRequired
         *
         * @apiErrorExample Error-Response:
         *     HTTP/1.1 400 Bad Request
         *     {
         *       "status": "FAILURE",
         *       "message": "Name required."
         *     }
         * @apiError (HTTP 400) FieldRequired
         *
         * @apiErrorExample Error-Response:
         *     HTTP/1.1 400 Bad Request
         *     {
         *       "status": "FAILURE",
         *       "message": "Phone required."
         *     }
         *
         * @apiError (HTTP 400) FieldRequired
         *
         * @apiErrorExample Error-Response:
         *     HTTP/1.1 400 Bad Request
         *     {
         *       "status": "FAILURE",
         *       "message": "Email required."
         *     }
         *
         * @apiError (HTTP 400) InvalidPhoneNumber invalid ZA phone number
         *
         * @apiErrorExample Error-Response:
         *     HTTP/1.1 400 Bad Request
         *     {
         *       "status": "FAILURE",
         *       "message": "Invalid phone number"
         *     }
         *
         * @apiError (HTTP 400) InvalidPhoneNumber phone number in use
         *
         * @apiErrorExample Error-Response:
         *     HTTP/1.1 400 Bad Request
         *     {
         *       "status": "FAILURE",
         *       "message": "Phone number already exists."
         *     }
         *
         * @apiError (HTTP 400) InvalidPhoneNumber fixed line phone number
         *
         * @apiErrorExample Error-Response:
         *     HTTP/1.1 400 Bad Request
         *     {
         *       "status": "FAILURE",
         *       "message": "You can not use a fixed line number"
         *     }
         *
         * @apiError (HTTP 500) Exception-Response
         *
         * @apiErrorExample Exception-Response:
         *     HTTP/1.1 500 Internal Error
         *     {
         *       "status": "FAILURE",
         *       "message": "Account could not be created."
         *     }
         * @apiError (HTTP 500) Exception-Response
         *
         * @apiErrorExample Exception-Response:
         *     HTTP/1.1 500 Internal Error
         *     {
         *       "status": "FAILURE",
         *       "message": "User could not be created."
         *     }
         *
         * @apiError (HTTP 500) Exception-Response
         *
         * @apiErrorExample Exception-Response:
         *     HTTP/1.1 500 Internal Error
         *     {
         *       "status": "FAILURE",
         *       "message": "PBX add failed. DNS create failed"
         *     }
         *
         * @apiError (HTTP 500) Exception-Response
         *
         * @apiErrorExample Exception-Response:
         *     HTTP/1.1 500 Internal Error
         *     {
         *       "status": "FAILURE",
         *       "message": "Extension could not be created"
         *     }
         *
         * @apiError (HTTP 500) Exception-Response
         *
         * @apiErrorExample Exception-Response:
         *     HTTP/1.1 500 Internal Error
         *     {
         *       "status": "FAILURE",
         *       "message": "Error exception."
         *     }
         */
        $test_numbers = ['0743141193'];
        if (!empty($this->request->mobile_number)) {
            try {
                $phone = $this->request->mobile_number;

                $number = phone($phone, ['ZA', 'US', 'Auto']);

                if (strlen($phone) != 10) {
                    return api_error('Invalid phone number.');
                }

                if ($number->isOfType('fixed_line')) {
                    return api_error('You can not use a fixed line number.');
                }

                $number = $number->formatForMobileDialingInCountry('ZA');
            } catch (\Throwable $ex) {
                exception_log($ex);
                $post_arr = (array) $this->request->all();
                if (!str_contains($ex->getMessage(), 'Number does not match the provided countries')) {
                    exception_email($ex, 'API error token phone number '.date('Y-m-d H:i'), $post_arr);
                }

                return api_error('Invalid phone number.');
            }
        }

        if (empty($this->request->name)) {
            return api_error('Name required.');
        }

        if (empty($this->request->mobile_number)) {
            return api_error('Phone required.');
        }

        if (empty($this->request->email)) {
            return api_error('Email required.');
        }

        if (in_array($this->request->mobile_number, $test_numbers)) {
            \DB::connection('pbx')->table('v_extensions')->where('mobile_app_number', $this->request->mobile_number)->update(['mobile_app_number' => null]);
            \DB::connection('pbx')->table('p_app_verification')->where('mobile_number', $this->request->mobile_number)->delete();
        }

        $number_used = \DB::connection('pbx')->table('v_extensions')->where('mobile_app_number', $this->request->mobile_number)->count();

        if ($number_used) {
            return api_error('Phone number already exists.');
        }

        try {
            $post_data = (object) $this->request->all();

            $insert_data = [];
            $verification_code = mt_rand(100000, 999999);

            if (in_array($post_data->mobile_number, $this->debug_numbers)) {
                $verification_code = '12345';
            }

            $token = \Hash::make($post_data->mobile_number);
            // $insert_data = [
            //     'created_at' => date('Y-m-d H:i:s'),
            //     'mobile_number' => $post_data->mobile_number,
            //     'code' => $verification_code,
            //     'verified' => 0,
            //     'api_token' => $token,
            //     'signup_data' => json_encode($post_data),
            // ];

            $insert_data = [
            'created_at' => date('Y-m-d H:i:s'),
            'mobile_number' => $post_data->mobile_number,
            'code' => $verification_code,
            'verified' => 0,
            'api_token' => $token,
            'signup_data' => json_encode($post_data),
        ];

            \DB::connection('pbx')->table('p_app_verification')->insert($insert_data);

            if (!in_array($post_data->mobile_number, $this->debug_numbers)) {
                $result = queue_sms(12, $post_data->mobile_number, 'Unlimited Mobile Verification Code - '.$verification_code, 1, 1);
            }

            return api_success('OTP Sent.');
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error signup '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function postCancel()
    {
        /*
        * @api {get} api/postcancel postCancel
        * @apiVersion 1.0.0
        * @apiName postCancel
        * @apiGroup Account
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiSampleRequest http://cloudtools.versaflow.io/api/postcancel?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
        * @apiSuccess (HTTP 200) {String} message
        * @apiSuccess (HTTP 200) {Array} account
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Account cancelled. Services will be deleted on [date]",
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */

        try {
            $id = $this->account->id;
            if (!$id) {
                return api_error('Account id not set.');
            }
            if ($id == 12) {
                return api_error('Cannot delete admin account.');
            }
            $deleted = \DB::table('crm_accounts')->where(['id' => $id, 'status' => 'Deleted'])->count();

            if ($deleted) {
                return api_error('Account already deleted.');
            }
            $cancelled = \DB::table('crm_accounts')->where(['id' => $id, 'account_status' => 'Cancelled'])->count();

            if ($cancelled) {
                return api_error('Account already cancelled.');
            }
            $account = dbgetaccount($id);
            if ($account->partner_id == 1) {
                (new \DBEvent())->setAccountAging($id);
            }
            \DB::connection('pbx')->table('v_extensions')->where('extension_uuid', $this->extension->extension_uuid)->update(['mobile_app_number' => null]);

            $data_product_ids = get_data_product_ids();
            $account_has_data = \DB::connection('default')->table('sub_services')->whereIn('product_id', $data_product_ids)->where('status', '!=', 'Deleted')->where('account_id', $id)->count();
            $cancellation_period = get_admin_setting('cancellation_schedule');

            if ($cancellation_period == 'Immediately') {
                $cancel_date = date('Y-m-d');
                if ($account_has_data) {
                    $cancel_date = date('Y-m-t', strtotime('+1 month'));
                }
            } elseif ($cancellation_period == 'This Month') {
                $cancel_date = date('Y-m-t');
                if ($account_has_data) {
                    $cancel_date = date('Y-m-t', strtotime('+1 month'));
                }
            } elseif ($cancellation_period == 'Next Month') {
                $cancel_date = date('Y-m-t', strtotime('+1 month'));
            }
            \DB::table('crm_accounts')->where('id', $id)->update(['account_status' => 'Cancelled', 'cancel_date' => $cancel_date]);

            // cancel email

            send_account_cancel_email($id);

            module_log(343, $id, 'cancelled', 'Account cancelled');

            return api_success('Account cancelled. Services will be deleted on '.$cancel_date.'.');
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error getAccount '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function postUndoCancel()
    {
        /*
        * @api {get} api/postundocancel postUndoCancel
        * @apiVersion 1.0.0
        * @apiName postUndoCancel
        * @apiGroup Account
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiSampleRequest http://cloudtools.versaflow.io/api/postundocancel?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
        * @apiSuccess (HTTP 200) {String} message
        * @apiSuccess (HTTP 200) {Array} account
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Cancellation stopped",
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */

        try {
            $id = $this->account->id;

            $deleted = \DB::table('crm_accounts')->where(['id' => $id, 'status' => 'Deleted'])->count();
            if ($deleted) {
                return api_error('Account already deleted.');
            }
            $cancelled = \DB::table('crm_accounts')->where(['id' => $id, 'account_status' => 'Cancelled'])->count();
            if (!$cancelled) {
                return api_error('Cancellation stopped.');
            }
            $cancelled = \DB::table('crm_accounts')->where('id', $id)->where('status', '!=', 'Deleted')->where('account_status', 'Cancelled')->count();
            if ($cancelled) {
                \DB::table('crm_accounts')->where('id', $id)->update(['is_deleted' => 0, 'cancelled' => 0, 'account_status' => 'Enabled', 'cancel_date' => null]);
                $account = dbgetaccount($id);
                if ($account->partner_id == 1) {
                    $data['internal_function'] = 'account_status_restored';

                    erp_process_notification($id, $data);
                }

                return api_success('Cancellation stopped.');
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error postUndoCancel '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function postSMSToken()
    {
        /*
        * @api {post} api/postsmstoken postSMSToken
        * @apiVersion 1.0.0
        * @apiName postSMSToken
        * @apiGroup Auth
        *
        * @apiParam {String} key appkey
        * @apiParam {Number} mobile_number valid za mobile number
        * @apiParam {String} code valid verification code sent in sms
        * @apiSampleRequest http://cloudtools.versaflow.io/api/postsmstoken
        * @apiSuccess (HTTP 200) {String} message
        * @apiSuccess (HTTP 200) {String} api_token
        * @apiSuccess (HTTP 200) {Array} extension
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "SMS Verified.",
        *       "api_token": "2y10$oW.IHaXEm4ooZPr48ISrGeaVQ2jVug7jgy6xgHfE8fpN22TBzDng.",
        *     }
        *
        * @apiError (HTTP 400) InvalidCode
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Verification code not found"
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        */

        try {
            $signup_check = \DB::connection('pbx')->table('p_app_verification')
                ->where('mobile_number', $this->request->mobile_number)
                ->where('code', $this->request->code)
                ->where('verified', 0)
                ->count();

            if ($signup_check) {
                $signup_data = \DB::connection('pbx')->table('p_app_verification')
                    ->where('mobile_number', $this->request->mobile_number)
                    ->where('code', $this->request->code)
                    ->where('verified', 0)
                    ->get()->first();
                $result = $this->createAccount($signup_data);

                if ($result !== 'complete') {
                    return $result;
                }
                $token = $signup_data->api_token;

                return api_success('Token sent', ['api_token' => $token]);
            } else {
                $mobile_number = \DB::connection('pbx')->table('v_extensions')
                    ->where('mobile_app_number', $this->request->mobile_number)
                    ->where('verification_code', $this->request->code)
                    ->get()->first();

                if (empty($mobile_number)) {
                    return api_error('Verification code not found');
                }

                $token = \Hash::make($mobile_number->mobile_app_number);
                \DB::connection('pbx')->table('v_extensions')->where('id', $mobile_number->id)->update(['verification_code' => null, 'api_token' => $token, 'verified' => 1]);

                return api_success('Token sent', ['api_token' => $token]);
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error token '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    private function sendPhoneNumberVerification($extension, $hashkey = '')
    {
        try {
            $verification_code = mt_rand(100000, 999999);
            if (in_array($extension->mobile_app_number, $this->debug_numbers)) {
                $verification_code = '12345';
            }
            \DB::connection('pbx')->table('v_extensions')->where('id', $extension->id)->update(['verification_code' => $verification_code]);

            if (!in_array($post_data->mobile_app_number, $this->debug_numbers)) {
                if ($hashkey == '') {
                    $result = queue_sms(12, $extension->mobile_app_number, 'Unlimited Mobile Verification Code: '.$verification_code, 1, 1);
                } else {
                    $result = queue_sms(12, $extension->mobile_app_number, '<#> Unlimited Mobile Verification Code: '.$verification_code.' '.$hashkey, 1, 1);
                }
            }
            ob_end_clean();

            return true;
        } catch (\Throwable $ex) {
            exception_log($ex);

            return false;
        }
    }

    private function createAccount($signup_data)
    {
        try {
            $verification_id = $signup_data->id;
            $post_data = json_decode($signup_data->signup_data);

            $token = $signup_data->api_token;

            $company = $post_data->name;
            if (!empty($post_data->company)) {
                $company = $post_data->company;
            }

            $account = new \stdClass();
            if (!empty($post_data->reseller_code)) {
                $account->partner_id = \DB::table('crm_account_partner_settings')
                    ->where('afriphone_signup_code', $post_data->reseller_code)
                    ->pluck('account_id')
                    ->first();
            }
            if (empty($account->partner_id)) {
                $account->partner_id = 1;
            }

            $account->company = $company;
            $account->marketing_channel_id = 40;
            $account->contact = $post_data->name;
            $account->notification_type = 'sms';
            $account->lead_score = 'Hot';

            if (!empty($post_data->mobile_number)) {
                $account->phone = $post_data->mobile_number;
            }

            if (!empty($post_data->email)) {
                $account->notification_type = 'email';
                $account->email = $post_data->email;
            }

            $account_id = create_customer($account, 'customer');

            if (!$account_id) {
                return api_abort('Account could not be created');
            }

            $pbx_domain = get_available_pbx_domain_name($account);

            \DB::connection('pbx')->table('p_app_verification')
                ->where('id', $verification_id)
                ->update(['domain_name' => $pbx_domain]);

            if (!$pbx_domain) {
                return api_abort('PBX add failed. DNS create failed');
            }

            pbx_add_domain($pbx_domain, $account_id);

            \DB::connection('default')->table('crm_accounts')
                ->where('id', $account_id)
                ->update(['type' => 'customer']);

            $account = dbgetaccount($account_id);
            $this->account = $account;

            $extension = provision_pbx_extension_default($account);

            if (!$extension) {
                return api_abort('Extension could not be created');
            }

            \DB::connection('pbx')->table('v_extensions')
                ->where('extension', $extension)
                ->where('domain_uuid', $account->domain_uuid)
                ->update(['mobile_app_number' => $post_data->mobile_number, 'api_token' => $token, 'verified' => 1]);

            $extension = \DB::connection('pbx')->table('v_extensions')
                ->where('extension', $extension)
                ->where('domain_uuid', $account->domain_uuid)
                ->get()
                ->first();

            $this->setManagerExtension($extension);

            $this->addPromoAirtime('app signup');

            \DB::connection('pbx')->table('p_app_verification')
                ->where('id', $signup_data->id)
                ->update(['verified' => 1]);

            return 'complete';
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error signup '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function getCallerIds()
    {
        /*
        * @api {get} api/getcallerids getCallerIds
        * @apiVersion 1.0.0
        * @apiName getCallerIds
        * @apiGroup Extension
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiSampleRequest http://cloudtools.versaflow.io/api/getcallerids?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
        * @apiSuccess (HTTP 200) {String} message
        * @apiSuccess (HTTP 200) {Array} caller_ids
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Caller IDs retrieved",
        *       "caller_ids": ['27111111111','27111111112','27111111113']
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception"
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */

        try {
            $caller_ids = $this->getSubscribedPhoneNumbers();

            return api_success('Caller IDs retrieved', ['caller_ids' => $caller_ids]);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error getCallerIds '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function getRouting()
    {
        /*
        * @api {get} api/getrouting getRouting
        * @apiVersion 1.0.0
        * @apiName getRouting
        * @apiGroup Extension
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiSampleRequest http://cloudtools.versaflow.io/api/getrouting?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
        * @apiSuccess (HTTP 200) {String} message
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Number routing retrieved",
        *       "routing": [{number: '27712223333', number_routing: '101'},{number: '27712224444', number_routing: '101'}]
        *     }
        *
        * @apiError (HTTP 400) InvalidInboundNumbers
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "No inbound numbers assigned to this account."
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */

        try {
            $domain_uuid = $this->extension->domain_uuid;
            $number_routing = \DB::connection('pbx')->table('p_phone_numbers')->select('number', 'number_routing')->where('domain_uuid', $domain_uuid)->get()->toArray();
            if (count($number_routing) == 0) {
                return api_error('No inbound numbers assigned to this account.');
            }

            return api_success('Number routing retrieved', ['routing' => $number_routing]);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error getRouting '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function getRoutingOptions()
    {
        /*
        * @api {get} api/getroutingoptions getRoutingOptions
        * @apiVersion 1.0.0
        * @apiName getRoutingOptions
        * @apiGroup Extension
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiSampleRequest http://cloudtools.versaflow.io/api/getroutingoptions?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
        * @apiSuccess (HTTP 200) {String} message
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Routing options retrieved",
        *       "routing_options": [{id: 0, extensionDetails: {extension: 101, label: 'Extension - 101'}},{id: 1, extensionDetails: {extension: 102, label: 'Extension - 102'}}]
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */

        try {
            $domain_uuid = $this->extension->domain_uuid;
            $routing_options = [];
            $i = 0;

            $extensions = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $domain_uuid)->orderby('extension')->get();
            foreach ($extensions as $ext) {
                $routing_options[] = ['extension' => $ext->extension, 'label' => 'Extension - '.$ext->extension];
                ++$i;
            }
            $ring_groups = \DB::connection('pbx')->table('v_ring_groups')->where('domain_uuid', $domain_uuid)->orderby('ring_group_extension')->get();
            foreach ($ring_groups as $ext) {
                $routing_options[] = ['extension' => $ext->ring_group_extension, 'label' => 'Ring Group - '.$ext->ring_group_name.' '.$ext->ring_group_extension];
                ++$i;
            }

            $ivr_menus = \DB::connection('pbx')->table('v_ivr_menus')->where('domain_uuid', $domain_uuid)->orderby('ivr_menu_extension')->get();
            foreach ($ivr_menus as $ext) {
                $routing_options[] = ['extension' => $ext->ivr_menu_extension, 'label' => 'IVR Menu - '.$ext->ivr_menu_name.' '.$ext->ivr_menu_extension];
                ++$i;
            }

            $ivr_menus = \DB::connection('pbx')->table('v_dialplans')->where('domain_uuid', $domain_uuid)->where('app_uuid', '4b821450-926b-175a-af93-a03c441818b1')->orderby('dialplan_number')->get();
            foreach ($ivr_menus as $ext) {
                $routing_options[] = ['extension' => $ext->dialplan_number, 'label' => 'Time Condition - '.$ext->dialplan_name.' '.$ext->dialplan_number];
                ++$i;
            }

            return api_success('Routing options retrieved', ['routing_options' => $routing_options]);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error getRoutingOptions '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function postRouting()
    {
        /*
        * @api {post} api/postrouting postRouting
        * @apiVersion 1.0.0
        * @apiName postRouting
        * @apiGroup Extension
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiParam {String} number
        * @apiParam {String} number_routing
        * @apiSampleRequest http://cloudtools.versaflow.io/api/postrouting
        * @apiSuccess (HTTP 200) {String} message
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Number routing updated",
        *     }
        *
        * @apiError (HTTP 400) InvalidNumberRouting
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Number routing values are the same, no changes made"
        *     }
        *
        * @apiError (HTTP 400) NumberRoutingUpdateFailed
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Number routing update failed"
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */

        try {
            $domain_uuid = $this->extension->domain_uuid;
            $post_data = (object) $this->request->all();
            $routing_type = get_routing_type($domain_uuid, $post_data->number_routing);

            // $sql = "update p_phone_numbers set number_routing = '". $post_data->number_routing ."', routing_type = '". $routing_type ."' where number = '". $post_data->number ."' and domain_uuid = '". $domain_uuid ."'";
            // $updated = \DB::connection('pbx')->update($sql);

            \DB::connection('pbx')->table('p_phone_numbers')
                ->where('number', $post_data->number)
                ->where('domain_uuid', $domain_uuid)
                ->update(['number_routing' => $post_data->number_routing]);

            $updated = \DB::connection('pbx')->table('p_phone_numbers')
                ->where('number', $post_data->number)
                ->where('number_routing', $post_data->number_routing)
                ->where('domain_uuid', $domain_uuid)
                ->count();

            if (!$updated) {
                return api_error('Number routing update failed');
            }

            return api_success('Number routing updated');
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error postRouting '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function getDomain()
    {
        /*
        * @api {get} api/getdomainsettings getDomainSettings
        * @apiVersion 1.0.0
        * @apiName getDomainSettings
        * @apiGroup Account
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiSampleRequest http://cloudtools.versaflow.io/api/getdomainsettings?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
        * @apiSuccess (HTTP 200) {String} message
        * @apiSuccess (HTTP 200) {Array} account
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Account data retrieved",
        *       "account": {company: 'Company Name',contact: 'Full name', etc }
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */

        try {
            $domain_uuid = $this->extension->domain_uuid;
            $domain = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->get()->first();
            $domain_data = [
                'domain_name' => $domain->domain_name,
                'balance' => $domain->balance,
                'enable_premium_routes' => $domain->enable_premium_routes,
            ];

            return api_success('Domain data retrieved', $domain_data);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error getDomainSettings '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function postDomainSettings()
    {
        /*
        * @api {post} api/postcallsettings postCallSettings
        * @apiVersion 1.0.0
        * @apiName postCallSettings
        * @apiGroup Extension
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiParam {Boolean} enable_premium_routes
        * @apiSampleRequest http://cloudtools.versaflow.io/api/postcallsettings
        * @apiSuccess (HTTP 200) {String} message
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Number routing updated",
        *     }
        *
        * @apiError (HTTP 400) InvalidNumberRouting
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Number routing values are the same, no changes made"
        *     }
        *
        * @apiError (HTTP 400) NumberRoutingUpdateFailed
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Number routing update failed"
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */

        try {
            $domain_uuid = $this->extension->domain_uuid;
            $post_data = (object) $this->request->all();

            if (!empty($post_data->enable_premium_routes)) {
                \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->update(['enable_premium_routes' => 1]);
            } else {
                \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $domain_uuid)->update(['enable_premium_routes' => 0]);
            }

            return api_success('Call settings updated');
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error postCallSettings '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function postCallerId()
    {
        /*
        * @api {post} api/postcallerid postCallerId
        * @apiVersion 1.0.0
        * @apiName postCallerId
        * @apiGroup Extension
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiParam {String} caller_id
        * @apiParam {String} number_routing
        * @apiSampleRequest http://cloudtools.versaflow.io/api/postcallerid
        * @apiSuccess (HTTP 200) {String} message
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Caller ID updated",
        *     }
        *
        * @apiError (HTTP 400) InvalidCallerId
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Invalid Caller ID"
        *     }
        *
        * @apiError (HTTP 400) CallerIdUpdateFailed
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Caller ID update failed"
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */
        try {
            $post_data = (object) $this->request->all();
            $phone_numbers = $this->getSubscribedPhoneNumbers();

            $extension_to_update = (!empty($post_data->number_routing)) ? $post_data->number_routing : $this->extension->id;

            $valid_caller_id = false;
            if (!in_array($post_data->caller_id, $phone_numbers)) {
                return api_error('Invalid Caller ID');
            }

            $caller_id_save_error = false;
            $erp = new \DBEvent(521);
            $ext = \DB::connection('pbx')->table('v_extensions')->where('id', $extension_to_update)->get()->first();

            $outbound_caller_id_number = $post_data->caller_id;
            $outbound_caller_id_name = $post_data->caller_id;
            if ($outbound_caller_id_number) {
                $data = (array) $ext;
                $data['outbound_caller_id_number'] = $outbound_caller_id_number;
                $data['outbound_caller_id_name'] = $this->account->company;

                if (empty($ext->forward_all_destination)) {
                    $data['forward_all_enabled'] = 0;
                    $data['forward_no_answer_enabled'] = 0;
                    $data['forward_busy_enabled'] = 0;
                }

                $result = $erp->save($data);

                if (!is_array($result) || empty($result['id'])) {
                    $caller_id_save_error = true;
                }
            }

            if ($caller_id_save_error) {
                return api_error('Caller ID update failed');
            }

            return api_success('Caller ID updated');
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error postCallerId '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function postCallForward()
    {
        /*
        * @api {post} api/postcallforward postCallForward
        * @apiVersion 1.0.0
        * @apiName postCallForward
        * @apiGroup Extension
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiParam {String} call_forward
        * @apiParam {Integer} forward_busy (1 or 0)
        * @apiParam {Integer} forward_no_answer (1 or 0)
        * @apiParam {Integer} forward_all (1 or 0)
        * @apiParam {Integer} voicemail_forward (1 or 0)
        * @apiSampleRequest http://cloudtools.versaflow.io/api/postcallforward
        * @apiSuccess (HTTP 200) {String} message
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Call Forward updated",
        *     }
        *
        * @apiError (HTTP 400) InvalidCallerId
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Invalid Request"
        *     }
        *
        * @apiError (HTTP 400) CallForwardFailed
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Call Forward update failed"
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */
        try {
            $post_data = (object) $this->request->all();

            $call_forward_save_error = false;
            $erp = new \DBEvent(521);
            $ext = \DB::connection('pbx')->table('v_extensions')->where('id', $this->extension->id)->get()->first();

            $forward_busy_enabled = 0;
            $forward_no_answer_enabled = 0;
            $forward_all_enabled = 0;
            if ($post_data->forward_busy == 1) {
                $forward_busy_enabled = 1;
            }
            if ($post_data->forward_no_answer == 1) {
                $forward_no_answer_enabled = 1;
            }
            if ($post_data->forward_all == 1) {
                $forward_all_enabled = 1;
            }
            $forward_number = $post_data->call_forward;
            if ($forward_number) {
                $data = (array) $ext;
                $data['forward_all_destination'] = $forward_number;
                $data['forward_busy_enabled'] = $forward_busy_enabled;
                $data['forward_no_answer_enabled'] = $forward_no_answer_enabled;
                $data['forward_all_enabled'] = $forward_all_enabled;

                if ($post_data->voicemail_forward == 1) {
                    $data['forward_busy_enabled'] = 0;
                    $data['forward_no_answer_enabled'] = 0;
                    $data['forward_all_enabled'] = 0;
                    $data['forward_user_not_registered_enabled'] = 0;
                }
                $result = $erp->save($data);

                if (!is_array($result) || empty($result['id'])) {
                    $call_forward_save_error = true;
                }
            }

            if ($caller_id_save_error) {
                $data = (array) $ext;
                $result = $erp->save($data);

                return api_error('Call Forward update failed');
            }

            return api_success('Call Forward updated', ['forward_all_destination' => $forward_number]);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error postCallForward '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    private function getSubscribedPhoneNumbers()
    {
        $numbers = \DB::connection('pbx')->table('p_phone_numbers')->where('domain_uuid', $this->account->domain_uuid)->pluck('number')->toArray();
        $options = [];

        if (!empty($numbers) && count($numbers) > 0) {
            foreach ($numbers as $n) {
                $options[] = $n;
            }
        }

        return $options;
    }

    public function getOrder()
    {
        // return view('__app.test.voipshop');
        /**
         * @api {get} api/getorder getOrder
         * @apiVersion 1.0.0
         * @apiName getOrder
         * @apiGroup Webviews
         *
         * @apiParam {String} key appkey
         * @apiParam {String} api_token
         * @apiSampleRequest https://cloudtools.versaflow.io/api/getorder?api_token=$2y$10$SOskLOcs1hc0ts8/ofTjN.FE.546h2opocBD085Zc0ltjqDrFdlfG&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
         *
         * @apiUse TokenRequiredError
         * @apiUse TokenInvalidError
         * @apiUse DomainDNSError
         * @apiUse AccountNotFoundError
         * @apiUse AccountTypeError
         * @apiUse PBXDomainError
         * @apiUse AccountStatusDisabledError
         * @apiUse AccountStatusDeletedError
         */
        $account = $this->account;
        session(['api_token' => $this->token]);
        session(['app_key' => $this->key]);
        $data = [
            'api_token' => $this->token,
            'app_key' => $this->key,
            'account' => $this->account,
            'reseller' => $this->reseller,
            //'logo' => get_partner_logo($this->reseller->id),
            'products' => $this->getOrderProducts(),
            'products_datasource' => $this->getOrderProducts(true),
            'numbers' => $this->getAvailableNumbers(),
        ];

        return view('_api.appshop', $data);

        //https://cloudtools.versaflow.io/api/getorder?api_token=dev&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
        //https://cloudtools.versaflow.io/api/getorder?api_token=$2y$10$RId/1MkGcqLMIkpchXx8COK.hz.bMT/IWhIbULkOm8g2xLoqoiZpe&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
    }

    public function postOrder()
    {
        //return json_alert('Select a phone number', 'warning');
        if (empty($this->request->product_id) || $this->request->product_id == 1) {
            return json_alert('Invalid product.', 'warning');
        }

        $product = \DB::connection('default')->table('crm_products')->where('id', $this->request->product_id)->get()->first();

        $provision_type = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('name')->first();
        if (!empty($this->request->mobile_app_number)) {
            $check = check_mobile_app_number_extension($this->request->mobile_app_number);
            if ($check > '') {
                return json_alert($check, 'warning');
            }
        }

        if ($provision_type == 'pbx_extension' && empty($this->request->mobile_app_number)) {
            return json_alert('Mobile number for app required.', 'warning');
        }

        $phone_number_ids = \DB::connection('default')->table('crm_products')->where('product_category_id', 961)->where('status', 'Enabled')->pluck('id')->toArray();

        if (in_array($this->request->product_id, $phone_number_ids) && empty($this->request->phone_number)) {
            return json_alert('Select a phone number', 'warning');
        }

        if (!empty($this->request->phone_number_port)) {
            $invoice_result = $this->createInvoice($this->request->product_id, $this->request->qty, false, $this->request->phone_number_port);
        } elseif (!empty($this->request->phone_number)) {
            $invoice_result = $this->createInvoice($this->request->product_id, $this->request->qty, $this->request->phone_number);
        } else {
            $invoice_result = $this->createInvoice($this->request->product_id, $this->request->qty);
        }

        if ($invoice_result == 'complete') {
            return json_alert('Invoice created');
        } else {
            return $invoice_result;
        }
    }

    public function getHelpdeskData()
    {
        /**
         * @api {get} api/gethelpdesk getHelpdesk
         * @apiVersion 1.0.0
         * @apiName getHelpdesk
         * @apiGroup Webviews
         *
         * @apiParam {String} key appkey
         * @apiParam {String} api_token
         * @apiSampleRequest http://cloudtools.versaflow.io/api/gethelpdesk?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
         *
         * @apiUse TokenRequiredError
         * @apiUse TokenInvalidError
         * @apiUse DomainDNSError
         * @apiUse AccountNotFoundError
         * @apiUse AccountTypeError
         * @apiUse PBXDomainError
         * @apiUse AccountStatusDisabledError
         * @apiUse AccountStatusDeletedError
         */

        //return redirect()->to('http://cloudtelecoms.tawk.help/');
        //return view('_api.faq', []);
        //$data['logo'] = get_partner_logo($this->reseller->id);

        $articles = [];

        $faqs = \DB::table('hd_customer_faqs')->where('is_deleted', 0)->where('internal', 0)->where('type', 'Voice')->orderBy('type')->orderBy('name')->get();
        foreach ($faqs as $faq) {
            $articles[] = ['title' => $faq->name, 'text' => $faq->content];
        }

        return api_success('Success', ['articles' => $articles]);
    }

    public function getHelpdesk()
    {
        /**
         * @api {get} api/gethelpdesk getHelpdesk
         * @apiVersion 1.0.0
         * @apiName getHelpdesk
         * @apiGroup Webviews
         *
         * @apiParam {String} key appkey
         * @apiParam {String} api_token
         * @apiSampleRequest http://cloudtools.versaflow.io/api/gethelpdesk?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
         *
         * @apiUse TokenRequiredError
         * @apiUse TokenInvalidError
         * @apiUse DomainDNSError
         * @apiUse AccountNotFoundError
         * @apiUse AccountTypeError
         * @apiUse PBXDomainError
         * @apiUse AccountStatusDisabledError
         * @apiUse AccountStatusDeletedError
         */

        //return redirect()->to('http://cloudtelecoms.tawk.help/');
        //return view('_api.faq', []);
        //$data['logo'] = get_partner_logo($this->reseller->id);

        $articles = [];

        $faqs = \DB::table('hd_customer_faqs')->where('is_deleted', 0)->where('internal', 0)->where('type', 'Voice')->orderBy('type')->orderBy('name')->get();
        foreach ($faqs as $faq) {
            $articles[] = ['title' => $faq->name, 'text' => $faq->content];
        }

        $data['articles'] = $articles;
        //return response()->json($data);
        return view('_api.helpdesk', $data);
    }

    public function getContact()
    {
        /*
        * @api {get} api/getcontact getContact
        * @apiVersion 1.0.0
        * @apiName getContact
        * @apiGroup Webviews
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiSampleRequest http://cloudtools.versaflow.io/api/getcontact?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
        *
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */
        $data['reseller'] = $this->reseller;

        //$data['logo'] = get_partner_logo($this->reseller->id);
        return view('_api.contact', $data);
    }

    public function postContactForm()
    {
        /*
        * @api {get} api/postcontactform postContactForm
        * @apiVersion 1.0.0
        * @apiName postContactForm
        * @apiGroup Webviews
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiSampleRequest http://cloudtools.versaflow.io/api/postcontactform?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
        *
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */

        try {
            $post_data = (object) $this->request->all();

            if (empty($post_data->full_name)) {
                return api_error('Full name required.');
            }

            if (empty($post_data->phone_number)) {
                return api_error('Phone number required.');
            }

            if (empty($post_data->message)) {
                return api_error('Message required.');
            }
            $data = [];
            $data['internal_function'] = 'app_contact_form';
            $data['full_name'] = $post_data->full_name;
            $data['phone_number'] = $post_data->phone_number;
            $data['feedback'] = $post_data->message;
            //$data['test_debug'] = 1;
            erp_process_notification(1, $data);

            return api_success('Message submitted. Thank you for your message.');
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error postContactForm '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function getDashboard()
    {
    }

    public function getRates()
    {
        // return view('__app.test.voiprates');
        /**
         * @api {get} api/getrates getRates
         * @apiVersion 1.0.0
         * @apiName getRates
         * @apiGroup Webviews
         *
         * @apiParam {String} key appkey
         * @apiParam {String} api_token
         * @apiSampleRequest https://cloudtools.versaflow.io/api/getrates?api_token=$2y$10$lqmYL8H3cOz2InHe1ZWDGOpLh7z0aCn.a6vSSqnkTKVZ/tGb0G8q.&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
         *
         * @apiUse TokenRequiredError
         * @apiUse TokenInvalidError
         * @apiUse DomainDNSError
         * @apiUse AccountNotFoundError
         * @apiUse AccountTypeError
         * @apiUse PBXDomainError
         * @apiUse AccountStatusDisabledError
         * @apiUse AccountStatusDeletedError
         */
        $ratesheet_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $this->extension->domain_uuid)->pluck('ratesheet_id')->first();

        $rates_sql = 'SELECT country,destination, rate,
        (SELECT sort_order FROM p_rates_summary WHERE p_rates_partner_items.destination=p_rates_summary.destination and p_rates_partner_items.country=p_rates_summary.country and lowest_rate=1 limit 1) as sort_order 
        FROM p_rates_partner_items WHERE country="south africa" and ratesheet_id='.$ratesheet_id.' and destination > "" ORDER BY  country asc, destination asc';

        $rates = \DB::connection('pbx')->select($rates_sql);

        $international_rates_sql = 'SELECT country,destination, rate,
        (SELECT sort_order FROM p_rates_summary WHERE p_rates_partner_items.destination=p_rates_summary.destination and p_rates_partner_items.country=p_rates_summary.country and lowest_rate=1 limit 1) as sort_order 
        FROM p_rates_partner_items WHERE country!="south africa" and ratesheet_id='.$ratesheet_id.' and destination > "" ORDER BY country asc, destination asc';

        $international_rates = \DB::connection('pbx')->select($international_rates_sql);
        $data = [
            'local_rates' => $rates,
            'top_rates' => $international_rates,
        ];
        //$data['logo'] = get_partner_logo($this->reseller->id);
        $data['key'] = $this->request->key;
        $data['api_token'] = $this->request->api_token;

        return view('_api.rates', $data);
    }

    public function getRatesSearch()
    {
        $rates = \DB::connection('pbx')->table('p_rates_complete')
            ->where('lowest_rate', 1)
            ->select('destination', 'country', 'destination_id', 'retail_rate_zar')
            ->where('destination_id', 'LIKE', $this->request->term.'%')
            ->orWhere('destination', 'LIKE', $this->request->term.'%')
            ->orderBy('country')
            ->orderBy('destination')
            ->get();
        $table = '<table class="table">';
        $table .= '<thead><th>Dial Code</th><th>Country</th><th>Network</th><th class="text-right">Cost per minute</th></thead><tbody>';
        if (!empty($rates)) {
            foreach ($rates as $rate) {
                $table .= '<tr><td>'.$rate->destination_id.'</td><td>'.$rate->country.'</td><td>'.$rate->destination.'</td><td class="text-right">'.currency($rate->retail_rate_zar).'</td></tr>';
            }
        }
        $table .= '</tbody></table>';
        echo $table;
    }

    public function getCdr()
    {
        /**
         * @api {get} api/getcdr getCdr
         * @apiVersion 1.0.0
         * @apiName getCdr
         * @apiGroup Webviews
         *
         * @apiParam {String} key appkey
         * @apiParam {String} api_token
         * @apiSampleRequest http://cloudtools.versaflow.io/api/getcdr?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
         *
         * @apiUse TokenRequiredError
         * @apiUse TokenInvalidError
         * @apiUse DomainDNSError
         * @apiUse AccountNotFoundError
         * @apiUse AccountTypeError
         * @apiUse PBXDomainError
         * @apiUse AccountStatusDisabledError
         * @apiUse AccountStatusDeletedError
         */
        $file_name = export_cdr('pbx_cdr', $this->account->id);
        $file_path = attachments_path().$file_name;
        $data['export_url'] = attachments_url().$file_name;

        return view('_api.cdr', $data);
    }

    public function getSubscriptions()
    {
        /**
         * @api {get} api/getsubscriptions getSubscriptions
         * @apiVersion 1.0.0
         * @apiName getSubscriptions
         * @apiGroup Webviews
         *
         * @apiParam {String} key appkey
         * @apiParam {String} api_token
         * @apiSampleRequest http://cloudtools.versaflow.io/api/getsubscriptions?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
         *
         * @apiUse TokenRequiredError
         * @apiUse TokenInvalidError
         * @apiUse DomainDNSError
         * @apiUse AccountNotFoundError
         * @apiUse AccountTypeError
         * @apiUse PBXDomainError
         * @apiUse AccountStatusDisabledError
         * @apiUse AccountStatusDeletedError
         */
        $data = [];
        $subscriptions = \DB::connection('default')->table('sub_services')
            ->select('sub_services.*', 'crm_products.code', 'crm_products.name', 'crm_products.sort_order', 'crm_products.product_category_id')
            ->join('crm_products', 'crm_products.id', '=', 'sub_services.product_id')
            ->where('account_id', $this->account->id)
            ->where('sub_services.status', '!=', 'Deleted')
            ->get();

        $data['subscriptions'] = sort_product_rows($subscriptions);
        $data['account_id'] = $this->account->id;

        return view('_api.subscriptions', $data);
    }

    public function getStatement()
    {
        /**
         * @api {get} api/getstatement getStatement
         * @apiVersion 1.0.0
         * @apiName getStatement
         * @apiGroup Webviews
         *
         * @apiParam {String} key appkey
         * @apiParam {String} api_token
         * @apiSampleRequest http://cloudtools.versaflow.io/api/getstatement?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
         *
         * @apiUse TokenRequiredError
         * @apiUse TokenInvalidError
         * @apiUse DomainDNSError
         * @apiUse AccountNotFoundError
         * @apiUse AccountTypeError
         * @apiUse PBXDomainError
         * @apiUse AccountStatusDisabledError
         * @apiUse AccountStatusDeletedError
         */
        $data = [];
        $data['statement'] = statement_pdfhtml($this->account->id);
        $data['account_id'] = $this->account->id;
        $data['fullwidth_body'] = 1;

        return view('_api.statement', $data);
    }

    public function getExtensionList()
    {
        /*
        * @api {get} api/getextensionlist getExtensionList
        * @apiVersion 1.0.0
        * @apiName getExtensionList
        * @apiGroup Extension
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiSampleRequest http://cloudtools.versaflow.io/api/getextensionlist?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
        * @apiSuccess (HTTP 200) {String} message
        * @apiSuccess (HTTP 200) {Array} extensions
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Extension retrieved",
        *       "extensions": [{username: '101', name: 'name', server: 'pbx.cloudtools.co.za', caller_id: '2711111111'}]
        *     }
        *
        * @apiError (HTTP 400) InvalidSip
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Extension not found"
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */

        //Taking too long to load
        // schedule_registrations_update();

        try {
            $extensions = \DB::connection('pbx')->table('v_extensions')
                ->select('v_extensions.*', 'mon_registrations.status', 'mon_registrations.ping_status')
                ->leftJoin('mon_registrations', function ($join) {
                    $join->on('v_extensions.domain_uuid', '=', 'mon_registrations.domain_uuid');
                    $join->on('v_extensions.extension', '=', 'mon_registrations.sip_username');
                })
                ->where('v_extensions.domain_uuid', $this->extension->domain_uuid)->get();
            $data = [];
            $data['extensions'] = [];

            foreach ($extensions as $extension) {
                $registered = 'false';
                $ping_status = 'false';
                if (!empty($extension->ping_status) && $extension->ping_status == 'Reachable') {
                    $ping_status = 'true';
                }
                if (!empty($extension->status) && str_contains($extension->status, 'Registered')) {
                    $registered = 'true';
                }

                $data['extensions'][] = (object) [
                    'username' => $extension->extension,
                    'user_record' => (empty($extension->user_record)) ? '' : $extension->user_record,
                    'name' => (empty($extension->effective_caller_id_name)) ? '' : $extension->effective_caller_id_name,
                    'server' => $extension->accountcode,
                    'caller_id' => $extension->outbound_caller_id_number,
                    'caller_id_name' => $this->extension->outbound_caller_id_name,
                    'enabled' => $extension->enabled,
                    'registered' => $registered,
                    'ping_status' => $ping_status,
                ];
            }
            if ($this->account->id == 12) {
                \DB::connection('default')->table('erp_users')->where('pbx_extension', $this->extension->extension)->where('account_id', 1)->update(['unlimited_mobile_login' => date('Y-m-d H:i:s')]);
            } else {
                \DB::connection('default')->table('erp_users')->where('account_id', $this->account->id)->update(['unlimited_mobile_login' => date('Y-m-d H:i:s')]);
            }

            return api_success('Extension retrieved', $data);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error getsip '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function getExtension()
    {
        /*
        * @api {get} api/getextension getExtension
        * @apiVersion 1.0.0
        * @apiName getExtension
        * @apiGroup Extension
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiSampleRequest http://cloudtools.versaflow.io/api/getextension?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
        * @apiSuccess (HTTP 200) {String} message
        * @apiSuccess (HTTP 200) {Array} extension
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Extension retrieved",
        *       "extension": {username: '101', password: '1234', server: 'pbx.cloudtools.co.za', caller_id: '2711111111', forward_all_destination: '2711111111', forward_busy_enabled: '1', forward_no_answer_enabled: '1', forward_all_enabled: '0', voicemail_forward: '0'}
        *     }
        *
        * @apiError (HTTP 400) InvalidSip
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Extension not found"
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */

        try {
            $username = $this->extension->extension;
            $password = $this->extension->password;
            $server = $this->extension->accountcode;
            $voicemail_forward = 0;
            if (!$this->extension->forward_busy_enabled != 'true' && $this->extension->forward_no_answer_enabled != 'true' && $this->extension->forward_all_enabled != 'true') {
                $voicemail_forward = 1;
            }
            $data = ['extension' => (object) [
                'username' => $username,
                'password' => $password,
                'server' => $server,
                'user_record' => (empty($this->extension->user_record)) ? '' : $this->extension->user_record,
                'caller_id' => $this->extension->outbound_caller_id_number,
                'forward_all_destination' => $this->extension->forward_all_destination,
                'forward_busy_enabled' => ($this->extension->forward_busy_enabled == 'true') ? 1 : 0,
                'forward_no_answer_enabled' => ($this->extension->forward_no_answer_enabled == 'true') ? 1 : 0,
                'forward_all_enabled' => ($this->extension->forward_all_enabled == 'true') ? 1 : 0,
                'manager_extension' => $this->extension->manager_extension,
                'voicemail_forward' => $voicemail_forward,
            ],
            ];

            return api_success('Extension retrieved', $data);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error getsip '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function postRecording()
    {
        /*
        * @api {get} api/postrecording postRecording
        * @apiVersion 1.0.0
        * @apiName postRecording
        * @apiGroup Extension
        *
        * @apiParam {String} key appkey (required)
        * @apiParam {String} api_token (required)
        * @apiParam {String} extension (required)
        * @apiParam {String} user_record (required)
        * @apiSampleRequest http://cloudtools.versaflow.io/api/postrecording?extension=101&user_record=outboundapi_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
        * @apiSuccess (HTTP 200) {String} message
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Extension created",
        *     }
        *
        * @apiError (HTTP 400) InvalidNumber
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Phone number required."
        *     }
        *
        * @apiError (HTTP 400) InvalidNumber
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Invalid phone number."
        *     }
        *
        * @apiError (HTTP 400) InvalidNumber
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Phone number needs to be unique."
        *     }
        *
        * @apiError (HTTP 400) InvalidNumber
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Phone number already exists."
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */

        try {
            $post_data = (object) $this->request->all();

            $exists = \DB::connection('pbx')->table('v_extensions')
                ->where('domain_uuid', $this->account->domain_uuid)
                ->where('extension', $post_data->extension)
                ->count();
            if (!$exists) {
                return api_error('Extension does not exists');
            }
            \DB::connection('pbx')->table('v_extensions')
                ->where('domain_uuid', $this->account->domain_uuid)
                ->where('extension', $post_data->extension)
                ->update(['user_record' => $post_data->user_record]);
            $extension = \DB::connection('pbx')->table('v_extensions')
                ->where('domain_uuid', $this->account->domain_uuid)
                ->where('extension', $post_data->extension)->get()->first();
            // set_recording_subscription($extension);
            return api_success('Recording updated.');
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error postRecording '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function postExtension()
    {
        /*
        * @api {get} api/postextension postExtension
        * @apiVersion 1.0.0
        * @apiName postExtension
        * @apiGroup Extension
        *
        * @apiParam {String} key appkey (required)
        * @apiParam {String} api_token (required)
        * @apiParam {String} mobile_number (required)
        * @apiSampleRequest http://cloudtools.versaflow.io/api/postextension?mobile_number=0821112233&api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
        * @apiSuccess (HTTP 200) {String} message
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Extension created",
        *     }
        *
        * @apiError (HTTP 400) InvalidNumber
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Phone number required."
        *     }
        *
        * @apiError (HTTP 400) InvalidNumber
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Invalid phone number."
        *     }
        *
        * @apiError (HTTP 400) InvalidNumber
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Phone number needs to be unique."
        *     }
        *
        * @apiError (HTTP 400) InvalidNumber
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Phone number already exists."
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */

        try {
            $post_data = (object) $this->request->all();

            if (empty($post_data->mobile_number)) {
                return api_error('Phone number required.');
            }

            $number = phone($post_data->mobile_number, ['ZA', 'US', 'Auto']);

            if (strlen($post_data->mobile_number) != 10) {
                return api_error('Invalid phone number.');
            }

            if ($number->isOfType('fixed_line')) {
                return api_error('Invalid phone number.');
            }

            $formatted_number = $number->formatForMobileDialingInCountry('ZA');

            if ($formatted_number == $this->extension->mobile_number) {
                return api_error('Phone number needs to be unique.');
            }

            $number_used = \DB::connection('pbx')->table('v_extensions')->where('mobile_app_number', $formatted_number)->count();

            if ($number_used) {
                return api_error('Phone number already exists.');
            }

            $account = $this->account;
            $extension = pbx_add_extension($account);
            \DB::connection('pbx')->table('v_extensions')
                ->where('domain_uuid', $this->extension->domain_uuid)
                ->where('extension', $extension['extension'])
                ->update(['mobile_app_number' => $formatted_number]);

            send_extension_details($account->id, $this->extension->domain_uuid, $extension['extension']);
            queue_sms(12, $formatted_number, 'Extension '.$extension['extension'].' created, login with '.$formatted_number.'. Download Unlimited Mobile - https://play.google.com/store/apps/details?id=com.cloudtelecoms.phoneapp', 1, 1);

            return api_success('Extension created.');
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error getsip '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function getBalance()
    {
        /*
        * @api {get} api/getbalance getBalance
        * @apiVersion 1.0.0
        * @apiName getBalance
        * @apiGroup Airtime
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiSampleRequest http://cloudtools.versaflow.io/api/getbalance?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
        * @apiSuccess (HTTP 200) {String} message
        * @apiSuccess (HTTP 200) {Number} balance
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Balances retrieved",
        *       "balance": 100.00,
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */

        try {
            $balance = \DB::table('sub_services')->where('status', '!=', 'Deleted')
                ->where('account_id', $this->account->id)->where('provision_type', 'airtime_prepaid')->pluck('current_usage')->first();
            $balance = currency($balance);

            $balance = \DB::connection('default')->table('sub_services')->where('status', '!=', 'Deleted')
                ->where('account_id', $this->account->id)->where('provision_type', 'airtime_contract')->get()->first();

            if (!$balance) {
                $balance = 0;
            } else {
                $balance = currency($balance->current_usage);
            }

            return api_success('Balances retrieved', ['balance' => $balance]);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error token '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function getNotifications()
    {
        /*
        * @api {post} api/getnotifications getNotifications
        * @apiVersion 1.0.0
        * @apiName getNotifications
        * @apiGroup Account
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiSampleRequest http://cloudtools.versaflow.io/api/getnotifications
        * @apiSuccess (HTTP 200) {String} message
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Notifications retrieved",
        *       "notifications": [{message: 'Notification message',created_at: '2020-06-01 10:00' }]
        *     }
        *
        * @apiError (HTTP 400) NoNotificationsError
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": No new Notifications"
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */
        try {
            $notifications = \DB::connection('default')
                ->table('isp_app_notifications')
                ->where('domain_name', $this->account->pabx_domain)
                ->where('status', 'Unsent')
                ->get();
            foreach ($notifications as $notification) {
                \DB::connection('default')
                    ->table('isp_app_notifications')
                    ->where('id', $notification->id)
                    ->update(['status' => 'Sent']);
            }

            return api_success('Account data retrieved', ['notifications' => $notifications]);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error airtime '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function getReviewLink()
    {
        /*
        * @api {post} api/getreviewlink getReviewLink
        * @apiVersion 1.0.0
        * @apiName getReviewLink
        * @apiGroup Account
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiParam {String} type [android, ios]
        * @apiSampleRequest http://cloudtools.versaflow.io/api/getreviewlink
        * @apiSuccess (HTTP 200) {String} message
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "ReviewLink retrieved",
        *       "reviewlink": [{message: 'Notification message',created_at: '2020-06-01 10:00' }]
        *     }
        *
        * @apiError (HTTP 400) NoReviewLinkError
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": No new ReviewLink"
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */
        try {
            if (!empty($this->request->type) && $this->request->type == 'ios') {
                return 'ios link';
            }

            return 'android link';

            return api_success('ReviewLink retrieved', ['reviewlink' => $reviewlink]);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error airtime '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function postReferral()
    {
        /*
        * @api {post} api/postreferral postReferral
        * @apiVersion 1.0.0
        * @apiName postReferral
        * @apiGroup Airtime
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiParam {String} referral_1 mobile number
        * @apiParam {String} referral_2 mobile number
        * @apiParam {String} referral_3 mobile number
        * @apiParam {String} referral_4 mobile number
        * @apiParam {String} referral_5 mobile number
        * @apiSampleRequest http://cloudtools.versaflow.io/api/postreferral
        * @apiSuccess (HTTP 200) {String} message
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "R10.00 Airtime Applied"
        *     }
        *
        * @apiError (HTTP 400) RequiredError
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "All referral numbers required"
        *     }
        *
        * @apiError (HTTP 400) UniqueError
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "All referral numbers needs to unique"
        *     }
        *
        * @apiError (HTTP 400) DuplicateNumbersUsed
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Mobile numbers needs to be unique."
        *       "numbers": ["0821112222","0731112222"]
        *     }
        *
        * @apiError (HTTP 400) NumbersUsed
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Mobile number has already been sent an invitation."
        *       "numbers": ["0821112222","0731112222"]
        *     }
        *
        * @apiError (HTTP 400) InvalidNumbers
        *
        * @apiErrorExample Error-Response:
        *     HTTP/1.1 400 Bad Request
        *     {
        *       "status": "FAILURE",
        *       "message": "Not a mobile number."
        *       "numbers": ["0123334444","055925554444"]
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */
        try {
            $post_data = (object) $this->request->all();
            for ($i = 1; $i < 6; ++$i) {
                if (empty($post_data->{'referral_'.$i})) {
                    return api_error('All referral numbers required');
                }
            }

            $invalid_numbers = [];
            $formatted_numbers = [];
            for ($i = 1; $i < 6; ++$i) {
                try {
                    $phone = $post_data->{'referral_'.$i};
                    $number = phone($phone, ['ZA', 'US', 'Auto']);

                    if (strlen($phone) != 10) {
                        //  $invalid_numbers[] = $post_data->{'referral_'.$i};
                    }

                    if ($number->isOfType('fixed_line')) {
                        $invalid_numbers[] = $post_data->{'referral_'.$i};
                    }
                    $number = $number->formatForMobileDialingInCountry('ZA');

                    if (in_array($number, $formatted_numbers)) {
                        return api_error('All referral numbers needs to unique');
                    }
                    $formatted_numbers[] = $number;
                } catch (\Throwable $ex) {
                    exception_log($ex);
                    $invalid_numbers[] = $post_data->{'referral_'.$i};
                }
            }

            if (count($invalid_numbers) > 0) {
                return api_error('Not a mobile number.', ['numbers' => $invalid_numbers]);
            }

            $used_numbers = [];
            foreach ($formatted_numbers as $formatted_number) {
                $exists = \DB::table('isp_voice_referrals')->where('number', $formatted_number)->count();
                if ($exists) {
                    $used_numbers[] = $formatted_number;
                }
            }

            if (count($used_numbers) > 0) {
                return api_error('Mobile number has already been sent an invitation.', ['numbers' => $used_numbers]);
            }

            $unique_numbers = collect($formatted_numbers)->unique()->toArray();
            $unique_numbers_count = collect($formatted_numbers)->unique()->count();
            $formatted_numbers_count = count($formatted_numbers);
            if ($formatted_numbers_count != $unique_numbers_count) {
                $duplicate_numbers = [];
                foreach ($formatted_numbers as $n) {
                    if (!in_array($n, $unique_numbers)) {
                        $duplicate_numbers[] = $n;
                    }
                }
                $duplicate_numbers = collect($duplicate_numbers)->unique()->toArray();

                return api_error('Mobile numbers needs to be unique.', ['numbers' => $duplicate_numbers]);
            }

            foreach ($formatted_numbers as $formatted_number) {
                $data = [
                    'account_id' => $this->account->id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'number' => $formatted_number,
                ];
                \DB::table('isp_voice_referrals')->insert($data);
                queue_sms(12, $formatted_number, 'Download Unlimited Mobile for the best features and unbeatables call rates. https://play.google.com/store/apps/details?id=com.cloudtelecoms.phoneapp', 1, 1);
            }

            $this->addPromoAirtime();

            return api_success('R10.00 Sign-Up Airtime Applied.');
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error airtime '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    private function addPromoAirtime($type = 'app referral', $amount = 10)
    {
        $airtime_history = [
            'created_at' => date('Y-m-d H:i:s'),
            'erp' => session('instance')->directory,
            'domain_uuid' => $this->account->domain_uuid,
            'total' => 10,
            'type' => $type,
        ];
        \DB::connection('pbx')->table('p_airtime_history')->insert($airtime_history);
        \DB::connection('pbx')->table('v_domains')->where('erp', session('instance')->directory)->where('account_id', $this->account->id)->increment('balance', 10);

        // queue_sms(12,$this->account->phone, 'Unlimited Mobile R10 free airtime applied',1,1);
    }

    public function getAccountPost()
    {
        /*
        * @api {get} api/getaccount getAccount
        * @apiVersion 1.0.0
        * @apiName getAccount
        * @apiGroup Account
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiSampleRequest http://cloudtools.versaflow.io/api/getaccount?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
        * @apiSuccess (HTTP 200) {String} message
        * @apiSuccess (HTTP 200) {Array} account
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Account data retrieved",
        *       "account": {company: 'Company Name',contact: 'Full name', etc }
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */

        try {
            $account_data = [
                'balance' => $this->account->balance,
                'pabx_type' => $this->account->pabx_type,
                'company' => $this->account->company,
                'contact' => $this->account->contact,
                'phone' => $this->account->phone,
                'email' => $this->account->email,
            ];

            return api_success('Account data retrieved', $account_data);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error getAccount '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function getAccount()
    {
        /*
        * @api {get} api/getaccount getAccount
        * @apiVersion 1.0.0
        * @apiName getAccount
        * @apiGroup Account
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiSampleRequest http://cloudtools.versaflow.io/api/getaccount?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
        * @apiSuccess (HTTP 200) {String} message
        * @apiSuccess (HTTP 200) {Array} account
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Account data retrieved",
        *       "account": {company: 'Company Name',contact: 'Full name', etc }
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */

        try {
            $account_data = [
                'balance' => $this->account->balance,
                'pabx_type' => $this->account->pabx_type,
                'company' => $this->account->company,
                'contact' => $this->account->contact,
                'phone' => $this->account->phone,
                'email' => $this->account->email,
            ];

            return api_success('Account data retrieved', $account_data);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error getAccount '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function getPartner()
    {
        /*
        * @api {get} api/getpartner getPartner
        * @apiVersion 1.0.0
        * @apiName getPartner
        * @apiGroup Account
        *
        * @apiParam {String} key appkey
        * @apiParam {String} api_token
        * @apiSampleRequest http://cloudtools.versaflow.io/api/getpartner?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
        * @apiSuccess (HTTP 200) {String} message
        * @apiSuccess (HTTP 200) {Array} account
        *
        * @apiSuccessExample Success-Response:
        *     HTTP/1.1 200 OK
        *     {
        *       "status": "SUCCESS",
        *       "message": "Partner data retrieved",
        *       "account": {company: 'Company Name',contact: 'Full name', etc }
        *     }
        *
        * @apiError (HTTP 500) Exception-Response
        *
        * @apiErrorExample Exception-Response:
        *     HTTP/1.1 500 Internal Error
        *     {
        *       "status": "FAILURE",
        *       "message": "Error exception."
        *     }
        *
        * @apiUse TokenRequiredError
        * @apiUse TokenInvalidError
        * @apiUse DomainDNSError
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse PBXDomainError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */
        try {
            $reseller = dbgetaccount($this->account->partner_id);

            $data = [
                'contact' => $reseller->contact,
                'company' => $reseller->company,
                'address' => $reseller->address,
                'phone' => $reseller->phone,
                'email' => $reseller->email,
                'website_address' => $reseller->website_address,
            ];

            return api_success('Partner data retrieved', ['account' => (object) $data]);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error getPartner '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    private function getAvailableNumbers()
    {
        $numbers = [];

        $gateway_uuids = \DB::connection('pbx')->table('v_gateways')->where('allow_provision_numbers', 1)->pluck('gateway_uuid')->toArray();
        $numbers[127] = \DB::connection('pbx')->table('p_phone_numbers')->where('status', 'Enabled')
            ->select('number', 'prefix')
            ->whereIn('gateway_uuid', $gateway_uuids)
            ->where('number', 'LIKE', '2787%')->whereNull('domain_uuid')
            ->orderby('number')->get();

        $numbers[128] = \DB::connection('pbx')->table('p_phone_numbers')->where('status', 'Enabled')
            ->select('number', 'prefix')
            ->whereIn('gateway_uuid', $gateway_uuids)
            ->where('number', 'NOT LIKE', '2787%')->where('number', 'NOT LIKE', '%786%')->whereNull('domain_uuid')
            ->orderby('number')->get();

        $numbers[176] = \DB::connection('pbx')->table('p_phone_numbers')->where('status', 'Enabled')
            ->select('number', 'prefix')
            ->whereIn('gateway_uuid', $gateway_uuids)
            ->where('number', 'NOT LIKE', '2786%')->where('number', 'LIKE', '%786%')->whereNull('domain_uuid')
            ->orderby('number')->get();

        return $numbers;
    }

    public function getVoicemailCount()
    {
        try {
            $voicemail_uuid = \DB::connection('pbx')->table('v_voicemails')
                ->where('domain_uuid', $this->extension->domain_uuid)
                ->where('voicemail_id', $this->extension->extension)
                ->pluck('voicemail_uuid')->first();
            $voicemail_count = \DB::connection('pbx')->table('v_voicemail_messages')->where('voicemail_uuid', $voicemail_uuid)->count();

            return api_success('Voicemal data retrieved', ['voicemail_count' => $voicemail_count]);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error getVoicemailCount '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    private function createInvoice($product_id, $qty, $phone_number = false, $port_number = false)
    {
        $db = new \DBEvent();
        $account_id = $this->account->id;

        if (str_starts_with($product_id, '437')) {
            $product_id = 437;
        }
        $product = \DB::connection('default')->table('crm_products')->where('id', $product_id)->get()->first();
        $provision_type = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('name')->first();
        $reference = 'App Invoice - '.ucwords(str_replace('_', ' ', $product->code));
        if ($phone_number) {
            $reference .= ' '.$phone_number;
        }
        if ($port_number) {
            $reference .= ' '.$port_number;
        }
        $account = dbgetaccount($account_id);
        $reseller = dbgetaccount($account->partner_id);
        $price = pricelist_get_price($account_id, $product_id, $qty);
        $price = $price->full_price;
        $total = $qty * $price;
        $tax = 0;
        if ($reseller->vat_enabled) {
            $tax = $total * 0.15;
        }

        $tax_total = $total + $tax;
        $data = [
            'docdate' => date('Y-m-d'),
            'doctype' => 'Tax Invoice',
            'completed' => 1,
            'account_id' => $account_id,
            'total' => $tax_total,
            'tax' => $tax,
            'reference' => $reference,
            'qty' => [$qty],
            'price' => [$price],
            'full_price' => [$price],
            'product_id' => [$product_id],
        ];

        $result = $db->setProperties(['validate_document' => 1])->setTable('crm_documents')->save($data);

        if (!is_array($result) || empty($result['id'])) {
            return $result;
        }

        $invoice_id = $result['id'];
        if ($phone_number) {
            $erp_subscription = new \ErpSubs();
            $erp_subscription->createSubscription($account_id, $product_id, $phone_number, $invoice_id);
            pbx_add_number($account->pabx_domain, $phone_number, 101);
            update_caller_id($account->domain_uuid);
            \DB::connection('default')->table('sub_activations')->where('invoice_id', $invoice_id)->update(['status' => 'Enabled']);
        }

        if (!empty($this->request->mobile_app_number) && $provision_type == 'pbx_extension') {
            $number = phone($this->request->mobile_app_number, ['ZA', 'US', 'Auto']);
            $formatted_number = $number->formatForMobileDialingInCountry('ZA');

            $customer = $this->account;
            $extension_info = pbx_add_extension($customer);

            update_caller_id($customer->domain_uuid);
            $ext = \DB::connection('pbx')->table('v_extensions')->where('extension', $extension_info['extension'])->where('domain_uuid', $customer->domain_uuid)->get()->first();
            if (!empty($this->request->mobile_app_number)) {
                set_mobile_app_number_extension($customer->id, $formatted_number, $extension_info['extension']);
            }

            aftersave_extensions($ext);
            schedule_update_extension_count();

            $erp_subscription = new \ErpSubs();
            $erp_subscription->createSubscription($account_id, $product_id, $extension_info['extension'], $invoice_id);
            \DB::connection('default')->table('sub_activations')->where('invoice_id', $invoice_id)->update(['status' => 'Enabled']);

            send_extension_details($account->id, $this->extension->domain_uuid, $extension_info['extension']);
            queue_sms(12, $formatted_number, 'Extension '.$extension_info['extension'].' created, login with '.$formatted_number.'. Download Unlimited Mobile - https://play.google.com/store/apps/details?id=com.cloudtelecoms.phoneapp', 1, 1);
        }

        return json_alert('Done');
    }

    private function validateAccount($account = false)
    {
        if (empty($account)) {
            return 'Account does not exists, create a new account';
        }

        if (!in_array($account->type, ['reseller_user', 'customer'])) {
            return 'Invalid account type';
        }

        if (!str_contains($account->pabx_domain, 'cloudtools') && !str_contains($account->pabx_domain, 'telecloud')) {
            return 'Invalid pbx domain';
        }

        if ('Enabled' != $account->status) {
            return 'Account '.strtolower($account->status);
        }

        return true;
    }

    private function validateToken()
    {
        if (is_dev() && $this->request->api_token == 'dev') {
            $extension = \DB::connection('pbx')->table('v_extensions')->where('extension_uuid', '0c176282-ea45-469b-9650-2a4cccddc4e1')->get()->first();
            $this->extension = $extension;
            $this->account = dbgetaccount(12);
            $this->reseller = dbgetaccount(1);

            return true;
        }

        if (empty($this->request->api_token)) {
            return 'Token required';
        }

        $extension = \DB::connection('pbx')->table('v_extensions')->where('api_token', $this->request->api_token)->get()->first();
        if (empty($extension)) {
            return 'Invalid token';
        }

        $account_id = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $extension->domain_uuid)->pluck('account_id')->first();
        $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $extension->domain_uuid)->pluck('domain_name')->first();

        if (empty($this->request->check_dns) or $this->request->check_dns == 0) {
        } elseif ($this->request->check_dns == 1) {
            $ip = gethostbyname($domain_name);
            if (empty($ip) || !str_starts_with($ip, '156.0.96.6')) {
                return 'Domain DNS not propagated';
            }
        }

        $account = dbgetaccount($account_id);

        $validation = $this->validateAccount($account);
        if ($validation !== true) {
            return $validation;
        }

        if (1 == $account->id) {
            $account = dbgetaccount(12);
        }

        $this->extension = $extension;
        $this->account = $account;
        $this->reseller = dbgetaccount($account->partner_id);

        return true;
    }

    private function getOrderProducts($select_datasource = false)
    {
        $account_id = $this->account->id;
        $account = $this->account;
        if ($account->partner_id != 1) {
            validate_partner_pricelists($account->partner_id);
        }
        $pabx_domain = (!empty($account->pabx_domain)) ? $account->pabx_domain : '';
        $pabx_type = (!empty($account->pabx_type)) ? $account->pabx_type : '';

        $products = \DB::table('crm_products as products')
            ->select('products.*', 'category.name as category', 'category.name as category', 'category.department as department')
            ->join('crm_product_categories as category', 'products.product_category_id', '=', 'category.id')
            ->where('products.status', 'Enabled')
            ->where('category.customer_access', 1)
            ->where('products.not_for_sale', 0)
            ->where('products.voip_app', 1)
            ->where('category.not_for_sale', 0)
            ->where('products.type', '!=', 'Bundle')
            ->orderBy('category.sort_order')
            ->orderBy('products.sort_order')
            ->get();

        $pbx_extension_product_ids = get_activation_type_product_ids('pbx_extension');

        $products = sort_product_rows($products);
        $list_products = [];
        foreach ($products as $row) {
            //   if(200 == $row->product_category_id && $this->request->api_token != 'dev'){
            //        continue;
            //    }

            $row->code_title = ucwords(str_replace('_', ' ', $row->code));
            $row->code = ucwords(str_replace('_', ' ', $row->code)).' - '.$row->name;

            if ('Bundle' == $row->type) {
                if (1 == $account->partner_id) {
                    $list_products[] = $row;
                }
            } elseif (in_array($row->id, $pbx_extension_product_ids)) {
                if ($row->provision_package == $pabx_type) {
                    $list_products[] = $row;
                }

                if (empty($pabx_domain)) {
                    $list_products[] = $row;
                }
            } elseif ('ltetopup' == $row->provision_function) {
                // check if customer has a lte account
                $lte_accounts_count = \DB::table('sub_services')->where(['account_id' => $account_id, 'provision_type' => 'lte_sim_card', 'status' => 'Enabled'])->count();
                if ($lte_accounts_count > 0) {
                    $list_products[] = $row;
                }
            } else {
                $list_products[] = $row;
            }
        }

        $product_list = [];

        foreach ($list_products as $product) {
            $pricing = pricelist_get_price($account_id, $product->id);
            $list_product = (object) [];
            $list_product->id = $product->id;
            $list_product->category = $product->category;
            $list_product->app_category = $product->app_category;
            $list_product->department = $product->department;
            $list_product->bill_frequency = 1;
            $list_product->type = $product->type;
            $list_product->website_link = $product->website_link;
            $list_product->code = $product->code;
            $list_product->code_title = $product->code_title;
            $list_product->text = $product->code_title;
            $list_product->description = strip_tags(str_ireplace(['<br />', '<br>', '<br/>'], "\r\n", $product->description));
            $list_product->price = currency($pricing->price);
            $list_product->activation_description = '';
            $list_product->image_path = '/home/erpcloud-live/htdocs/html/uploads/telecloud/71/'.$product->upload_file;
            $list_product->image_url = url('/uploads/telecloud/71/'.$product->upload_file);

            $list_product->price = currency($pricing->price);
            if (!empty($product->activation_fee)) {
                $list_product->price = currency($product->activation_fee);
            }
            $list_product->price_tax = currency($list_product->price * 1.15);
            $list_product->full_price = currency($pricing->full_price);
            $list_product->full_price_tax = currency($pricing->full_price * 1.15);
            $list_product->qty = 1;

            if (!empty($product->provision_plan_id)) {
                $list_product->provision_type = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('name')->first();
            } else {
                $list_product->provision_type = '';
            }
            $product_list[] = $list_product;
        }
        if (!$select_datasource) {
            return $product_list;
        }
        $form_select = [];
        $list = collect($product_list);

        $categories = $list->unique('app_category')->filter();
        foreach ($categories as $i => $category) {
            $category_products = $list->where('app_category', $category->app_category)->all();
            $product_list = [];
            foreach ($category_products as $product) {
                if ($product->id == 127) {
                    $product->image_url = url('assets/unlimitedmobile/product_087.png');
                }

                if ($product->id == 128) {
                    $product->code_title = 'Numbers 012';
                    $product->image_url = url('assets/unlimitedmobile/product_geo.png');
                }

                if ($product->id == 176) {
                    $product->image_url = url('assets/unlimitedmobile/product_786.png');
                }

                if ($product->id == 437) {
                    $product->code_title = 'Airtime Prepaid';
                    $product->image_url = url('assets/unlimitedmobile/product_prepaid.png');
                }

                if ($product->id == 730) {
                    $product->code_title = 'Airtime Topup';
                    $product->image_url = url('assets/unlimitedmobile/product_topup.png');
                }

                if ($product->provision_type == 'pbx_extension') {
                    $product->image_url = url('assets/unlimitedmobile/ext_shop200.png');
                }

                $product_list[] = (object) [
                    'id' => $product->id,
                    'text' => $product->code_title,
                    'product_id' => $product->id,
                    'image_url' => $product->image_url,
                    'is_category' => 0,
                ];
            }
            if ($category->category == 'PBX Systems') {
                $category->category = 'PBX Extensions';
            }
            $category->image_url = '';
            $category->subtext = '';
            if ($category->app_category == 'Data') {
                $category->image_url = url('assets/unlimitedmobile/category_data_aligned.png');
                $category->subtext = 'Purchase data bundles';
            }
            if ($category->app_category == 'Voice') {
                $category->image_url = url('assets/unlimitedmobile/category_voice_aligned.png');
                $category->subtext = 'Purchase voice bundles';
            }
            $form_select[] = (object) [
                'id' => 'category'.$i,
                'text' => $category->app_category,
                'is_category' => 1,
                'child' => array_values($product_list),
                'image_url' => $category->image_url,
                'subtext' => $category->subtext,
            ];
        }

        return $form_select;
    }

    private function setManagerExtension($extension)
    {
        $manager_extension_set = \DB::connection('pbx')->table('v_extensions')->where('domain_uuid', $extension->domain_uuid)->where('manager_extension', 1)->count();
        if (!$manager_extension_set) {
            \DB::connection('pbx')->table('v_extensions')->where('id', $extension->id)->update(['manager_extension' => 1]);
        }
    }

    public function getPayNowLink()
    {
        $account_id = $this->request->account_id;
        $amount = $this->request->amount;
        $redirect_url = generate_paynow_app_link($account_id, $amount, '$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O', $this->token);

        $json = ['redirect_url' => $redirect_url];
        header('Access-Control-Allow-Origin: *');

        return response()->json($json);
    }
}
