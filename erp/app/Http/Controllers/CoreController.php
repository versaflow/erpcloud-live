<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Redirect;
use Validator;

class CoreController extends BaseController
{
    protected $layout = 'layouts.main';

    public function __construct()
    {
        $this->data = [];
    }

    public function index(Request $request)
    {
        session()->forget('webform_module_id');
        session()->forget('webform_id');
        session()->forget('webform_account_id');
        session()->forget('webform_subscription_id');

        $status = '';
        $message = '';
        if (! empty(session('status'))) {
            $status = session('status');
        }
        if (! empty(session('message'))) {
            $message = session('message');
        }

        if (! empty(session('role_id'))) {
            $role = \DB::connection('default')->table('erp_user_roles')->where('id', session('role_id'))->get()->first();
            $module_access_list = \DB::connection('default')->table('erp_forms')
                ->where('role_id', session('role_id'))->where('is_view', 1)
                ->pluck('module_id')->toArray();
            $default_page = false;
            $module_id = $role->default_module;

            if ($module_id && in_array($module_id, $module_access_list)) {
                $default_page = \DB::connection('default')->table('erp_cruds')
                    ->where('id', $module_id)
                    ->pluck('slug')->first();
            }

            if (! $default_page) {
                $default_page = \DB::connection('default')->table('erp_cruds')
                    ->whereIn('id', $module_access_list)
                    ->pluck('slug')->first();
            }

            if (session('role_level') == 'Customer' || session('role_level') == 'Partner') {
                $default_page = 'dashboard';
            }
            if (session('role_level') == 'Admin') {
                if (is_main_instance()) {
                    $default_page = 'workboard';
                } else {
                    $default_page = 'customers';
                }
            }
            if ($status) {
                return Redirect::to('/'.$default_page)->with('message', $message)->with('status', $status);
            } else {
                return Redirect::to('/'.$default_page);
            }
        } else {
            $currentLang = \Session::get('lang');
            \Auth::logout();
            \Session::flush();
            \Session::put('lang', $currentLang);
            if ($status) {
                return Redirect::to('/user/login')->with('message', $message)->with('status', $status);
            } else {
                return Redirect::to('/user/login');
            }
        }
    }

    public function getLogin()
    {
        if (! session('instance')->installed) {
            return Redirect::to('/');
        }

        if (\Auth::check()) {
            return Redirect::to('/');
        } else {
            // $this->data['disable_signup'] = 1;
            $partner_id = \DB::connection('default')->table('crm_account_partner_settings')->where('whitelabel_domain', $_SERVER['HTTP_HOST'])->pluck('account_id')->first();

            if ($partner_id) {
                $reseller = dbgetaccount($partner_id);
                $this->data['disable_signup'] = $reseller->disable_signup;
                $this->data['logo'] = get_partner_logo($partner_id);
                $this->data['menu_name'] = 'Login - '.$reseller->company;
                $this->data['website_address'] = $reseller->website_address;
            } else {
                $this->data['menu_name'] = 'Login - CloudTools';
                $this->data['logo'] = get_partner_logo();

            }
            $this->data['requires_otp'] = (! empty(request()->requires_otp)) ? 1 : 0;

            return view('_auth.login', $this->data);
        }
    }

