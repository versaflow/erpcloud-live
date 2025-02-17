<?php

function schedule_set_services_shared()
{
    \DB::table('crm_rental_spaces')->where('account_id', 0)->update(['has_lease' => 'Vacant']);
    \DB::table('crm_rental_spaces')->update(['deposit_invoiced' => 0, 'lease_fee_invoiced' => 0, 'active_expiry_date' => null]);
    \DB::table('crm_rental_spaces')->where('has_lease', 'Vacant')->update(['actual_rent_amount' => 0]);
    \DB::table('crm_rental_spaces')->where('has_lease', '!=', 'Vacant')->update(['actual_rent_amount' => \DB::raw('rent_amount')]);

    $sql = "UPDATE crm_rental_spaces 
    JOIN crm_rental_leases ON crm_rental_leases.rental_space_id=crm_rental_spaces.id
    SET crm_rental_spaces.active_expiry_date = crm_rental_leases.lease_expiry_date WHERE crm_rental_leases.status='Enabled'";
    \DB::statement($sql);

    $sql = 'UPDATE crm_opportunities 
    JOIN crm_accounts ON crm_opportunities.account_id=crm_accounts.id
    SET crm_opportunities.form_name = crm_accounts.form_name,crm_opportunities.ad_form_id = crm_accounts.form_id,crm_opportunities.source = crm_accounts.source,crm_opportunities.phone = crm_accounts.phone,crm_opportunities.email = crm_accounts.email,crm_opportunities.last_call = crm_accounts.last_call';
    \DB::statement($sql);

    $rentals = \DB::table('crm_rental_leases')->where('status', 'Enabled')->get();
    foreach ($rentals as $rental) {
        // \DB::table('crm_rental_spaces')->where('id',$rental->rental_space_id)->update(['account_id'=>$rental->account_id]);
        if ($rental->account_id > 0) {
            if ($rental->status == 'Deleted') {
                \DB::table('crm_rental_spaces')->where('id', $rental->rental_space_id)->update(['account_id' => 0]);
            } else {
                $docids = \DB::table('crm_documents')->where('account_id', $rental->account_id)->whereIn('doctype', ['Order', 'Tax Invoice'])->where('reversal_id', 0)->pluck('id')->toArray();
                $deposit_amount = \DB::table('crm_document_lines')->whereIn('document_id', $docids)->where('product_id', 11)->sum('price');
                $lease_fee = \DB::table('crm_document_lines')->whereIn('document_id', $docids)->where('product_id', 149)->sum('price');
                \DB::table('crm_rental_spaces')->where('id', $rental->rental_space_id)->update(['account_id' => $rental->account_id, 'deposit_invoiced' => $deposit_amount * 1.15, 'lease_fee_invoiced' => $lease_fee * 1.15]);
            }
        }
    }
    $demo_account_ids = [];
    $disabled_tenant_count = 0;
    $disabled_tenant_ids = [];
    $disabled_account_ids = \DB::table('crm_accounts')->where('status', 'Disabled')->pluck('id')->toArray();
    if (! empty($disabled_account_ids) && is_array($disabled_account_ids) && count($disabled_account_ids) > 0) {
        $disabled_tenant_count = \DB::table('crm_rental_leases')->whereIn('account_id', $disabled_account_ids)->count();
        $disabled_tenant_ids = \DB::table('crm_rental_leases')->whereIn('account_id', $disabled_account_ids)->pluck('id')->toArray();
    }

    // $service_balance = \DB::table('sub_service_balances')->where('is_deleted', 0)->orderBy('id', 'desc')->get()->first();
    // $shared_services_total = $service_balance->waste_management_sanitation;
    $water_bill = $service_balance->water_bill;
    $armed_response_total = $service_balance->armed_response;

    $guide = 'Sanitation & Waste: R 450'; //'.$shared_services_total.'<br>';
    $guide .= 'Water bill: '.$water_bill.'<br>';
    $guide .= 'Armed response: '.$armed_response_total.'<br>';

    $module_id = \DB::table('erp_cruds')->where('db_table', 'crm_rental_spaces')->update(['guide' => $guide]);

    if ($disabled_tenant_count > 0) {
        $total_fixed_tenants = \DB::table('crm_rental_leases')
            ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
            ->whereNotIn('account_id', $demo_account_ids)
            ->where('crm_rental_leases.account_id', '>', 0)
            ->whereNotIn('crm_rental_leases.id', $disabled_tenant_ids)
            ->where('crm_rental_leases.status', '!=', 'Deleted')
            ->count();
        // $total_sqm_share = \DB::table('crm_rental_leases')
        //  ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
        // ->whereNotIn('account_id', $demo_account_ids)
        // ->where('crm_rental_leases.account_id', '>', 0)
        // ->whereNotIn('crm_rental_leases.id', $disabled_tenant_ids)
        // ->where('crm_rental_leases.status', '!=', 'Deleted')
        // ->sum('crm_rental_spaces.sqm_share');
        $total_sanitation_share = \DB::table('crm_rental_spaces')
            ->where('has_lease', 'Occupied')
            ->sum('sanitation_share');
        // $total_water_share = \DB::table('crm_rental_leases')
        //  ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
        // ->whereNotIn('account_id', $demo_account_ids)
        // ->where('crm_rental_leases.account_id', '>', 0)
        // ->whereNotIn('crm_rental_leases.id', $disabled_tenant_ids)
        // ->where('crm_rental_leases.status', '!=', 'Deleted')
        // ->sum('crm_rental_spaces.sqm_share');
        $total_armed_response = $total_fixed_tenants;
    } else {
        $total_fixed_tenants = \DB::table('crm_rental_leases')
            ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
            ->whereNotIn('account_id', $demo_account_ids)
            ->where('crm_rental_leases.status', '!=', 'Deleted')
            ->where('crm_rental_leases.account_id', '>', 0)
            ->count();
        // $total_sa_share = \DB::table('crm_rental_leases')
        // ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
        // ->whereNotIn('account_id', $demo_account_ids)
        // ->where('crm_rental_leases.status', '!=', 'Deleted')
        // ->where('crm_rental_leases.account_id', '>', 0)
        // ->sum('crm_rental_spaces.sqm_share');
        $total_sanitation_share = \DB::table('crm_rental_spaces')
            ->where('has_lease', 'Occupied')
            ->sum('sanitation_share');
        $total_water_share = \DB::table('crm_rental_leases')
            ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
            ->whereNotIn('account_id', $demo_account_ids)
            ->where('crm_rental_leases.status', '!=', 'Deleted')
            ->where('crm_rental_leases.account_id', '>', 0)
            ->sum('crm_rental_spaces.water_share');
        $total_armed_response = $total_fixed_tenants;
    }

    \DB::table('crm_rental_spaces')->update(['price_per_sanitation' => \DB::raw(currency($shared_services_total / $total_sanitation_share))]);
    \DB::table('crm_rental_spaces')->update(['price_per_tap' => \DB::raw(currency($water_bill / $total_water_share))]);
    \DB::table('crm_rental_spaces')->update(['price_per_armed_response' => \DB::raw(currency($armed_response_total / $total_fixed_tenants))]);
    \DB::table('crm_rental_spaces')->where('has_lease', 'Vacant')->update(['price_per_armed_response' => '0', 'price_per_tap' => '0', 'price_per_sanitation' => '0']);

    $rental_space_ids = \DB::table('crm_rental_leases')->where('status', '!=', 'Deleted')->pluck('rental_space_id')->toArray();
    \DB::table('crm_rental_spaces')->whereIn('id', $rental_space_ids)->where('has_lease', '!=', 'Internal')->update(['has_lease' => 'Occupied']);
    \DB::table('crm_rental_spaces')->whereNotIn('id', $rental_space_ids)->where('has_lease', '!=', 'Internal')->update(['has_lease' => 'Vacant']);
    \DB::table('crm_rental_spaces')->where('has_lease', 'Vacant')->update(['internet_price' => 0, 'inverter_price' => 0]);

    \DB::table('crm_rental_spaces')->where('has_lease', '!=', 'Vacant')->where('office_number', '!=', '11 Boardroom')->update(['sqm_share' => \DB::raw('office_size')]);
    \DB::table('crm_rental_spaces')->where('has_lease', '!=', 'Vacant')->where('office_number', '!=', '11 Boardroom')->where('taps', '>', 0)->update(['water_share' => \DB::raw('office_size')]);
    \DB::table('crm_rental_spaces')->where('has_lease', 'Vacant')->update(['sqm_share' => 0, 'water_share' => 0]);
    \DB::table('crm_rental_spaces')->where('office_number', '11 Boardroom')->update(['sqm_share' => 0, 'water_share' => 0]);
}

