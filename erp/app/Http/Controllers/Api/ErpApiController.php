<?php

namespace App\Http\Controllers\Api;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Redirect;
use Validator;

class ErpApiController extends BaseController
{
    protected $request; // request as an attribute of the controllers
    protected $token;
    protected $account;
    protected $debug_numbers;

    public function __construct(Request $request)
    {
        $this->debug_numbers = ['0813334444'];
        $this->request = $request; // Request becomes available for all the controller functions that call $this->request
        $this->middleware(function ($request, $next) {
            if ('App\\Http\\Controllers\\Api\\ErpApiController@documentation' != \Route::getCurrentRoute()->getActionName()) {
                $appkeys = ['$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O', 'opFldG94bgQudpVyciFpbG9gLp3vo5'];
                if (empty($request->key) || !in_array($request->key, $appkeys)) {
                    // return api_error('Invalid API Key');
                }

                if ('App\\Http\\Controllers\\Api\\ErpApiController@getLogin' != \Route::getCurrentRoute()->getActionName()
                && 'App\\Http\\Controllers\\Api\\ErpApiController@postSignup' != \Route::getCurrentRoute()->getActionName()
                && 'App\\Http\\Controllers\\Api\\ErpApiController@postSMSToken' != \Route::getCurrentRoute()->getActionName()) {
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
     * @apiDefine TokenRequiredError
     *
     * @apiError (HTTP 400) TokenRequired
     *
     * @apiErrorExample Global-Validation-Error:
     *     HTTP/1.1 400 Bad Request
     *     {
     *       "status": "FAILURE",
     *       "message": "Token required"
     *     }
     */

    /**
     * @apiDefine TokenInvalidError
     *
     * @apiError (HTTP 400) TokenInvalid
     *
     * @apiErrorExample Global-Validation-Error:
     *     HTTP/1.1 400 Bad Request
     *     {
     *       "status": "FAILURE",
     *       "message": "Token invalid"
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
    public function getLogin()
    {
        // 105 - $2y$10$CRQmxS.4BoSi2qc9.vCUreGcKz7Hl26.7pkH5N.5PAhQJYOCOJ0.a
        //http://ct.versaflow.io/erp_api/getlogin?key=$2y10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O&mobile_number=0812223333
        /*
        * @api {get} api/login getLogin
        * @apiVersion 1.0.0
        * @apiName getLogin
        * @apiGroup Auth
        *
        * @apiParam {String} key appkey
        * @apiParam {Number} mobile_number valid za mobile number
        * @apiSampleRequest http://ct.versaflow.io/erp_api/getlogin?key=$2y10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O&mobile_number=0812223333
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
            if (!str_contains($ex->getMessage(), 'The string supplied did not seem to be a phone number.')) {
                exception_email($ex, 'API error token phone number '.date('Y-m-d H:i'), $post_arr);
            }

            return api_error('Invalid phone number.');
        }

        try {
            $user = \DB::connection('default')->table('erp_users')->where('erp_app_number', $number)->get()->first();
            // aa('User details ');
            // aa($user);

            // \DB::connection('default')->table('erp_users')->where('id', $user->id)->update(['active' => 1]);

            if (!$user) {
                return api_error('Phone number not found. Already have an account? Call us to link your number to your account.');
            }
            $account_id = $user->account_id;

            $account = dbgetaccount($account_id);
            // \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->update(['balance' => 10]);
            // aa('ddd');
            // \DB::connection('default')->table('crm_accounts')
            // ->where('id', $account->id)
            // ->update(['status' => 'Enabled']);

            $validation = $this->validateAccount($account);
            if ($validation !== true) {
                return api_error($validation);
            }

            // if (!$mobile_number->verified) {
            $result = $this->sendPhoneNumberVerification($user);
            // aa('Access token');
            // aa($token);

            $token = \Hash::make($user->erp_app_number);
            // aa('Access token');
            // aa($token);

            return api_success('Token sent', ['api_token' => $token]);

            // }

            return api_success('Account exists.');
        } catch (\Throwable $ex) {
            exception_log($ex);
            aa($ex);
            exception_email($ex, 'API error token '.date('Y-m-d H:i'));

            return api_abort('Error exception'.$ex);
        }
    }

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
         * @apiSampleRequest http://ct.versaflow.io/erp_api/postsignup
         * @apiSuccess (HTTP 200) {String} message
         *
         * @apiSuccessExample Success-Response:
         *     HTTP/1.1 200 OK
         *     {
         *       "status": "SUCCESS",
         *       "message": "Account Created. SMS Sent.",
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
         *       "message": "User could not be created"
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
                    //  return api_error('Invalid phone number.');
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
            \DB::connection('default')->table('erp_users')->where('erp_app_number', $this->request->mobile_number)->update(['erp_app_number' => null]);
            \DB::connection('default')->table('erp_user_api')->where('mobile_number', $this->request->mobile_number)->delete();
        }
        $number_used = \DB::connection('default')->table('erp_users')->where('erp_app_number', $this->request->mobile_number)->count();

        if ($number_used) {
            return api_error('Phone number already registered.');
        }

        try {
            $post_data = (object) $this->request->all();

            $insert_data = [];
            $verification_code = mt_rand(100000, 999999);
            if (in_array($post_data->mobile_number, $this->debug_numbers)) {
                $verification_code = '12345';
            }
            $token = \Hash::make($post_data->mobile_number);
            $insert_data = [
                'created_at' => date('Y-m-d H:i:s'),
                'mobile_number' => $post_data->mobile_number,
                'code' => $verification_code,
                'verified' => 0,
                'api_token' => $token,
                'signup_data' => json_encode($post_data),
            ];
            \DB::connection('default')->table('erp_user_api')->insert($insert_data);
            if (!in_array($post_data->mobile_number, $this->debug_numbers)) {
                $result = queue_sms(1, $post_data->mobile_number, 'Vehicle DB Verification Code - '.$verification_code, 1, 1);
            }

            return api_success('OTP Sent.');
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error signup '.date('Y-m-d H:i'));

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
        * @apiSampleRequest http://ct.versaflow.io/erp_api/postsmstoken
        * @apiSuccess (HTTP 200) {String} message
        * @apiSuccess (HTTP 200) {String} api_token
        * @apiSuccess (HTTP 200) {Array} user
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

        //     try {
        //         $signup_check = \DB::connection('default')->table('erp_user_api')
        //             ->where('mobile_number', $this->request->mobile_number)
        //             ->where('code', $this->request->code)
        //             ->where('verified', 0)
        //             ->count();

        //         if ($signup_check) {
        //             $signup_data = \DB::connection('default')->table('erp_user_api')
        //                 ->where('mobile_number', $this->request->mobile_number)
        //                 ->where('code', $this->request->code)
        //                 ->where('verified', 0)
        //                 ->get()->first();
        //             $result = $this->createAccount($signup_data);
        //             if ($result !== 'complete') {
        //                 return $result;
        //             }
        //             $token = $signup_data->api_token;

        //             return api_success('Token sent', ['api_token' => $token]);
        //         } else {
        //             $user = \DB::table('erp_users')
        //                 ->where('erp_app_number', $this->request->mobile_number)
        //                 ->where('verification_code', $this->request->code)
        //                 ->get()->first();

        //             if (empty($user)) {
        //                 return api_error('Verification code not found');
        //             }

        //             $token = \Hash::make($user->erp_app_number);
        //             \DB::connection('default')->table('erp_users')->where('id', $user->id)->update(['verification_code' => null,'api_token' => $token,'verified'=> 1]);

        //             return api_success('Token sent', ['api_token' => $token]);
        //         }
        //     } catch (\Throwable $ex) {  exception_log($ex);
        //         exception_email($ex, 'API error token '.date('Y-m-d H:i'));
        //         return api_abort('Error exception');
        //     }
        // }

        try {
            // aa('Starting signup check');
            $signup_check = \DB::connection('default')->table('erp_user_api')
            ->where('mobile_number', $this->request->mobile_number)
            ->where('code', $this->request->code)
            ->where('verified', 0)
            ->count();

            // aa('Signup check result', ['signup_check' => $signup_check]);

            if ($signup_check) {
                aa('Signup data found');
                $signup_data = \DB::connection('default')->table('erp_user_api')
                ->where('mobile_number', $this->request->mobile_number)
                ->where('code', $this->request->code)
                ->where('verified', 0)
                ->get()->first();
                $result = $this->createAccount($signup_data);
                // aa('Create account result', ['result' => $result]);

                if ($result !== 'complete') {
                    return $result;
                }
                $token = $signup_data->api_token;

                // aa('Token generated', ['api_token' => $token]);

                return api_success('Token sent', ['api_token' => $token]);
            } else {
                // aa('No signup data found, checking erp_users');
                $user = \DB::table('erp_users')
                ->where('erp_app_number', $this->request->mobile_number)
                ->where('verification_code', $this->request->code)
                ->get()->first();

                if (empty($user)) {
                    // aa('Verification code not found');

                    return api_error('Verification code not found');
                }

                $token = \Hash::make($user->erp_app_number);
                \DB::connection('default')->table('erp_users')->where('id', $user->id)->update(['verification_code' => null, 'api_token' => $token, 'verified' => 1]);

                // aa('Token generated and user updated', ['api_token' => $token]);

                return api_success('Token sent', ['api_token' => $token]);
            }
        } catch (\Throwable $ex) {
            aa('Exception caught', ['exception' => $ex]);
            exception_log($ex);
            exception_email($ex, 'API error token '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function getVehicledbDropdowns()
    {
        try {
            $search = (!empty($this->request->search)) ? $this->request->search : '';
            $make = (!empty($this->request->make)) ? urldecode($this->request->make) : '';
            $model = (!empty($this->request->model)) ? urldecode($this->request->model) : '';
            $sub_type = (!empty($this->request->sub_type)) ? urldecode($this->request->sub_type) : '';
            $year = (!empty($this->request->year)) ? $this->request->year : '';

            $query = \DB::connection('default')->table('crm_vehicle_models');
            $query->select('make', 'model', 'sub_type', \DB::raw('SUBSTRING(intro_date,1,4) as year'));

            if (!empty($search)) {
                $query->whereRaw('(LOWER(make) LIKE "%'.strtolower($search).'%" or  LOWER(model) LIKE "%'.strtolower($search).'%")');
                $query->orderBy('make');
                $query->orderBy('model');
            }
            if (!empty($make)) {
                $query->whereRaw('(LOWER(make) LIKE "%'.strtolower($make).'%")');
                $query->orderBy('make');
            }
            if (!empty($model)) {
                $query->whereRaw('(LOWER(model) LIKE "%'.strtolower($model).'%")');
                $query->orderBy('model');
            }
            if (!empty($sub_type)) {
                $query->whereRaw('(LOWER(sub_type) LIKE "%'.strtolower($sub_type).'%")');
                $query->orderBy('sub_type');
            }

            if (!empty($year)) {
                $query->where('intro_date', 'LIKE', $year.'%');
                $query->orderBy('year', 'desc');
            }

            $makes = $query->groupBy('make')->pluck('make')->filter()->unique()->toArray();
            $models = $query->groupBy('model')->pluck('model')->filter()->unique()->toArray();
            $sub_types = $query->groupBy('sub_type')->pluck('sub_type')->filter()->unique()->toArray();
            $years = $query->groupBy(\DB::raw('SUBSTRING(intro_date,1,4)'))->pluck('year')->filter()->unique()->toArray();

            $data = [
                'makes' => $makes,
                'models' => $models,
                'sub_types' => $sub_types,
                'years' => $years,
            ];

            return api_success('Success', $data);
        } catch (\Throwable $ex) {
            exception_log($ex);

            return api_abort('Error exception'.$ex->getMessage());
        }
    }

    public function getVehicledbVehicles()
    {
        try {
            $search = (!empty($this->request->search)) ? $this->request->search : '';

            $make = (!empty($this->request->make)) ? urldecode($this->request->make) : '';
            $model = (!empty($this->request->model)) ? urldecode($this->request->model) : '';
            $sub_type = (!empty($this->request->sub_type)) ? urldecode($this->request->sub_type) : '';
            $year = (!empty($this->request->year)) ? $this->request->year : '';

            $page = (!empty($this->request->page)) ? $this->request->page : 0;
            $limit = (!empty($this->request->limit)) ? $this->request->limit : 500;
            $offset = ($page) ? $page * $limit : 0;
            $query = \DB::connection('default')->table('crm_vehicle_models');

            if (!empty($search)) {
                $query->whereRaw('(LOWER(make) LIKE "%'.strtolower($search).'%" or  LOWER(model) LIKE "%'.strtolower($search).'%")');
                $query->orderBy('make');
                $query->orderBy('model');
            }
            if (!empty($make)) {
                $query->whereRaw('(LOWER(make) LIKE "%'.strtolower($make).'%")');
                $query->orderBy('make');
            }
            if (!empty($model)) {
                $query->whereRaw('(LOWER(model) LIKE "%'.strtolower($model).'%")');
                $query->orderBy('model');
            }
            if (!empty($sub_type)) {
                $query->whereRaw('(LOWER(sub_type) LIKE "%'.strtolower($sub_type).'%")');
                $query->orderBy('sub_type');
            }

            if (!empty($year)) {
                $query->where('intro_date', 'LIKE', $year.'%');
                $query->orderBy('year', 'desc');
            }

            $num_rows = $query->count();
            //$query->limit($limit);
            //if($offset){
            //    $query->offset($offset);
            //}

            //$r = querybuilder_to_sql($query);

            //if(is_dev()){
            // }

            $data = $query->get();

            return api_success('Success', ['total_rows' => $num_rows, 'rows' => $data]);
        } catch (\Throwable $ex) {
            exception_log($ex);

            return api_abort('Error exception'.$ex->getMessage());
        }
    }

    public function postVehicledbInvoice()
    {
        /*
        * @api {post} api/postinvoice postInvoice
        * @apiVersion 1.0.0
        * @apiName postInvoice
        * @apiGroup Auth
        *
        * @apiParam {String} key appkey
        * @apiParam {Number} account_id
        * @apiParam {Number} amount
        * @apiParam {String} reference
        * @apiSampleRequest http://ct.versaflow.io/erp_api/postinvoice
        * @apiSuccess (HTTP 200) {String} message
        * @apiSuccess (HTTP 200) {String} api_token
        * @apiSuccess (HTTP 200) {Array} user
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
            if (empty($this->account->id)) {
                return api_error('Account id is required');
            }
            if (empty($this->request->amount)) {
                return api_error('Amount is required');
            }
            if (empty($this->request->qty)) {
                return api_error('Qty is required');
            }
            if (empty($this->request->reference)) {
                return api_error('Reference is required');
            }
            $result = create_vehicledb_invoice($this->account->id, $this->request->amount, $this->request->qty, $this->request->reference);
            if (!$result) {
                return api_error('Invoice could not be created');
            }

            return api_success('Invoice created');
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error token '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function postVehicledbCreditsUsed()
    {
        try {
            if (empty($this->account->id)) {
                return api_error('Account id is required');
            }

            if (empty($this->request->qty)) {
                return api_error('Amount is required');
            }
            if (empty($this->request->report_type)) {
                return api_error('Amount is required');
            }
            $credits_balance = \DB::table('crm_purchase_history')->where('account_id', $this->account->id)->sum('charge');
            if (empty($credits_balance)) {
                $credits_balance = 0;
            }
            $qty = abs($this->request->qty);
            $data = [
                'account_id' => $this->account->id,
                'report_type' => $this->request->report_type,
                'charge' => $qty * -1,
                'balance' => $credits_balance - $qty,
            ];
            dbinsert('crm_purchase_history', $data);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error signup '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function postCreditOnEvaluationFail()
    {
        try {
            if (empty($this->account->id)) {
                return api_error('Account id is required');
            }

            if (empty($this->request->qty)) {
                return api_error('Amount is required');
            }
            if (empty($this->request->report_type)) {
                return api_error('Amount is required');
            }

            $credits_balance = \DB::table('crm_purchase_history')->where('account_id', $this->account->id)->sum('charge');
            if (empty($credits_balance)) {
                $credits_balance = 0;
            }

            $data = [
                'account_id' => $this->account->id,
                'report_type' => 'credits_purchase',
                'charge' => $this->request->qty,
                'balance' => $credits_balance + $this->request->qty,
            ];

            \DB::connection('default')->table('crm_purchase_history')->insert($data);
        } catch (\Throwable $ex) {
            aa('Exception occurred: '.$ex->getMessage());
            exception_log($ex);
            exception_email($ex, 'API error signup '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function postContactForm()
    {
        try {
            $post_data = (object) $this->request->all();
            if (empty($post_data->fullName)) {
                return api_error('Full name required.');
            }

            if (empty($post_data->phoneNumber)) {
                return api_error('Phone number required.');
            }

            if (empty($post_data->email)) {
                return api_error('Phone number required.');
            }

            if (empty($post_data->message)) {
                return api_error('Message required.');
            }
            $data = [];
            $data['full_name'] = $post_data->fullName;
            $data['phone_number'] = $post_data->phoneNumber;
            $data['email'] = $post_data->email;
            $data['feedback'] = $post_data->message;
            $data['internal_function'] = 'debug_email';
            //$data['test_debug'] = 1;
            admin_email('Vehicledb App Contact Form', json_encode($data));

            return api_success('Message submitted. Thank you for your message.');
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error postContactForm '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function getWebviewSession(Request $request)
    {
        //if (in_array($user->erp_app_number, $this->debug_numbers)) {
        //    return 'ok';
        //}

        $cookie = \Cookie::forget('connection');
        $rules = [
            'api_token' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->passes()) {
            $remember = false; //(!is_null($request->get('remember')) ? 'true' : 'false' );
            $user_id = \DB::table('erp_users')->where('api_token', $request->input('api_token'))->pluck('id')->first();

            if ($user_id && \Auth::loginUsingId($user_id, true)) {
                $row = \Auth::user();

                if (empty($row) || empty($row->username)) {
                    return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Your username/password combination was incorrect')->withInput();
                }

                //disable all logins
                /*
                if (3696 != $row->id || 1 != $row->id) {
                return Redirect::back()->with('status', 'error')->with('message', 'Maintenance: Login unavailable');
                }
                // disable customer login

                if($row->account_id !=1){
                return Redirect::back()->with('status', 'error')->with('message','Maintenance: Login unavailable');
                }
                */
                if ('0' == $row->active) {
                    \Auth::logout();

                    return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Your Account is suspended. Please contact support.')->withInput();
                } elseif ('2' == $row->active) {
                    // Blocked users
                    \Auth::logout();

                    return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Your Account is blocked.')->withInput();
                } elseif ('1' == $row->active) {
                    $account = dbgetaccount($row->account_id);

                    if (empty($account)) {
                        \Auth::logout();

                        return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Account not found.')->withInput();
                    }

                    if ('Deleted' == $account->status) {
                        \Auth::logout();

                        return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Account not found.')->withInput();
                    }

                    //   if ('Disabled' == $account->status) {
                    //       \Auth::logout();
                    //       return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Account disabled. Please contact the admin or make a payment to Enable your account.')->withInput();
                    //   }

                    $reseller = dbgetaccount($account->partner_id);
                    if (empty($reseller) || 'Deleted' == $reseller->status) {
                        \Auth::logout();

                        return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Invalid Reseller Account.')->withInput();
                    }

                    $group_exists = \DB::table('erp_user_roles')->where('id', $row->role_id)->count();
                    if (!$group_exists) {
                        \Auth::logout();

                        return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Invalid Access.')->withInput();
                    }

                    \DB::table('erp_users')->where('id', '=', $row->id)->update(['last_login' => date('Y-m-d H:i:s')]);

                    if ('Deleted' != $account->status) {
                        /* Set Lang if available */
                        if (!is_null($request->input('language'))) {
                            $session['lang'] = $request->input('language');
                        } else {
                            $session['lang'] = $this->config['cnf_lang'];
                        }

                        $result = set_session_data($row->id);
                        if ($result !== true) {
                            return $result;
                        }
                        session(['is_api_session' => true]);
                    }

                    if (!empty(session('role_id'))) {
                        if (empty($default_page) && session('role_level') == 'Admin') {
                            $default_page = 'dashboard';
                        }
                        if (empty($default_page)) {
                            $module_id = \DB::connection('default')->table('erp_user_roles')->where('id', session('role_id'))->pluck('default_module')->first();
                            $default_page = get_menu_url($module_id);
                            if (empty($default_page)) {
                                $role_id = session('role_id');
                                $default_page = \DB::connection($connection)->table('erp_menu')
                                    ->join('erp_menu_role_access as ra', 'ra.menu_id', '=', 'erp_menu.id')
                                    ->where('ra.role_id', $role_id)
                                    ->where('ra.is_view', 1)
                                    ->where('menu_type', '!=', 'module_form')
                                    ->pluck('slug')->first();
                            }
                        }

                        if (!empty($this->request->redirect_to)) {
                            return Redirect::to('/'.$this->request->redirect_to);
                        } else {
                            return Redirect::to('/'.$default_page);
                        }
                    } else {
                        $currentLang = \Session::get('lang');
                        \Auth::logout();
                        \Session::flush();
                        \Session::put('lang', $currentLang);

                        return Redirect::to('/user/login');
                    }
                }
            } else {
                \Auth::logout();

                return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Your username/password combination was incorrect')->withInput();
            }
        } else {
            \Auth::logout();

            return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Your username/password combination was incorrect')->withInput();
        }
    }

    public function getLoginAs($account_id = 0, $redirect_to = '/')
    {
        $result = set_session_data(session('user_id'), $account_id);
        if ($result !== true) {
            return $result;
        }

        return Redirect::to($redirect_to);
    }

    private function sendPhoneNumberVerification($user, $hashkey = '')
    {
        try {
            $verification_code = mt_rand(100000, 999999);
            if (in_array($user->erp_app_number, $this->debug_numbers)) {
                $verification_code = '12345';
            }
            \DB::connection('default')->table('erp_users')->where('id', $user->id)->update(['verification_code' => $verification_code]);
            aa('Verificaiton Code');
            aa($verification_code);
            if (!in_array($post_data->erp_app_number, $this->debug_numbers)) {
                if ($hashkey == '') {
                    $result = queue_sms(1, $user->erp_app_number, 'Login Code: '.$verification_code, 1, 1);
                } else {
                    $result = queue_sms(1, $user->erp_app_number, '<#> Login Code: '.$verification_code.' '.$hashkey, 1, 1);
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
                $account->partner_id = \DB::table('crm_account_partner_settings')->where('afriphone_signup_code', $post_data->reseller_code)->pluck('account_id')->first();
            }
            if (empty($account->partner_id)) {
                $account->partner_id = 1;
            }
            $account->company = $company;
            $account->marketing_channel_id = 39;
            $account->contact = $post_data->name;
            $account->notification_type = 'sms';

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

            $account = dbgetaccount($account_id);
            $this->account = $account;

            \DB::connection('default')->table('erp_users')
                ->where('account_id', $account->id)
                ->update(['erp_app_number' => $post_data->mobile_number, 'api_token' => $token, 'verified' => 1]);

            \DB::connection('default')->table('erp_user_api')->where('id', $signup_data->id)->update(['verified' => 1]);

            $this->add_promo_credit($account->id, 1);

            return 'complete';
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error signup '.date('Y-m-d H:i'));

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
        * @apiSampleRequest http://ct.versaflow.io/erp_api/getaccount?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
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
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
        * @apiUse AccountStatusDisabledError
        * @apiUse AccountStatusDeletedError
        */

        try {
            $account_data = [
                'id' => $this->account->id,
                'balance' => $this->account->balance,
                'pabx_type' => $this->account->pabx_type,
                'company' => $this->account->company,
                'contact' => $this->account->contact,
                'phone' => $this->account->phone,
                'email' => $this->account->email,
            ];
            $is_vehicledb = false;
            if (str_contains($this->request->url(), 'vehicledb') || session('instance')->directory == 'vehicledb') {
                $is_vehicledb = true;
            }

            if ($is_vehicledb) {
                $credits_balance = \DB::table('crm_purchase_history')->where('account_id', $this->account->id)->sum('charge');
                if (empty($credits_balance)) {
                    $credits_balance = 0;
                }
                $account_data['credits_balance'] = $credits_balance;
            }

            return api_success('Account data retrieved', $account_data);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'API error getAccount '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }

    public function getAppRoutes()
    {
        try {
            $modules = \DB::table('erp_cruds')->select('id', 'name', 'slug')->get();
            $forms = \DB::table('erp_forms')->get();
            $app_routes = \DB::table('erp_app_routes')->where('is_deleted', 0)->orderBy('sort_order')->get();
            foreach ($app_routes as $route) {
                if ($route->url > '') {
                    $routes[] = [
                        'name' => ucfirst($route->url),
                        'route' => $route->url,
                    ];
                } else {
                    $access = $forms->where('module_id', $route->module_id)->where('role_id', $this->user->role_id)->where('is_view', 1)->count();
                    if ($access) {
                        $routes[] = [
                            'name' => $modules->where('id', $route->module_id)->pluck('name')->first(),
                            'route' => $modules->where('id', $route->module_id)->pluck('slug')->first(),
                        ];
                    }
                }
            }

            return api_success('Success', ['routes' => $routes]);
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
        * @apiSampleRequest http://ct.versaflow.io/erp_api/getpartner?api_token=$2y$10$NfqNMruT8Az1ezzVYcW5TeV28p7XvBp0A7BH/GD1mbXKDoS9lCli6&key=$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O
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
        * @apiUse AccountNotFoundError
        * @apiUse AccountTypeError
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

    private function validateAccount($account = false)
    {
        // aa($account);
        if (empty($account)) {
            return 'Account does not exists, create a new account';
        }

        if (!in_array($account->type, ['reseller_user', 'reseller', 'customer'])) {
            return 'Invalid account type';
        }

        if ('Enabled' != $account->status) {
            return 'Account '.strtolower($account->status);
        }

        return true;
    }

    private function validateToken()
    {
        if (is_dev() && $this->request->api_token == 'dev') {
            $user = \DB::connection('default')->table('erp_users')->where('id', 3696)->get()->first();
            $this->user = $user;
            $this->account = dbgetaccount(1);
            $this->reseller = dbgetaccount(1);

            return true;
        }

        if (empty($this->request->api_token)) {
            return 'Token required';
        }

        $user = \DB::connection('default')->table('erp_users')->where('api_token', $this->request->api_token)->get()->first();
        if (empty($user)) {
            return 'Invalid token';
        }

        $account_id = $user->account_id;
        $account = dbgetaccount($account_id);

        $validation = $this->validateAccount($account);
        if ($validation !== true) {
            return $validation;
        }

        $this->user = $user;
        $this->account = $account;
        $this->reseller = dbgetaccount($account->partner_id);

        return true;
    }

    private function add_promo_credit($account_id, $qty)
    {
        // Calculate the current credits balance
        $credits_balance = \DB::table('crm_purchase_history')->where('account_id', $account_id)->sum('charge');
        if (empty($credits_balance)) {
            $credits_balance = 0;
        }

        // Prepare the data for the new credit entry
        $data = [
            'account_id' => $account_id,
            'report_type' => 'credits_purchase',
            'charge' => $qty,
            'balance' => $credits_balance + $qty,
        ];

        // Insert the new credit entry into the purchase history
        \DB::connection('default')->table('crm_purchase_history')->insert($data);
    }

    private function deleteAccount($id, $conn = 'default')
    {
        try {
            aa('Starting deleteAccount method');

            // Check if the account exists
            $account = \DB::connection($conn)->table('crm_accounts')->where('id', $id)->first();

            if (empty($account)) {
                aa('Account not found');

                return api_error('Account not found');
            }

            aa('Deleting account with ID: '.$id);
            // Delete the account
            \DB::connection($conn)->table('crm_accounts')->where('id', $id)->delete();

            aa('Account deleted successfully');

            return api_success('Account deleted successfully');
        } catch (\Throwable $ex) {
            aa('Exception occurred: '.$ex->getMessage());
            exception_log($ex);
            exception_email($ex, 'API error delete account '.date('Y-m-d H:i'));

            return api_abort('Error exception');
        }
    }
}