    public function autoLogin()
    {
        $login_data = \Erp::decode(request()->login_data);
        if (empty($login_data['account_id']) || empty($login_data['user_id']) || empty($login_data['route'])) {
            return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Invalid link.');
        }

        if (! empty(session('account_id')) && session('account_id') == $login_data['account_id']) {
            return Redirect::to($login_data['route']);
        }

        $account = dbgetaccount($login_data['account_id']);
        $user_id = \DB::table('erp_users')->where('account_id', $login_data['account_id'])->where('id', $login_data['user_id'])->pluck('id')->first();

        if ($user_id && \Auth::loginUsingId($user_id, true)) {
            $row = \Auth::user();

            $disable_customer_login = get_admin_setting('disable_customer_login');

            $account = dbgetaccount($row->account_id);
            if ($row->account_id != 1 && $disable_customer_login) {
                return Redirect::back()->with('status', 'error')->with('message', 'Erp access disabled');
            }

            if ($row->is_deleted) {
                \Auth::logout();

                return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Your Account is suspended. Please contact support.')->withInput();
            } elseif (! $row->active) {
                \Auth::logout();

                return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Your Account is suspended. Please contact support.')->withInput();
            } else {
                $account = dbgetaccount($row->account_id);

                if (empty($account)) {
                    \Auth::logout();

                    return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Account not found.')->withInput();
                }

                if ($account->status == 'Deleted') {
                    \Auth::logout();

                    return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Account not found.')->withInput();
                }

                //   if ('Disabled' == $account->status) {
                //       \Auth::logout();
                //       return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Account disabled. Please contact the admin or make a payment to Enable your account.')->withInput();
                //   }

                $reseller = dbgetaccount($account->partner_id);
                if (empty($reseller) || $reseller->status == 'Deleted') {
                    \Auth::logout();

                    return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Invalid Reseller Account.')->withInput();
                }

                $group_exists = \DB::table('erp_user_roles')->where('id', $row->role_id)->count();
                if (! $group_exists) {
                    \Auth::logout();

                    return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Invalid Access.')->withInput();
                }

                \DB::table('erp_users')->where('id', '=', $row->id)->update(['last_login' => date('Y-m-d H:i:s')]);

                if ($account->status != 'Deleted') {

                    /* Set Lang if available */

                    $session['lang'] = $this->config['cnf_lang'];

                    $user_group = dbgetcell('erp_user_roles', 'id', $row->role_id, 'name');

                    $result = set_session_data($row->id);
                    if ($result !== true) {
                        return $result;
                    }
                }
            }
        }

        return Redirect::to($login_data['route']);
    }

    public function adminLogin()
    {
        if (! empty(request()->user_id)) {
            $user = \DB::connection('system')->table('erp_users')->where('id', request()->user_id)->get()->first();
            // aa($user);
            if ($user->is_deleted) {
                \Auth::logout();

                return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Your Account is suspended. Please contact support.');
            }

            if (! empty($user) && $user->active) {
                $role = \DB::connection('system')->table('erp_user_roles')->where('id', $user->role_id)->get()->first();
                // aa($role);
                if ($role->level == 'Admin') {
                    $logged_in = \DB::connection('system')->table('erp_user_sessions')->where('user_id', $user->id)->where('ip_address', request()->ip())->count();

                    if ($logged_in) {
                        $row = \DB::connection('default')->table('erp_users')->where('username', $user->username)->get()->first();

                        if ($row) {
                            // check admin instance access
                            $user_id = \DB::connection('system')->table('erp_users')->where('username', $row->username)->pluck('id')->first();

                            $role = \DB::connection('system')->table('erp_user_roles')->where('id', $row->role_id)->get()->first();
                            $instance_access = get_admin_instance_access($user->username);
                            if (! empty(session('instance')->id)) {
                                $instance_id = session('instance')->id;
                            } else {
                                $instance_id = \DB::connection('system')->table('erp_instances')->where('domain_name', str_replace('https://', '', request()->root()))->pluck('id')->first();
                            }
                            if (! in_array($instance_id, $instance_access)) {
                                return Redirect::to('/user/login')->with('status', 'error')->with('message', 'No Access.')->withInput(); //Ahmed todo
                            }

                            set_session_data($row->id);
                            session(['original_role_id' => $role->id]);
                        }
                    }
                }
            }

            $redirect_page = request()->redirect_page;
            // aa($redirect_page);

            if (! empty($redirect_page) && $redirect_page == 'processes') {
                $redirect_page = get_menu_url_from_table('crm_accounts');
            }

            if (! empty($redirect_page) && ! empty(request()->load_reports)) {
                return Redirect::to($redirect_page.'?load_reports=1');
            } elseif (! empty($redirect_page)) {
                if (! empty(request()->layout_id)) {
                    return Redirect::to($redirect_page.'?layout_id='.request()->layout_id);
                } else {
                    return Redirect::to($redirect_page);
                }
            } elseif (! empty(request()->redirect_page_token)) {
                $url = '/';
                $redirect_page_token = \Erp::decode(request()->redirect_page_token);
                if ($redirect_page_token['url'] && $redirect_page_token['report_id']) {
                    $url = $redirect_page_token['url'].'?report_id='.$redirect_page_token['report_id'];
                } elseif ($redirect_page_token['url']) {
                    $url = $redirect_page_token['url'];
                }

                return Redirect::to($url);
            } else {
                return Redirect::to('/');
            }
        }
    }