/*
  $rentals = \DB::table('crm_rental_leases')
    ->select('crm_rental_leases.*','crm_rental_spaces.*','crm_rental_leases.id as id')
    ->join('crm_rental_spaces','crm_rental_leases.rental_space_id','=','crm_rental_spaces.id')
    ->get();
*/
function get_price_per_tap()
{
    return 200;
}

function schedule_update_lease_expiring()
{
    \DB::table('crm_rental_spaces')->update(['lease_expiring' => 0]);
    $leases = \DB::table('crm_rental_leases')->where('status', 'Enabled')->get();
    foreach ($leases as $lease) {
        if (! empty($lease->lease_expiry_date) && $lease->lease_expiry_date <= date('Y-m-t', strtotime('last day next month'))) {
            \DB::table('crm_rental_spaces')->where('id', $lease->rental_space_id)->update(['lease_expiring' => 1]);
        }
    }
}

function schedule_process_cancelled_leases()
{
    $module_id = \DB::table('erp_cruds')->where('db_table', 'crm_rental_leases')->pluck('id')->first();
    $leases = \DB::table('crm_rental_leases')->where('status', 'Enabled')->where('cancel_date', '>', '')->where('cancel_date', '<=', date('Y-m-d'))->get();
    foreach ($leases as $lease) {
        $account = dbgetaccount($lease->account_id);
        $data = [
            'module_id' => $module_id,
            'row_id' => $lease->id,
            'title' => 'Delete - rental lease '.$account->id,
            'processed' => 0,
            'requested_by' => get_user_id_default(),
        ];

        (new \DBEvent)->setTable('crm_approvals')->save($data);
    }
}

function button_rentals_cancel_lease($request)
{
    // try{
    // $credit_note_id = create_rental_deposit_credit_note($request->id);

    // if(!$credit_note_id){
    //     return json_alert('Rental deposit could not be credited','warning');
    // }
    \DB::table('crm_rental_leases')->where('id', $request->id)->update(['cancel_date' => date('Y-m-d', strtotime('+1 month')), 'cancelled_by_id' => get_user_id_default()]);

    return json_alert('Done');
    // } catch(\Throwable $ex) {
    //     aa($ex->getMessage());
    //     aa($ex->getTraceAsString());
    // }
}

function aftersave_service_balances_set_water_bill($request)
{
    \DB::table('sub_service_balances')->where('id', $request->id)->update(['water_bill' => \DB::raw('water_bill_total-bayleaf_water_topups-pawsandall_water_topups')]);
    \DB::table('sub_service_balances')->where('id', $request->id)->where('water_bill', '<', 0)->update(['water_bill' => 0]);
}

function schedule_create_rental_deposit_activations()
{
    $tenants = \DB::table('crm_accounts')->where('active_leases', 1)->where('status', '!=', 'Deleted')->get();
    foreach ($tenants as $tenant) {
        $docids = \DB::table('crm_documents')->where('account_id', $tenant->id)->whereIn('doctype', ['Order', 'Tax Invoice'])->where('reversal_id', 0)->pluck('id')->toArray();
        $deposit_amount = \DB::table('crm_document_lines')->whereIn('document_id', $docids)->where('product_id', 11)->sum('price');
        if ((! $deposit_amount && $tenant->id != 8) || ! $tenant->id_file || ! $tenant->lease_agreement) {
            $pending_activation = \DB::table('sub_activations')
                ->where('account_id', $tenant->id)
                ->where('product_id', 11)
                ->whereIn('status', ['Pending', 'Awaiting Third-Party'])
                ->count();
            if (! $pending_activation) {
                $data = [
                    'account_id' => $tenant->id,
                    'product_id' => 11,
                    'provision_type' => 'rental_deposit',
                    'status' => 'Pending',
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => get_system_user_id(),
                ];
                \DB::table('sub_activations')->insert($data);
            }
        }
    }
}

