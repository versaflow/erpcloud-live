<?php

function provision_virtual_server_form($provision, $input, $product, $customer)
{
   
    $ip_addr = (!empty($input['ip_addr'])) ? $input['ip_addr'] : '';
 
    $server_username = (!empty($input['server_username'])) ? $input['server_username'] : '';
    $server_pass = (!empty($input['server_pass'])) ? $input['server_pass'] : '';
    $server_os = (!empty($input['server_os'])) ? $input['server_os'] : $customer->server_os;
    $date_activated = (!empty($input['date_activated'])) ? $input['date_activated'] : '';
    $admin_url = (!empty($input['admin_url'])) ? $input['admin_url'] : '';
    $admin_username = (!empty($input['admin_username'])) ? $input['admin_username'] : '';
    $admin_password = (!empty($input['admin_password'])) ? $input['admin_password'] : '';


    $form .= '<input placeholder="IP address" name="ip_addr" id="ip_addr" type="text" value="'.$ip_addr.'" ><br>';

  
    $form .= '<input placeholder="VM Username" name="server_username" id="server_username" type="text" value="'.$server_username.'" ><br>';
    $form .= '<input placeholder="VM Password" name="server_pass" id="server_pass" type="text" value="'.$server_pass.'" ><br>';
    
    $form .= '<input placeholder="Server OS" name="server_os" id="server_os" type="text" value="'.$server_os.'" ><br>';
   
    $form .= '<h6 class="mt-2">Management</h6>';
    $form .= '<input placeholder="Admin URL" name="admin_url" id="admin_url" type="text" value="http://vm.cloudtools.co.za:8117/" readonly="readonly"><br>';
    $form .= '<input placeholder="Admin Username" name="admin_username" id="admin_username" type="text" value="'.$admin_username.'" ><br>';
    $form .= '<input placeholder="Admin Password" name="admin_password" id="admin_password" type="text" value="'.$admin_password.'" ><br>';
    $form .= '<h6 class="mt-2">Date Activated</h6>';
    
    $form .= '<input class="text-date" name="date_activated" id="date_activated" type="text" value="'.$date_activated.'" ><br>';

    return $form;
}

function provision_number_porting_form($provision, $input, $product, $customer)
{
    $form = '';
    $phone_number = (!empty($input['phone_number'])) ? $input['phone_number'] : '';
    $number_routing = (!empty($input['number_routing'])) ? $input['number_routing'] : '';

    $submitted_number = \DB::connection('default')->table('sub_forms_number_porting')->where('subscription_id',$provision->id)->pluck('number_to_port')->first();
    $form .= '<div class="col"><label for="phone_number">Number submitted by customer: '.$submitted_number.'</label></div>';
    $form .= '<div class="col-4"><label for="phone_number">Enter number to port (format 27)</label></div>';
    $form .= '<div class="col-8"><input type="text" id="phone_number" name="phone_number" value="'.$phone_number.'"  placeholder="Phone number to port"></div>';
    if (!empty($customer->domain_uuid)) {
        $row = ['domain_uuid' => $customer->domain_uuid];
        $routing_options = number_routing_select($row);
    } else {
        $routing_options = [];
    }

    if (!empty($routing_options) && count($routing_options) > 0) {
        $form .= '<div class="col-4"><label for="number_routing">Number Routing</label></div>';
        $form .= '<div class="col-8"><select id="number_routing" name="number_routing" >';

        foreach ($routing_options as $key => $value) {
            $selected = ($number_routing == $key) ? 'selected="selected"' : '';
            $form .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
        }

        $form .= '</select></div>';
    }

    return $form;
}