    public function getInstall(Request $request)
    {
        if (session('instance')->installed) {
            abort(403);
        }
        if (config('database.connections.default.database') == config('database.connections.system.database') || config('database.connections.default.database') == 'flexerp_portal') {
            abort(403, 'Invalid DB');
        }

        $data = [
            'menu_name' => 'ERP Installation',
        ];
        $data['ledger_accounts'] = \DB::connection('system')->table('acc_ledger_accounts')->where('ledger_account_category_id', '>=', 30)->get();
        $data['apps'] = \DB::connection('system')->table('erp_apps')->get();

        return view('__app.components.install', $data);
    }

    public function postInstall(Request $request)
    {
        if (session('instance')->installed) {
            abort(403);
        }

        if (config('database.connections.default.database') == config('database.connections.system.database') || config('database.connections.default.database') == 'flexerp_portal') {
            abort(403, 'Invalid DB');
        }

        try {
            $erp = new \ErpInstance;
            $result = $erp->install($request);

            if ($result === true) {
                return json_alert('Installation complete.');
            } else {
                return $result;
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_log($ex->getMessage());
            exception_log($ex->getTraceAsString());

            return json_alert($ex->getMessage(), 'error');
        }
    }

    public function getRegister()
    {
        if ($this->config['cnf_regist'] == 'false') {
            if (\Auth::check()) {
                return Redirect::to('')->with('message', 'You are already loggedin')->with('status', 'success');
            } else {
                return Redirect::to('user/login');
            }
        } else {
            $this->data['socialize'] = config('services');

            if (session('instance')->alias == $_SERVER['HTTP_HOST']) {
                return Redirect::to('/');
            }

            $disable_signup = 1;
            $partner_id = \DB::connection('default')->table('crm_account_partner_settings')->where('whitelabel_domain', $_SERVER['HTTP_HOST'])->pluck('account_id')->first();
            if ($partner_id) {
                $reseller = dbgetaccount($partner_id);
                $this->data['logo'] = get_partner_logo($partner_id);
                $this->data['menu_name'] = 'Login - '.$reseller->company;
                $disable_signup = $reseller->disable_signup;
            } else {
                $this->data['menu_name'] = 'Login - CloudTools';
                $this->data['logo'] = get_partner_logo();
            }
            if ($disable_signup) {
                return Redirect::to('/');
            }

            if (! empty(request()->referral_code)) {
                $this->data['referral_code'] = request()->referral_code;
            }
            if (! empty(session('referral_code'))) {
                $this->data['referral_code'] = session('referral_code');
            }

            return View('_auth.register', $this->data);
        }
    }

    public function postCreate(Request $request)
    {
        $rules = [
            'username' => 'required',
            'mobile' => 'required',
            'contact' => 'required',
            'g-recaptcha-response' => 'required|captcha',
        ];

        $referral_account_id = 0;
        if (! empty($request->referral_code)) {
            session(['referral_code' => $request->referral_code]);
            $decoded_referral = \Erp::decode($request->referral_code);
            if (! empty($decoded_referral) && ! empty($decoded_referral['account_id'])) {
                $referral_account_id = $decoded_referral['account_id'];
            }
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->passes()) {
            $mobile_number = $request->input('mobile');
            try {
                $number = phone($mobile_number, ['ZA', 'US', 'Auto']);
                if ($number->isOfType('fixed_line')) {
                    return Redirect::to('user/register')->with('message', 'Mobile phone number required')->with('status', 'error')->withInput();
                }
                $number = $number->formatForMobileDialingInCountry('ZA');
                if (strlen($number) != 10) {
                    return Redirect::to('user/register')->with('message', 'Mobile phone number required')->with('status', 'error')->withInput();
                }

                request()->merge(['mobile' => $number]);
            } catch (\Throwable $ex) {
                exception_log($ex);

                return Redirect::to('user/register')->with('message', $ex->getMessage())->with('status', 'error')->withInput();
            }

            if (empty(trim($request->input('username'))) && empty(trim($request->input('mobile')))) {
                return Redirect::to('user/register')->with('message', 'Email or phone required')->with('status', 'error')->withInput();
            }

            if (empty($request->company) || empty($request->contact)) {
                return Redirect::to('user/register')->with('message', 'Company and full name required')->with('status', 'error')->withInput();
            }
            $email = '';
            if (! empty(trim($request->input('username')))) {
                $email = clean_email($request->input('username'));
                $validated = erp_email_unique($email);
                if (clean_email($validated) != $email) {
                    return Redirect::to('user/register')->with('message', $validated)->with('status', 'error')->withInput();
                }
            }
            $partner_id = \DB::connection('default')->table('crm_account_partner_settings')->where('whitelabel_domain', $_SERVER['HTTP_HOST'])->pluck('account_id')->first();
            if (! $partner_id) {
                return Redirect::to('user/register')->with('message', 'Invalid Partner account')->with('status', 'error')->withInput();
            }

            $customer = new \stdClass;
            $customer->notification_type = 'sms';
            $redirect_msg = 'Please check your sms inbox for login credentials';
            if (! empty($email)) {
                $customer->notification_type = 'email';
                $redirect_msg = 'Please check your email for login credentials';
            }
            $customer->company = (empty($request->input('company'))) ? $request->input('contact') : $request->input('company');
            $customer->contact = $request->input('contact');
            $customer->email = $email;
            $customer->phone = trim($request->input('mobile'));
            $customer->pricelist = 1;
            $customer->status = 'Enabled';
            $customer->marketing_channel_id = 39;
            $customer->partner_id = $partner_id;

            if (! empty($request->input('newsletter'))) {
                $customer->newsletter = 1;
            } else {
                $customer->newsletter = 0;
            }

            $account_id = create_customer($customer, 'customer');

            send_email_verification_link($id);
            session()->forget('referral_code');

            return Redirect::to('user/login')->with('message', $redirect_msg)->with('status', 'success');
        } else {
            $messages = $validator->messages();

            $msgs = [];
            foreach ($messages->all() as $m) {
                if ($m == 'The g-recaptcha-response field is required.') {
                    $msgs[] = 'Captcha Required';
                } elseif ($m == 'The username field is required.') {
                    $msgs[] = 'The email field is required.';
                } else {
                    $msgs[] = $m;
                }
            }
            $err = implode(PHP_EOL, $msgs);

            return Redirect::to('user/register')->with('message', $err)->with('status', 'error')->withInput();
        }
    }

    public function getUserToken(Request $request)
    {
        $cookie = \Cookie::forget('connection');
        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->passes()) {
            $remember = false; //(!is_null($request->get('remember')) ? 'true' : 'false' );
            if (strtolower($request->input('username')) == 'system') {
                return json_alert('Your username/password combination was incorrect', 'error');
            }
            if (\Auth::attempt(['username' => $request->input('username'), 'password' => $request->input('password')], true)) {
                $row = \Auth::user();

                if (empty($row) || empty($row->username)) {
                    return json_alert('Your username/password combination was incorrect', 'error');
                }

                \Auth::logout();
                $token_str = $row->id.date('Y-m-d H:i:s');
                $api_token = \Hash::make($token_str);
                $token_exists = \DB::table('erp_users')->where('api_token', $api_token)->count();
                while ($token_exists) {
                    $token_str = $row->id.date('Y-m-d H:i:s');
                    $api_token = \Hash::make($token_str);
                    $token_exists = \DB::table('erp_users')->where('api_token', $api_token)->count();
                }
                \DB::table('erp_users')->where('id', $row->id)->update(['api_token' => $api_token]);

                return json_alert('Token generated', 'success', ['api_token' => $api_token]);
            } else {
                \Auth::logout();

                return json_alert('Your username/password combination was incorrect', 'error');
            }
        } else {
            \Auth::logout();

            return json_alert('Your username/password combination was incorrect', 'error');
        }
    }