function afterdelete_rental_leases_create_rental_deposit_credit_note($request)
{
    create_rental_deposit_credit_note($request->id);
}

function create_rental_deposit_credit_note($lease_id)
{
    $lease = \DB::table('crm_rental_leases')->where('id', $lease_id)->get()->first();

    $credit_note_id = \DB::table('crm_documents')
        ->where('account_id', $lease->account_id)
        ->join('crm_document_lines', 'crm_document_lines.document_id', '=', 'crm_documents.id')
        ->where('crm_document_lines.product_id', 11)
        ->where('doctype', 'Credit Note')
        ->where('credit_note_reason', 'Rental deposit credited')
        ->pluck('crm_documents.id')->first();
    if ($credit_note_id) {
        // check if credit note already exists
        return $credit_note_id;
    }

    $invoice_id = \DB::table('crm_documents')
        ->where('account_id', $lease->account_id)
        ->join('crm_document_lines', 'crm_document_lines.document_id', '=', 'crm_documents.id')
        ->where('crm_document_lines.product_id', 11)
        ->where('doctype', 'Tax Invoice')
        ->pluck('crm_documents.id')->first();

    if (! $invoice_id) {
        return false;
    }

    $invoice = \DB::table('crm_documents')->where('id', $invoice_id)->get()->first();
    if (! empty($invoice->reversal_id)) {
        return false;
    }
    $lines = \DB::table('crm_document_lines')->where('product_id', 11)->where('document_id', $invoice_id)->get();

    $subtotal = 0;
    $tax = 0;
    $data = (array) $invoice;
    $data['docdate'] = (date('Y-m-d') > $invoice->docdate) ? date('Y-m-d') : $invoice->docdate;
    $data['doctype'] = 'Credit Note';
    $data['credit_note_reason'] = 'Rental deposit credited';
    $data['reversal_id'] = $invoice_id;
    unset($data['id']);

    $credit_note_id = \DB::table('crm_documents')->insertGetId($data);
    foreach ($lines as $line) {
        $line_data = (array) $line;
        $line_data['document_id'] = $credit_note_id;
        $line_data['original_line_id'] = $line->id;
        unset($line_data['id']);
        \DB::table('crm_document_lines')->insert($line_data);
        $subtotal += ($line->qty * $line->price);
    }

    $tax = 0;
    if ($invoice->tax > 0) {
        $tax = $subtotal * 0.15;
    }
    $total = $subtotal + $tax;

    \DB::table('crm_documents')->where('id', $credit_note_id)->update(['total' => $total, 'tax' => $tax]);

    \DB::table('crm_documents')->where('id', $invoice_id)->update(['reversal_id' => $credit_note_id]);

    $db = new DBEvent;
    $db->setTable('crm_documents');
    $db->postDocument($credit_note_id);
    $db->postDocumentCommit();

    return $credit_note_id;
}

function button_rentals_cancel($request)
{
    $rental = \DB::table('crm_rental_leases')->where('id', $request->id)->get()->first();
    if ($rental->status == 'Cancelled') {
        return json_alert('Rental space already cancelled', 'warning');
    }

    \DB::table('crm_rental_leases')->where('id', $request->id)->update(['status' => 'Cancelled']);

    return json_alert('Rental space cancelled');
}

function button_rentals_archive($request)
{
    $rental = \DB::table('crm_rental_leases')->where('id', $request->id)->get()->first();
    if ($rental->status == 'Deleted') {
        return json_alert('Rental space already archived', 'warning');
    }
    $data = (array) $rental;
    unset($data['id']);
    unset($data['account_id']);
    unset($data['lease_start_date']);
    unset($data['lease_start_amount']);
    unset($data['next_escalation_date']);
    unset($data['lease_expiry_date']);
    unset($data['last_escalation_date']);
    \DB::table('crm_rental_leases')->insert($data);
    \DB::table('crm_rental_leases')->where('id', $request->id)->update(['status' => 'Deleted']);

    return json_alert('Rental space archived');
}

function beforesave_rentals_check_account($request)
{
    $beforesave_row = session('event_db_record');
    if (! empty($request->id) && $beforesave_row->lease_start_date != $rental->lease_start_date) {
        return 'Lease start date cannot be changed, archive rental space and assign.';
    }

    if (empty($request->account_id)) {
        return 'Account required, to remove account archive the rental space.';
    }
    if (empty($request->lease_start_amount)) {
        return 'Account required, to remove account archive the rental space.';
    }
    if (empty($request->lease_start_date)) {
        return 'Account required, to remove account archive the rental space.';
    }
}

function beforesave_rentals_check_dates($request)
{
    if (! empty($request->account_id)) {
        if (empty($request->lease_expiry_date)) {
            return 'Lease expiry date required';
        }
    }
}

function button_accounts_rental_spaces($request)
{
    $id = \DB::table('crm_rental_leases')->where('account_id', $request->id)->pluck('id')->first();
    if (! $id) {
        return json_alert('Record not found');
    }
    $url = '/rental_spaces/edit/'.$id;

    return redirect()->to($url);
}

function aftersave_rentals_spaces_calculate_price($request)
{
    $rentals = \DB::table('crm_rental_leases')
        ->select('crm_rental_leases.*', 'crm_rental_spaces.*', 'crm_rental_leases.id as id')
        ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
        ->where('crm_rental_leases.status', 'Enabled')
        ->where('crm_rental_spaces.id', $request->id)
        ->get();

    foreach ($rentals as $rental) {
        set_rental_prices($rental);
    }
}