function provision_phone_number_form($provision, $input, $product, $customer)
{
    $form = '';

    $phone_number = (!empty($input['phone_number'])) ? $input['phone_number'] : '';
    $number_routing = (!empty($input['number_routing'])) ? $input['number_routing'] : '';
    $domain_uuids = \DB::connection('pbx')->table('v_domains')->pluck('domain_uuid')->toArray();
    \DB::connection('pbx')->table('p_phone_numbers')->whereNotIn('domain_uuid', $domain_uuids)->update(['domain_uuid' => null]);

    $cost_calculation = \DB::connection('pbx')->table('v_domains')->where('account_id',$customer->id)->pluck('cost_calculation')->first();
    if($cost_calculation == 'volume'){
        $gateway_uuids = \DB::connection('pbx')->table('v_gateways')->where('enabled', 'true')->pluck('gateway_uuid')->toArray();
    }else{
        $gateway_uuids = \DB::connection('pbx')->table('v_gateways')->where('allow_provision_numbers', 1)->where('enabled', 'true')->pluck('gateway_uuid')->toArray();
    }
    $gateways = \DB::connection('pbx')->table('v_gateways')->select('gateway_uuid','gateway')->whereIn('gateway_uuid', $gateway_uuids)->get();
    if (127 == $product->id) {
        $pbx_numbers = \DB::connection('pbx')->table('p_phone_numbers')->where('status', 'Enabled')
            ->select('number', 'prefix','gateway_uuid')
            ->where('is_spam',0)
            ->where('number', 'LIKE', '2787%')->whereNull('domain_uuid')
            ->whereIn('gateway_uuid', $gateway_uuids)
            ->orderby('number')->get();
    }

    if (176 == $product->id) {
        $pbx_numbers = \DB::connection('pbx')->table('p_phone_numbers')->where('status', 'Enabled')
            ->select('number', 'prefix','gateway_uuid')
            ->where('is_spam',0)
            ->where('number', 'NOT LIKE', '2786%')->where('number', 'LIKE', '%786%')->whereNull('domain_uuid')
            ->whereIn('gateway_uuid', $gateway_uuids)
            ->orderby('number')->get();
    }

    if (128 == $product->id) {
        $pbx_numbers = \DB::connection('pbx')->table('p_phone_numbers')->where('status', 'Enabled')
            ->select('number', 'prefix','gateway_uuid')
            ->where('is_spam',0)
            ->where('number', 'NOT LIKE', '2787%')->whereNull('domain_uuid')
            ->whereIn('gateway_uuid', $gateway_uuids)
            ->orderby('number')->get();
    }

    if (empty($pbx_numbers)) {
        return 'No phone numbers available';
    } else {
        $pbx_numbers_count = $pbx_numbers->count();
        if ($pbx_numbers_count == 0) {
            return 'No phone numbers available';
        }
    }
    $prefix_ds = [];
    $prefixes = collect($pbx_numbers)->pluck('prefix')->unique()->toArray();
    $pbx_numbers_arr = collect($pbx_numbers)->pluck('number')->toArray();

    if (!empty($phone_number)) {
        $pbx_numbers_arr[] = $phone_number;
        $prefix_val = substr($phone_number, 0, 4);
    }
    $form .= '<label for="prefix">Choose a prefix</label><select id="prefix" >';
    foreach ($prefixes as $prefix) {
        $prefix_key = '27'.substr($prefix, 1);

        $selected = '';
        if (!empty($prefix_val) && $prefix_val == $prefix_key) {
            $selected = 'selected="selected"';
        }
        $prefix_ds[$prefix_key] = collect($pbx_numbers)->where('prefix', $prefix)->all();
        $form .= '<option value="'.$prefix_key.'" '.$selected.'>'.$prefix.'</option>';
    }
    $form .= '</select>';

    $form .= '<div id="numberdiv"><label for="phone_number">Choose a number</label>';
    $form .= '<input id="phone_number" name="phone_number" />';


    if (session('role_level') == 'Admin') {
        $form .= '<br><br><label>Test ringing on all phone numbers</label><br><button class="e-btn e-flat e-small testphonebtn" id="testfixedtelkom" type="button" disabled="disabled">Test Fixed Telkom</button>';
        $form .= '<button class="e-btn e-flat e-small testphonebtn" id="testmobiletelkom" type="button" disabled="disabled">Test Mobile Telkom</button>';
        $form .= '<button class="e-btn e-flat e-small testphonebtn" id="testvodacom" type="button" disabled="disabled">Test Vodacom</button>';
        $form .= '<button class="e-btn e-flat e-small testphonebtn" id="testmtn" type="button" disabled="disabled">Test MTN</button>';
        $form .= '<button class="e-btn e-flat e-small testphonebtn" id="testcellc" type="button" disabled="disabled">Test CellC</button><br><br>';
    }

    if (!empty($customer->domain_uuid)) {
        $row = ['domain_uuid' => $customer->domain_uuid];
        $routing_options = number_routing_select($row);
    } else {
        $routing_options = [];
    }

    if (!empty($routing_options) && count($routing_options) > 0) {
        $form .= '<label for="number_routing">Number Routing</label>';
        $form .= '<select id="number_routing" name="number_routing" >';

        foreach ($routing_options as $key => $value) {
            $selected = ($number_routing == $key) ? 'selected="selected"' : '';
            $form .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
        }

        $form .= '</select>';
    }
    $form .= '<script>';
    foreach ($prefix_ds as $key => $arr) {
        $form .= '
        window["prefixds'.$key.'"] = [';
        $form .= '{number: "",display_number: ""},';
        foreach ($arr as $n) {
            $display_number = (session('role_level') == 'Admin') ? $n->number.' '.$gateways->where('gateway_uuid',$n->gateway_uuid)->pluck('gateway')->first() : $n->number;
            $form .= '{number: "'.$n->number.'",display_number: "'.$display_number.'"},';
        }
        if (!empty($phone_number)) {
            if (str_starts_with($phone_number, $key)) {
                $form .= '{number: "'.$phone_number.'"},';
            }
        }
        $form .= '];
        ';
    }
    if (!empty($phone_number)) {
        $form .= '
        window["selected_number"] = "'.$phone_number.'";
        ';
    }
    $form .= '</script>';

    return $form;
}

function provision_pbx_extension_form($provision, $input, $product, $customer)
{
    $form = '';

    $email = (!empty($customer->email)) ? $customer->email : '';
    $mobile = (!empty($customer->phone)) ? $customer->phone : '';

    $form .= '<label for="email">Email</label>';
    $form .= '<input type="text" id="email" name="email" value="'.$email.'" placeholder="Email" /><br>';

    $form .= '<label for="mobile">Mobile</label>';
    $form .= '<input type="text" id="mobile" name="mobile" value="'.$mobile.'" placeholder="Mobile" /><br>';

    $form .= '<label for="mobile"> Unlimited Mobile App Mobile Number</label>';
    $form .= '<input type="text" id="mobile_app_number" name="mobile_app_number" value="" placeholder="Unlimited Mobile App Mobile Number" /><br>';

    return $form;
}

function provision_sip_trunk_form($provision, $input, $product, $customer)
{
    $form = '';

    $email = (!empty($customer->email)) ? $customer->email : '';
    $mobile = (!empty($customer->phone)) ? $customer->phone : '';

    $form .= '<label for="email">Email</label>';
    $form .= '<input type="text" id="email" name="email" value="'.$email.'" placeholder="Email" /><br>';

    $form .= '<label for="mobile">Mobile</label>';
    $form .= '<input type="text" id="mobile" name="mobile" value="'.$mobile.'" placeholder="Mobile" /><br>';

    $form .= '<label for="mobile"> Unlimited Mobile App Mobile Number</label>';
    $form .= '<input type="text" id="mobile_app_number" name="mobile_app_number" value="" placeholder="Unlimited Mobile App Mobile Number" /><br>';

    $form .= '<label for="cidr">Authentication IP (Optional)</label>';
    $form .= '<input type="text" id="cidr" name="cidr" value="" placeholder="Authentication IP" /><br>';

    return $form;
}

function provision_pbx_extension_recording_form($provision, $input, $product, $customer)
{
    $form = '';

    // prepopulate fields with existing values

    $extension = (!empty($input['extension'])) ? $input['extension'] : '';
    $extensions = \DB::table('sub_services')
        ->where(['account_id' => $provision->account_id, 'status' => 'Enabled', 'provision_type' => 'pbx_extension'])
        ->get();

    if (empty($extensions)) {
        return 'No Extension available to add recording addon';
    }

    $form .= '<select id="extension" name="extension" >';

    foreach ($extensions as $ext) {
        $selected = ($extension == $ext->detail) ? 'selected="selected"' : '';
        $form .= '<option value="'.$ext->detail.'" '.$selected.'>'.$ext->detail.'</option>';
    }

    $form .= '</select>';

    return $form;
}

function provision_lte_sim_card_form($provision, $input, $product, $customer)
{
    $form = '';
    // prepopulate fields with existing values
    $msisdn = (!empty($input['msisdn'])) ? $input['msisdn'] : '';
    $form .= '<input type="text" id="msisdn" name="msisdn" value="'.$msisdn.'" placeholder="MSISDN Number" /><br>';

    return $form;
}


function provision_save_lte_number_form($provision, $input, $product, $customer)
{
    $form = '';
    // prepopulate fields with existing values
    $mobile_number = (!empty($input['phone_number'])) ? $input['phone_number'] : '';
    $form .= '<input type="text" id="phone_number" name="phone_number" value="'.$mobile_number.'" placeholder="Mobile Number" /><br>';

    return $form;
}

function provision_sitebuilderaddon_form($provision, $input, $product, $customer)
{
    $domain_list = [];
    $domains = \DB::table('sub_services')->where('account_id', $customer->id)->where('status', '!=', 'Deleted')->where('provision_type', 'hosting')->pluck('detail')->toArray();
    if (count($domains) > 0) {
        foreach ($domains as $domain) {
            $exists = \DB::table('sub_services')->where('account_id', $customer->id)->where('status', '!=', 'Deleted')
            ->where('provision_type', 'sitebuilder')->where('detail', $domain)->count();
            if (!$exists) {
                $domain_list[] = $domain;
            }
        }
    }
    if (count($domain_list) == 0) {
        $form = 'No available domains to install sitebuilder. Please order and provision a new hosting account.';
    } else {
        $form = '
        <p> Select domain to install sitebuilder. </p>
        <select id="domain_name" name="domain_name" required>';
        foreach ($domain_list as $domain_name) {
            $form .= '<option value="'.$domain_name.'" >'.$domain_name.'</option>';
        }
        $form .= '</select>';
    }

    return $form;
}

function provision_sitebuilder_form($provision, $input, $product, $customer)
{
    $form = "<div id='hosting_tabs'>";
    // prepopulate fields with existing values
    $register_selected = '';
    $transfer_selected = '';
    if (!empty($input['domain_action'])) {
        $register_selected = ('Register' == $input['domain_action']) ? 'selected="selected"' : '';
        $transfer_selected = ('Transfer' == $input['domain_action']) ? 'selected="selected"' : '';
    }
    $domain_name = (!empty($input['domain_name'])) ? $input['domain_name'] : '';
    $domain_epp = (!empty($input['domain_epp'])) ? $input['domain_epp'] : '';
    $email_address_1 = (!empty($input['email_address_1'])) ? $input['email_address_1'] : '';
    $email_address_2 = (!empty($input['email_address_2'])) ? $input['email_address_2'] : '';
    $email_address_3 = (!empty($input['email_address_3'])) ? $input['email_address_3'] : '';
    $email_password_1 = (!empty($input['email_password_1'])) ? $input['email_password_1'] : '';
    $email_password_2 = (!empty($input['email_password_2'])) ? $input['email_password_2'] : '';
    $email_password_3 = (!empty($input['email_password_3'])) ? $input['email_password_3'] : '';
    $ftp_account = (!empty($input['ftp_account'])) ? $input['ftp_account'] : '';
    $ftp_password = (!empty($input['ftp_password'])) ? $input['ftp_password'] : '';

    $form .= '
    <div class="e-tab-header">    
       <div>Domain</div> 
       <div>Email Addresses</div>
       <div>FTP Account</div>
    </div>
    <div class="e-content">';

    $form .= '
    <div>
    <input placeholder="Domain Name" name="domain_name" id="domain_name" type="text" value="'.$domain_name.'" required><br>';

    $form .= '
    <select id="domain_action" name="domain_action" required>
    <option '.$register_selected.'>Register</option>
    <option '.$transfer_selected.'>Transfer</option>
    </select><br>';
    $form .= '
    <div id="epp_div" style="display:none;">
    <p>EPP Key is required for transfers of top-level domains eg: .com , .net, .org</p>
    <input type="text" id="domain_epp" name="domain_epp" value="'.$domain_epp.'" placeholder="EPP Key"  >
    </div>';
    $form .= '
    </div>
    <div><br>';
    $form .= '<input placeholder="Email Address 1" name="email_address_1" id="email_address_1" type="text" value="'.$email_address_1.'" ><br>';
    $form .= '<input placeholder="Email Password 1" name="email_password_1" id="email_password_1" type="text" value="'.$email_password_1.'" ><br>';
    $form .= '<input placeholder="Email Address 2" name="email_address_2" id="email_address_2" type="text" value="'.$email_address_2.'" ><br>';
    $form .= '<input placeholder="Email Password 2" name="email_password_2" id="email_password_2" type="text" value="'.$email_password_2.'" ><br>';
    $form .= '<input placeholder="Email Address 3" name="email_address_3" id="email_address_3" type="text" value="'.$email_address_3.'" ><br>';
    $form .= '<input placeholder="Email Password 3" name="email_password_3" id="email_password_3" type="text" value="'.$email_password_3.'" ><br>';
    $form .= '
    </div>
    <div><br>';
    $form .= '<input placeholder="FTP Account" name="ftp_account" id="ftp_account" type="text" value="'.$ftp_account.'" ><br>';
    $form .= '<input placeholder="FTP Password" name="ftp_password" id="ftp_password" type="text" value="'.$ftp_password.'" ><br>';
    $form .= '
    </div>
    </div>
    </div>';
    $form .= '
    <script>
    var tabObj = new ej.navigations.Tab();
    
    //Render initialized Tab component
    tabObj.appendTo("#hosting_tabs");
    </script>';
    return $form;
}

function provision_hosting_form($provision, $input, $product, $customer)
{
    $form = "<div id='hosting_tabs'>";
    // prepopulate fields with existing values
    $register_selected = '';
    $transfer_selected = '';
    if (!empty($input['domain_action'])) {
        $register_selected = ('Register' == $input['domain_action']) ? 'selected="selected"' : '';
        $transfer_selected = ('Transfer' == $input['domain_action']) ? 'selected="selected"' : '';
    }
    $domain_name = (!empty($input['domain_name'])) ? $input['domain_name'] : '';
    $domain_epp = (!empty($input['domain_epp'])) ? $input['domain_epp'] : '';
    $email_address_1 = (!empty($input['email_address_1'])) ? $input['email_address_1'] : '';
    $email_address_2 = (!empty($input['email_address_2'])) ? $input['email_address_2'] : '';
    $email_address_3 = (!empty($input['email_address_3'])) ? $input['email_address_3'] : '';
    $email_password_1 = (!empty($input['email_password_1'])) ? $input['email_password_1'] : '';
    $email_password_2 = (!empty($input['email_password_2'])) ? $input['email_password_2'] : '';
    $email_password_3 = (!empty($input['email_password_3'])) ? $input['email_password_3'] : '';
    $ftp_account = (!empty($input['ftp_account'])) ? $input['ftp_account'] : '';
    $ftp_password = (!empty($input['ftp_password'])) ? $input['ftp_password'] : '';

    $form .= '
    <div class="e-tab-header">    
       <div>Domain</div> 
       <div>Email Addresses</div>
       <div>FTP Account</div>
    </div>
    <div class="e-content">';

    $form .= '
    <div>
    <input placeholder="Domain Name" name="domain_name" id="domain_name" type="text" value="'.$domain_name.'" required><br>';

    $form .= '
    <select id="domain_action" name="domain_action" required>
    <option '.$register_selected.'>Register</option>
    <option '.$transfer_selected.'>Transfer</option>
    </select><br>';
    $form .= '
    <div id="epp_div" style="display:none;">
    <p>EPP Key is required for transfers of top-level domains eg: .com , .net, .org</p>
    <input type="text" id="domain_epp" name="domain_epp" value="'.$domain_epp.'" placeholder="EPP Key"  >
    </div>';
    $form .= '
    </div>
    <div><br>';
    $form .= '<input placeholder="Email Address 1" name="email_address_1" id="email_address_1" type="text" value="'.$email_address_1.'" ><br>';
    $form .= '<input placeholder="Email Password 1" name="email_password_1" id="email_password_1" type="text" value="'.$email_password_1.'" ><br>';
    $form .= '<input placeholder="Email Address 2" name="email_address_2" id="email_address_2" type="text" value="'.$email_address_2.'" ><br>';
    $form .= '<input placeholder="Email Password 2" name="email_password_2" id="email_password_2" type="text" value="'.$email_password_2.'" ><br>';
    $form .= '<input placeholder="Email Address 3" name="email_address_3" id="email_address_3" type="text" value="'.$email_address_3.'" ><br>';
    $form .= '<input placeholder="Email Password 3" name="email_password_3" id="email_password_3" type="text" value="'.$email_password_3.'" ><br>';
    $form .= '
    </div>
    <div><br>';
    $form .= '<input placeholder="FTP Account" name="ftp_account" id="ftp_account" type="text" value="'.$ftp_account.'" ><br>';
    $form .= '<input placeholder="FTP Password" name="ftp_password" id="ftp_password" type="text" value="'.$ftp_password.'" ><br>';
    $form .= '
    </div>
    </div>
    </div>';
    $form .= '
    <script>
    var tabObj = new ej.navigations.Tab();
    
    //Render initialized Tab component
    tabObj.appendTo("#hosting_tabs");
    </script>';
    return $form;
}

function provision_fibre_addon_form($provision, $input, $product, $customer)
{
    $form = '';

    $fibre_username = (!empty($input['fibre_username'])) ? $input['fibre_username'] : '';
    $fibre_accounts = \DB::table('sub_services')->where('account_id', $provision->account_id)->where('status', 'Enabled')->where('provision_type', 'fibre')->get();
    if (empty($fibre_accounts)) {
        return 'Order Fibre to provision';
    }

    $form .= '<select id="fibre_username" name="fibre_username" >';

    foreach ($fibre_accounts as $fibre_account) {
        $selected = ($fibre_username == $fibre_account->detail) ? 'selected="selected"' : '';
        $form .= '<option value="'.$fibre_account->detail.'" '.$selected.'>'.$fibre_account->detail.'</option>';
    }

    $form .= '</select>';

    return $form;
}

function provision_airtime_prepaid_form($provision, $input, $product, $customer)
{
    return '<p>Provision Airtime Prepaid</p>';
}

function provision_airtime_postpaid_form($provision, $input, $product, $customer)
{
    return '<p>Provision Airtime Prepaid</p>';
}

function provision_airtime_contract_form($provision, $input, $product, $customer)
{
    return '<p>Provision Airtime Contract</p>';
}

function provision_airtime_unlimited_form($provision, $input, $product, $customer)
{
    return '<p>Provision Airtime Contract</p>';
}

function provision_bulk_sms_prepaid_form($provision, $input, $product, $customer)
{
    return '<p>Provision Bulk SMS</p>';
}


function provision_bulk_sms_form($provision, $input, $product, $customer)
{
    return '<p>Provision Bulk SMS</p>';
}

function provision_fibre_product_form($provision, $input, $product, $customer)
{
    $fibre_username = (!empty($input['fibre_username'])) ? $input['fibre_username'] : '';
    $fibre_password = (!empty($input['fibre_password'])) ? $input['fibre_password'] : '';
    $address = (!empty($input['address'])) ? $input['address'] : '';
    $b_number = (!empty($input['b_number'])) ? $input['b_number'] : '';
    $full_name = (!empty($input['full_name'])) ? $input['full_name'] : $customer->contact;
    $phone_number = (!empty($input['phone_number'])) ? $input['phone_number'] : $customer->phone;
    $line_speed = (!empty($input['line_speed'])) ? $input['line_speed'] : '';
    $coverage_address = \DB::table('crm_documents')->where('id',$provision->invoice_id)->pluck('coverage_address')->first();
    $form.="Coverage address: ".$coverage_address;

    $form .= '<input placeholder="Fibre Username" name="fibre_username" id="fibre_username" type="text" value="'.$fibre_username.'" ><br>';
    $form .= '<input placeholder="Fibre Password" name="fibre_password" id="fibre_password" type="text" value="'.$fibre_password.'" ><br>';

    $form .= '<input placeholder="Address" name="address" id="address" type="text" value="'.$address.'" ><br>';
    $form .= '<input placeholder="B Number" name="b_number" id="b_number" type="text" value="'.$b_number.'" ><br>';

    $form .= '<input placeholder="Full Name" name="full_name" id="full_name" type="text" value="'.$full_name.'" ><br>';
    $form .= '<input placeholder="Phone Number" name="phone_number" id="phone_number" type="text" value="'.$phone_number.'" ><br>';

    $form .= '<input placeholder="Line Speed" name="line_speed" id="line_speed" type="text" value="'.$line_speed.'" ><br>';
    return $form;
}

function provision_fibre_form($provision, $input, $product, $customer)
{
    /*
    // MAP FIELDS
    strAddress String(36) Fibre Line Owner Address
    strLatLong String(36) Fibre Line Installation coordinates

    // REQUIRED FIELDS
    strOwner String(36) Fibre Line Owner Name
    strCell String(36) Fibre Line Owner Cell
    strSuburb String(36) Fibre Line Owner Suburb
    strCity String(36) Fibre Line Owner City
    strCode String(36) Fibre Line Owner Postal Code
    strAddressType String(36) Retreived from the ’getAddressType’ function

    // OPTIONAL FIELDS
    strBuildingId String(36) Fibre Line Installation Building Number
    strFloorId String(36) Fibre Line Installation Floor
    strUnitNumber String(36) Fibre Line Installation Unit Number
    strBlockName String(36) Fibre Line Installation Block Name
    */
    $map_inputs = \DB::table('sub_activation_steps')->where('service_table', 'sub_activations')->where('provision_plan_id', 50)->where('provision_id', $provision->id)->pluck('input')->first();
    $map_inputs = json_decode($map_inputs, true);

    $axxess = new Axxess();
    $address_types = $axxess->getAddressTypes()->arrAddressTypes;


    $strAddress = (!empty($map_inputs['address-input'])) ? $map_inputs['address-input'] : '';
    $strLatLong = (!empty($map_inputs['latlong-input'])) ? $map_inputs['latlong-input'] : '';
    $strOwner = (!empty($input['strOwner'])) ? $input['strOwner'] : '';

    $strCell = (!empty($input['strCell'])) ? $input['strCell'] : '';
    $strSuburb = (!empty($input['strSuburb'])) ? $input['strSuburb'] : '';
    $strCity = (!empty($input['strCity'])) ? $input['strCity'] : '';
    $strCode = (!empty($input['strCode'])) ? $input['strCode'] : '';
    $strAddressType = (!empty($input['strAddressType'])) ? $input['strAddressType'] : '';

    $strBuildingId = (!empty($input['strBuildingId'])) ? $input['strBuildingId'] : '';
    $strFloorId = (!empty($input['strFloorId'])) ? $input['strFloorId'] : '';
    $strUnitNumber = (!empty($input['strUnitNumber'])) ? $input['strUnitNumber'] : '';
    $strBlockName = (!empty($input['strBlockName'])) ? $input['strBlockName'] : '';

    // GET VALUES FROM ACCOUNTS
    if (empty($strOwner)) {
        $strOwner = $customer->contact;
    }
    if (empty($strCell) && !empty($customer->phone)) {
        $validated_number  = '';
        try {
            $number = phone($customer->phone, ['Auto','ZA','US']);

            $number = $number->formatForMobileDialingInCountry('ZA');
        } catch (\Throwable $ex) {  exception_log($ex);
        }
        if (!empty($number)) {
            $strCell = $number;
        }
    }

    // REQUIRED FIELDS
    $form = '';
    $form .= '<input placeholder="Latitude/Longitude" name="strLatLong" id="strLatLong" type="hidden" value="'.$strLatLong.'" required disabled><br>';
    $form .= '<input placeholder="Full Name" name="strOwner" id="strOwner" type="text" value="'.$strOwner.'" required><br>';
    $form .= '<input placeholder="Cellphone Number" name="strCell" id="strCell" type="text" value="'.$strCell.'" required><br>';
    $form .= '<label for="strAddressType" >Address Type</label><select id="strAddressType" name="strAddressType"  required>';

    foreach ($address_types as $type) {
        $selected = ($strAddressType == $type) ? 'selected="selected"' : '';
        $form .= '<option value="'.$type.'" '.$selected.'>'.$type.'</option>';
    }

    $form .= '</select><br>';


    $form .= '<input placeholder="Address" name="strAddress" id="strAddress" type="text" value="'.$strAddress.'" required disabled><br>';
    $form .= '<input placeholder="Suburb" name="strSuburb" id="strSuburb" type="text" value="'.$strSuburb.'" required><br>';
    $form .= '<input placeholder="City" name="strCity" id="strCity" type="text" value="'.$strCity.'" required><br>';
    $form .= '<input placeholder="Postal Code" name="strCode" id="strCode" type="text" value="'.$strCode.'" required><br>';

    $form .= '<input placeholder="Building Number (optional)" name="strBuildingId" id="strBuildingId" type="text" value="'.$strBuildingId.'" ><br>';
    $form .= '<input placeholder="Floor Number (optional)" name="strFloorId" id="strFloorId" type="text" value="'.$strFloorId.'" required><br>';
    $form .= '<input placeholder="Unit Number (optional)" name="strUnitNumber" id="strUnitNumber" type="text" value="'.$strUnitNumber.'" ><br>';
    $form .= '<input placeholder="Block Name (optional)" name="strBlockName" id="strBlockName" type="text" value="'.$strBlockName.'" required><br>';

    return $form;
}

function provision_pbx_domain_form($provision, $input, $product, $customer)
{
    return '<p>Provision PBX Setup</p>';
}


function provision_unlimited_channel_form($provision, $input, $product, $customer)
{
    return '<p>Unlimited Channel</p>';
}

function provision_channel_partner_form($provision, $input, $product, $customer)
{
    return '<p>Partner Setup</p>';
}

function provision_iptv_reseller_form($provision, $input, $product, $customer)
{
    return '<p>Reseller Setup</p>';
    $iptv_username = (!empty($input['iptv_username'])) ? $input['iptv_username'] : '';
    $form = '<p>IPTV Reseller Setup</p>';
    $form .= '<div class="row mb-2">';
    $form .= '<div class="col">';
    $form .= '<label for="iptv_username" >Username</label>';
    
    $form .= '</div>';
    $form .= '<div class="col-auto">';
    $form .= '<input type="text" name="iptv_username" id="iptv_username" '.$iptv_username.' >';
    $form .= '</div>';
    $form .= '</div>';
    $form .= '<div class="row mb-2">';
    $form .= '<div class="col">';
    $form .= '<label for="iptv_password" >Password</label>';
    
    $form .= '</div>';
    $form .= '<div class="col-auto">';
    $form .= '<input type="text" name="iptv_password" id="iptv_password" '.$iptv_password.' >';
    $form .= '</div>';
    $form .= '</div>';
    return $form;
}


function provision_ip_range_gateway_form($provision, $input, $product, $customer)
{
    $ip_address = (!empty($input['ip_address'])) ? $input['ip_address'] : '';
    $gateway = (!empty($input['gateway'])) ? $input['gateway'] : '';
    $type = (!empty($input['type'])) ? $input['type'] : '';
  
    $loa_as_number = (!empty($input['loa_as_number'])) ? $input['loa_as_number'] : '';
    $company = (!empty($input['company'])) ? $input['company'] : '';
  
   
    $rkpi = (!empty($input['rkpi'])) ? 'checked="checked"' : '';
    $auth_letter = (!empty($input['auth_letter'])) ? 'checked="checked"' : '';
    $types = ['Tunnel','Route Object'];

    if (empty($product->provision_package)) {
        return json_alert('Subnet not set on provision api code field on product.', 'warning');
    }
    $ipranges = \DB::table('isp_data_ip_ranges')->where('account_id', 0)->where('subnet', $product->provision_package)->where('is_deleted', 0)->orderBy('ip_range')->get();

    if (empty($ipranges) || (is_array($ip_ranges) && count($ipranges) == 0)) {
        return json_alert('No IP ranges available for this subnet.', 'warning');
    }
    
    $form .= '<div class="container w-50">';
    $form .= '<div class="row mb-2">';
    $form .= '<div class="col">';
    $form .= '<label for="ip_address">IP Range</label>';
    
    $form .= '</div>';
    $form .= '<div class="col-auto">';
    $form .= '<select id="ip_address" name="ip_address"  required>';

    foreach ($ipranges as $iprange) {
        $selected = ($ip_address == $iprange->ip_range) ? 'selected="selected"' : '';
        $form .= '<option value="'.$iprange->ip_range.'" '.$selected.'>'.$iprange->ip_range.'</option>';
    }

    $form .= '</select>';
    $form .= '</div>';
    $form .= '</div>';
   
    
    $gateways = select_options_gateway_list();
    
    $form .= '<div class="row mb-2 gatewayrow">';
    $form .= '<div class="col">';
    $form .= '<label for="gateway">Gateway</label>';

    $form .= '</div>';
    $form .= '<div class="col-auto">';
    $form .= '<select id="gateway" name="gateway">';
    $form .= '<option value="" >None</option>';
    foreach ($gateways as $gateway_opt) {
        $selected = ($gateway == $gateway_opt) ? 'selected="selected"' : '';
        $form .= '<option value="'.$gateway_opt.'" '.$selected.'>'.$gateway_opt.'</option>';
    }
    
    $form .= '</select>';
    $form .= '</div>';
    $form .= '</div>';
    $form .= 'route: [IP RANGE] <br>
    descr: CLOUD-TELECOMS<br>
    origin: [AS NUMBER]<br>
    org: ORG-CTL3-AFRINIC<br>
    mnt-by: CLOUDTELECOMS-MNT<br>
    changed: helpdesk@telecloud.co.za<br>
    source: AFRINIC<br>
    password: [PASSWORD] <br><a href="https://www.afrinic.net/whois">https://www.afrinic.net/whois</a>';
    $form .= '</div>';

    
/*
    $form .= '<input placeholder="AS Number" name="as_number" id="as_number" type="text" value="'.$as_number.'" ><br>';
    $form .= '<input placeholder="Company" name="company" id="company" type="text" value="'.$company.'" ><br>';

    $form .= '<label for="route_object" >Requires Route Object</label><br>';
    $form .= '<input type="checkbox" name="route_object" id="route_object" '.$route_object.' ><br>';
    $form .= '<label for="rkpi" >Requires RKPI</label><br>';
    $form .= '<input type="checkbox" name="rkpi" id="rkpi" '.$rkpi.' ><br>';
    $form .= '<label for="auth_letter" >Requires Letter of Authority</label><br>';
    $form .= '<input type="checkbox" name="auth_letter" id="auth_letter" '.$auth_letter.' ><br>';
*/
    return $form;
}

function provision_ip_range_route_form($provision, $input, $product, $customer)
{
    $ip_address = (!empty($input['ip_address'])) ? $input['ip_address'] : '';
    $gateway = (!empty($input['gateway'])) ? $input['gateway'] : '';
    $type = (!empty($input['type'])) ? $input['type'] : '';
  
    $loa_as_number = (!empty($input['loa_as_number'])) ? $input['loa_as_number'] : '';
    $loa_company = (!empty($input['loa_company'])) ? $input['loa_company'] : '';
   
   

   
    $rkpi = (!empty($input['rkpi'])) ? 'checked="checked"' : '';
    $auth_letter = (!empty($input['auth_letter'])) ? 'checked="checked"' : '';
    $types = ['Tunnel','Route Object'];

    if (empty($product->provision_package)) {
        return json_alert('Subnet not set on provision api code field on product.', 'warning');
    }
    $ipranges = \DB::table('isp_data_ip_ranges')->where('account_id', 0)->where('subnet', $product->provision_package)->where('is_deleted', 0)->orderBy('ip_range')->get();

    if (empty($ipranges) || (is_array($ip_ranges) && count($ipranges) == 0)) {
        return json_alert('No IP ranges available for this subnet.', 'warning');
    }
    
    $form .= '<div class="container w-50">';
    $form .= '<div class="row mb-2">';
    $form .= '<div class="col">';
    $form .= '<label for="ip_address">IP Range</label>';
    
    $form .= '</div>';
    $form .= '<div class="col-auto">';
    $form .= '<select id="ip_address" name="ip_address"  required>';

    foreach ($ipranges as $iprange) {
        $selected = ($ip_address == $iprange->ip_range) ? 'selected="selected"' : '';
        $form .= '<option value="'.$iprange->ip_range.'" '.$selected.'>'.$iprange->ip_range.'</option>';
    }

    $form .= '</select>';
    $form .= '</div>';
    $form .= '</div>';
    
  
    
    $form .= '<div class="row mb-2 asnumberrow">';
    $form .= '<div class="col">';
    $form .= '<label for="route_object" >LOA AS NUMBER</label>';
    
    $form .= '</div>';
    $form .= '<div class="col-auto">';
    $form .= '<input type="text" name="loa_as_number" id="loa_as_number" '.$loa_as_number.' >';
    $form .= '</div>';
    $form .= '</div>';
    $form .= '<div class="row mb-2 asnumberrow">';
    $form .= '<div class="col">';
    $form .= '<label for="route_object" >LOA COMPANY</label>';
    
    $form .= '</div>';
    $form .= '<div class="col-auto">';
    $form .= '<input type="text" name="loa_company" id="loa_company" '.$loa_company.' >';
    $form .= '</div>';
    $form .= '</div>';


    
    
    $gateways = select_options_gateway_list();
    

    $form .= 'route: [IP RANGE] <br>
    descr: CLOUD-TELECOMS<br>
    origin: [AS NUMBER]<br>
    org: ORG-CTL3-AFRINIC<br>
    mnt-by: CLOUDTELECOMS-MNT<br>
    changed: helpdesk@telecloud.co.za<br>
    source: AFRINIC<br>
    password: [PASSWORD] <br><a href="https://www.afrinic.net/whois">https://www.afrinic.net/whois</a>';
    $form .= '</div>';

    
/*
    $form .= '<input placeholder="AS Number" name="as_number" id="as_number" type="text" value="'.$as_number.'" ><br>';
    $form .= '<input placeholder="Company" name="company" id="company" type="text" value="'.$company.'" ><br>';

    $form .= '<label for="route_object" >Requires Route Object</label><br>';
    $form .= '<input type="checkbox" name="route_object" id="route_object" '.$route_object.' ><br>';
    $form .= '<label for="rkpi" >Requires RKPI</label><br>';
    $form .= '<input type="checkbox" name="rkpi" id="rkpi" '.$rkpi.' ><br>';
    $form .= '<label for="auth_letter" >Requires Letter of Authority</label><br>';
    $form .= '<input type="checkbox" name="auth_letter" id="auth_letter" '.$auth_letter.' ><br>';
*/
    return $form;
}

function provision_ip_range_deactivation_form($provision, $input, $product, $customer)
{
    
    
    $form = '<div class="container w-50">';

    $form .= 'route: [IP RANGE] <br>
    descr: CLOUD-TELECOMS<br>
    origin: [AS NUMBER]<br>
    org: ORG-CTL3-AFRINIC<br>
    mnt-by: CLOUDTELECOMS-MNT<br>
    changed: helpdesk@telecloud.co.za<br>
    source: AFRINIC<br>
    password: [PASSWORD] <br><a href="https://www.afrinic.net/whois">https://www.afrinic.net/whois</a>';
    $form .= '</div>';

    
/*
    $form .= '<input placeholder="AS Number" name="as_number" id="as_number" type="text" value="'.$as_number.'" ><br>';
    $form .= '<input placeholder="Company" name="company" id="company" type="text" value="'.$company.'" ><br>';

    $form .= '<label for="route_object" >Requires Route Object</label><br>';
    $form .= '<input type="checkbox" name="route_object" id="route_object" '.$route_object.' ><br>';
    $form .= '<label for="rkpi" >Requires RKPI</label><br>';
    $form .= '<input type="checkbox" name="rkpi" id="rkpi" '.$rkpi.' ><br>';
    $form .= '<label for="auth_letter" >Requires Letter of Authority</label><br>';
    $form .= '<input type="checkbox" name="auth_letter" id="auth_letter" '.$auth_letter.' ><br>';
*/
    return $form;
}

function provision_number_porting_deactivation_form($provision, $input, $product, $customer)
{
    return '';
}


function provision_telkom_lte_sim_card_form($provision, $input, $product, $customer)
{
    return '<p>Telkom LTE Setup</p>';
}
function provision_mtn_lte_sim_card_form($provision, $input, $product, $customer)
{
    return '<p>MTN LTE Setup</p>';
}

function provision_mtn5g_lte_sim_card_form($provision, $input, $product, $customer)
{

    /*
    // MAP FIELDS
    strAddress String(36) Fibre Line Owner Address
    strLatLong String(36) Fibre Line Installation coordinates

    // REQUIRED FIELDS
    strOwner String(36) Fibre Line Owner Name
    strCell String(36) Fibre Line Owner Cell
    strSuburb String(36) Fibre Line Owner Suburb
    strCity String(36) Fibre Line Owner City
    strCode String(36) Fibre Line Owner Postal Code
    strAddressType String(36) Retreived from the ’getAddressType’ function

    // OPTIONAL FIELDS
    strBuildingId String(36) Fibre Line Installation Building Number
    strFloorId String(36) Fibre Line Installation Floor
    strUnitNumber String(36) Fibre Line Installation Unit Number
    strBlockName String(36) Fibre Line Installation Block Name
    */
    
    /*
    [2024-06-08 09:15:39] local.DEBUG: array (
  'address-input' => '675a Uitenhage Crescent, Faerie Glen, Pretoria',
  'provision_plan_id' => '240',
  'provision_id' => '4419',
  'current_step' => '1',
  'num_steps' => '3',
  'service_table' => 'sub_activations',
  'latlong-input' => '-25.7891722, 28.312166',
  'width-input' => '528',
  'height-input' => '400',
  'i-input' => '264',
  'j-input' => '200',
  'bbox-input' => '3151380.599194282,-2973231.227572754,3152011.2046775473,-2972753.4961460386',
  'streetofusage' => '675a Uitenhage Crescent',
  'suburbofusage' => 'Faerie Glen',
  'cityofusage' => 'Pretoria',
  'provinceofusage' => '0081',
  'postalofusage' => 'Gauteng',
) 
    */
    
    $map_inputs = \DB::table('sub_activation_steps')->where('service_table', 'sub_activations')->where('provision_plan_id', 240)->where('provision_id', $provision->id)->pluck('input')->first();
    $map_inputs = json_decode($map_inputs, true);

    $axxess = new Axxess();
    $address_types = $axxess->getAddressTypes()->arrAddressTypes;

    $strStreet = (!empty($map_inputs['address-input'])) ? $map_inputs['address-input'] : '';
    $strLatLong = (!empty($map_inputs['latlong-input'])) ? $map_inputs['latlong-input'] : '';
    $strOwner = (!empty($input['strOwner'])) ? $input['strOwner'] : '';

    $strSuburb = (!empty($input['strSuburb'])) ? $input['strSuburb'] : '';
    $strCity = (!empty($input['strCity'])) ? $input['strCity'] : '';
    $strProvince = (!empty($input['strProvince'])) ? $input['strProvince'] : '';
    $strCode = (!empty($input['strCode'])) ? $input['strCode'] : '';


    if(empty($strSuburb) && !empty($map_inputs['suburbofusage'])){
        $strSuburb = $map_inputs['suburbofusage'];
    }
    if(empty($strCity) && !empty($map_inputs['cityofusage'])){
        $strCity = $map_inputs['cityofusage'];
    }
    if(empty($strProvince) && !empty($map_inputs['postalofusage'])){
        $strProvince = $map_inputs['postalofusage'];
    }
    if(empty($strCode) && !empty($map_inputs['provinceofusage'])){
        $strCode = $map_inputs['provinceofusage'];
    }

    // GET VALUES FROM ACCOUNTS
    if (empty($strOwner)) {
        $strOwner = $customer->contact;
    }
    if (empty($strCell) && !empty($customer->phone)) {
        $validated_number  = '';
        try {
            $number = phone($customer->phone, ['Auto','ZA','US']);

            $number = $number->formatForMobileDialingInCountry('ZA');
        } catch (\Throwable $ex) {  
            exception_log($ex);
        }
        if (!empty($number)) {
            $strCell = $number;
        }
    }

    // REQUIRED FIELDS
    $form = '';
    $form .= '<input placeholder="Latitude/Longitude" name="strLatLong" id="strLatLong" type="hidden" value="'.$strLatLong.'" required disabled><br>';
    
    $form .= 'Address:<br><input placeholder="Street" name="Street" id="Street" type="text" value="'.$strStreet.'" required disabled><br>';
    $form .= 'Suburb:<br><input placeholder="Suburb" name="strSuburb" id="strSuburb" type="text" value="'.$strSuburb.'" required><br>';
    $form .= 'City:<br><input placeholder="City" name="strCity" id="strCity" type="text" value="'.$strCity.'" required><br>';
    $form .= 'Province:<br><input placeholder="Province" name="strProvince" id="strProvince" type="text" value="'.$strProvince.'" required><br>';
    $form .= 'Postal code:<br><input placeholder="Postal Code" name="strCode" id="strCode" type="text" value="'.$strCode.'" required><br>';

    return $form;
}

function provision_telkom_lte_topup_form($provision, $input, $product, $customer)
{
    $guidServiceId = (!empty($input['guidServiceId'])) ? $input['guidServiceId'] : '';
    $lte_sim_cards = \DB::table('isp_data_lte_axxess_accounts')->where('account_id', $customer->id)->get();

    if (empty($lte_sim_cards) || (is_array($lte_sim_cards) && count($lte_sim_cards) == 0)) {
        return json_alert('No Telkom simcards assigned to this account.', 'warning');
    }
    $form .= '<label for="guidServiceId">LTE Account</label><select id="guidServiceId" name="guidServiceId"  required>';

    foreach ($lte_sim_cards as $lte_sim_card) {
        $selected = ($guidServiceId == $lte_sim_card->guidServiceId) ? 'selected="selected"' : '';
        $form .= '<option value="'.$lte_sim_card->guidServiceId.'" '.$selected.'>'.$lte_sim_card->reference.'</option>';
    }

    $form .= '</select><br>';


    return $form;
}

function provision_products_monthly_form($provision, $input, $product, $customer)
{
    $form = '<p>Product Activation</p>';
    $sub = \DB::table('sub_activations')->where('id', $provision->id)->get()->first();
    if (!$sub->printed) {
        $form .= '<p>Invoice needs to be printed.</p><br>';
    }
    if (empty($sub->pod_file)) {
        $form .= '<p>POD file needs to be uploaded.</p><br>';
    }
    return $form;
}

function provision_iptv_form($provision, $input, $product, $customer)
{
   
    $iptv_id = (!empty($input['iptv_id'])) ? $input['iptv_id'] : 0;


    $iptv_accounts = \DB::table('isp_data_iptv')->where('account_id',0)->where('product_id',$provision->product_id)->where('global_panel',0)->where('is_deleted',0)->get();

    if (empty($iptv_accounts) || (is_array($iptv_accounts) && count($iptv_accounts) == 0)) {
        return json_alert('No available iptv accounts, please create a new account first.', 'warning');
    }
    $form .= '<label for="iptv">IPTV Account</label><select id="iptv_id" name="iptv_id"  required>';

    foreach ($iptv_accounts as $iptv_account) {
        $selected = ($iptv_id == $iptv_account->id) ? 'selected="selected"' : '';
        $form .= '<option value="'.$iptv_account->id.'" '.$selected.'>'.$iptv_account->username.'</option>';
    }

    $form .= '</select><br>';

    return $form;
}


function provision_iptv_addon_form($provision, $input, $product, $customer)
{
   
    $iptv_id = (!empty($input['iptv_id'])) ? $input['iptv_id'] : 0;


    $iptv_accounts = \DB::table('isp_data_iptv')->where('account_id',$customer->id)->where('global_panel',0)->where('is_deleted',0)->get();

    if (empty($iptv_accounts) || (is_array($iptv_accounts) && count($iptv_accounts) == 0)) {
        return json_alert('No available iptv accounts, please create a new account first.', 'warning');
    }
    $form .= '<label for="iptv">IPTV Account</label><select id="iptv_id" name="iptv_id"  required>';

    foreach ($iptv_accounts as $iptv_account) {
        $selected = ($iptv_id == $iptv_account->id) ? 'selected="selected"' : '';
        $form .= '<option value="'.$iptv_account->id.'" '.$selected.'>'.$iptv_account->username.'</option>';
    }

    $form .= '</select><br>';

    return $form;
}


function provision_iptv_global_form($provision, $input, $product, $customer)
{
   
    $iptv_id = (!empty($input['iptv_id'])) ? $input['iptv_id'] : 0;


    $iptv_accounts = \DB::table('isp_data_iptv')->where('account_id',0)->where('product_id',$provision->product_id)->where('global_panel',1)->where('is_deleted',0)->get();

    if (empty($iptv_accounts) || (is_array($iptv_accounts) && count($iptv_accounts) == 0)) {
        return json_alert('No available iptv accounts, please create a new account first.', 'warning');
    }
    $form .= '<label for="iptv">IPTV Account</label><select id="iptv_id" name="iptv_id"  required>';

    foreach ($iptv_accounts as $iptv_account) {
        $selected = ($iptv_id == $iptv_account->id) ? 'selected="selected"' : '';
        $form .= '<option value="'.$iptv_account->id.'" '.$selected.'>'.$iptv_account->username.'</option>';
    }

    $form .= '</select><br>';

    return $form;
}
function provision_iptv_trial_form($provision, $input, $product, $customer)
{
   
    $iptv_id = (!empty($input['iptv_id'])) ? $input['iptv_id'] : 0;


    $iptv_accounts = \DB::table('isp_data_iptv')->where('account_id',0)->where('trial',1)->where('is_deleted',0)->get();

    if (empty($iptv_accounts) || (is_array($iptv_accounts) && count($iptv_accounts) == 0)) {
        return json_alert('No available iptv accounts, please create a new account first.', 'warning');
    }
    $form .= '<label for="iptv">IPTV Account</label><select id="iptv_id" name="iptv_id"  required>';

    foreach ($iptv_accounts as $iptv_account) {
        $selected = ($iptv_id == $iptv_account->id) ? 'selected="selected"' : '';
        $form .= '<option value="'.$iptv_account->id.'" '.$selected.'>'.$iptv_account->username.'</option>';
    }

    $form .= '</select><br>';

    return $form;
}

function provision_noip_form($provision, $input, $customer, $product)
{
    return 'No-IP Account';
}

function provision_teamoffice_form($provision, $input, $customer, $product)
{ 
    $username = (!empty($input['username'])) ? $input['username'] : '';
    $password = (!empty($input['password'])) ? $input['password'] : '';
    $domain_name = (!empty($input['domain_name'])) ? $input['domain_name'] : '';
    $form = '<p>Team office Setup</p>';
    
    
    $form .= '<div class="row mb-2">';
    $form .= '<div class="col">';
    $form .= '<label for="domain_name" >Domain name</label>';
    $form .= '</div>';
    $form .= '<div class="col-auto">';
    $form .= '<input type="text" name="domain_name" id="domain_name" '.$domain_name.' >';
    $form .= '</div>';
    $form .= '</div>';
    
    $form .= '<div class="row mb-2">';
    $form .= '<div class="col">';
    $form .= '<label for="username" >Username</label>';
    $form .= '</div>';
    $form .= '<div class="col-auto">';
    $form .= '<input type="text" name="username" id="username" '.$username.' >';
    $form .= '</div>';
    $form .= '</div>';
    
    $form .= '<div class="row mb-2">';
    $form .= '<div class="col">';
    $form .= '<label for="password" >Password</label>';
    $form .= '</div>';
    $form .= '<div class="col-auto">';
    $form .= '<input type="text" name="password" id="password" '.$password.' >';
    $form .= '</div>';
    $form .= '</div>';
    
    return $form;
}
