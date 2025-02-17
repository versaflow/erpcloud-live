<?php




function button_statement_make_payment($request)
{
    $data['customer'] = dbgetaccount(session('account_id'));
    $data['reseller'] = dbgetaccount($data['customer']->partner_id);
    $data['amount'] = 100;
    $webform_data = [];
    $webform_data['module_id'] = 390;
    $webform_data['account_id'] = session('account_id');

    $link_data = \Erp::encode($webform_data);
    $data['debit_order_link'] = request()->root().'/webform/'.$link_data;

    return view('__app.components.pages.make_payment', $data);
}

function button_payments_make_payment($request)
{
    $data['customer'] = dbgetaccount(session('account_id'));
    $data['reseller'] = dbgetaccount($data['customer']->partner_id);
    $data['amount'] = 100;
    $webform_data = [];
    $webform_data['module_id'] = 390;
    $webform_data['account_id'] = session('account_id');

    $link_data = \Erp::encode($webform_data);
    $data['debit_order_link'] = request()->root().'/webform/'.$link_data;

    return view('__app.components.pages.make_payment', $data);
}

function button_payments_instant_payment($request)
{
    if (1 != session('parent_id')) {
        return json_alert('No Access', 'warning');
    }

    $url = generate_paynow_link(session('account_id'));

    return Redirect::to($url);
}

function generate_paynow_app_link($account_id, $amount, $app_key, $api_token)
{
    $account = dbgetaccount($account_id);

    if (empty($account) || 1 != $account->partner_id || ('customer' != $account->type && 'reseller' != $account->type) || 'Deleted' == $account->status) {
        return false;
    }

    $user = \DB::table('erp_users')->where('account_id', $account_id)->get()->first();
    if (empty($user)) {
        return false;
    }

    if (!$amount) {
        $balance = get_debtor_balance($account_id);
        if ($balance > 0) {
            $amount = abs($balance);
        } else {
            $amount = 200;
        }
    }
    $data = ['account_id' => $account_id, 'amount' => $amount, 'app_key' => $app_key, 'api_token' => $api_token];

    $encoded_link = url('/paynow').'/'.\Erp::encode($data);

    return $encoded_link;
}

function generate_paynow_link($account_id, $amount = null, $code_only = false, $app_payment = false)
{
    $account = dbgetaccount($account_id);

    if (empty($account) || 1 != $account->partner_id || ('customer' != $account->type && 'reseller' != $account->type) || 'Deleted' == $account->status) {
        //    return false;
    }

    $user = \DB::table('erp_users')->where('account_id', $account_id)->get()->first();
    if (empty($user)) {
        //   return false;
    }

    if (!$amount) {
        $balance = get_debtor_balance($account_id);
        if ($balance > 0) {
            $amount = (abs($balance) < 200) ? 200 : abs($balance);
        } else {
            $amount = 200;
        }
    }
    $data = ['account_id' => $account_id, 'amount' => $amount];
    if ($app_payment) {
        $data['app_payment'] = 1;
    }
    if ($code_only) {
        $encoded_link = \Erp::encode($data);
    } else {
        $encoded_link = url('/payment_options').'/'.\Erp::encode($data);
    }

    $encoded_link = str_replace('https://portal.telecloud.co.za',\Config::get('app.url'),$encoded_link);
    
    return $encoded_link;
}

function generate_paynow_button($account_id, $amount = null, $code_only = false, $app_payment = false){
    $paynow_link = generate_paynow_link($account_id, $amount, $code_only, $app_payment);
    $paynow_button = '';
    
    $remove_payment_options = \DB::table('erp_admin_settings')->where('id', 1)->pluck('remove_payment_options')->first();
    if ($paynow_link && !$remove_payment_options) {
        if(session('instance')->directory == 'moviemagic'){
            $paynow_text = 'Subscribe Now'; 
            $paynow_button .= 'If you are happy with our service, click here to subscribe.';
        }else{
            $paynow_text = 'Pay Now';    
        }
        $paynow_button .= '<br><br><a style="padding: 6px !important;background: #2196f3 !important;color: #ffffff !important;font-family: Tahoma, sans-serif !important;font-size: 12px !important;font-weight: 600 !important;line-height: 120% !important;margin: 0 !important;text-decoration: none !important;text-transform: none !important;" href="'.$paynow_link.'">'.$paynow_text.'</a><br><br>';
    }
    return $paynow_button;
}

function generate_payfast_email_link($account_id, $amount = null, $code_only = false, $app_payment = false)
{
    $m_payment_id = $account_id.'_'.date('U');
    $btn = '<a href="https://www.payfast.co.za/eng/process?cmd=_paynow&receiver=10000100&item_name=Cloud+Telecoms+Services&m_payment_id='.$m_payment_id.'&custom_int1='.$account_id.'&email_confirmation=1&confirmation_address=accounts@telecloud.co.za&return_url=https://portal.telecloud.co.za/payfast_return&cancel_url=https://portal.telecloud.co.za/payfast_cancel&notify_url=https://portal.telecloud.co.za/payfast_notify&amount='.currency($amount).'">Pay Now</a>';
    return $btn;
}

function generate_sandboxpayfast_email_link($account_id, $amount = null, $code_only = false, $app_payment = false)
{
    $m_payment_id = $account_id.'_'.date('U');
    $btn = '<a href="https://sandbox.payfast.co.za/eng/process?cmd=_paynow&receiver=10000100&item_name=Cloud+Telecoms+Services&m_payment_id='.$m_payment_id.'&custom_int1='.$account_id.'&email_confirmation=1&confirmation_address=accounts@telecloud.co.za&return_url=https://portal.telecloud.co.za/payfast_return&cancel_url=https://portal.telecloud.co.za/payfast_cancel&notify_url=https://portal.telecloud.co.za/payfast_notify&amount='.currency($amount).'">Pay Now</a>';
    return $btn;
}

function decode_paynow_link($encoded_link)
{
    $data = \Erp::decode($encoded_link);

    if (!is_array($data) || 2 != count($data)) {
        return false;
    }

    $account = dbgetaccount($data['account_id']);
    if (empty($account)) {
        return false;
    }

    return $data;
}


function get_orders_total($account_id)
{
    $order_total = \DB::table('crm_documents')->where('doctype', 'Order')->where('account_id', $account_id)->sum('total');
    return $order_total;
}