function aftersave_rentals_leases_calculate_price($request = false)
{
    if ($request != false) {
        $rentals = \DB::table('crm_rental_leases')
            ->select('crm_rental_leases.*', 'crm_rental_spaces.*', 'crm_rental_leases.id as id')
            ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
            ->where('crm_rental_leases.status', 'Enabled')
            ->where('crm_rental_leases.id', $request->id)
            ->get();

        foreach ($rentals as $rental) {
            set_rental_prices($rental);
        }
    }
}

function schedule_rental_leases_update_accounts()
{
    \DB::table('crm_accounts')->update(['active_leases' => 0]);
    $accounts = \DB::table('crm_accounts')->get();
    foreach ($accounts as $account) {
        $c = \DB::table('crm_rental_leases')->where('account_id', $account->id)->where('status', '!=', 'Deleted')->count();
        $active_leases = ($c > 0) ? 1 : 0;
        \DB::table('crm_accounts')->where('id', $account->id)->update(['active_leases' => $active_leases]);
    }
}

function set_all_rental_prices()
{
    DB::table('sub_services')->truncate();
    $deleted_accounts = \DB::table('crm_accounts')->where('status', 'Deleted')->pluck('id')->toArray();
    \DB::table('crm_rental_leases')->whereIn('account_id', $deleted_accounts)
        ->update(['status' => 'Deleted']);

    $rentals = \DB::table('crm_rental_leases')
        ->select('crm_rental_leases.*', 'crm_rental_spaces.*', 'crm_rental_leases.id as id')
        ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
        ->where('crm_rental_leases.status', 'Enabled')
        ->get();

    foreach ($rentals as $rental) {
        // vd($rental);
        set_rental_prices($rental);
    }
}

