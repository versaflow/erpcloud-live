<?php

function set_session_data($user_id, $login_as_account_id = false)
{
    if ($login_as_account_id && $login_as_account_id != session('original_account_id') && is_parent_of($login_as_account_id)) {
        $user_id = \DB::connection('default')->table('erp_users')->where('account_id', $login_as_account_id)->pluck('id')->first();
    }
    $user = \DB::connection('default')->table('erp_users')->where('id', $user_id)->get()->first();

    if ($user->account_id == 1) {
        $customer = $reseller = dbgetaccount($user->account_id);
    } else {
        $customer = dbgetaccount($user->account_id);
        $reseller = dbgetaccount($customer->partner_id);
    }
    $role = \DB::connection('default')->table('erp_user_roles')->where('id', $user->role_id)->get()->first();

    if (empty($user)) {
        return Redirect::back()->with('status', 'error')->with('message', 'User does not exists');
    }
    if (empty($role)) {
        return Redirect::back()->with('status', 'error')->with('message', 'Role does not exists');
    }
    if (empty($customer)) {
        return Redirect::back()->with('status', 'error')->with('message', 'Customer does not exists');
    }
    if (empty($reseller)) {
        return Redirect::back()->with('status', 'error')->with('message', 'Partner does not exists');
    }
    // Log in the user programmatically
    // Log in the user programmatically
    $auth_user = App\Models\User::find($user->id);
    Auth::login($auth_user);
    $session = [
        'active_user_id' => $user->id,
        'role_id' => $role->id,
        'extra_role_id' => $user->extra_role_id,
        'role_level' => $role->level,
        'role_name' => $role->name,
        'account_id' => $customer->id,
        'customer_company' => $customer->company,
        'customer_logo' => $customer->logo,
        'customer_type' => $customer->type,
        'customer_email' => $customer->email,
        'customer_mobile' => $customer->phone,
        'customer_address' => $customer->address,
        'customer_pricelist' => $customer->pricelist_id,

        'parent_id' => $reseller->id,
        'partner_id' => $reseller->id,
        'parent_company' => $reseller->company,
        'parent_logo' => $reseller->logo,
        'parent_email' => $reseller->email,
        'parent_mobile' => $reseller->phone,
        'parent_address' => $reseller->address,
        'parent_enable_vat' => $reseller->vat_enabled,
        'parent_vat_number' => $reseller->vat_number,
        // 'parent_document_footer' => $reseller->document_footer,
        'enable_client_invoice_creation' => $reseller->enable_client_invoice_creation,
    ];
    $role_ids = [$role->id];

    if (! empty($user->extra_role_id)) {
        $extra_role_ids = explode(',', $user->extra_role_id);
        $role_ids = array_merge($role_ids, $extra_role_ids);
    }

    $session['role_ids'] = $role_ids;

    $set_timesheet = false;
    if (empty(session('original_role_id'))) {
        // session data only set on first login
        $session['original_role_id'] = $role->id;
        $session['original_role_level'] = $role->level;
        $session['original_account_id'] = $customer->id;
        $session['user_id'] = $user->id;
        $session['username'] = $user->username;
        $session['full_name'] = $user->full_name;
        $set_timesheet = true;
    }

    if ($role->level == 'Admin') {
        $instance_ids = get_admin_instance_access($user->username);
        $session['admin_instance_ids'] = $instance_ids;
    }

    if ($role->level == 'Partner') {
        $subscription_product_ids = \DB::connection('default')->table('sub_services')
            ->join('crm_accounts', 'crm_accounts.id', '=', 'sub_services.account_id')
            ->where('sub_services.status', '!=', 'Deleted')
            ->where('crm_accounts.partner_id', $customer->id)
            ->pluck('sub_services.product_id')->toArray();
        $session['subscription_product_ids'] = $subscription_product_ids;
    }

    if ($role->level == 'Customer') {
        $subscription_product_ids = \DB::connection('default')->table('sub_services')
            ->where('status', '!=', 'Deleted')
            ->where('account_id', $customer->id)
            ->pluck('product_id')->toArray();
        $session['subscription_product_ids'] = $subscription_product_ids;
    }

    $session = array_merge(session()->all(), $session);
    session($session);

    if ($set_timesheet) {
        timesheet_in();
    }

    return true;
}

function button_roles_access_table($request)
{
    $forms_url = get_menu_url_from_table('erp_forms');
    $role = \DB::connection('default')->table('erp_user_roles')->where('id', $request->id)->get()->first();
    $html = '<div class="p-2"><h4>'.$role->name.'</h4>';
    $module_access = \DB::connection('default')->table('erp_forms')
        ->select('erp_forms.*', 'erp_cruds.name')
        ->join('erp_cruds', 'erp_cruds.id', '=', 'erp_forms.module_id')
        ->where('erp_forms.role_id', $role->id)
        ->get();
    $html .= '<div class="table-responsive"><table class="table table-bordered"><thead>';
    $html .= '<tr><th></th><th>Module</th><th>View</th><th>Add</th><th>Edit</th><th>Delete</th></tr>';
    $html .= '</thead><tbody>';
    foreach ($module_access as $a) {
        $html .= '<tr><td><a data-target="sidebarform" href="'.$forms_url.'/edit/'.$a->id.'"><i class="fas fa-edit"></i></a> </td><td>'.ucwords(str_replace('_', ' ', $a->name)).'</td><td>'.$a->is_view.'</td><td>'.$a->is_add.'</td><td>'.$a->is_edit.'</td><td>'.$a->is_delete.'</td></tr>';
    }
    $html .= '</tbody></table></div></div>';
    echo $html;
}