    public function postValidateToken(Request $request)
    {
        $user_id = \DB::table('erp_users')->where('api_token', $request->input('api_token'))->pluck('id')->first();
        if (empty($user_id)) {
            return json_alert('Invalid Token', 'error');
        } else {
            return json_alert('Valid Token');
        }
    }

    public function postSigninToken(Request $request)
    {
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
                if ($row->is_deleted) {
                    \Auth::logout();

                    return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Your Account is suspended. Please contact support.')->withInput();
                } elseif ($row->active == '0') {
                    \Auth::logout();

                    return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Your Account is suspended. Please contact support.')->withInput();
                } elseif ($row->active == '2') {
                    // Blocked users
                    \Auth::logout();

                    return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Your Account is blocked.')->withInput();
                } elseif ($row->active == '1') {
                    $account = dbgetaccount($row->account_id);

                    if (empty($account)) {
                        \Auth::logout();

                        return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Account not found.')->withInput();
                    }

                    if ($account->status == 'Deleted') {
                        \Auth::logout();

                        return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Account not found.')->withInput();
                    }

                    //   if ('Disabled' == $account->status) {
                    //       \Auth::logout();
                    //       return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Account disabled. Please contact the admin or make a payment to Enable your account.')->withInput();
                    //   }

                    $reseller = dbgetaccount($account->partner_id);
                    if (empty($reseller) || $reseller->status == 'Deleted') {
                        \Auth::logout();

                        return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Invalid Reseller Account.')->withInput();
                    }

                    $group_exists = \DB::table('erp_user_roles')->where('id', $row->role_id)->count();
                    if (! $group_exists) {
                        \Auth::logout();

                        return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Invalid Access.')->withInput();
                    }

                    \DB::table('erp_users')->where('id', '=', $row->id)->update(['last_login' => date('Y-m-d H:i:s')]);

                    if ($account->status != 'Deleted') {
                        /* Set Lang if available */
                        if (! is_null($request->input('language'))) {
                            $session['lang'] = $request->input('language');
                        } else {
                            $session['lang'] = $this->config['cnf_lang'];
                        }

                        $result = set_session_data($row->id);
                        if ($result !== true) {
                            return $result;
                        }
                    }

                    if (! empty(session('role_id'))) {
                        if (! is_main_instance() && empty($default_page) && session('role_level') == 'Admin') {
                            $default_page = 'customers';
                        }

                        if (empty($default_page)) {
                            $role = \DB::connection('default')->table('erp_user_roles')->where('id', session('role_id'))->get()->first();
                            $module_access_list = \DB::connection('default')->table('erp_forms')
                                ->where('role_id', session('role_id'))->where('is_view', 1)
                                ->pluck('module_id')->toArray();

                            if (! $default_page) {
                                $module_id = $role->default_module;

                                if ($module_id && in_array($module_id, $module_access_list)) {
                                    $default_page = \DB::connection('default')->table('erp_menu')
                                        ->where('erp_menu.module_id', $module_id)
                                        ->where('menu_type', '!=', 'module_form')
                                        ->pluck('slug')->first();
                                }
                            }
                        }

                        if ($status) {
                            return Redirect::to('/'.$default_page)->with('message', $message)->with('status', $status);
                        } else {
                            return Redirect::to('/'.$default_page);
                        }
                    } else {
                        $currentLang = \Session::get('lang');
                        \Auth::logout();
                        \Session::flush();
                        \Session::put('lang', $currentLang);

                        if ($status) {
                            return Redirect::to('/user/login')->with('message', $message)->with('status', $status);
                        } else {
                            return Redirect::to('/user/login');
                        }
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

    public function postSignin(Request $request)
    {
        $cookie = \Cookie::forget('connection');
        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];
        if (isset($this->config) && $this->config['cnf_recaptcha'] == 'true') {
            $rules['captcha'] = 'required|captcha';
        }
        //if(empty($request->input('account_type'))){
        //    return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Account type required')->withInput();
        //}
        if (! empty($request->input('account_type'))) {
            $account_ids = \DB::table('crm_accounts')->where('type', $request->input('account_type'))->where('status', '!=', 'Deleted')->pluck('id')->toArray();
        } else {
            $account_ids = \DB::table('crm_accounts')->where('status', '!=', 'Deleted')->pluck('id')->toArray();
        }
        $account_ids[] = 1;
        $validator = Validator::make($request->all(), $rules);
        if ($validator->passes()) {
            $remember = false; //(!is_null($request->get('remember')) ? 'true' : 'false' );
            if ($request->input('username') == 'system') {
                return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Your username/password combination was incorrect')->withInput();
            }

            $authenticated = false;
            if (! $authenticated) {
                $authenticated = \Auth::attempt(['username' => $request->input('username'), 'password' => $request->input('password')], true);
            }

            if ($authenticated) {
                $row = \Auth::user();

                if (empty($row) || empty($row->username)) {
                    return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Your username/password combination was incorrect')->withInput();
                }
                $disable_customer_login = get_admin_setting('disable_customer_login');

                $account = dbgetaccount($row->account_id);
                if ($row->account_id != 1 && $disable_customer_login) {
                    return Redirect::back()->with('status', 'error')->with('message', 'Erp access disabled');
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

                if ($row->is_deleted) {
                    \Auth::logout();

                    return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Your Account is suspended. Please contact support.')->withInput();
                } elseif ($row->active == '0') {
                    \Auth::logout();

                    return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Your Account is suspended. Please contact support.')->withInput();
                } elseif ($row->active == '2') {

                    // Blocked users
                    \Auth::logout();

                    return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Your Account is blocked.')->withInput();
                } elseif ($row->active == '1') {
                    $account = dbgetaccount($row->account_id);

                    if (empty($account)) {
                        \Auth::logout();

                        return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Account not found.')->withInput();
                    }

                    if ($account->status == 'Deleted') {
                        \Auth::logout();

                        return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Account not found.')->withInput();
                    }

                    //   if ('Disabled' == $account->status) {
                    //       \Auth::logout();
                    //       return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Account disabled. Please contact the admin or make a payment to Enable your account.')->withInput();
                    //   }

                    $reseller = dbgetaccount($account->partner_id);
                    if (empty($reseller) || $reseller->status == 'Deleted') {
                        \Auth::logout();

                        return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Invalid Reseller Account.')->withInput();
                    }

                    $group_exists = \DB::table('erp_user_roles')->where('id', $row->role_id)->count();
                    if (! $group_exists) {
                        \Auth::logout();

                        return Redirect::to('/user/login')->with('status', 'error')->with('message', 'Invalid Access.')->withInput();
                    }

                    \DB::table('erp_users')->where('id', '=', $row->id)->update(['last_login' => date('Y-m-d H:i:s')]);

                    if ($account->status != 'Deleted') {

                        /* Set Lang if available */
                        if (! is_null($request->input('language'))) {
                            $session['lang'] = $request->input('language');
                        } else {
                            $session['lang'] = isset($this->config) ? $this->config['cnf_lang']: '';
                        }

                        $user_group = dbgetcell('erp_user_roles', 'id', $row->role_id, 'name');

                        $result = set_session_data($row->id);
                        if ($result !== true) {
                            return $result;
                        }
                    }

                    return Redirect::to('/');
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

    private function sendPhoneNumberOTP($user, $hashkey = '')
    {
        try {
            $verification_code = mt_rand(100000, 999999);

            \DB::connection('default')->table('erp_users')->where('id', $user->id)->update(['verification_code' => $verification_code]);

            if ($hashkey == '') {

                $result = queue_sms(1, $user->phone, 'ERP Verification Code: '.$verification_code, 1, 1);
            } else {

                $result = queue_sms(1, $user->phone, '<#> ERP Verification Code: '.$verification_code.' '.$hashkey, 1, 1);
            }

            ob_end_clean();

            return true;
        } catch (\Throwable $ex) {
            exception_log($ex);

            return false;
        }
    }

    public function getLogout()
    {

        if ((session('original_role_id') == session('role_id')) || empty(session('role_id')) || empty(session('parent_id'))) { // logout

            timesheet_out();
            $currentLang = \Session::get('lang');
            \Auth::logout();
            \Session::flush();
            \Session::put('lang', $currentLang);

            return Redirect::to('/');
        } elseif (session('parent_id') != 1 && session('original_role_level') == 'Admin') { //admin - customer level to reseller level

            $account_id = session('parent_id');

            return Redirect::to('user/loginas/'.$account_id);
        } else {

            $result = set_session_data(session('user_id'), session('original_account_id'));
            if ($result !== true) {
                return $result;
            }

            return Redirect::to('/');
        }

        return Redirect::to('/');
    }

    public function getLoginAs($account_id = 0, $redirect_to = '/')
    {
        session()->forget('pbx_account_id');
        $result = set_session_data(session('user_id'), $account_id);
        if ($result !== true) {
            return $result;
        }

        return Redirect::to($redirect_to);
    }

    public function postSavepassword(Request $request)
    {
        $rules = [
            'password' => 'required|between:6,12',
            'password_confirmation' => 'required|between:6,12',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->passes()) {
            $user = \App\Models\User::find(\Session::get('user_id'));
            $user->password = \Hash::make($request->input('password'));
            $user->save();

            return Redirect::to('user/profile')->with('status', 'success')->with('message', 'Password has been saved!');
        } else {
            return Redirect::to('user/profile')->with('status', 'error')->with('message', 'The following errors occurred')->withErrors($validator)->withInput();
        }
    }

    public function postReset(Request $request)
    {
        $rules = [
            'reset_username' => 'required',
            '_token' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if (empty($request->input('_token'))) {
            return Redirect::back()->with('status', 'error')->with('message', 'Invalid token.');
        }
        if ($validator->passes()) {
            $user = \App\Models\User::where('username', '=', $request->input('reset_username'))->get()->first();
            $account_exists = \DB::table('crm_accounts')->where('id', $user->account_id)->count();
            if (! $account_exists) {
                return Redirect::back()->with('status', 'error')->with('message', 'Account not found.');
            }
            $account_deleted = \DB::table('crm_accounts')->where('id', $user->account_id)->where('status', 'Deleted')->count();
            if ($account_deleted) {
                return Redirect::back()->with('status', 'error')->with('message', 'Account not found.');
            }
            if (! empty($user)) {
                $affectedRows = \App\Models\User::where('username', '=', $user->username)
                    ->update(['activation_reset' => $request->input('_token')]);
                $data = ['token' => $request->input('_token')];

                $subject = 'Password Reset Request';

                $account = dbgetaccount($user->account_id);
                if (! empty($account->phone) && is_numeric($request->input('reset_username'))) {
                    $portal = get_whitelabel_domain($account->partner_id);
                    $token = $data['token'];

                    $reset_link = $portal.'/user/reset/'.$token;
                    $username = $user->username;

                    $sms_message = 'Use this link to reset your password. Username: '.$username.' Reset Link: '.$reset_link;
                    $phone_number = valid_za_mobile_number($user->phone);
                    if (! $phone_number) {
                        $phone_number = valid_za_mobile_number($account->phone);
                        if (! $phone_number) {
                            return Redirect::back()->with('status', 'error')->with('message', 'Invalid phone number.');
                        }
                    }
                    queue_sms(1, $phone_number, $sms_message, 1, 1);

                    return Redirect::back()->with('status', 'success')->with('message', 'Password reset, please check your sms inbox.');
                } elseif (! empty($account->email) && ! is_numeric($request->input('reset_username'))) {
                    $portal = get_whitelabel_domain($account->partner_id);
                    $token = $data['token'];

                    $data['reset_link'] = '<a href="'.$portal.'/user/reset/'.$token.'" target="_blank" >Reset Password</a>';
                    $data['username'] = $user->username;
                    $data['portal'] = $portal;
                    $function_variables = get_defined_vars();
                    $data['internal_function'] = 'reset_password_token';
                    //$data['test_debug'] = 1;

                    $result = erp_process_notification($user->account_id, $data, $function_variables);

                    return Redirect::back()->with('status', 'success')->with('message', 'Password reset, please check your email.');
                }

                return Redirect::back()->with('status', 'error')->with('message', 'Invalid email or phone.');
            } else {
                return Redirect::back()->with('status', 'error')->with('message', 'Email address not found.');
            }
        } else {
            return Redirect::back()->with('status', 'error')->with('message', 'Invalid Username.');
        }
    }

    public function getReset($token = '')
    {
        if (\Auth::check()) {
            \Auth::logout();
            \Session::flush();
        }
        if ($token == '') {
            return Redirect::back()->with('status', 'error')->with('message', 'Invalid Token');
        }
        $user = \App\Models\User::where('activation_reset', '=', $token);
        if ($user->count() >= 1) {
            $this->data['verCode'] = $token;

            $partner_id = \DB::connection('default')->table('crm_account_partner_settings')->where('whitelabel_domain', $_SERVER['HTTP_HOST'])->pluck('account_id')->first();
            if ($partner_id) {
                $reseller = dbgetaccount($partner_id);
                $this->data['logo'] = get_partner_logo($partner_id);
                $this->data['menu_name'] = 'Login - '.$reseller->company;
            } else {
                $this->data['menu_name'] = 'Login - CloudTools';
                $this->data['logo'] = get_partner_logo();
            }

            return view('_auth.remind', $this->data);
        } else {
            return Redirect::to('user/login')->with('status', 'error')->with('message', 'Reset code not found.');
        }
    }

    public function postDoreset(Request $request, $token = '')
    {
        if ($token == '') {
            return Redirect::back()->with('status', 'error')->with('message', 'Invalid Token');
        }
        $rules = [
            'password' => 'required|between:6,12|confirmed',
            'password_confirmation' => '',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->passes()) {
            $user = \App\Models\User::where('activation_reset', '=', $token);
            if ($user->count() >= 1) {
                $data = $user->get();
                $user = \App\Models\User::find($data[0]->id);

                $user->activation_reset = '';

                $user->password = \Hash::make($request->input('password'));

                $user->save();
            }

            return Redirect::to('user/login')->with('status', 'success')->with('message', 'Password has been reset!');
        } else {
            return Redirect::back()->with('status', 'error')->with('message', 'Fill all Fields');
        }
    }

    public function kernelList(\Illuminate\Contracts\Console\Kernel $kernel, \Illuminate\Console\Scheduling\Schedule $schedule)
    {
        if (is_superadmin()) {
            $kernel->schedule($schedule);
            foreach ($schedule->events() as $event) {

            }
        }
    }

    public function runHelper($function, $var1 = null, $var2 = null)
    {
        try {
            if (! empty(session('role_id')) && (check_access('1,31') || is_dev())) {
                if (isset($var1) && isset($var2)) {
                    $function($var1, $var2);
                } elseif (isset($var1)) {
                    $function($var1);
                } else {
                    $function();
                }
            } else {
                return Redirect::back();
            }
        } catch (\Throwable $ex) {
            exception_log($ex);

            return json_alert($ex->getMessage(), 'error');
        }
    }

    public function getPHPInfo()
    {
        if (check_access('1,31')) {
            phpinfo();
        }
    }

    public function postkendomail($email, Request $request)
    {
        $pdf_data = base64_decode($request->base64);
        if ($request->email) {
            $email = $request->email;
        } else {
            $email = \Auth::user()->id;
        }
        directmail($email, 'Document', 'Please see attached document.', '', '', $pdf_data);
        echo 'Email sent to '.$email;
    }

    public function getActivation(Request $request)
    {
        $num = $request->input('code');
        if ($num == '') {
            return Redirect::to('user/login')->with('message', 'Invalid Code Activation!')->with('status', 'error');
        }

        $user = \App\Models\User::where('activation_reset', '=', $num)->get();
        if (count($user) >= 1) {
            \DB::table('erp_users')->where('activation_reset', $num)->update(['active' => 1, 'activation_reset' => '']);

            return Redirect::to('user/login')->with('message', 'Your account is active now, you may log in.')->with('status', 'success');
        } else {
            return Redirect::to('user/login')->with('message', 'Invalid Code Activation!')->with('status', 'error');
        }
    }
}