function set_rental_prices($rental)
{
    $demo_account_ids = [];
    $disabled_tenant_count = 0;
    $disabled_tenant_ids = [];
    $disabled_account_ids = \DB::table('crm_accounts')->where('status', 'Disabled')->pluck('id')->toArray();
    if (! empty($disabled_account_ids) && is_array($disabled_account_ids) && count($disabled_account_ids) > 0) {
        $disabled_tenant_count = \DB::table('crm_rental_leases')->whereIn('account_id', $disabled_account_ids)->count();
        $disabled_tenant_ids = \DB::table('crm_rental_leases')->whereIn('account_id', $disabled_account_ids)->pluck('id')->toArray();
    }

    $office_size = $rental->office_size;
    $rent_amount = $office_size * $rental->price_per_sqm;

    // if ($rental->office_number == '11 Boardroom') {
    //     \DB::table('crm_rental_spaces')->where('id', $rental->rental_space_id)->update(['sqm_share' => 0]);
    //     \DB::table('crm_rental_spaces')->where('id', $rental->rental_space_id)->update(['water_share' => 0]);
    // } elseif ($rental->has_lease == 'Vacant') {
    //     \DB::table('crm_rental_spaces')->where('id', $rental->rental_space_id)->update(['sqm_share' => 0]);
    //     \DB::table('crm_rental_spaces')->where('id', $rental->rental_space_id)->update(['water_share' => 0]);
    // } else {
    //     \DB::table('crm_rental_spaces')->where('id', $rental->rental_space_id)->update(['sqm_share' => \DB::raw('office_size')]);
    //     \DB::table('crm_rental_spaces')->where('id', $rental->rental_space_id)->where('taps', '>', 0)->update(['water_share' => \DB::raw('office_size')]);
    // }
    \DB::table('crm_rental_spaces')->where('id', $rental->rental_space_id)->update(['rent_amount' => $rent_amount]);

    $reference = 'Office #'.$rental->office_number.' Rent';
    $service_reference = 'Office #'.$rental->office_number.' Shared Services';
    $internet_reference = 'Office #'.$rental->office_number.' Internet';
    $inverter_reference = 'Office #'.$rental->office_number.' Inverter';
    $water_reference = 'Office #'.$rental->office_number.' Water Usage';
    $armed_response_reference = 'Office #'.$rental->office_number.' Armed Response';
    if (empty($rental->account_id)) {
        DB::table('sub_services')->where('detail', $reference)->delete();
        DB::table('sub_services')->where('detail', $service_reference)->delete();
        DB::table('sub_services')->where('detail', $internet_reference)->delete();
        DB::table('sub_services')->where('detail', $inverter_reference)->delete();
        DB::table('sub_services')->where('detail', $water_reference)->delete();
        DB::table('sub_services')->where('detail', $armed_response_reference)->delete();

        return '';
    }

    DB::table('sub_services')->where('detail', $reference)->update(['account_id' => $rental->account_id]);
    DB::table('sub_services')->where('detail', $service_reference)->update(['account_id' => $rental->account_id]);
    DB::table('sub_services')->where('detail', $internet_reference)->update(['account_id' => $rental->account_id]);
    DB::table('sub_services')->where('detail', $inverter_reference)->update(['account_id' => $rental->account_id]);
    DB::table('sub_services')->where('detail', $water_reference)->update(['account_id' => $rental->account_id]);
    DB::table('sub_services')->where('detail', $armed_response_reference)->update(['account_id' => $rental->account_id]);

    $rent_exists = \DB::table('sub_services')->where('detail', $reference)->where('status', '!=', 'Deleted')->count();
    if (! $rent_exists) {
        $rent = [
            'account_id' => $rental->account_id,
            'product_id' => 13,
            'created_at' => date('Y-m-d H:i:s'),
            'detail' => $reference,
            'status' => 'Enabled',
            'price' => $rent_amount,
            'price_incl' => currency($rent_amount * 1.15),
        ];
        dbinsert('sub_services', $rent);
    }

    $services_exists = \DB::table('sub_services')->where('detail', $service_reference)->where('status', '!=', 'Deleted')->count();
    if (! $services_exists) {
        $services = [
            'account_id' => $rental->account_id,
            'product_id' => 158,
            'detail' => $service_reference,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'Enabled',
            'price_incl' => currency(450 * 1.15),
        ];

        dbinsert('sub_services', $services);
    }

    // $armed_response_exists = \DB::table('sub_services')->where('detail', $armed_response_reference)->where('status', '!=', 'Deleted')->count();
    // if (!$armed_response_exists) {
    //     $armed_response = [
    //         'account_id' => $rental->account_id,
    //         'product_id' => 148,
    //         'detail' => $armed_response_reference,
    //         'created_at' => date('Y-m-d H:i:s'),
    //         'status' => 'Enabled',
    //     ];
    //     dbinsert('sub_services', $armed_response);
    // }

    // $water_reference_exists = \DB::table('sub_services')->where('detail', $water_reference)->where('status', '!=', 'Deleted')->count();
    // if (!$water_reference_exists) {
    //     $rent = [
    //         'account_id' => $rental->account_id,
    //         'product_id' => 14,
    //         'created_at' => date('Y-m-d H:i:s'),
    //         'detail' => $water_reference,
    //         'status' => 'Enabled',
    //     ];
    //     dbinsert('sub_services', $rent);
    // }

    //council services
    // $service_balance = \DB::table('sub_service_balances')->where('is_deleted', 0)->orderBy('id', 'desc')->get()->first();
    // $shared_services_total = $service_balance->waste_management_sanitation;
    // $water_bill = $service_balance->water_bill;
    // // vd($shared_services_total);

    // if ($disabled_tenant_count > 0) {
    //     $total_fixed_tenants = \DB::table('crm_rental_leases')
    //     ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
    //     ->whereNotIn('account_id', $demo_account_ids)
    //     ->where('crm_rental_leases.account_id', '>', 0)
    //     ->whereNotIn('crm_rental_leases.id', $disabled_tenant_ids)
    //     ->where('crm_rental_leases.status', '!=', 'Deleted')
    //     ->count();
    //     $total_sqm_share = \DB::table('crm_rental_leases')
    //     ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
    //     ->whereNotIn('account_id', $demo_account_ids)
    //     ->where('crm_rental_leases.account_id', '>', 0)
    //     ->whereNotIn('crm_rental_leases.id', $disabled_tenant_ids)
    //     ->where('crm_rental_leases.status', '!=', 'Deleted')
    //     ->sum('crm_rental_spaces.sqm_share');
    //     $total_sanitation_share = \DB::table('crm_rental_spaces')
    //     ->where('has_lease', 'Occupied')
    //     ->sum('sanitation_share');
    //     $total_water_share = \DB::table('crm_rental_leases')
    //     ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
    //     ->whereNotIn('account_id', $demo_account_ids)
    //     ->where('crm_rental_leases.account_id', '>', 0)
    //     ->whereNotIn('crm_rental_leases.id', $disabled_tenant_ids)
    //     ->where('crm_rental_leases.status', '!=', 'Deleted')
    //     ->sum('crm_rental_spaces.sqm_share');
    //     $total_armed_response = $total_fixed_tenants;
    // } else {
    //     $total_fixed_tenants = \DB::table('crm_rental_leases')
    //      ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
    //     ->whereNotIn('account_id', $demo_account_ids)
    //     ->where('crm_rental_leases.status', '!=', 'Deleted')
    //     ->where('crm_rental_leases.account_id', '>', 0)
    //     ->count();
    //     $total_sa_share = \DB::table('crm_rental_leases')
    //      ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
    //     ->whereNotIn('account_id', $demo_account_ids)
    //     ->where('crm_rental_leases.status', '!=', 'Deleted')
    //     ->where('crm_rental_leases.account_id', '>', 0)
    //     ->sum('crm_rental_spaces.sqm_share');
    //     $total_sanitation_share = \DB::table('crm_rental_spaces')
    //     ->where('has_lease', 'Occupied')
    //     ->sum('sanitation_share');
    //     $total_water_share = \DB::table('crm_rental_leases')
    //      ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
    //     ->whereNotIn('account_id', $demo_account_ids)
    //     ->where('crm_rental_leases.status', '!=', 'Deleted')
    //     ->where('crm_rental_leases.account_id', '>', 0)
    //     ->sum('crm_rental_spaces.water_share');
    //     $total_armed_response = $total_fixed_tenants;
    // }

    // $rent = [
    //     'account_id' => $rental->account_id,
    //     'product_id' => 158,
    //     'created_at' => date('Y-m-d H:i:s'),
    //     'detail' => $service_reference,
    //     'status' => 'Enabled',
    // ];
    // dbinsert('sub_services', $rent);

    // $subs = \DB::table('sub_services')->where('product_id', 2)->where('status', '!=', 'Deleted')->get();
    // foreach ($subs as $sub) {
    //     $data['price'] = currency($shared_services_total / $total_fixed_tenants);
    //     $data['price_incl'] = currency($data['price'] * 1.15);
    //     \DB::table('sub_services')->where('id', $sub->id)->update($data);
    // }

    \DB::table('crm_rental_spaces')->where('id', $rental->rental_space_id)
        ->update(['rent_amount' => $rent_amount]);
    \DB::table('sub_services')->where('detail', $reference)
        ->update(['price' => $rent_amount, 'price_incl' => currency($rent_amount * 1.15), 'account_id' => $rental->account_id]);
    \DB::table('sub_services')->where('detail', $service_reference)
        ->update(['account_id' => $rental->account_id]);
    // \DB::table('sub_services')->where('detail', $water_reference)
    //     ->update(['taps' => $rental->taps, 'account_id' => $rental->account_id]);

    if ($rental->inverter_price > 0) {
        $exists = \DB::table('sub_services')->where('detail', $inverter_reference)->where('status', '!=', 'Deleted')->count();
        $price_tax = $rental->inverter_price;
        if (! $exists) {
            $services = [
                'account_id' => $rental->account_id,
                'product_id' => 151,
                'detail' => $inverter_reference,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'Enabled',
            ];
            dbinsert('sub_services', $services);
        }

        $data['price'] = currency($price_tax);
        $data['price_incl'] = currency($price_tax * 1.15);
        $data['account_id'] = $rental->account_id;

        \DB::table('sub_services')->where('detail', $inverter_reference)
            ->update($data);
    } else {
        \DB::table('sub_services')->where('detail', $inverter_reference)
            ->delete();
    }

    unset($data);

    if ($rental->internet_price > 0) {
        $exists = \DB::table('sub_services')->where('detail', $internet_reference)->where('status', '!=', 'Deleted')->count();
        if (! $exists) {
            $services = [
                'account_id' => $rental->account_id,
                'product_id' => 15,
                'detail' => $internet_reference,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'Enabled',
            ];

            dbinsert('sub_services', $services);
        }

        $data['price'] = currency($rental->internet_price);
        $data['price_incl'] = currency($data['price'] * 1.15);
        $data['account_id'] = $rental->account_id;
        \DB::table('sub_services')->where('detail', $internet_reference)
            ->update($data);
    } else {
        \DB::table('sub_services')->where('detail', $internet_reference)
            ->delete();
    }
    unset($data);
    //council services
    $service_balance = \DB::table('sub_service_balances')->where('is_deleted', 0)->orderBy('id', 'desc')->get()->first();

    $shared_services_total = $service_balance->waste_management_sanitation;
    $armed_response_total = $service_balance->armed_response;
    $water_bill = $service_balance->water_bill;

    // WATER
    $subs = \DB::table('sub_services')->where('detail', $water_reference)->where('product_id', 14)->where('status', '!=', 'Deleted')->get();

    $price_per_tap = get_price_per_tap();
    foreach ($subs as $sub) {
        $data = [];
        $price = currency($water_bill / $total_water_share);

        $office_number = str_replace('Water Usage', '', $sub->detail);
        $office_number = str_replace('Office #', '', $office_number);
        $office_number = trim($office_number);
        $account_share = \DB::table('crm_rental_leases')
            ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
            ->where('crm_rental_leases.account_id', $sub->account_id)
            ->where('crm_rental_spaces.office_number', $office_number)
            ->where('crm_rental_leases.status', '!=', 'Deleted')
            ->sum('crm_rental_spaces.water_share');

        if ($account_share == 0 || in_array($sub->account_id, $demo_account_ids)) {
            $data['price'] = 0;
            $data['price_incl'] = 0;
            \DB::table('sub_services')->where('id', $sub->id)->update($data);
        } else {
            $data['price'] = $price * $account_share;
            $data['price_incl'] = currency($data['price'] * 1.15);
            \DB::table('sub_services')->where('id', $sub->id)->update($data);
        }
    }

    // SANITATION
    // $subs = \DB::table('sub_services')->where('product_id', 2)->where('status', 'Enabled')->get();
    // foreach ($subs as $sub) {
    //     // aa($sub);
    //     $data = [];
    //     $office_number = str_replace("Sanitation & Waste", "", $sub->detail);
    //     $office_number = str_replace("Office #", "", $office_number);
    //     $office_number = trim($office_number);
    //     $account_share = \DB::table('crm_rental_leases')
    //     ->leftJoin('crm_rental_spaces','crm_rental_leases.rental_space_id','=','crm_rental_spaces.id')
    //     ->where('crm_rental_leases.account_id', $sub->account_id)
    //     ->where('crm_rental_spaces.office_number', $office_number)
    //     ->where('crm_rental_leases.status','!=','Deleted')
    //     ->sum('crm_rental_spaces.sanitation_share');
    //     // vd($account_share);
    //     if ($account_share == 0 || in_array($sub->account_id, $demo_account_ids)) {
    //         $data['price'] = 0;
    //         $data['price_incl'] = 0;
    //         \DB::table('sub_services')->where('id', $sub->id)->update($data);
    //     } else {
    //         $price = currency($account_share * $shared_services_total / $total_sanitation_share);
    //         $data['price'] = $price;
    //         $data['price_incl'] = currency($data['price'] * 1.15);
    //         \DB::table('sub_services')->where('id', $sub->id)->update($data);
    //         vd($data);
    //     }
    // }

    // ARMED RESPONSE
    // $subs = \DB::table('sub_services')->where('product_id', 148)->where('status', 'Enabled')->get();
    // foreach ($subs as $sub) {
    //     $detail = explode(' ', $sub->detail);
    //     $office_number = str_replace('Armed Response', '', $sub->detail);
    //     $office_number = str_replace('Office #', '', $office_number);
    //     $office_number = trim($office_number);
    //     $armed_response = \DB::table('crm_rental_leases')
    //     ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
    //     ->where('crm_rental_leases.status', '!=', 'Deleted')
    //     ->where('crm_rental_leases.account_id', $sub->account_id)->where('crm_rental_spaces.office_number', $office_number)
    //    ->count();
    //     if ($armed_response == 0 || in_array($sub->account_id, $demo_account_ids)) {
    //         $data['price'] = 0;
    //         $data['price_incl'] = 0;
    //         \DB::table('sub_services')->where('id', $sub->id)->update($data);
    //     } else {
    //         $data = [];
    //         $data['price'] = currency($armed_response_total / $total_armed_response);
    //         $data['price_incl'] = currency($data['price'] * 1.15);
    //         \DB::table('sub_services')->where('id', $sub->id)->update($data);
    //     }
    // }
}

