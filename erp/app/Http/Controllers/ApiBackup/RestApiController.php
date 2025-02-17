<?php

namespace App\Http\Controllers\Api;

use App\User;
use Validator;
use App\Models\RestApiModel;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class RestApiController extends BaseController
{
    protected $request; // request as an attribute of the controllers
    protected $model;
    protected $account;
    protected $user;

    public function __construct(Request $request)
    {
        $this->request = $request; // Request becomes available for all the controller functions that call $this->request


        $this->middleware(function ($request, $next) {
            $appkeys = ['$2y$10$rO4mTY12aZPeuV570behsOujwA/kHChV.46RLDBTmox1V3aNekc4O','opFldG94bgQudpVyciFpbG9gLp3vo5'];
            if (empty($request->key) || !in_array($request->key, $appkeys)) {
                 return api_error('Invalid APP Key');
            }
       
            if('App\\Http\\Controllers\\RestApiController@register' == \Route::getCurrentRoute()->getActionName()) {
                // signup returns auth token
                  
            }else if('App\\Http\\Controllers\\RestApiController@getAccount' == \Route::getCurrentRoute()->getActionName()) {
                
                // check token
                $validation = $this->validateToken();
                if ($validation !== true) {
                    return $validation;
                } 
                  
            }else if('App\\Http\\Controllers\\RestApiController@postLogin' == \Route::getCurrentRoute()->getActionName()) {
                // signup returns auth token
                  
            }else if ('App\\Http\\Controllers\\RestApiController@endpoints' == \Route::getCurrentRoute()->getActionName()) {
                        

                // check token
                $validation = $this->validateToken();
                if ($validation !== true) {
                    return $validation;
                }   
            }else if ('App\\Http\\Controllers\\RestApiController@postLogin' != \Route::getCurrentRoute()->getActionName()) {

                // check token
                $validation = $this->validateToken();
                if ($validation !== true) {
                    return $validation;
                }

                // set module
                $api_route = $request->segment(2);
                $module = \DB::table('erp_cruds')->where('api_route', $api_route)->get()->first();
                if (!$module) {
                    return response()->json(['message' => 'Requested resource not found','status'=>'error'], 404);
                }
                $this->model = new RestApiModel($module->id);

                // validate http method against form access and row access
                $request_segments = count($request->segments());
                $method = $request->method();

                if ($method == 'GET') {
                    $permission = 'is_view';
                }
                if ($method == 'POST') {
                    $permission = 'is_add';
                }
                if ($method == 'PUT') {
                    $permission = 'is_edit';
                }
                if ($method == 'DELETE') {
                    $permission = 'is_delete';
                }
                
                if($module->permissions == 'Read'){
                    if(in_array($permission,['is_add','is_edit','is_delete'])){ 
                        return response()->json(['message' => 'You do not have access to the requested resource','status'=>'error'], 401);
                    }
                }
                if($module->permissions == 'Write'){
                    if(in_array($permission,['is_edit','is_delete'])){ 
                        return response()->json(['message' => 'You do not have access to the requested resource','status'=>'error'], 401);
                    }
                }
                if($module->permissions == 'Modify'){
                    if(in_array($permission,['is_add','is_delete'])){ 
                        return response()->json(['message' => 'You do not have access to the requested resource','status'=>'error'], 401);
                    }
                }
                
                if($module->permissions == 'Write and Modify'){
                    if(in_array($permission,['is_delete'])){ 
                        return response()->json(['message' => 'You do not have access to the requested resource','status'=>'error'], 401);
                    }
                }
                
                $action_permission = \DB::table('erp_forms')->where('role_id', $this->user->role_id)->where('module_id', $module->id)->pluck($permission)->first();
                if (!$action_permission) {
                    return response()->json(['message' => 'You do not have access to the requested resource','status'=>'error'], 401);
                }
                if ($method == 'PUT' && $request_segments != 3) {
                    return response()->json(['message' => 'Record id required','status'=>'error'], 403);
                }
                if ($method == 'DELETE' && $request_segments != 3) {
                    return response()->json(['message' => 'Record id required','status'=>'error'], 403);
                }
            }

            return $next($request);
        });
    }

    public function validateToken()
    {
        if (empty($this->request->token)) {
            return response()->json(['message' => 'Token required','status'=>'error'], 401);
        }
        
        // expire token on schedule - 24 hours
        $user = \DB::table('erp_users')->where('api_token', $this->request->token)->get()->first();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized','status'=>'error'], 401);
        }
        $role_level = \DB::table('erp_user_roles')->where('id', $user->role_id)->pluck('level')->first();
        // expire non admin tokens
        /*
        if ($role_level != 'Admin' && $user->token_expire < date('Y-m-d H:i:s')) {
            \DB::table('erp_users')->where('id', $user->id)->update(['api_token' => '', 'token_expire'=>null]);
            return response()->json(['message' => 'Token expired','status'=>'error'], 401);
        }
        */
        $session_set = false;
        if (\Auth::loginUsingId($user->id, true)) {
            $session_set = set_session_data($user->id);
        }
        if (!$session_set) {
            return response()->json(['message' => 'Unauthorized session','status'=>'error'], 401);
        }
        $this->user = $user;
        $this->account = dbgetaccount($user->account_id);
        return true;
    }

    public function postLogin()
    {
        // expire token on schedule - 4 hours
        $rules = array(
            'username' => 'required',
            'password' => 'required',
        );

        $validator = Validator::make($this->request->all(), $rules);
        if ($validator->passes()) {
            if (strtolower($this->request->input('username')) == 'system') {
                return response()->json(['message' => 'Incorrect login credentials','status'=>'error'], 401);
            }

            if (!\Auth::attempt(array('username' => $this->request->input('username'), 'password' => $this->request->input('password')), true)) {
                \Auth::logout();
                return response()->json(['message' => 'Incorrect login credentials','status'=>'error'], 401);
            }

            $user = \Auth::user();

            if (empty($user) || empty($user->username)) {
                return response()->json(['message' => 'Incorrect login credentials','status'=>'error'], 401);
            }

            if (!$user->active) {
                return response()->json(['message' => 'Incorrect login credentials','status'=>'error'], 401);
            }

            // check account status
            $account = dbgetaccount($user->account_id);

            if (empty($account) || 'Deleted' == $account->status) {
                \Auth::logout();
                return response()->json(['message' => 'Account not found','status'=>'error'], 401);
            }

            $reseller = dbgetaccount($account->partner_id);
            if (empty($reseller) || 'Deleted' == $reseller->status) {
                \Auth::logout();
                return response()->json(['message' => 'Invalid reseller account','status'=>'error'], 401);
            }

            $group_exists = \DB::table('erp_user_roles')->where('id', $user->role_id)->count();
            if (!$group_exists) {
                \Auth::logout();
                return response()->json(['message' => 'Assigned role does not exists','status'=>'error'], 401);
            }

           // $admin_role = \DB::table('erp_user_roles')->where('id', $user->role_id)->where('level', 'Admin')->count();
            //if (!$admin_role) {
          //      \Auth::logout();
           //     return response()->json(['message' => 'Unavailable','status'=>'error'], 401);
          //  }

            $token_str = $user->id.date('Y-m-d H:i:s');
            $api_token = \Hash::make($token_str);
            
            set_session_data($user->id);
            $default_page = '';
            if (!is_main_instance() && empty($default_page) && session('role_level') == 'Admin') {
                $default_page = 'customers';
            }

            if (empty($default_page)) {
                $role = \DB::connection('default')->table('erp_user_roles')->where('id', session('role_id'))->get()->first();
                $module_access_list =  \DB::connection('default')->table('erp_forms')
                ->where('role_id', session('role_id'))->where('is_view', 1)
                ->pluck('module_id')->toArray();
                
              
                if (!$default_page){
                $module_id = $role->default_module;
                
                if ($module_id && in_array($module_id,$module_access_list)) {
                $default_page =  \DB::connection($connection)->table('erp_menu')
                ->where('erp_menu.module_id', $module_id)
                ->where('menu_type', '!=', 'module_form')
                ->pluck('slug')->first();
                }
                }
            }
            
            \DB::table('erp_users')->where('id', $user->id)->update(['api_token' => $api_token,'token_expire'=>date('Y-m-d H:i:s', strtotime('+24 hours'))]);
            return response()->json(['message' => 'Login success','status'=>'success','token'=>$api_token,'default_page'=>$default_page], 200);
        } else {
            \Auth::logout();
            return response()->json(['message' => 'Incorrect login credentials','status'=>'error'], 401);
        }
    }
    
    public function getAccount()
    {
        /**
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
                'id' =>  $this->account->id,
                'balance' => $this->account->balance,
                'pabx_type' => $this->account->pabx_type,
                'company' => $this->account->company,
                'contact' => $this->account->contact,
                'phone' => $this->account->phone,
                'email' => $this->account->email,
            ];
            return api_success('Account data retrieved', $account_data);
        } catch (\Throwable $ex) {  exception_log($ex);
            exception_email($ex, 'API error getAccount '.date('Y-m-d H:i'));
            return api_abort('Error exception');
        }
    }

    public function register()
    {
        
        if(empty($this->request->email) || empty($this->request->company)  || empty($this->request->contact)  || empty($this->request->mobile)){
            return response()->json(['message' => 'Fill all required fields','status'=>'error'], 401);
        }
        
        $customer = new \stdClass();
     
        $customer->notification_type = 'email';
        $customer->company = $this->request->input('company');
        $customer->contact = $this->request->input('contact');
        $customer->email = $this->request->input('email');
        $customer->phone = trim($this->request->input('mobile'));
        $customer->pricelist_id = 1;
        $customer->status = 'Enabled';
        $customer->partner_id = 1;
        
        if (!empty($this->request->input('newsletter'))) {
            $customer->newsletter = 1;
        } else {
            $customer->newsletter = 0;
        }
        
        
        $account_id = create_customer($customer, 'customer');
        
        $token_str = $user->id.date('Y-m-d H:i:s');
        $api_token = \Hash::make($token_str);

        \DB::table('erp_users')->where('account_id', $account_id)->update(['api_token' => $api_token,'token_expire'=>date('Y-m-d H:i:s', strtotime('+24 hours'))]);
        return response()->json(['message' => 'Register success','status'=>'success','token'=>$api_token], 200);
    }

    public function endpoints()
    {
        $module_ids = \DB::table('erp_forms')->where('role_id',$this->user->role_id)->where('is_view',1)->pluck('module_id')->filter()->unique()->toArray();
        $detail_module_ids = \DB::table('erp_cruds')->whereIn('id',$module_ids)->pluck('detail_module_id')->filter()->unique()->toArray();
        $module_ids = array_merge($module_ids,$detail_module_ids);
        $endpoints = \DB::table('erp_cruds')->whereIn('id',$module_ids)->orderBy('api_route')->pluck('api_route')->filter()->unique()->toArray();
        return response()->json(['message' => 'OK','status'=>'success','endpoints'=>array_values($endpoints)], 200);
    }

    public function index()
    {
        return $this->model->getRows($this->request);
    }

    public function show($api_route, $id)
    {
        return $this->model->getRow($id);
    }

    public function store()
    {
        $validation = $this->model->createRow($this->request);
        if ($validation !== true) {
            return $validation;
        }

        return response()->json(['message' => 'Record saved','status'=>'success'], 200);
    }

    public function update($api_route, $id)
    {
        $validation = $this->model->updateRow($this->request, $id);
        if ($validation !== true) {
            return $validation;
        }

        return response()->json(['message' => 'Record saved','status'=>'success'], 200);
    }

    public function destroy($api_route, $id)
    {
        $validation = $this->model->deleteRow($id);
        if ($validation !== true) {
            return $validation;
        }

        return response()->json(['message' => 'Record deleted','status'=>'success'], 200);
    }
}