function aftersave_settings_set_shared_services($request) {}

function provision_shared_services_form($provision, $input, $product, $customer)
{
    $taps = (! empty($input['taps'])) ? $input['taps'] : '';
    $office_number = (! empty($input['office_number'])) ? $input['office_number'] : '';
    $form = '';

    $form .= '<label for="taps">Number of taps</label>';
    $form .= '<input type="text" id="taps" name="taps" value="'.$taps.'" placeholder="Number of taps" /><br>';

    $form .= '<label for="office_number">Office Number</label>';
    $form .= '<input type="text" id="office_number" name="office_number" value="'.$office_number.'" placeholder="Office Number" /><br>';

    return $form;
}

function provision_shared_internet_form($provision, $input, $product, $customer)
{
    $office_number = (! empty($input['office_number'])) ? $input['office_number'] : '';
    $form = '';

    $form .= '<label for="office_number">Office Number</label>';
    $form .= '<input type="text" id="office_number" name="office_number" value="'.$office_number.'" placeholder="Office Number" /><br>';

    return $form;
}

function provision_rental_form($provision, $input, $product, $customer)
{
    $office_number = (! empty($input['office_number'])) ? $input['office_number'] : '';
    $form = '';

    $form .= '<label for="office_number">Office Number</label>';
    $form .= '<input type="text" id="office_number" name="office_number" value="'.$office_number.'" placeholder="Office Number" /><br>';

    return $form;
}

function provision_shared_services($provision, $input, $product, $customer)
{
    if (empty($input['office_number'])) {
        return 'Office number is required';
    }
    //add sms to amount
    $invoice_line = \DB::table('crm_document_lines')
        ->where(['document_id' => $provision->invoice_id, 'product_id' => $provision->product_id])
        ->get()->first();

    $info = [
        'price' => $invoice_line->full_price,
        'taps' => $input['taps'],
    ];

    return ['detail' => 'Office '.$input['office_number'].' Sanitation & Waste', 'info' => $info];
}

function provision_shared_internet($provision, $input, $product, $customer)
{
    if (empty($input['office_number'])) {
        return 'Office number is required';
    }
    //add sms to amount
    $invoice_line = \DB::table('crm_document_lines')
        ->where(['document_id' => $provision->invoice_id, 'product_id' => $provision->product_id])
        ->get()->first();

    $info = [
        'price' => $invoice_line->full_price,
    ];

    return ['detail' => 'Office '.$input['office_number'].' Internet', 'info' => $info];
}

function provision_rental($provision, $input, $product, $customer)
{
    if (empty($input['office_number'])) {
        return 'Office number is required';
    }
    //add sms to amount
    $invoice_line = \DB::table('crm_document_lines')
        ->where(['document_id' => $provision->invoice_id, 'product_id' => $provision->product_id])
        ->get()->first();

    $info = [
        'price' => $invoice_line->full_price,
    ];

    return ['detail' => 'Office '.$input['office_number'].' Rent', 'info' => $info];
}

function schedule_rent_inflation()
{
    // run on the first of every month

    $month_day = date('m-d');
    $db = new \DBEvent;

    $rentals = \DB::table('crm_rental_leases')
        ->select('crm_rental_leases.*', 'crm_rental_spaces.*', 'crm_rental_leases.id as id')
        ->join('crm_rental_spaces', 'crm_rental_leases.rental_space_id', '=', 'crm_rental_spaces.id')
        ->where('crm_rental_leases.status', '!=', 'Deleted')
        ->where('crm_rental_leases.account_id', '>', 0)->where('next_escalation_date', '<=', date('Y-m-d'))->get();

    $offices_adjusted = '';
    foreach ($rentals as $rental) {
        $expiry_day = date('Y-m-d', strtotime($rental->next_escalation_date));
        $next_escalation_date = date('Y-m-d', strtotime($expiry_day.' +1 year'));
        $office_size = $rental->office_size;
        $rent_amount = $office_size * $rental->price_per_sqm;
        $price = $rental->price_per_sqm;
        $price = $price + ($price * 0.08);
        $old_rent_amount = $office_size * $rental->price_per_sqm;
        $new_rent_amount = $office_size * $price;
        $update = [];
        $update['price_per_sqm'] = $price;
        $update['rent_amount'] = $new_rent_amount;

        \DB::table('crm_rental_spaces')->where('id', $rental->rental_space_id)->update($update);

        $office_number = $rental->office_number;

        $log = [
            'rental_id' => $rental->id,
            'account_id' => $rental->account_id,
            'old_price_per_sqm' => $rental->price_per_sqm,
            'price_per_sqm' => $price,
            'old_price' => $old_rent_amount,
            'price' => $new_rent_amount,
            'reason' => 'Annual Escalation',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $offices_adjusted .= 'Office '.$rental->office_number.'<br> Rent amount: R'.currency($old_rent_amount).'<br> New rent amount: R'.currency($new_rent_amount).'<br><br>';
        \DB::table('crm_rental_escalations')->insert($log);

        \DB::table('crm_rental_leases')->where('id', $rental->id)->update(['last_escalation_date' => $expiry_day, 'next_escalation_date' => $next_escalation_date]);

        /*
        $this_month = Carbon::parse( date('Y-m-01'))->floorMonth(); // returns 2019-07-01
        $start_month = Carbon::parse($rental->next_escalation_date)->floorMonth(); // returns 2019-06-01
        $qty = $start_month->diffInMonths($this_month);
        $amount = $new_rent_amount - $old_rent_amount;
        $total = $qty * $amount;
        $reference = 'Rental escalation '.date('F Y',strtotime($rental->next_escalation_date)).' to '.date('F Y');
        if($qty > 0){
            $db = new DBEvent();
            $data = [
            'docdate' => date('Y-m-d'),
            'doctype' => 'Tax Invoice',
            'completed' => 1,
            'account_id' => $rental->account_id,
            'total' => $total,
            'tax' => $total - ($total/1.15),
            'reference' => $reference,
            'billing_type' => '',
            'qty' => [$qty],
            'price' => [$amount/1.15],
            'full_price' => [$amount/1.15],
            'product_id' => [147],
            'description' => 'Rental inflation per month'
            ];

            $result = $db->setProperties(['validate_document' => 1])->setTable('crm_documents')->save($data);
        }
        */
    }

    if ($offices_adjusted > '') {
        $data['offices'] = $offices_adjusted;
        $function_variables = get_defined_vars();
        $data['function_name'] = __FUNCTION__;
        //$data['debug'] = 1;
        // erp_process_notification(1, $data, $function_variables);
    }
    \DB::table('crm_rental_leases')->where('account_id', 0)->update(['next_escalation_date' => null, 'lease_expiry_date' => null, 'last_escalation_date' => null]);

    $rentals = \DB::table('crm_rental_leases')->get();
    foreach ($rentals as $rental) {
        \DB::table('crm_rental_escalations')->where('rental_id', $rental->id)->where('account_id', '!=', $rental->account_id)->update(['is_deleted' => 1]);
    }
}

function afterdelete_lease_create_deactivation($request)
{
    dbinsert('sub_activations', ['account_id' => $request->account_id, 'product_id' => 152, 'provision_type' => 'lease_termination', 'status' => 'Pending']);
}

function schedule_rent_test()
{
    // run on the first of every month
}

function button_rentals_services_balances($request)
{
    return redirect()->to('/services_balances/edit/1');
}

function button_accounts_scc_form($request)
{
    return redirect()->to(attachments_url().'scc_demand_s29.pdf');
}

function button_accounts_letter_of_demand($request)
{
    $email_id = \DB::table('crm_email_manager')->where('internal_function', 'button_accounts_letter_of_demand')->pluck('id')->first();
    $data['account'] = dbgetaccount($request->id);
    $data['aging'] = $data['account']->aging;

    return email_form($email_id, $request->id, $data);
}

function button_accounts_cancellation_notice($request)
{
    $email_id = \DB::table('crm_email_manager')->where('internal_function', 'button_accounts_cancellation_notice')->pluck('id')->first();
    $data['account'] = dbgetaccount($request->id);
    $data['aging'] = $data['account']->aging;

    return email_form($email_id, $request->id, $data);
}

function button_rentals_electricity_recovered($request)
{
    $data['electricity_balance'] = \DB::table('sub_service_balances')->where('is_deleted', 0)->orderBy('id', 'desc')->pluck('electricity_balance')->first();
    $data['citiq_balance'] = \DB::table('sub_service_balances')->where('is_deleted', 0)->orderBy('id', 'desc')->pluck('citiq_balance')->first();

    return view('__app.button_views.electricity_recovered', $data);
}

function aftersave_water_readings_set_water_bill($request)
{
    $tariff_total = $request->reading / 1000 * $request->tariff_rate;
    \DB::table('crm_water_readings')->where('id', $request->id)->update(['tariff_total' => $tariff_total]);

    $premises = \DB::table('crm_water_readings')->where('is_deleted', 0)->pluck('premises')->unique()->toArray();
    foreach ($premises as $premise) {
        $last_reading = 0;

        $readings = \DB::table('crm_water_readings')->where('is_deleted', 0)->where('premises', $premise)->orderBy('date_logged', 'asc')->orderBy('id', 'asc')->get();
        foreach ($readings as $reading) {
            if (! $last_reading) {
                $reading_difference = 0;
            } else {
                $reading_difference = $last_reading - $reading->reading;
            }
            if ($reading_difference == 0) {
                \DB::table('crm_water_readings')->where('id', $reading->id)->update(['reading_difference' => $reading_difference, 'total_usage' => 0]);
            } else {
                \DB::table('crm_water_readings')->where('id', $reading->id)->update(['reading_difference' => $reading_difference, 'total_usage' => ($reading_difference / 1000) * $reading->tariff_rate]);
            }
            $last_reading = $reading->reading;
        }
    }
}

/*
    \DB::table('crm_rental_escalations')->truncate();
    $rentals = \DB::table('crm_rental_leases')->get();
    foreach($rentals as $rental){
        $price_per_sqm = $rental->lease_start_amount/$rental->office_size;
        $next_escalation_date = date('Y-m-d',strtotime($rental->lease_start_date.' +1 year'));
        $update = [
            'next_escalation_date' => $next_escalation_date,
            'last_escalation_date' => null,
            'price_per_sqm' => $price_per_sqm,
            'rent_amount' => $rental->lease_start_amount,
        ];
        \DB::table('crm_rental_leases')->where('id',$rental->id)
        ->update($update);
    }


    $db = new \DBEvent();
    $rentals = \DB::table('crm_rental_leases')->where('account_id','>',0)->where('next_escalation_date','<=',date('Y-m-d'))->orderby('office_number')->get();
    $offices_adjusted = '';
    foreach ($rentals as $rental) {
        $expiry_day =  date('Y-m-d', strtotime($rental->next_escalation_date));
        $next_escalation_date = date('Y-m-d',strtotime($expiry_day.' +1 year'));
        $office_size = $rental->office_size;
        $rent_amount = $office_size * $rental->price_per_sqm;
        $price = $rental->price_per_sqm;
        $price = $price + ($price*0.08);
        $old_rent_amount = $office_size * $rental->price_per_sqm;
        $new_rent_amount = $office_size * $price;
        $update = (array) $rental;
        $update['price_per_sqm'] = $price;
        $update['rent_amount'] = $new_rent_amount;

        $result = $db->setTable('crm_rental_leases')->save($update);

        $office_number = $rental->office_number;

        $log = [
            'rental_id' => $rental->id,
            'account_id' => $rental->account_id,
            'old_price_per_sqm' => $rental->price_per_sqm,
            'price_per_sqm' => $price,
            'old_price' => $old_rent_amount,
            'price' => $new_rent_amount,
            'reason' => 'Annual Escalation',
            'created_at' => date('Y-m-d H:i:s', strtotime($expiry_day))
        ];

        $offices_adjusted .= 'Office '.$rental->office_number.'<br> Rent amount: R'.currency($old_rent_amount).'<br> New rent amount: R'.currency($new_rent_amount).'<br><br>';
        \DB::table('crm_rental_escalations')->insert($log);

        \DB::table('crm_rental_leases')->where('id', $rental->id)->update(['last_escalation_date' => $expiry_day,'next_escalation_date'=>$next_escalation_date]);
    }


    \DB::table('crm_rental_leases')->where('account_id', 0)->update(['next_escalation_date' => null,'lease_expiry_date' => null,'last_escalation_date' => null ]);


    $rentals =  \DB::table('crm_rental_leases')->get();
    foreach ($rentals as $rental) {
        \DB::table('crm_rental_escalations')->where('rental_id', $rental->id)->where('account_id', '!=', $rental->account_id)->delete();
    }


*/
