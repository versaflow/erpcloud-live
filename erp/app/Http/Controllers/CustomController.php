<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Redirect;

class CustomController extends BaseController
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! session()->has('user_id') || empty(session('user_id')) ||
            ! session()->has('account_id') || empty(session('account_id')) ||
            ! session()->has('role_id') || empty(session('role_id'))) {
                \Auth::logout();
                \Session::flush();

                return Redirect::to('/');
            }

            return $next($request);
        });
    }

    public function supportFormPost(Request $request)
    {
        //aa($request->all());

        // Check all checklist items completed
        $row = \DB::table('hd_support_tickets')->where('id', $request->id)->get()->first();
        if ($row->email_sent) {
            return json_alert('Email already sent', 'warning', ['close_dialog' => 1]);
        }
        $template = \DB::table('hd_support_ticket_templates')->where('id', $row->template_id)->get()->first();
        $checklist_items_completed = $request->checklist_items;
        $checklist = explode(PHP_EOL, $template->checklist);
        $checklist_items = [];
        foreach ($checklist as $i => $v) {
            $checked = false;
            if ($checklist_items_completed && is_array($checklist_items_completed) && count($checklist_items_completed) > 0) {
                if (in_array(trim($v), $checklist_items_completed)) {
                    $checked = true;
                }
            }
            $checklist_items[] = ['name' => trim($v), 'checked' => $checked];
        }
        $ck_completed = true;
        foreach ($checklist_items as $ck) {
            if (! $ck['checked']) {
                $ck_completed = false;
            }
        }
        \DB::table('hd_support_tickets')->where('id', $request->id)->update(['updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default(), 'checklist_completed' => $ck_completed, 'checklist_items_completed' => json_encode($checklist_items_completed)]);
        if (! $ck_completed) {
            return json_alert('Complete all checklist items', 'warning');
        }
        if (! $row->checklist_completed) {
            return json_alert('Checklist items completed', 'success');
        }

        $customer = dbgetaccount($request->account_id);
        $mail_data = [];
        $mail_data['partner_company'] = $request->partner_company;
        $mail_data['partner_email'] = $request->partner_email;
        $mail_data['customer_type'] = $request->customer_type;
        $mail_data['subject'] = ucwords(str_replace('_', ' ', $request->subject));
        $mail_data['message'] = $request->messagebox;
        $mail_data['to_email'] = $request->emailaddress;
        $mail_data['cc_emails'] = $request->ccemailaddress;
        $mail_data['bcc_email'] = $request->bccemailaddress;
        $mail_data['message_template'] = 'default';
        $mail_data['formatted'] = 1;

        $mail_data['form_submit'] = 1;
        $mail_data['test_debug'] = 1;

        $mail_data['notification_id'] = $request->notification_id;
        try {
            if ($customer->partner_id != 1) {
                $mail_data['reseller_user_company'] = $customer->company;
                $mail_result = erp_process_notification($customer->partner_id, $mail_data);
            } else {
                $mail_result = erp_process_notification($customer->id, $mail_data);
            }
            \DB::table('hd_support_tickets')->where('id', $request->id)->update(['updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default(), 'email_sent' => 1]);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'Support Email Error');

            return json_alert('Mail error', 'warning');
        }

        return json_alert('Done');
    }

    public function debtorsFileUpload(Request $request)
    {
        try {
            return json_alert('Use accountability imports module', 'error');
            if (empty($_FILES)) {
                return json_alert('CSV File required', 'error');
            }

            if (empty($request->file('form_ab'))) {
                return json_alert('Form A and B CSV File required', 'error');
            }

            // if(empty($request->file('handover'))){
            //     return json_alert('Collections File required', 'error');
            // }

            $form_a_debtor_status_id = get_accountability_debtor_status_id('Form A');
            $form_b_debtor_status_id = get_accountability_debtor_status_id('Form B');

            $file_content = $request->file('form_ab')->get();

            $debtors_file = array_map('str_getcsv', preg_split('/\r*\n+|\r+/', trim($file_content)));
            $keys = $debtors_file[0];

            unset($debtors_file[0]);
            foreach ($debtors_file as $i => $t) {
                $debtors_file[$i] = array_combine($keys, $t);
            }
            $debtors_file = collect($debtors_file)->sortByDesc('date')->unique('Company/Individual')->filter();

            $notfound_result = '';
            $update_result = '<b>Form A and B Update</b><br>';
            foreach ($debtors_file as $row) {
                $company = $row['Company/Individual'];
                $company = str_replace([' QUERY', ' DISPUTE'], '', $company);
                $exists = \DB::table('crm_accounts')->where('company', $company)->count();

                if (! $exists) {
                    $notfound_result .= $company.' company not found.<br>';
                } else {
                    $debtor_status_id = 0;

                    if ($row['Form A'] == 'Yes') {
                        $debtor_status_id = $form_a_debtor_status_id;
                    }
                    if ($row['Form B'] == 'Yes') {
                        $debtor_status_id = $form_b_debtor_status_id;
                    }
                    if ($row['Form C'] == 'Yes') {
                        continue;
                    }

                    if ($debtor_status_id) {
                        $updated = \DB::table('crm_accounts')->where('company', $company)->update(['last_accountability_upload' => date('Y-m-d'), 'accountability_current_status_id' => $debtor_status_id]);

                        $update_result .= $company.' updated.<br>';
                    } else {
                        $update_result .= $company.' Debtor status not found.<br>';
                    }
                }
            }
            /*
            unset($debtors_file);
            $file_content = $request->file('handover')->get();

            $debtors_file = array_map("str_getcsv", preg_split('/\r*\n+|\r+/', trim($file_content)));
            $keys = $debtors_file[0];

            unset($debtors_file[0]);
            foreach ($debtors_file as $i => $t) {
                $debtors_file[$i] = array_combine($keys, $t);
            }
            $debtors_file = collect($debtors_file)->sortByDesc("date")->unique("Company/Individual")->filter();



            $update_result .= '<b>Collections Update</b><br>';
            foreach($debtors_file as $row){
                $company = $row["Company/Individual"];
                $company = str_replace([' QUERY',' DISPUTE'],'',$company);
                $exists = \DB::table('crm_accounts')->where('company',$company)->count();

                if(!$exists){
                    $notfound_result .= $company.' company not found.<br>';
                }else{
                    $debtor_status_id = 0;

                    $amount = currency(str_replace('R ','',$row['Amount']));

                    if($amount < 5000){
                        $debtor_status_id = 10;
                    }
                    if($amount > 5000){
                        $debtor_status_id = 12;
                    }

                    if($debtor_status_id){
                        $updated = \DB::table('crm_accounts')->where('company',$company)->update(['accountability_current_status_id'=>$debtor_status_id]);

                        $update_result .= $company.'.<br>';
                    }else{
                        $update_result .= $company.' Debtor status not found.<br>';
                    }
                }
            }
            */

            if ($notfound_result > '') {
                $update_result = '<b>Companies not found: </b><br>'.$notfound_result.$update_result;
            }

            \DB::table('crm_accounts')->update(['accountability_match' => 0]);
            \DB::table('crm_accounts')->whereRaw('debtor_status_id=accountability_current_status_id')->update(['accountability_match' => 1]);

            return json_alert('File Uploaded', 'success', ['update_result' => $update_result]);
        } catch (\Throwable $ex) {
            exception_log($ex);

            return json_alert($ex->getMessage().' '.$ex->getFile().':'.$ex->getLine(), 'error');
        }
    }

    public function formProductsUpdate(Request $request)
    {
        $product_list = get_transaction_products($request->account_id, $request->account_type);
        // aa($request->all());
        foreach ($request->product_id as $i => $val) {
            $product = \DB::table('crm_products')->where('id', $val)->get()->first();
            if (! $product->special_price_incl) {
                $line_qty = $request->qty[$i];
                $line_price = $request->price[$i];
                foreach ($product_list as $j => $item) {
                    if ($item->id == $val) {
                        $pricing = pricelist_get_price($request->account_id, $product->id, $request->qty[$i], $request->bill_frequency, $request->contract_period[$i]);

                        $product_list[$j]->price = currency($pricing->price);
                        $product_list[$j]->full_price = currency($pricing->full_price);
                        $product_list[$j]->full_price_incl = $pricing->full_price_incl;
                    }
                }
            }
        }

        //  aa($product_list);
        return response()->json($product_list);
    }

    public function formioAdhocSave(Request $request)
    {
        try {
            \DB::connection('default')->table('erp_adhoc_forms')->where('id', $request->id)->update(['form_json' => $request->form_json]);

            return json_alert('Saved');
        } catch (\Throwable $ex) {
            exception_log($ex);
            $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();

            return json_alert($ex->getMessage(), 'error');
        }
    }

    public function formioAdhocSubmit(Request $request)
    {
        /*
        //aa('formioSubmit');
        //aa($request->all());

        `id`
        `account_id`
        `user_id`
        `form_data`
        `user_data`
        `form_id`
        `created_at`
       */
    }

    public function formioCalculatedValues(Request $request)
    {
        $data = $request->form_data;
        $changed_field = $request->changed_field;
        $calc_function = $request->calc_function;

        return $calc_function($data, $changed_field);
    }

    public function syncfusionSelectOptions(Request $request, $field_id)
    {
        // aa($request->all());
        // aa($field_id);
        $cors = $request->header('sec-fetch-mode');
        // if (empty($cors) || $cors != 'cors') {
        //
        //    return redirect()->to('/');
        // }

        $field = \DB::connection('default')->table('erp_module_fields')->where('id', $field_id)->get()->first();
        $module = \DB::connection('default')->table('erp_cruds')->where('id', $field->module_id)->get()->first();
        $row = [];
        if (! empty($request->row_id)) {
            $row = \DB::connection($module->connection)->table($module->db_table)->where($module->db_key, $request->row_id)->get()->first();
            $row = (array) $row;
        }
        foreach ($request->all() as $key => $val) {
            if ($key != 'row_id' && $key != 'filter') {
                $row[$key] = $val;
            }
        }
        if (empty($row['connection'])) {
            $row['connection'] = $module->connection;
        }
        $results = get_module_field_options($field->module_id, $field->field, $row);

        $values = collect($results)->pluck('value')->toArray();
        $text_filter = false;
        if (! empty($request->where[0]['value'])) {
            $text_filter = $request->where[0]['value'];
        }
        if ($field->field_type == 'select_module' && ! empty($text_filter)) {
            if (is_numeric($text_filter)) {
                if (! in_array($text_filter, $values)) {
                    $filter = $text_filter;
                    $results = collect($results)->filter(function ($item) use ($filter) {
                        return str_contains(strtolower($item->text), strtolower($filter));
                    })->values()->all();
                }
            } else {
                $filter = $text_filter;
                $results = collect($results)->filter(function ($item) use ($filter) {
                    return str_contains(strtolower($item->text), strtolower($filter));
                })->values()->all();
            }
        } elseif (! empty($text_filter)) {
            $filter = $text_filter;
            $results = collect($results)->filter(function ($item) use ($filter) {
                return str_contains(strtolower($item->text), strtolower($filter));
            })->values()->all();
        }
        //aa($request->all());
        // aa($results);
        echo json_encode($results);
    }

    public function formioSelectOptions(Request $request, $field_id)
    {
        // aa($request->all());
        // aa($field_id);
        $cors = $request->header('sec-fetch-mode');
        // if (empty($cors) || $cors != 'cors') {
        //
        //    return redirect()->to('/');
        // }

        $field = \DB::connection('default')->table('erp_module_fields')->where('id', $field_id)->get()->first();
        $module = \DB::connection('default')->table('erp_cruds')->where('id', $field->module_id)->get()->first();
        $row = [];
        if (! empty($request->row_id)) {
            $row = \DB::connection($module->connection)->table($module->db_table)->where($module->db_key, $request->row_id)->get()->first();
            $row = (array) $row;
        }
        foreach ($request->all() as $key => $val) {
            if ($key != 'row_id' && $key != 'filter') {
                $row[$key] = $val;
            }
        }
        if (empty($row['connection'])) {
            $row['connection'] = $module->connection;
        }
        $results = get_module_field_options($field->module_id, $field->field, $row);

        $values = collect($results)->pluck('value')->toArray();

        if ($field->field_type == 'select_module' && ! empty($request->filter)) {
            if (is_numeric($request->filter)) {
                if (! in_array($request->filter, $values)) {
                    $filter = $request->filter;
                    $results = collect($results)->filter(function ($item) use ($filter) {
                        return str_contains(strtolower($item->text), strtolower($filter));
                    });
                }
            } else {
                $filter = $request->filter;
                $results = collect($results)->filter(function ($item) use ($filter) {
                    return str_contains(strtolower($item->text), strtolower($filter));
                });
            }
        } elseif (! empty($request->filter)) {
            $filter = $request->filter;
            $results = collect($results)->filter(function ($item) use ($filter) {
                return str_contains(strtolower($item->text), strtolower($filter));
            });
        }

        echo json_encode(['Results' => $results]);
    }

    public function formioSave(Request $request)
    {
        try {
            if (empty($request->module_id)) {
                return json_alert('Module id required', 'warning');
            }
            if (empty($request->role_id)) {
                return json_alert('Role id required', 'warning');
            }

            if (empty($request->id)) {
                $form_count = \DB::connection('default')->table('erp_forms')->where('module_id', $request->module_id)->where('role_id', $request->role_id)->count();
                if ($form_count) {
                    return json_alert('Form with role already exists', 'warning');
                }
            }

            if (! empty($request->id)) {
                $form_count = \DB::connection('default')->table('erp_forms')->where('id', '!=', $request->id)->where('module_id', $request->module_id)->where('role_id', $request->role_id)->count();
                if ($form_count) {
                    return json_alert('Form with role already exists', 'warning');
                }
            }

            if (empty($request->form_json) || $request->form_json == 'false' || $request->form_json == false) {
                return json_alert('Invalid json', 'warning');
            }

            $data = (array) $request->all();
            unset($data['copy_role_id']);
            if (empty($request->id)) {
                \DB::connection('default')->table('erp_forms')->insert($data);
            } else {
                \DB::connection('default')->table('erp_forms')->where('id', $request->id)->update($data);
            }

            if (! empty($request->copy_role_id)) {
                unset($data['id']);
                $data['role_id'] = $request->copy_role_id;
                \DB::connection('default')->table('erp_forms')->updateOrInsert(['module_id' => $data['module_id'], 'role_id' => $data['role_id']], $data);
            }

            return json_alert('Saved');
        } catch (\Throwable $ex) {
            exception_log($ex);
            $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();

            //aa($error);
            return json_alert($ex->getMessage(), 'error');
        }
    }

    public function formioSubmitFile(Request $request, $field_id = false)
    {
        if (empty($field_id)) {
            return json_alert('Field Id Required', 'warning');
        }
        $field = \DB::connection('default')->table('erp_module_fields')->where('id', $field_id)->get()->first();
        $method = $request->method();

        //aa($method);
        //aa($field_id);
        //aa($request->all());

        // UPLOAD FILE
        if ($method == 'POST') {
            if (! empty($request->{$field->field})) {
                $file = $request->{$field->field};
            } else {
                $file = $request->file;
            }
            $destinationPath = uploads_path($field->module_id);
            if (! is_dir($destinationPath)) {
                mkdir($destinationPath);
            }
            $filename = ($file->getClientOriginalName() > '') ? $file->getClientOriginalName() : $request->name;
            $uploadSuccess = $file->move($destinationPath, $filename);
            //aa($destinationPath);
            //aa($filename);
            //aa($uploadSuccess);
            if ($uploadSuccess) {
                $original_name = $file->getClientOriginalName();
                $file_mime_type = $file->getClientMimeType();
                $file_size = $file->getSize();
                $file_data = [
                    'field_id' => $field_id,
                    'original_name' => $original_name,
                    'name' => $request->name,
                    'file_size' => $file_size / 1024,
                    'file_mime_type' => $file_mime_type,
                ];
                \DB::connection('default')->table('erp_form_files')->insert($file_data);
            }
        }
        // OPEN FILE
        if ($method == 'GET') {
            return redirect()->to(uploads_url($field->module_id).'/'.$request->form);
            $destinationPath = uploads_path($field->module_id);
            $file_name = $request->form;
            $file_path = $destinationPath.'/'.$file_name;

            return file_get_contents($file_path);
        }
        // DELETE FILE
        if ($method == 'DELETE') {
            $file_name = str_replace('/', '', $file_name);
            $destinationPath = uploads_path($field->module_id);
            $file_name = $request->form;
            $file_path = $destinationPath.$file_name;
            try {
                unlink($file_path);
            } catch (\Throwable $ex) {
            }
            \DB::connection('default')->table('erp_form_files')->where('field_id', $field_id)->where('name', $file_name)->delete();
        }

        return json_alert('Saved');
    }

    public function globalSearch(Request $request)
    {
        // aa('globalSearch');
        //aa($request->all());
        try {
            $customer_module_ids = \DB::connection('default')->table('erp_menu')->where('module_id', '>', 0)->where('location', 'customer_menu')->pluck('module_id')->unique()->filter()->toArray();
            $accounts_url = get_menu_url_from_module_id(343);
            $opportunities_url = get_menu_url_from_module_id(1923);
            $debtors_url = get_menu_url_from_module_id(343);
            $leads_url = get_menu_url_from_module_id(343);
            $resellers_url = get_menu_url_from_module_id(343);

            $account_contacts_url = get_menu_url_from_table('erp_users');
            $subscriptions_url = get_menu_url_from_table('sub_services');
            $documents_url = get_menu_url_from_table('crm_documents');
            $suppliers_url = get_menu_url_from_table('crm_suppliers');
            $products_url = get_menu_url_from_table('crm_products');
            $events_url = get_menu_url_from_table('erp_form_events');
            $pricing_url = get_menu_url_from_module_id(508);
            $kb_url = get_menu_url_from_module_id(1948);

            $pricing_access = \DB::connection('default')->table('erp_forms')->where('module_id', 508)->where('role_id', session('role_id'))->where('is_view', 1)->count();
            if (! $pricing_access) {
                $pricing_url = get_menu_url_from_module_id(1931);
            }

            $global_search_modules = \DB::connection('default')->table('erp_cruds')->where('global_search', 1)->whereNotIn('id', $customer_module_ids)->get();
            $global_search_result = [];

            $autocomplete_response = [];
            $deleted_items = [];

            $c = 'default';
            if (! empty($request->params['keyword'])) {
                $search_text = $request->params['keyword'];
            } else {
                $search_text = $request->where[0]['value'];
            }
            if (! empty($request->searchtext)) {
                $search_text = $request->searchtext;
            }

            if ($request->search_type == 'customer' || $request->search_type == 'global') {
                $pbx_domains = \DB::connection('pbx')->table('v_domains')->get();

                $customers_query = \DB::connection($c)->table('crm_accounts');
                $customers_query->select('crm_accounts.id', 'company', 'currency', 'type', 'phone', 'email', 'status', 'balance', 'pbx_balance', 'pabx_domain');
                $customers_query->leftJoin('isp_voice_pbx_domains', 'isp_voice_pbx_domains.account_id', '=', 'crm_accounts.id');
                $customers_query->where('crm_accounts.status', '!=', 'Deleted');
                $customers_query->where(function ($customers_query) use ($search_text) {
                    $customers_query->orWhereRaw('LOWER(company) LIKE "%'.$search_text.'%"');
                    $customers_query->orWhereRaw('LOWER(contact) LIKE "%'.$search_text.'%"');
                });
                $customers = $customers_query->get();
                /*
                 $contact_query = \DB::connection($c)->table('erp_users');
                 $contact_query->select('erp_users.id', 'erp_users.account_id', 'erp_users.full_name', 'erp_users.type', 'erp_users.phone', 'erp_users.email', 'crm_accounts.company');
                 $contact_query->leftJoin('crm_accounts','erp_users.account_id','=','crm_accounts.id');
                 $contact_query->where('crm_accounts.status', '!=', 'Deleted');
                 $contact_query->where(function ($contact_query) use ($search_text) {
                     $contact_query->orWhereRaw('LOWER(crm_accounts.contact) LIKE "%'.$search_text.'%"');
                     $contact_query->orWhereRaw('LOWER(erp_users.phone) LIKE "%'.$search_text.'%"');
                 });
                 $contacts = $contact_query->get();
                 */

                /*
                $subscriptions_query = \DB::connection($c)->table('sub_services');
                $subscriptions_query->select('sub_services.id', 'sub_services.detail', 'crm_accounts.company', 'crm_products.code');
                $subscriptions_query->join('crm_accounts', 'crm_accounts.id', '=', 'sub_services.account_id');
                $subscriptions_query->join('crm_products', 'crm_products.id', '=', 'sub_services.product_id');
                $subscriptions_query->where(function ($subscriptions_query) use ($search_text) {
                    $subscriptions_query->orWhere('crm_accounts.company', 'LIKE', '%'.$search_text.'%');
                    $subscriptions_query->orWhere('crm_accounts.contact', 'LIKE', '%'.$search_text.'%');
                    $subscriptions_query->orWhere('sub_services.detail', 'LIKE', '%'.$search_text.'%');
                });
                $subscriptions = $subscriptions_query->where('sub_services.status', '!=', 'Deleted')->where('crm_accounts.status', '!=', 'Deleted')->get();
                */

                foreach ($customers as $customer) {
                    if ($customer->type != 'reseller') {
                        continue;
                    }
                    $url = $resellers_url;

                    $data = [
                        'id' => $customer->id,
                        'type' => 'Resellers',
                        'module_name' => 'Resellers',
                        'icon' => 'fas fa-user-friends',
                        'name' => $customer->company.' ('.$customer->status.')',
                        'phone' => $customer->phone,
                        'balance' => currency_formatted($customer->balance, $customer->currency),
                        'email' => $customer->email,
                        'account_id' => $customer->id,
                        'link' => url($url.'?id='.$customer->id),
                        'status' => $customer->status,
                        'support_link' => url($subscriptions_url.'?partner_id='.$customer->id),
                        'desc' => 'Type: '.$customer->type.' | Balance: '.currency_formatted($customer->balance, $customer->currency),
                    ];

                    if ($customer->status == 'Deleted') {
                        $deleted_items[] = $data;
                    } else {
                        $autocomplete_response[] = $data;
                    }
                }
                /*
                foreach ($contacts as $customer) {

                    $url = $account_contacts_url;
                    $data = [
                        'id' => $customer->id,
                        'type' => 'Contact',
                        'module_name' => 'Contacts',
                        'icon' => 'fas fa-phone',
                        'name' => $customer->company.' ('.$customer->phone.')',
                        'phone' => $customer->phone,
                        'account_id' => $customer->id,
                        'link' => url($url.'?id='.$customer->id),
                        'status' => $customer->status,
                        'desc' => ''

                    ];


                    $autocomplete_response[] = $data;

                }
                */
                foreach ($customers as $customer) {
                    if (str_contains($customer->type, 'reseller')) {
                        continue;
                    }
                    if ($customer->type == 'lead') {
                        continue;
                    }
                    $url = $accounts_url;

                    $data = [
                        'id' => $customer->id,
                        'type' => 'Customers',
                        'module_name' => 'Customers',
                        'icon' => 'fas fa-user-friends',
                        'name' => $customer->company.' ('.$customer->status.')',
                        'phone' => $customer->phone,
                        'balance' => currency_formatted($customer->balance, $customer->currency),
                        'email' => $customer->email,
                        'account_id' => $customer->id,
                        'link' => url($url.'?id='.$customer->id),
                        'status' => $customer->status,
                        'support_link' => url($subscriptions_url.'?account_id='.$customer->id),
                        'desc' => 'Type: '.$customer->type.' | Balance: '.currency_formatted($customer->balance, $customer->currency),
                    ];
                    if ($customer->pabx_domain > '') {
                        $data['pbx_domain'] = $customer->pabx_domain;
                        $data['pbx_balance'] = $customer->pabx_domain;
                        $data['desc'] .= ' | Airtime Balance: '.$pbx_domains->where('account_id', $customer->id)->pluck('balance')->first();
                    }
                    if ($customer->status == 'Deleted') {
                        $deleted_items[] = $data;
                    } else {
                        $autocomplete_response[] = $data;
                    }
                }

                foreach ($customers as $customer) {
                    if ($customer->type != 'reseller_user') {
                        continue;
                    }
                    $url = $accounts_url;

                    $data = [
                        'id' => $customer->id,
                        'type' => 'Reseller Users',
                        'module_name' => 'Reseller Users',
                        'icon' => 'fas fa-user-friends',
                        'name' => $customer->company.' ('.$customer->status.')',
                        'phone' => $customer->phone,
                        'balance' => currency_formatted($customer->balance, $customer->currency),
                        'email' => $customer->email,
                        'account_id' => $customer->id,
                        'link' => url($url.'?id='.$customer->id),
                        'status' => $customer->status,
                        'desc' => 'Type: '.$customer->type.' | Balance: '.currency_formatted($customer->balance, $customer->currency),
                    ];
                    if ($customer->pabx_domain > '') {
                        $data['pbx_domain'] = $customer->pabx_domain;
                        $data['pbx_balance'] = $customer->pabx_domain;
                        $data['desc'] .= ' | Airtime Balance: '.$pbx_domains->where('account_id', $customer->id)->pluck('balance')->first();
                    }
                    if ($customer->status == 'Deleted') {
                        $deleted_items[] = $data;
                    } else {
                        $autocomplete_response[] = $data;
                    }
                }

                foreach ($customers as $customer) {
                    if ($customer->type != 'lead') {
                        continue;
                    }
                    $url = $leads_url;

                    $data = [
                        'id' => $customer->id,
                        'type' => 'Leads',
                        'module_name' => 'Leads',
                        'icon' => 'fas fa-user-friends',
                        'name' => $customer->company.' ('.$customer->status.')',
                        'phone' => $customer->phone,
                        'balance' => currency_formatted($customer->balance, $customer->currency),
                        'email' => $customer->email,
                        'account_id' => $customer->id,
                        'link' => url($url.'?id='.$customer->id),
                        'status' => $customer->status,
                        'desc' => 'Type: '.$customer->type,
                    ];
                    if ($customer->status == 'Deleted') {
                        $deleted_items[] = $data;
                    } else {
                        $autocomplete_response[] = $data;
                    }
                }
            }
            /*
            if($request->search_type == 'product' || $request->search_type == 'global'){

                $products_query = \DB::connection($c)->table('crm_products');
                $products_query->join('crm_pricelist_items', 'crm_pricelist_items.product_id', '=', 'crm_products.id');
                $products_query->select(
                    'crm_products.id',
                    'crm_products.code',
                    'crm_products.name',
                    'crm_products.qty_on_hand',
                    'crm_products.upload_file',
                    'crm_products.type',
                    'crm_pricelist_items.cost_price',
                    'crm_pricelist_items.price_tax',
                    'crm_pricelist_items.reseller_price_tax',
                    'crm_pricelist_items.price_tax_6',
                    'crm_pricelist_items.price_tax_12',
                    'crm_pricelist_items.reseller_price_tax_12',
                    'crm_pricelist_items.price_tax_24',
                );
                $products_query->where(function ($products_query) use ($search_text) {
                    $products_query->orWhere('crm_products.code', 'LIKE', '%'.$search_text.'%');
                    $products_query->orWhere('crm_products.name', 'LIKE', '%'.$search_text.'%');
                });
                $products_query->where('crm_pricelist_items.pricelist_id', 1);
                $products = $products_query->where('crm_products.status', '!=', 'Deleted')->orderBy('crm_products.sort_order')->get();


                foreach ($products as $product) {
                    $data = [
                        'type' => 'Product',
                        'module_name' => 'Products',
                        'icon' => 'fas fa-box-open',
                        'name' => $product->code,
                        'desc' => $product->name,
                        'qty' => $product->qty_on_hand,
                        'price_tax' => $product->price_tax,
                        'reseller_price_tax' => $product->reseller_price_tax,
                        'price_tax_6' => currency($product->price_tax_6),
                        'price_tax_12' => currency($product->price_tax_12),
                        'reseller_price_tax_12' => currency($product->reseller_price_tax_12),
                        'annual_price' => currency($product->price_tax_12*12),
                        'reseller_annual_price' => currency($product->reseller_price_tax_12*12),
                        'price_tax_24' => currency($product->price_tax_24),
                        'img_file' => uploads_url(71).$product->upload_file,
                        'product_type' => $product->type,
                        'link' => url($products_url.'?id='.$product->id),
                        'product_link' => url($pricing_url.'?product_id='.$product->id),
                    ];


                    $data['cost_price'] = $product->cost_price;
                    $usd_pricing = \DB::connection($c)->table('crm_pricelist_items')->where('pricelist_id',2)->where('product_id',$product->id)->get()->first();

                    $data['cost_price_usd'] = $usd_pricing->cost_price;
                    $data['price_tax_usd'] = $usd_pricing->price_tax;
                    $data['reseller_price_tax_usd'] = $usd_pricing->reseller_price_tax;
                    $data['price_tax_6_usd'] = $usd_pricing->price_tax_6;
                    $data['price_tax_12_usd'] = $usd_pricing->price_tax_12;
                    $data['price_tax_24_usd'] = $usd_pricing->price_tax_24;


                    $autocomplete_response[] = $data;
                }
            }
*/

            if ($request->search_type == 'system' || $request->search_type == 'global') {
                $suppliers_query = \DB::connection($c)->table('crm_suppliers');
                $suppliers_query->select('id', 'balance', 'currency', 'company', 'phone', 'email');
                $suppliers_query->where(function ($suppliers_query) use ($search_text) {
                    $suppliers_query->orWhere('company', 'LIKE', '%'.$search_text.'%');
                    $suppliers_query->orWhere('contact', 'LIKE', '%'.$search_text.'%');
                });
                $suppliers = $suppliers_query->where('status', '!=', 'Deleted')->get();

                foreach ($suppliers as $supplier) {
                    $data = [
                        'type' => 'Supplier',
                        'module_name' => 'Suppliers',
                        'icon' => 'fas fa-shipping-fast',
                        'name' => $supplier->company.' ('.currency_formatted($supplier->balance, $supplier->currency).')',
                        'phone' => $supplier->phone,
                        'email' => $supplier->email,
                        'account_id' => $supplier->id,
                        'link' => url($suppliers_url.'?id='.$supplier->id),
                    ];
                    $autocomplete_response[] = $data;
                }

                foreach ($global_search_modules as $global_search_module) {
                    $display_field = app('erp_config')['module_fields']->where('module_id', $global_search_module->id)->where('display_field', 1)->pluck('field')->first();
                    $text_fields = app('erp_config')['module_fields']->where('module_id', $global_search_module->id)->where('field_type', 'text')->pluck('field')->toArray();
                    $modules_query = \DB::connection($global_search_module->connection)->table($global_search_module->db_table);
                    $has_is_deleted = app('erp_config')['module_fields']->where('module_id', $global_search_module->id)->where('field', 'is_deleted')->count();
                    $has_status = app('erp_config')['module_fields']->where('module_id', $global_search_module->id)->where('field', 'status')->count();

                    if ($global_search_module->db_table == 'erp_cruds') {
                        $modules_query->select($global_search_module->db_key, $display_field, 'slug');
                    } else {
                        $modules_query->select($global_search_module->db_key, $display_field);
                    }

                    $modules_query->where(function ($modules_query) use ($search_text, $display_field) {
                        $modules_query->orWhere($display_field, 'LIKE', '%'.$search_text.'%');
                    });
                    if ($has_is_deleted) {
                        $modules_query->where('is_deleted', 0);
                    }
                    if ($has_status) {
                        $modules_query->where('status', '!=', 'Deleted');
                    }

                    foreach ($text_fields as $text_field) {
                        $modules_query->orWhere(function ($modules_query) use ($search_text, $text_field) {
                            $modules_query->orWhere($text_field, 'LIKE', '%'.$search_text.'%');
                        });
                    }
                    // $r = querybuilder_to_sql($modules_query);

                    $rows = $modules_query->get();
                    foreach ($rows as $row) {
                        if ($global_search_module->db_table == 'erp_cruds') {
                            $link = $row->slug;
                        } else {
                            $link = $global_search_module->slug.'?'.$global_search_module->db_key.'='.$row->{$global_search_module->db_key};
                        }

                        $data = [
                            'type' => 'Module',
                            'module_name' => $global_search_module->name,
                            'icon' => 'fas fa-cubes',
                            'name' => $global_search_module->name.' - '.$row->{$display_field},
                            'link' => url($link),
                        ];
                        if ($global_search_module->id == 1948) {
                            $data['email_link'] = '/email_form/default/1?faq_id='.$row->id;
                        }
                        $autocomplete_response[] = $data;
                    }
                }
            }

            /*
            if($request->search_type == 'events' || $request->search_type == 'global'){

                $events_query = \DB::connection($c)->table('erp_form_events');

                $events_query->select(
                    'erp_form_events.id',
                    'erp_form_events.function_name',
                    'erp_form_events.name',
                );
                $events_query->where(function ($events_query) use ($search_text) {
                    $events_query->orWhere('erp_form_events.function_name', 'LIKE', '%'.$search_text.'%');
                    $events_query->orWhere('erp_form_events.name', 'LIKE', '%'.$search_text.'%');
                });

                $events = $events_query->orderBy('erp_form_events.sort_order')->get();


                foreach ($events as $event) {
                    $data = [
                        'type' => 'event',
                        'module_name' => 'events',
                        'icon' => 'far fa-clock',
                        'name' => $event->name,
                        'desc' => $event->function_name,
                        'link' => url($events_url.'?id='.$event->id),
                    ];


                    $autocomplete_response[] = $data;
                }
            }
            */

            foreach ($deleted_items as $deleted_item) {
                $autocomplete_response[] = $deleted_item;
            }
            foreach ($autocomplete_response as $i => $row) {
                if ($row['type'] == 'Leads' || $row['type'] == 'Customers' || $row['type'] == 'Resellers') {
                    $autocomplete_response[$i]['opp_btn'] = \DB::connection($c)->table('crm_opportunities')->where('account_id', $row['id'])->count();
                    $autocomplete_response[$i]['debtors_btn'] = \DB::connection($c)->table('crm_accounts')->where('debtor_status_id', '!=', 1)->where('accountability_match', 0)->where('id', $row['id'])->count();
                    if ($row['type'] == 'Customers') {
                        $autocomplete_response[$i]['telecoms_btn'] = \DB::connection($c)->table('isp_voice_pbx_domains')->where('account_id', $row['id'])->count();
                        $autocomplete_response[$i]['cloud_btn'] = \DB::connection($c)->table('isp_host_websites')->where('status', '!=', 'Deleted')->where('account_id', $row['id'])->count();
                        $autocomplete_response[$i]['data_btn'] = \DB::connection($c)->table('sub_services')->whereIn('provision_type', ['ip_range_gateway', 'ip_range_route', 'fibre_product', 'fibre', 'telkom_lte_sim_card', 'mtn_lte_sim_card'])->where('status', '!=', 'Deleted')->where('account_id', $row['id'])->count();
                    } else {
                        $autocomplete_response[$i]['telecoms_btn'] = 0;
                        $autocomplete_response[$i]['cloud_btn'] = 0;
                        $autocomplete_response[$i]['data_btn'] = 0;
                    }
                }
            }

            foreach ($autocomplete_response as $i => $row) {
                if (empty($autocomplete_response[$i]['instance_name'])) {
                    $autocomplete_response[$i]['opp_link'] = 'https://'.session('instance')->domain_name.'/'.$opportunities_url.'?account_id='.$row['id'];
                    $autocomplete_response[$i]['account_link'] = 'https://'.session('instance')->domain_name.'/'.$accounts_url.'?id='.$row['id'];
                    $autocomplete_response[$i]['debtors_link'] = 'https://'.session('instance')->domain_name.'/'.$debtors_url.'?id='.$row['id'];
                }
            }

            foreach ($autocomplete_response as $result) {
                $global_search_result[] = $result;
            }

            return $global_search_result;
        } catch (\Throwable $ex) {
        }
    }

    public function stripoSave(Request $request)
    {
        if (empty($request->id)) {
            return json_alert('Id required', 'error');
        }
        $main_instance = \DB::connection('system')->table('erp_instances')->where('id', 1)->pluck('domain_name')->first();
        $html = str_replace('://'.session('instance')->domain_name.'/get_email_logo', '://'.$main_instance.'/get_email_logo', $request->html);
        $html = str_replace('https://'.$main_instance.'/get_email_logo', 'http://'.$main_instance.'/get_email_logo', $html);
        $css = $request->css;

        $html_file = \Erp::encode($html);
        $css_file = \Erp::encode($css);
        $data = [
            'stripo_html' => $html_file,
            'stripo_css' => $css_file,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => get_user_id_default(),
        ];
        \DB::connection('default')->table('crm_newsletters')->where('id', $request->id)->update($data);

        return json_alert('Newsletter saved.');
    }

    public function stripoSaveDefault(Request $request)
    {
        if (! str_contains($request->html, 'get_email_logo')) {
            return json_alert('Logo url cannot be changed', 'warning');
        }

        $main_instance = \DB::connection('system')->table('erp_instances')->where('id', 1)->pluck('domain_name')->first();
        $html = str_replace('://'.session('instance')->domain_name.'/get_email_logo', '://'.$main_instance.'/get_email_logo', $request->html);
        $html = str_replace('https://'.$main_instance.'/get_email_logo', 'http://'.$main_instance.'/get_email_logo', $html);
        $css = $request->css;

        \Storage::disk('templates')->put(base_path().'/uploads/'.session('instance')->directory.'/notification_html.txt', $html);
        \Storage::disk('templates')->put(base_path().'/uploads/'.session('instance')->directory.'/notification_css.txt', $css);

        return json_alert('Default template saved.');
    }

    public function airtimeForm(Request $request)
    {
        $data = [];
        if (session('role_id') < 10) {
            $data['is_admin'] = true;
            $data['partners'] = \DB::table('crm_accounts')
                ->select('id', 'company', 'type', 'currency', 'payment_method')
                ->where('partner_id', 1)
                ->where('status', '!=', 'Deleted')
                ->whereIn('type', ['customer', 'reseller'])
                ->where('id', '!=', 1)
                ->orderBy('type')
                ->orderBy('company')
                ->get();
        } else {
            $data['is_admin'] = false;
            $data['account'] = dbgetaccount(session('account_id'));
        }

        return view('__app.components.transaction_airtime', $data);
    }

    public function airtimeFormPost(Request $request) {}

    public function companyInfoEdit(Request $request)
    {
        if (check_access('1,31')) {
            $account_data = [];
            $settings_data = [];
            foreach ($request->all() as $key => $val) {
                if ($key == '_token') {
                    continue;
                }
                if ($key == 'bank_details') {
                    $settings_data[$key] = $val;
                } else {
                    $account_data[$key] = $val;
                }
            }
            \DB::table('crm_accounts')->where('id', 1)->update($account_data);
            \DB::table('crm_account_partner_settings')->where('id', 1)->update($settings_data);

            return json_alert('Saved');
        }
    }

    public function setCDRArchive(Request $request)
    {
        if ($request->cdr_table) {
            session(['cdr_archive_table' => $request->cdr_table]);
        }

        return json_alert('Archive table set.', 'refresh_instant');
    }

    public function ledgerRebuild(Request $request)
    {
        //aa($request->all());
        try {
            // generate_stock_history_current_month();
            if ($request->ledger_date == 'All') {
                $start_year = 2016;
                $end_year = date('Y');
                $range = range($start_year, $end_year);
                foreach ($range as $year) {
                    repost_documents_by_year($request->id, $year);
                }
            } else {
                repost_documents($request->id, $request->ledger_date);
            }

            return json_alert('Done.');
        } catch (\Throwable $ex) {
            exception_log($ex->getMessage());
            exception_log($ex->getTraceAsString());

            return json_alert($ex->getMessage(), 'error');
        }
    }

    public function provisionWizard(Request $request)
    {
        $service_table = 'sub_activations';

        if (! empty($request->type) && $request->type == 'operations') {
            $service_table = 'sub_activations';
        }
        if (! empty($request->type) && $request->type == 'topup') {
            $service_table = 'sub_service_topups';
        }

        $provision = \DB::table($service_table)->where('id', $request->id)->get()->first();
        if (empty($provision)) {
            return json_alert('Invalid Id', 'error');
        }

        if ($service_table == 'sub_activations' && ! empty($provision->provision_type) && ($provision->provision_type == 'phone_number' || $provision->provision_type == 'airtime_prepaid' || $provision->provision_type == 'airtime_contract' || $provision->provision_type == 'airtime_unlimited')) {
            $extension_product_ids = get_activation_type_product_ids('pbx_extension');
            $pending_extensions = \DB::table('sub_services')->where('id', '!=', $request->id)->where('account_id', $provision->account_id)->whereIn('product_id', $extension_product_ids)->where('status', 'Pending')->count();
            if ($pending_extensions && ! in_array($provision->product_id, $extension_product_ids)) {
                return json_alert('Please provision pending extensions first.', 'warning');
            }

            $sip_trunk_product_ids = get_activation_type_product_ids('sip_trunk');
            $pending_sip_trunks = \DB::table('sub_services')->where('id', '!=', $request->id)->where('account_id', $provision->account_id)->whereIn('product_id', $sip_trunk_product_ids)->where('status', 'Pending')->count();
            if ($pending_sip_trunks && ! in_array($provision->product_id, $pending_sip_trunks)) {
                return json_alert('Please provision pending sip trunks first.', 'warning');
            }
        }

        if ($provision->status != 'Pending') {
            return json_alert('Invalid Provision Status', 'error');
        }

        $product = \DB::table('crm_products')->where('id', $provision->product_id)->get()->first();
        if (empty($product)) {
            return json_alert('Product not found', 'error');
        }

        $plan_name = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('name')->first();

        if (empty($plan_name)) {
            return json_alert('No Provision name set for this product', 'error');
        }

        // get total num pr>ovision steps
        $current_step = ! empty($provision->step) ? $provision->step : 1;
        $is_admin = (check_access('1,31')) ? 1 : 0;

        $num_steps = \DB::table('sub_activation_plans')->where('activation_type_id', $product->provision_plan_id)->where('status', 'Enabled')->count();
        $data['provision'] = $provision;
        $data['num_steps'] = $num_steps;
        $data['current_step'] = $current_step;
        $data['selected_step'] = $current_step;
        $data['service_table'] = $service_table;

        return view('__app.components.activations.wizard', $data);
    }

    public function provisionService(Request $request, $service_table, $id)
    {
        // aa('provisionService');
        try {
            // $pbx = new \FusionPBX();
            // $pbx->importDomains();
            $provision = \DB::table($service_table)->where('id', $id)->get()->first();

            $data['service_table'] = $service_table;
            if (empty($provision)) {
                return json_alert('Invalid Id2', 'error');
            }

            if ($provision->status != 'Pending') {
                return json_alert('Invalid Provision Status', 'error');
            }

            $product = \DB::table('crm_products')->where('id', $provision->product_id)->get()->first();
            if (empty($product)) {
                return json_alert('Product not found', 'error');
            }
            $plan_name = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('name')->first();
            if (empty($plan_name)) {
                return json_alert('No Provision name set for this product', 'error');
            }

            // get total num provision steps
            $current_step = ! empty($provision->step) ? $provision->step : 1;
            $is_admin = (session('role_level') == 'Admin') ? 1 : 0;

            $num_steps = \DB::table('sub_activation_plans')->where('activation_type_id', $product->provision_plan_id)->where('status', 'Enabled')->count();

            if (isset($request->step_number)) {
                $step_number = $request->step_number + 1;
                if ($current_step != $step_number) {
                    $repeatable = \DB::table('sub_activation_plans')
                        ->where('activation_type_id', $product->provision_plan_id)->where('status', 'Enabled')
                        ->where('step', $step_number)
                        ->where('repeatable', 1)
                        ->where('automated', 0)
                        ->count();
                    if (! $repeatable) {
                        return json_alert('You cannot redo this step.', 'error');
                    } else {
                        $current_step = $step_number;
                    }
                }
            }

            $provision_plans = \DB::table('sub_activation_plans')->where('activation_type_id', $product->provision_plan_id)->where('status', 'Enabled')->orderBy('step')->get();
            foreach ($provision_plans as $provision_plan) {
                if ($current_step == $provision_plan->step) {
                    if (! $is_admin && $provision_plan->admin_only) {
                        return json_alert('An administrator needs to complete the following provisioning process.', 'error');
                    }
                }
                if ($provision_plan->type == 'Email' && $provision_plan->automated && $current_step == $provision_plan->step) {
                    $step_record = \DB::table('sub_activation_steps')
                        ->where('provision_plan_id', $provision_plan->id)
                        ->where('provision_id', $provision->id)
                        ->where('service_table', $service_table)
                        ->get()->first();

                    if (empty($step_record)) {
                        \DB::table('sub_activation_steps')
                            ->insert([
                                'service_table' => $service_table,
                                'provision_id' => $provision->id,
                                'provision_plan_id' => $provision_plan->id,
                                'created_at' => date('Y-m-d H:i:s'),
                            ]);
                    }

                    $step_update_data['updated_at'] = date('Y-m-d H:i:s');

                    $mail_data = [];
                    $customer = dbgetaccount($provision->account_id);
                    $reseller = dbgetaccount($customer->partner_id);
                    if ($customer->partner_id == 1) {
                        $mail_data['partner_company'] = 'Cloud Telecoms';
                        $mail_data['partner_email'] = 'no-reply@telecloud.co.za';
                    } else {
                        $mail_data['partner_company'] = $reseller->company;
                        $mail_data['partner_email'] = $reseller->email;
                    }
                    $mail_data['parent_company'] = $mail_data['partner_company'];
                    $mail_data['account_id'] = $customer->id;
                    $mail_data['provision_id'] = $provision->id;
                    $mail_data['detail'] = $provision->detail;
                    $mail_data['customer_type'] = 'customer';
                    $mail_data['emailaddress'] = $customer->email;
                    $mail_data['bccemailaddress'] = 'ahmed@telecloud.co.za';
                    $mail_data['ccemailaddress'] = $reseller->email;
                    $mail_data['subject'] = ucwords(str_replace('_', ' ', $provision_plan->name));
                    $mail_data['message'] = $provision_plan->step_email;
                    $mail_data['message_box_id'] = $provision_plan->id;
                    $mail_data['customer'] = $customer;
                    if (! empty($provision_plan->email_id)) {
                        $mail_data['notification_id'] = $provision_plan->email_id;
                    }
                    $sub = \DB::table($service_table)->where('id', $provision->id)->get()->first();
                    $product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();
                    if (! empty($product)) {
                        $provision_plan_name = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('name')->first();
                        if (! empty($provision_plan_name)) {
                            $email_id = \DB::table('sub_activation_plans')->where('activation_type_id', $product->provision_plan_id)->where('email_id', '>', '')->pluck('email_id')->first();
                            if (! empty($email_id)) {
                                $customer = dbgetaccount($sub->account_id);

                                $mail_data['detail'] = $sub->detail;
                                $mail_data['product'] = ucwords(str_replace('_', ' ', $product->code));
                                $mail_data['product_code'] = $product->code;
                                $mail_data['product_description'] = $product->name;

                                $activation_data = get_activation_email_data($provision_plan_name, $sub, $customer, $provision, $service_table);
                                if (! empty($activation_data) && count($activation_data) > 0) {
                                    foreach ($activation_data as $k => $v) {
                                        $mail_data[$k] = $v;
                                    }
                                }
                            }
                        }
                    }
                    try {
                        $mail_data['subscription_id'] = $provision->id;
                        $mail_data['activation_email'] = true;

                        if ($customer->partner_id != 1) {
                            $mail_data['reseller_user_company'] = $customer->company;
                            $mail_result = erp_process_notification($customer->partner_id, $mail_data);
                        } else {
                            $mail_result = erp_process_notification($customer->id, $mail_data);
                        }
                        //aa($mail_result);
                    } catch (\Throwable $ex) {
                        exception_log($ex);

                        return json_alert('Mail error', 'warning');
                    }

                    $step_update_data['result'] = $mail_result;
                    \DB::table('sub_activation_steps')
                        ->where('provision_plan_id', $request->provision_plan_id)
                        ->where('provision_id', $request->provision_id)
                        ->where('service_table', $service_table)
                        ->update($step_update_data);
                } elseif ($provision_plan->type == 'Function' && $provision_plan->automated && $current_step == $provision_plan->step) {
                    if (! empty($provision->detail)) {
                        return json_alert('Provision detail already set1', 'error');
                    }
                    $step_record = \DB::table('sub_activation_steps')
                        ->where('provision_plan_id', $provision_plan->id)
                        ->where('provision_id', $provision->id)
                        ->where('service_table', $service_table)
                        ->get()->first();

                    if (empty($step_record)) {
                        \DB::table('sub_activation_steps')
                            ->insert([
                                'service_table' => $service_table,
                                'provision_id' => $provision->id,
                                'provision_plan_id' => $provision_plan->id,
                                'created_at' => date('Y-m-d H:i:s'),
                            ]);
                    }

                    $step_update_data['updated_at'] = date('Y-m-d H:i:s');
                    $provision_function = 'provision_'.function_format($provision_plan->name);
                    if (! empty($provision_plan->function_name)) {
                        $provision_function = $provision_plan->function_name;
                    }
                    // aa($provision_function);
                    if (! function_exists($provision_function)) {
                        return json_alert('Provision function does not exists', 'error');
                    }
                    $product = \DB::table('crm_products')->where('id', $provision->product_id)->get()->first();
                    $customer = dbgetaccount($provision->account_id);

                    // aa($customer);
                    if ($provision->is_test) {
                        $step_update_data['result'] = 'test';
                        $provision_result['detail'] = 'test';
                    } else {
                        $provision_result = $provision_function($provision, '', $customer, $product);
                        $step_update_data['result'] = (! empty($provision_result['detail'])) ? $provision_result['detail'] : $provision_result;
                    }

                    if ($step_update_data['result'] === true || is_array($provision_result)) {
                        $step_update_data['result'] = 'complete';
                    }
                    if (! empty($provision_result['detail'])) {
                        $step_update_data['subscription_detail'] = $provision_result['detail'];
                    }
                    if (! empty($provision_result['info'])) {
                        $step_update_data['subscription_info'] = json_encode($provision_result['info'], true);
                    }

                    if (! empty($provision_result['table_data'])) {
                        unset($provision_result['table_data']['service_table']);
                        $step_update_data['table_data'] = json_encode($provision_result['table_data'], true);
                    }

                    if (! empty($provision_result['detail'])) {
                        if ($service_table != 'sub_service_topups') {
                            \DB::table($service_table)->where('id', $provision->id)->update(['detail' => $provision_result['detail']]);
                        }
                    }

                    \DB::table('sub_activation_steps')
                        ->where('provision_plan_id', $provision_plan->id)
                        ->where('provision_id', $provision->id)
                        ->where('service_table', $service_table)
                        ->update($step_update_data);

                    if (! is_array($provision_result) && $provision_result !== true) {
                        return json_alert($provision_result, 'error');
                    }
                }

                if ($provision_plan->automated && $current_step == $provision_plan->step) {
                    if ($provision_plan->add_subscription) {
                        $subscription_data = \DB::table('sub_activation_steps')
                            ->where('provision_id', $provision->id)
                            ->where('service_table', $service_table)
                            ->whereNotNull('subscription_detail')
                            ->get()->first();
                        if (empty($subscription_data)) {
                            $j = 1;
                            $detail = $provision_plan_name.'_'.$j;
                            $detail_exists = \DB::table('sub_services')
                                ->where('account_id', $provision->account_id)
                                ->where('product_id', $provision->product_id)
                                ->where('detail', $detail)
                                ->count();
                            while ($detail_exists) {
                                $j++;
                                $detail = $provision_plan_name.'_'.$j;
                                $detail_exists = \DB::table('sub_services')
                                    ->where('account_id', $provision->account_id)
                                    ->where('product_id', $provision->product_id)
                                    ->where('detail', $detail)
                                    ->count();
                            }
                            $subscription_data = (object) ['detail' => $detail];
                        }

                        /*
                        if ($product->activation_fee > 0) {
                            $invoice_result = create_prorata_invoice($provision->account_id, $provision->product_id, $subscription_data->subscription_detail);

                            if ($invoice_result instanceof \Illuminate\Http\JsonResponse) {
                                return $invoice_result;
                            }

                            if (empty($invoice_result) || !is_array($invoice_result) || empty($invoice_result['id'])) {
                                return json_alert('Error creating activation invoice, please contact support', 'error');
                            }
                        }
                        */
                        $detail = $subscription_data->subscription_detail;
                        if (! $provision->is_test) {
                            $erp_subscription = new \ErpSubs;
                            $erp_subscription->createSubscription($provision->account_id, $provision->product_id, $detail, $provision->invoice_id, $provision->bill_frequency, $provision->bundle_id);
                        }

                        if (! empty($subscription_data->subscription_info)) {
                            $subscription_info = json_decode($subscription_data->subscription_info, true);
                            if ($service_table == 'sub_services') {
                                \DB::table('sub_services')
                                    ->where('id', $provision->id)
                                    ->update($subscription_info);
                            } else {
                                if (empty($provision->subscription_id)) {
                                    $provision->subscription_id = \DB::table('sub_services')
                                        ->where('account_id', $provision->account_id)
                                        ->where('product_id', $provision->product_id)
                                        ->where('detail', $detail)
                                        ->where('invoice_id', $provision->invoice_id)
                                        ->pluck('id')->first();
                                }

                                if ($provision->subscription_id) {
                                    \DB::table('sub_services')
                                        ->where('id', $provision->subscription_id)
                                        ->update($subscription_info);
                                }
                            }
                        }
                    }

                    if ($current_step == $num_steps) {
                        if (! empty($subscription_data->table_data)) {
                            $table_data_list = provision_get_table_data($provision->id, $service_table);

                            if (! empty($table_data_list) && is_array($table_data_list)) {
                                foreach ($table_data_list as $insert_table => $insert_data) {
                                    if (is_array($insert_data)) {
                                        $insert_table_cols = get_columns_from_schema($insert_table);
                                        if (in_array('subscription_id', $insert_table_cols)) {
                                            $insert_data['subscription_id'] = \DB::table('sub_services')
                                                ->where('account_id', $provision->account_id)
                                                ->where('product_id', $provision->product_id)
                                                ->where('detail', $detail)
                                                ->where('status', 'Enabled')->pluck('id')->first();
                                        }
                                        unset($insert_data['service_table']);

                                        if ($insert_table == 'isp_data_ip_ranges') {
                                            \DB::connection('default')->table('isp_data_ip_ranges')
                                                ->where('ip_range', $insert_data['ip_range'])
                                                ->update($insert_data);
                                        } elseif ($insert_table == 'isp_data_iptv') {
                                            unset($insert_data['trial']);
                                            \DB::connection('default')->table('isp_data_iptv')
                                                ->where('username', $insert_data['username'])
                                                ->update($insert_data);
                                        } elseif ($insert_table == 'p_phone_numbers') {
                                            $customer = dbgetaccount($provision->account_id);
                                            $domain_uuid = \DB::connection('pbx')->table('v_domains')->where('domain_name', $customer->pabx_domain)->pluck('domain_uuid')->first();

                                            pbx_add_number($customer->pabx_domain, $insert_data['number']);
                                            unset($insert_data['supplier_id']);
                                            \DB::connection('pbx')->table('p_phone_numbers')->where('number', $insert_data['number'])
                                                ->update($insert_data);
                                        } elseif ($insert_table == 'isp_host_websites') {
                                            $exists = \DB::table($insert_table)->where('domain', $insert_data['domain'])->count();
                                            if (! $exists) {
                                                \DB::table($insert_table)->insert($insert_data);
                                            } else {
                                                \DB::table($insert_table)->where('domain', $insert_data['domain'])->update(['to_register' => 1, 'status' => 'Enabled']);
                                            }
                                        } else {
                                            \DB::table($insert_table)->insert($insert_data);
                                        }
                                    }
                                }
                            }
                        }
                        $user_guide_id = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('user_guide_id')->first();
                        if ($user_guide_id) {
                            send_user_guide_to_customer($provision->account_id, $user_guide_id);
                        }
                        $date_activated = date('Y-m-d H:i:s');
                        if (! empty($subscription_info['date_activated'])) {
                            $date_activated = $subscription_info['date_activated'];
                        }

                        if ($service_table == 'sub_services') {
                            \DB::table($service_table)
                                ->where('id', $provision->id)
                                ->update(['bundle_id' => $provision->bundle_id, 'status' => 'Enabled', 'date_activated' => $date_activated, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default()]);

                            if (! empty($provision->bundle_id)) {
                                $pending_bundle_activations = \DB::table('sub_activations')->where('bundle_id', $provision->bundle_id)->where('status', 'Pending')->count();
                                if ($pending_bundle_activations == 0) {
                                    \DB::table('sub_services')->where('id', $provision->bundle_id)->where('status', 'Pending')->update(['status' => 'Enabled']);
                                }
                            }
                        } else {
                            \DB::table($service_table)
                                ->where('id', $provision->id)
                                ->update(['bundle_id' => $provision->bundle_id, 'status' => 'Enabled', 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default()]);

                            if (! empty($provision->bundle_id)) {
                                $pending_bundle_activations = \DB::table('sub_activations')->where('bundle_id', $provision->bundle_id)->where('status', 'Pending')->count();
                                if ($pending_bundle_activations == 0) {
                                    \DB::table('sub_services')->where('id', $provision->bundle_id)->where('status', 'Pending')->update(['status' => 'Enabled']);
                                }
                            }
                        }

                        module_log(554, $provision->id, 'updated', 'activation completed');
                        if (! empty(session('user_id'))) {
                            return json_alert('Provision Completed', 'success', ['close_dialog' => 1, 'close_left_dialog' => 1]);
                        }
                    } elseif ($current_step < $num_steps) {
                        $plan_name = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('name')->first();

                        $next_step = \DB::table('sub_activation_plans')->where('activation_type_id', $product->provision_plan_id)->where('step', '>', $current_step)->where('status', '!=', 'Deleted')->orderby('step')->pluck('step')->first();

                        \DB::table($service_table)
                            ->where('id', $provision->id)
                            ->update(['step' => $next_step, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default()]);
                        update_admin_only_activations();
                        module_log(554, $provision->id, 'updated', 'activation step '.$current_step.' completed');
                        $step_update_data['completed'] = 1;
                        \DB::table('sub_activation_steps')
                            ->where('provision_plan_id', $provision_plan->id)
                            ->where('provision_id', $provision->id)
                            ->where('service_table', $service_table)
                            ->update($step_update_data);

                        $current_step++;
                    }
                }

                if (! $provision_plan->automated) {
                    if ($current_step == $provision_plan->step) {
                        $data['provision_plan_id'] = $provision_plan->id;
                        $data['provision_id'] = $provision->id;
                        if ($provision_plan->type == 'Function') {
                            if ($provision->is_test) {
                                $data['provision_form'] = (! empty($provision_plan->function_name)) ? $provision_plan->function_name : $provision_plan->name.' function';
                            } else {
                                if (! empty($provision->detail)) {
                                    return json_alert('Provision detail already set', 'error');
                                }
                                $data['provision_form'] = build_provision_form($provision, $provision_plan, $service_table);
                                if (is_array($data['provision_form'])) {
                                    return json_alert(data['provision_form'][0], 'warning');
                                }

                                if (! $data['provision_form']) {
                                    return json_alert('Provision form does not exist', 'warning');
                                }
                            }
                        }

                        if ($provision_plan->type == 'Module') {
                            if (empty($provision_plan->module_id)) {
                                return json_alert('Activation type module_id not set', 'warning');
                            }
                            $menu_name = get_menu_url($provision_plan->module_id);

                            $model = new \App\Models\ErpModel;

                            $model->setMenuData($menu_name);

                            $model_data = $model->info;

                            $model_data['menu'] = $model->menu;
                            $form = new \ErpForm($model_data, ['account_id' => $provision->account_id, 'subscription_id' => $provision->id]);
                            $form->setEditType('add');
                            $row = $model->getRow(null);

                            $form_data = $form->getForm($row);
                            $data['provision_form'] = $form_data['form_html'].'<script>'.$form_data['form_script'].'</script>';
                            $data['exclude_input_script'] = true;
                        }

                        if ($provision_plan->type == 'Fibremap') {
                            $data['provision_form'] = '<iframe id="provision_iframe" src="/axxess_map_provision?service_table='.$service_table.'&num_steps='.$num_steps.'&current_step='.$current_step.'&provision_id='.$provision->id.'&provision_plan_id='.$provision_plan->id.'" width="100%" frameborder="0px" height="600px" onerror="alert(\'Failed\')" style="margin-bottom:-5px;"><!-- //required for browser compatibility --></iframe>';
                        }
                        if ($provision_plan->type == 'LTE5Gmap') {
                            $data['provision_form'] = '<iframe id="provision_iframe" src="/axxess_map_mtn5g_provision?service_table='.$service_table.'&num_steps='.$num_steps.'&current_step='.$current_step.'&provision_id='.$provision->id.'&provision_plan_id='.$provision_plan->id.'" width="100%" frameborder="0px" height="600px" onerror="alert(\'Failed\')" style="margin-bottom:-5px;"><!-- //required for browser compatibility --></iframe>';
                        }

                        if ($provision_plan->type == 'Iframe') {
                            $iframe_url = str_replace('{{$account_id}}', $provision->account_id, $provision_plan->iframe_url);
                            $iframe_url = str_replace('{{$sub_id}}', $provision->id, $iframe_url);
                            $data['provision_form'] = '<iframe id="provision_iframe" src="'.$iframe_url.'" width="100%" frameborder="0px" height="600px" onerror="alert(\'Failed\')" style="margin-bottom:-5px;"><!-- //required for browser compatibility --></iframe>';
                        }

                        if ($provision_plan->type == 'Email') {
                            $mail_data = [];
                            $customer = dbgetaccount($provision->account_id);
                            $reseller = dbgetaccount($customer->partner_id);

                            $mail_data['partner_company'] = $reseller->company;
                            $mail_data['partner_email'] = $reseller->email;

                            $mail_data['parent_company'] = $mail_data['partner_company'];
                            $mail_data['account_id'] = $customer->id;
                            $mail_data['provision_id'] = $provision->id;
                            $mail_data['detail'] = $provision->detail;

                            $mail_data['customer_type'] = 'customer';
                            $mail_data['emailaddress'] = $customer->email;
                            $mail_data['ccemailaddress'] = $reseller->email;
                            $mail_data['bccemailaddress'] = 'ahmed@telecloud.co.za';
                            $mail_data['subject'] = $provision_plan->name;
                            $mail_data['customer'] = $customer;

                            $sub = \DB::table($service_table)->where('id', $provision->id)->get()->first();
                            $product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();
                            if (! empty($product)) {
                                $provision_plan_name = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('name')->first();
                                if (! empty($provision_plan_name)) {
                                    $email_id = \DB::table('sub_activation_plans')->where('activation_type_id', $product->provision_plan_id)->where('email_id', '>', '')->pluck('email_id')->first();
                                    if (! empty($email_id)) {
                                        $customer = dbgetaccount($sub->account_id);

                                        $mail_data['detail'] = $sub->detail;
                                        $mail_data['product'] = ucwords(str_replace('_', ' ', $product->code));
                                        $mail_data['product_code'] = $product->code;
                                        $mail_data['product_description'] = $product->name;

                                        $subscription_data = \DB::table('sub_activation_steps')
                                            ->where('provision_id', $provision->id)
                                            ->where('service_table', $service_table)
                                            ->whereNotNull('subscription_detail')->get()->first();

                                        $activation_data = get_activation_email_data($provision_plan_name, $sub, $customer, $provision, $service_table);
                                        if (! empty($activation_data) && count($activation_data) > 0) {
                                            foreach ($activation_data as $k => $v) {
                                                $mail_data[$k] = $v;
                                            }
                                        }
                                    }
                                }
                            }

                            if (! empty($provision_plan->email_id)) {
                                $newsletter = \DB::table('crm_email_manager')->where('id', $provision_plan->email_id)->get()->first();
                                $subject = $newsletter->name;
                                $mail_data['subject'] = $newsletter->name;

                                //aa($mail_data);
                                $mail_data['html'] = get_email_html($customer->id, $reseller->id, $mail_data, $newsletter);

                                //aa($mail_data['html']);
                                $mail_data['css'] = '';
                                $template_file = '_emails.gjs';
                            }

                            $mail_data['message'] = view($template_file, $mail_data);

                            //aa($mail_data['message']);

                            $mail_data['exclude_script'] = true;
                            $data['exclude_form_script'] = true;
                            $mail_data['subscription_id'] = $provision->id;
                            $mail_data['activation_email'] = 1;
                            $data['provision_form'] = email_form($provision_plan->email_id, $provision->account_id, $mail_data);
                        }

                        if ($provision_plan->type == 'Checklist') {
                            $product_monthly = \DB::table('crm_products')->where('is_subscription', 1)->where('id', $provision->product_id)->count();
                            if ($product_monthly && $provision_plan->name == 'products') {
                                $product_monthly = \DB::table('crm_products')->where('is_subscription', 1)->where('id', $provision->product_id)->count();

                                $data['provision_form'] = account_has_processed_debit_order($provision->account_id);
                            }
                            if (! empty($data['provision_form'])) {
                                $data['provision_form'] = $data['provision_form'].'<br>'.build_provision_checklist($provision, $provision_plan, $service_table);
                            } else {
                                $data['provision_form'] = build_provision_checklist($provision, $provision_plan, $service_table);
                            }
                        }

                        if ($provision_plan->type == 'Debitorder') {
                            $account = dbgetaccount($provision->account_id);

                            $data['provision_form'] = account_has_processed_debit_order($provision->account_id);
                        }
                    }
                }
            }

            $data['provision'] = $provision;
            $data['provision_plans'] = $steps;
            $data['num_steps'] = $num_steps;
            $data['current_step'] = $current_step;
            $num_automated = \DB::table('sub_activation_plans')
                ->where('activation_type_id', $product->provision_plan_id)
                ->where('automated', 1)
                ->where('status', 'Enabled')
                ->where('step', '<', $current_step)
                ->count();
            $num_deleted = \DB::table('sub_activation_plans')
                ->where('activation_type_id', $product->provision_plan_id)
                ->where('status', 'Deleted')
                ->count();
            $data['selected_tab'] = (($current_step - $num_automated) - 1);
            $data['menu_route'] = $this->data['menu_route'];
            if (! empty($request->topup)) {
                $data['topup'] = 1;
            } else {
                $data['topup'] = 0;
            }

            $data['service_table'] = $service_table;

            return view('__app.components.activations.wizard_step', $data);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'provision error');

            return response()->json(['status' => 'error', 'message' => 'An error occurred4']);
        }
    }

    public function provisionServicePost(Request $request)
    {
        try {
            $service_table = $request->service_table;

            $provision = \DB::table($service_table)->where('id', $request->provision_id)->get()->first();

            $provision_plan = \DB::table('sub_activation_plans')->where('id', $request->provision_plan_id)->get()->first();
            if ($provision_plan->step < $provision->step && ! $provision_plan->repeatable) {
                return json_alert('Invalid Provision Step', 'warning');
            }
            $product = \DB::table('crm_products')->where('id', $provision->product_id)->get()->first();
            $customer = dbgetaccount($provision->account_id);

            //	return json_alert('Unavailable','error');

            $form_step = $request->form_step + 1;
            $current_step = $provision->step;
            $num_steps = $request->num_steps;
            $succes_msg = '';
            $step_record = \DB::table('sub_activation_steps')
                ->where('provision_plan_id', $request->provision_plan_id)
                ->where('provision_id', $request->provision_id)
                ->where('service_table', $service_table)
                ->get()->first();

            if (empty($step_record)) {
                \DB::table('sub_activation_steps')
                    ->insert([
                        'service_table' => $service_table,
                        'provision_id' => $request->provision_id,
                        'provision_plan_id' => $request->provision_plan_id,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            $step_update_data['updated_at'] = date('Y-m-d H:i:s');

            $provision = \DB::table($service_table)->where('id', $request->provision_id)->get()->first();

            $provision_plan = \DB::table('sub_activation_plans')->where('id', $request->provision_plan_id)->get()->first();
            if ($provision_plan->step < $provision->step && ! $provision_plan->repeatable) {
                return json_alert('Invalid Provision Step', 'warning');
            }
            if ($provision_plan->type == 'Fibremap') {
                $default_values = ['provision_plan_id', 'provision_id', 'current_step', 'num_steps', 'form_step', 'topup'];
                foreach ($request->all() as $k => $v) {
                    if (! in_array($k, $default_values)) {
                        $inputs[$k] = $v;
                    }
                }
                $request->addressinput = $request->{'address-input'};
                $request->latlonginput = $request->{'latlong-input'};

                if (empty($request->addressinput) || empty($request->latlonginput)) {
                    return json_alert('Address required', 'error');
                }

                $verified_latlong = get_lat_long($request->addressinput);

                if ($verified_latlong == ',') {
                    $verified_latlong = '0,0';
                }

                if ($verified_latlong != $request->latlonginput) {
                    $request->latlonginput = $verified_latlong;
                }
                $latlong = explode(',', $request->latlonginput);
                $mapdata = ['lat' => $latlong[0], 'long' => $latlong[1]];
                if ($request->latlonginput == '0,0') {
                    return json_alert('No fibre available for this location.', 'error', $mapdata);
                }
                $latlong = $request->latlonginput;
                $address = $request->addressinput;
                $latlong_arr = explode(',', $latlong);

                $axxess = new \Axxess;
                //$axxess = $axxess->setDebug();
                $available = $axxess->checkFibreAvailability($latlong_arr[0], $latlong_arr[1], $address);

                if ($available->intCode != 200 || count($available->arrAvailableProvidersGuids) == 0) {
                    return json_alert('A fibre provider is not available for this location.', 'error', $mapdata);
                }

                $available_products = '';
                foreach ($available->arrAvailableProvidersGuids as $provider) {
                    if ($provider->intPreOrder == 0 && ! empty($provider->guidNetworkProviderId)) {
                        $available_products_arr = \DB::table('isp_data_products')
                            ->where('guidNetworkProviderId', $provider->guidNetworkProviderId)
                            ->where('product_id', '!=', 0)
                            ->where('status', 'Enabled')
                            ->get();
                        foreach ($available_products_arr as $ap) {
                            $available_products .= '<br>'.ucfirst($ap->provider).': '.$ap->product;
                        }
                    }
                }

                if (empty($available_products)) {
                    return json_alert('A fibre provider is not available for this location.', 'error', $mapdata);
                }

                $inputs['address-input'] = $request->addressinput;
                $inputs['latlong-input'] = $request->latlonginput;
                $step_update_data['input'] = json_encode($inputs);
                \DB::table('sub_activation_steps')
                    ->where('provision_plan_id', $request->provision_plan_id)
                    ->where('provision_id', $request->provision_id)
                    ->where('service_table', $service_table)
                    ->update($step_update_data);
            }

            if ($provision_plan->type == 'LTE5Gmap') {
                $default_values = ['provision_plan_id', 'provision_id', 'current_step', 'num_steps', 'form_step', 'topup'];
                foreach ($request->all() as $k => $v) {
                    if (! in_array($k, $default_values)) {
                        $inputs[$k] = $v;
                    }
                }
                // aa('LTE5Gmap');
                // aa($request->all());
                $request->addressinput = $request->{'address-input'};
                $request->latlonginput = $request->{'latlong-input'};

                if (empty($request->addressinput) || empty($request->latlonginput)) {
                    return json_alert('Address required', 'error');
                }

                $verified_latlong = get_lat_long($request->addressinput);

                if ($verified_latlong == ',') {
                    $verified_latlong = '0,0';
                }

                if ($verified_latlong != $request->latlonginput) {
                    $request->latlonginput = $verified_latlong;
                }
                $latlong = explode(',', $request->latlonginput);
                $mapdata = ['lat' => $latlong[0], 'long' => $latlong[1]];
                if ($request->latlonginput == '0,0') {
                    return json_alert('No fibre available for this location.', 'error', $mapdata);
                }
                $latlong = $request->latlonginput;
                $address = $request->addressinput;
                $latlong_arr = explode(',', $latlong);

                $axxess = new \Axxess;
                //$axxess = $axxess->setDebug();
                $available = $axxess->checkFibreAvailability($latlong_arr[0], $latlong_arr[1], $address, $strBBox, $strWidth, $strHeight, $strICoOrdinate, $strJCoOrdinate);
                //checkMtn5GAvailability($strLatitude, $strLongitude, $strAddress, $strBBox, $strWidth, $strHeight, $strICoOrdinate, $strJCoOrdinate)
                if ($available->intCode != 200 || count($available->arrAvailableProvidersGuids) == 0) {
                    return json_alert('A fibre provider is not available for this location.', 'error', $mapdata);
                }

                $inputs['address-input'] = $request->addressinput;
                $inputs['latlong-input'] = $request->latlonginput;
                $step_update_data['input'] = json_encode($inputs);
                \DB::table('sub_activation_steps')
                    ->where('provision_plan_id', $request->provision_plan_id)
                    ->where('provision_id', $request->provision_id)
                    ->where('service_table', $service_table)
                    ->update($step_update_data);
            }

            if ($provision_plan->type == 'Module') {
                $provision_form_fields = ['provision_plan_id', 'provision_id', 'current_step', 'num_steps', 'service_table'];
                $request->replace($request->except($provision_form_fields));
                $erp = new \DBEvent;
                $erp->setModule($provision_plan->module_id);
                $result = $erp->save($request);
                if (! is_array($result) || empty($result['id'])) {
                    return $result;
                }
            }

            if ($provision_plan->type == 'Iframe') {
                \DB::table($service_table)
                    ->where('id', $provision->id)
                    ->increment('step');

                if (empty($succes_msg)) {
                    $succes_msg = 'Provision step completed';
                }
                $provision_url = url('provision?id='.$request->provision_id);
                if ($service_table == 'sub_service_topups') {
                    $provision_url .= '&type=topup';
                }
                if ($service_table == 'sub_activations') {
                    $provision_url .= '&type=operations';
                }

                return json_alert($succes_msg, 'info', ['provision_id' => $request->provision_id, 'provision_url' => $provision_url]);
            }

            if ($provision_plan->type == 'Function') {
                if (! empty($provision->detail)) {
                    // return json_alert('Provision detail already set2', 'error');
                }
                $default_values = ['provision_plan_id', 'provision_id', 'current_step', 'num_steps', 'form_step', 'topup'];
                $inputs = [];

                foreach ($request->all() as $k => $v) {
                    if (! in_array($k, $default_values)) {
                        $inputs[$k] = $v;
                    }
                }
                $step_update_data['input'] = json_encode($inputs);
                \DB::table('sub_activation_steps')
                    ->where('provision_plan_id', $request->provision_plan_id)
                    ->where('provision_id', $request->provision_id)
                    ->where('service_table', $service_table)
                    ->update($step_update_data);

                $provision_function = 'provision_'.function_format($provision_plan->name);

                if (! empty($provision_plan->function_name)) {
                    $provision_function = $provision_plan->function_name;
                }
                if (! function_exists($provision_function)) {
                    return json_alert('Provision function does not exist.', 'error');
                }

                $step_record = \DB::table('sub_activation_steps')
                    ->where('provision_plan_id', $request->provision_plan_id)
                    ->where('provision_id', $request->provision_id)
                    ->where('service_table', $service_table)
                    ->get()->first();

                $input_data = json_decode($step_record->input, true);

                if ($provision->is_test) {
                    $step_update_data['result'] = 'test';
                    $provision_result['detail'] = 'test';
                } else {
                    $provision_result = $provision_function($provision, $input_data, $customer, $product);
                    $step_update_data['result'] = (! empty($provision_result['detail'])) ? $provision_result['detail'] : $provision_result;
                }

                if ($step_update_data['result'] === true || is_array($provision_result)) {
                    $step_update_data['result'] = 'complete';
                }
                if (! empty($provision_result['detail'])) {
                    $step_update_data['subscription_detail'] = $provision_result['detail'];
                }
                if (! empty($provision_result['info'])) {
                    $step_update_data['subscription_info'] = json_encode($provision_result['info'], true);
                }

                if (! empty($provision_result['table_data'])) {
                    unset($provision_result['table_data']['service_table']);
                    $step_update_data['table_data'] = json_encode($provision_result['table_data'], true);
                }

                if (! empty($provision_result['detail'])) {
                    \DB::table($service_table)->where('id', $provision->id)->update(['detail' => $provision_result['detail'], 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default()]);
                }

                if (! empty($provision_result['ref'])) {
                    if ($service_table != 'sub_service_topups') {
                        \DB::table($service_table)->where('id', $provision->id)->update(['ref' => $provision_result['ref']]);
                    }
                }

                \DB::table('sub_activation_steps')
                    ->where('provision_plan_id', $request->provision_plan_id)
                    ->where('provision_id', $request->provision_id)
                    ->where('service_table', $service_table)
                    ->update($step_update_data);

                if (! is_array($provision_result) && $provision_result !== true) {
                    return json_alert($provision_result, 'Provision Result Error');
                }
            }

            if ($provision_plan->type == 'Email') {
                $mail_data['partner_company'] = $request->partner_company;
                $mail_data['partner_email'] = $request->partner_email;
                $mail_data['customer_type'] = $request->customer_type;
                $mail_data['subject'] = ucwords(str_replace('_', ' ', $request->subject));
                $mail_data['message'] = $request->messagebox;
                $mail_data['to_email'] = $request->emailaddress;
                $mail_data['cc_emails'] = $request->ccemailaddress;
                $mail_data['bcc_email'] = $request->bccemailaddress;
                $mail_data['message_template'] = 'default';
                $mail_data['formatted'] = 1;

                $mail_data['form_submit'] = 1;
                //$mail_data['test_debug'] = 1;
                $mail_data['activation_email'] = true;
                $mail_data['notification_id'] = $request->notification_id;
                try {
                    if ($customer->partner_id != 1) {
                        $mail_data['reseller_user_company'] = $customer->company;
                        $mail_result = erp_process_notification($customer->partner_id, $mail_data);
                    } else {
                        $mail_result = erp_process_notification($customer->id, $mail_data);
                    }
                } catch (\Throwable $ex) {
                    exception_log($ex);
                    exception_email($ex, 'Provision Email Error');
                    \Log::debug($ex);

                    return json_alert('Mail error', 'warning');
                }

                $step_update_data['result'] = $mail_result;

                $plan_name = \DB::table('sub_activation_plans')->where('id', $request->provision_plan_id)->pluck('name')->first();
                $has_function_step = \DB::table('sub_activation_plans')->where('activation_type_id', $product->provision_plan_id)->where('type', 'Function')->count();
                if (! $has_function_step) {
                    $step_update_data['subscription_detail'] = $provision->provision_type.'_'.$provision->id;
                }

                \DB::table('sub_activation_steps')
                    ->where('provision_plan_id', $request->provision_plan_id)
                    ->where('provision_id', $request->provision_id)
                    ->where('service_table', $service_table)
                    ->update($step_update_data);

                if ($mail_result != 'Sent') {
                    return json_alert($mail_result, 'error');
                }

                $succes_msg = $mail_result;
            }

            if ($provision_plan->type == 'Checklist') {
                $product_monthly = \DB::table('crm_products')->where('is_subscription', 1)->where('id', $provision->product_id)->count();
                if ($product_monthly && $provision_plan->name == 'products') {
                    $step_update_data['input'] = account_has_processed_debit_order($provision->account_id);
                    if ($step_update_data['input'] != 'Debit order processed.') {
                        return json_alert($step_update_data['input'], 'warning', ['close_dialog' => 1, 'close_left_dialog' => 1]);
                    }
                }
                $default_values = ['provision_plan_id', 'provision_id', 'current_step', 'num_steps', 'form_step'];
                $checklist_post = [];

                foreach ($request->all() as $k => $v) {
                    if (! in_array($k, $default_values)) {
                        $checklist_post[] = $k;
                    }
                }
                $step_update_data['input'] = json_encode($checklist_post);
                \DB::table('sub_activation_steps')
                    ->where('provision_plan_id', $request->provision_plan_id)
                    ->where('provision_id', $request->provision_id)
                    ->where('service_table', $service_table)
                    ->update($step_update_data);
                //update checklist from post
                $checklist_plan = $provision_plan->step_checklist;
                $checklist = explode(PHP_EOL, $checklist_plan);
                foreach ($checklist as $i => $list_item) {
                    $item_id = 'checklist_item_'.$i;
                    if (! in_array($item_id, $checklist_post)) {
                        return json_alert('Complete checklist to continue', 'warning', ['close_dialog' => 1, 'close_left_dialog' => 1]);
                    }
                }
            }

            if ($provision_plan->type == 'Debitorder') {
                if (! empty($request->retry_debit_order)) {
                    $step_update_data['input'] = account_has_processed_debit_order($provision->account_id, true);
                } else {
                    $step_update_data['input'] = account_has_processed_debit_order($provision->account_id, false);
                }
                if ($step_update_data['input'] != 'Debit order processed.') {
                    return json_alert($step_update_data['input'], 'warning', ['close_dialog' => 1, 'close_left_dialog' => 1]);
                }

                \DB::table('sub_activation_steps')
                    ->where('provision_plan_id', $request->provision_plan_id)
                    ->where('provision_id', $request->provision_id)
                    ->where('service_table', $service_table)
                    ->update($step_update_data);
            }

            if ($provision_plan->step < $current_step) {
                return json_alert('Repeatable step completed', 'info', ['provision_id' => $request->provision_id]);
            }

            if ($provision_plan->add_subscription) {
                $subscription_data = \DB::table('sub_activation_steps')
                    ->where('service_table', $service_table)
                    ->where('provision_id', $provision->id)
                    ->whereNotNull('subscription_detail')->get()->first();

                if (empty($subscription_data)) {
                    $j = 1;
                    $detail = $provision_plan_name.'_'.$j;
                    $detail_exists = \DB::table('sub_services')
                        ->where('account_id', $provision->account_id)
                        ->where('product_id', $provision->product_id)
                        ->where('detail', $detail)
                        ->count();
                    // while($detail_exists){
                    //     $j++;
                    //     $detail = $provision_plan_name.'_'.$j;
                    //     $detail_exists = \DB::table('sub_services')
                    //     ->where('account_id',$provision->account_id)
                    //     ->where('product_id',$provision->product_id)
                    //     ->where('detail',$detail)
                    //     ->count();
                    // }
                    $subscription_data = (object) ['detail' => $detail];
                }
                /*
                if ($product->activation_fee > 0) {
                    $invoice_result = create_prorata_invoice($provision->account_id, $provision->product_id, $subscription_data->subscription_detail);
                    if ($invoice_result instanceof \Illuminate\Http\JsonResponse) {
                        return $invoice_result;
                    }
                    if (empty($invoice_result) || !is_array($invoice_result) || empty($invoice_result['id'])) {
                        return json_alert('Error creating activation invoice, please contact support', 'error');
                    }
                }
                */
                // aa($subscription_data);
                $detail = $subscription_data->subscription_detail;

                if (! $provision->is_test) {
                    $erp_subscription = new \ErpSubs;
                    $erp_subscription->createSubscription($provision->account_id, $provision->product_id, $detail, $provision->invoice_id, $provision->bill_frequency, $provision->bundle_id);
                }

                if (! empty($subscription_data->subscription_info)) {
                    $subscription_info = json_decode($subscription_data->subscription_info, true);
                    if ($provision->product_id == 126) {
                        \DB::table('sub_services')
                            ->where('account_id', $provision->account_id)
                            ->where('product_id', $subscription_info['product_id'])
                            ->where('detail', $detail)
                            ->where('status', 'Enabled')
                            ->delete();
                    }
                    \DB::table('sub_services')
                        ->where('account_id', $provision->account_id)
                        ->where('product_id', $provision->product_id)
                        ->where('detail', $detail)
                        ->where('status', 'Enabled')
                        ->update($subscription_info);
                }

                if (str_contains($provision->provision_type, 'ip_range')) {
                    \DB::table('isp_data_ip_ranges')->where('ip_range', $detail)->update(['account_id' => $provision->account_id]);
                }

                if (! empty($subscription_data->table_data)) {
                    $table_data_list = provision_get_table_data($provision->id, $service_table);
                    // aa($table_data_list);
                    if (! empty($table_data_list) && is_array($table_data_list)) {
                        foreach ($table_data_list as $insert_table => $insert_data) {
                            if (is_array($insert_data)) {
                                $insert_table_cols = get_columns_from_schema($insert_table);
                                if (in_array('subscription_id', $insert_table_cols)) {
                                    $insert_data['subscription_id'] = \DB::table('sub_services')
                                        ->where('account_id', $provision->account_id)
                                        ->where('product_id', $provision->product_id)
                                        ->where('detail', $detail)
                                        ->where('status', 'Enabled')->pluck('id')->first();
                                }
                                // aa($insert_data['subscription_id']);
                                unset($insert_data['service_table']);

                                if ($insert_table == 'isp_data_ip_ranges') {
                                    \DB::connection('default')->table('isp_data_ip_ranges')
                                        ->where('ip_range', $insert_data['ip_range'])
                                        ->update($insert_data);
                                } elseif ($insert_table == 'isp_data_iptv') {
                                    unset($insert_data['trial']);
                                    $r = \DB::connection('default')->table('isp_data_iptv')
                                        ->where('username', $insert_data['username'])
                                        ->update($insert_data);
                                } elseif ($insert_table == 'p_phone_numbers') {
                                    $customer = dbgetaccount($provision->account_id);
                                    $domain_uuid = \DB::connection('pbx')->table('v_domains')->where('domain_name', $customer->pabx_domain)->pluck('domain_uuid')->first();

                                    pbx_add_number($customer->pabx_domain, $insert_data['number']);
                                    unset($insert_data['supplier_id']);
                                    \DB::connection('pbx')->table('p_phone_numbers')->where('number', $insert_data['number'])
                                        ->update($insert_data);
                                } else {
                                    if ($insert_table == 'isp_host_websites') {
                                        $exists = \DB::table($insert_table)->where('domain', $insert_data['domain'])->count();
                                        if (! $exists) {
                                            \DB::table($insert_table)->insert($insert_data);
                                        } else {
                                            \DB::table($insert_table)->where('domain', $insert_data['domain'])->update(['to_register' => 1, 'status' => 'Enabled']);
                                        }
                                    } else {
                                        \DB::table($insert_table)->insert($insert_data);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // aa($current_step);
            // aa($num_steps);
            if ($current_step == $num_steps) {
                $step_update_data['completed'] = 1;
                $date_activated = date('Y-m-d H:i:s');

                $user_guide_id = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('user_guide_id')->first();
                if ($user_guide_id) {
                    send_user_guide_to_customer($provision->account_id, $user_guide_id);
                }

                if (! empty($subscription_info['date_activated'])) {
                    $date_activated = $subscription_info['date_activated'];
                }
                \DB::table('sub_activation_steps')
                    ->where('provision_plan_id', $request->provision_plan_id)
                    ->where('provision_id', $request->provision_id)
                    ->where('service_table', $service_table)
                    ->update($step_update_data);
                if ($service_table == 'sub_services') {
                    \DB::table($service_table)
                        ->where('id', $provision->id)
                        ->update(['bundle_id' => $provision->bundle_id, 'status' => 'Enabled', 'date_activated' => $date_activated, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default()]);
                    if (! empty($provision->bundle_id)) {
                        $pending_bundle_activations = \DB::table('sub_activations')->where('bundle_id', $provision->bundle_id)->where('status', 'Pending')->count();
                        if ($pending_bundle_activations == 0) {
                            \DB::table('sub_services')->where('id', $provision->bundle_id)->where('status', 'Pending')->update(['status' => 'Enabled']);
                        }
                    }
                } else {
                    \DB::table($service_table)
                        ->where('id', $provision->id)
                        ->update(['bundle_id' => $provision->bundle_id, 'status' => 'Enabled', 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default()]);
                    if (! empty($provision->bundle_id)) {
                        $pending_bundle_activations = \DB::table('sub_activations')->where('bundle_id', $provision->bundle_id)->where('status', 'Pending')->count();
                        if ($pending_bundle_activations == 0) {
                            \DB::table('sub_services')->where('id', $provision->bundle_id)->where('status', 'Pending')->update(['status' => 'Enabled']);
                        }
                    }
                }

                module_log(554, $provision->id, 'updated', 'activation completed');

                return json_alert('Provision Completed', 'success', ['close_dialog' => 1, 'close_left_dialog' => 1]);
            } elseif ($current_step < $num_steps) {
                $plan_name = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('name')->first();

                $next_step = \DB::table('sub_activation_plans')->where('activation_type_id', $product->provision_plan_id)->where('step', '>', $current_step)->where('status', '!=', 'Deleted')->orderby('step')->pluck('step')->first();

                \DB::table($service_table)
                    ->where('id', $provision->id)
                    ->update(['step' => $next_step, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default()]);
                update_admin_only_activations();

                module_log(554, $provision->id, 'updated', 'activation step '.$current_step.' completed');

                $step_update_data['completed'] = 1;
                \DB::table('sub_activation_steps')
                    ->where('provision_plan_id', $request->provision_plan_id)
                    ->where('provision_id', $request->provision_id)
                    ->where('service_table', $service_table)
                    ->update($step_update_data);

                if (empty($succes_msg)) {
                    $succes_msg = 'Provision step completed';
                }
                $provision_url = url('provision?id='.$request->provision_id);
                if ($service_table == 'sub_service_topups') {
                    $provision_url .= '&type=topup';
                }
                if ($service_table == 'sub_activations') {
                    $provision_url .= '&type=operations';
                }

                return json_alert($succes_msg, 'info', ['provision_id' => $request->provision_id, 'provision_url' => $provision_url]);
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'provision error');
            exception_log($ex);

            return response()->json(['status' => 'error', 'message' => 'An error occurred5']);
        }
    }

    public function deactivateWizard(Request $request)
    {
        $service_table = 'sub_activations';

        $provision = \DB::table($service_table)->where('id', $request->id)->get()->first();

        if (empty($provision)) {
            return json_alert('Invalid Id', 'error');
        }

        if ($service_table == 'sub_activations' && ! empty($provision->provision_type) && ($provision->provision_type == 'phone_number' || $provision->provision_type == 'airtime_prepaid' || $provision->provision_type == 'airtime_contract' || $provision->provision_type == 'airtime_unlimited')) {
            $extension_product_ids = get_activation_type_product_ids('pbx_extension');
            $pending_extensions = \DB::table('sub_services')->where('id', '!=', $request->id)->where('account_id', $provision->account_id)->whereIn('product_id', $extension_product_ids)->where('status', 'Pending')->count();
            if ($pending_extensions && ! in_array($provision->product_id, $extension_product_ids)) {
                return json_alert('Please provision pending extensions first.', 'warning');
            }

            $sip_trunk_product_ids = get_activation_type_product_ids('sip_trunk');
            $pending_sip_trunks = \DB::table('sub_services')->where('id', '!=', $request->id)->where('account_id', $provision->account_id)->whereIn('product_id', $sip_trunk_product_ids)->where('status', 'Pending')->count();
            if ($pending_sip_trunks && ! in_array($provision->product_id, $pending_sip_trunks)) {
                return json_alert('Please provision pending sip trunks first.', 'warning');
            }
        }

        if ($provision->status != 'Pending') {
            return json_alert('Invalid Provision Status', 'error');
        }

        $product = \DB::table('crm_products')->where('id', $provision->product_id)->get()->first();
        if (empty($product)) {
            return json_alert('Product not found', 'error');
        }

        $plan_name = \DB::table('sub_activation_types')->where('id', $product->deactivate_plan_id)->pluck('name')->first();

        if (empty($plan_name)) {
            $plan_name = $provision->provision_type;
            $product->deactivate_plan_id = \DB::table('sub_activation_types')->where('name', $plan_name)->pluck('id')->first();
        }

        if (empty($plan_name)) {
            return json_alert('No Provision name set for this product', 'error');
        }

        // get total num provision steps
        $current_step = ! empty($provision->step) ? $provision->step : 1;
        $is_admin = (check_access('1,31')) ? 1 : 0;

        $num_steps = \DB::table('sub_activation_plans')->where('activation_type_id', $product->deactivate_plan_id)->where('status', 'Enabled')->count();
        $data['provision'] = $provision;
        $data['num_steps'] = $num_steps;
        $data['current_step'] = $current_step;
        $data['selected_step'] = $current_step;
        $data['service_table'] = $service_table;

        return view('__app.components.activations.deactivate_wizard', $data);
    }

    public function deactivateService(Request $request, $service_table, $id)
    {
        try {
            // $pbx = new \FusionPBX();
            // $pbx->importDomains();
            $provision = \DB::table($service_table)->where('id', $id)->get()->first();

            $data['service_table'] = $service_table;
            if (empty($provision)) {
                return json_alert('Invalid Id2', 'error');
            }

            if ($provision->status != 'Pending') {
                return json_alert('Invalid Provision Status', 'error');
            }

            $product = \DB::table('crm_products')->where('id', $provision->product_id)->get()->first();
            if (empty($product)) {
                return json_alert('Product not found', 'error');
            }
            $plan_name = \DB::table('sub_activation_types')->where('id', $product->deactivate_plan_id)->pluck('name')->first();

            if (empty($plan_name)) {
                $plan_name = $provision->provision_type;
                $product->deactivate_plan_id = \DB::table('sub_activation_types')->where('name', $plan_name)->pluck('id')->first();
            }
            if (empty($plan_name)) {
                return json_alert('No Provision name set for this product11', 'error');
            }

            // get total num provision steps
            $current_step = ! empty($provision->step) ? $provision->step : 1;

            $is_admin = (session('role_level') == 'Admin') ? 1 : 0;

            $num_steps = \DB::table('sub_activation_plans')->where('activation_type_id', $product->deactivate_plan_id)->where('status', 'Enabled')->count();
            $provision_plans = \DB::table('sub_activation_plans')->where('activation_type_id', $product->deactivate_plan_id)->where('status', 'Enabled')->orderBy('step')->get();

            if (isset($request->step_number)) {
                $step_number = $request->step_number + 1;
                if ($current_step != $step_number) {
                    $repeatable = \DB::table('sub_activation_plans')
                        ->where('activation_type_id', $product->deactivate_plan_id)->where('status', 'Enabled')
                        ->where('step', $step_number)
                        ->where('repeatable', 1)
                        ->where('automated', 0)
                        ->count();
                    if (! $repeatable) {
                        return json_alert('You cannot redo this step.', 'error');
                    } else {
                        $current_step = $step_number;
                    }
                }
            }

            foreach ($provision_plans as $provision_plan) {
                if ($current_step == $provision_plan->step) {
                    if (! $is_admin && $provision_plan->admin_only) {
                        return json_alert('An administrator needs to complete the following provisioning process.', 'error');
                    }
                }

                if ($provision_plan->type == 'Email' && $provision_plan->automated && $current_step == $provision_plan->step) {
                    $step_record = \DB::table('sub_activation_steps')
                        ->where('deactivate_plan_id', $provision_plan->id)
                        ->where('provision_id', $provision->id)
                        ->where('service_table', $service_table)
                        ->get()->first();

                    if (empty($step_record)) {
                        \DB::table('sub_activation_steps')
                            ->insert([
                                'service_table' => $service_table,
                                'provision_id' => $provision->id,
                                'deactivate_plan_id' => $provision_plan->id,
                                'created_at' => date('Y-m-d H:i:s'),
                            ]);
                    }

                    $step_update_data['updated_at'] = date('Y-m-d H:i:s');

                    $mail_data = [];
                    $customer = dbgetaccount($provision->account_id);
                    $reseller = dbgetaccount($customer->partner_id);
                    if ($customer->partner_id == 1) {
                        $mail_data['partner_company'] = 'Cloud Telecoms';
                        $mail_data['partner_email'] = 'no-reply@telecloud.co.za';
                    } else {
                        $mail_data['partner_company'] = $reseller->company;
                        $mail_data['partner_email'] = $reseller->email;
                    }
                    $mail_data['parent_company'] = $mail_data['partner_company'];
                    $mail_data['account_id'] = $customer->id;
                    $mail_data['provision_id'] = $provision->id;
                    $mail_data['detail'] = $provision->detail;

                    $mail_data['customer_type'] = 'customer';
                    $mail_data['emailaddress'] = $customer->email;
                    $mail_data['bccemailaddress'] = 'ahmed@telecloud.co.za';
                    $mail_data['ccemailaddress'] = $reseller->email;
                    $mail_data['subject'] = ucwords(str_replace('_', ' ', $provision_plan->name));
                    $mail_data['message'] = $provision_plan->step_email;
                    $mail_data['message_box_id'] = $provision_plan->id;
                    $mail_data['customer'] = $customer;
                    if (! empty($provision_plan->email_id)) {
                        $mail_data['notification_id'] = $provision_plan->email_id;
                    }

                    $sub = \DB::table($service_table)->where('id', $provision->id)->get()->first();
                    $product = \DB::table('crm_products')->where('id', $sub->product_id)->get()->first();
                    if (! empty($product)) {
                        $provision_plan_name = \DB::table('sub_activation_types')->where('id', $product->deactivate_plan_id)->pluck('name')->first();
                        if (! empty($provision_plan_name)) {
                            $email_id = \DB::table('sub_activation_plans')->where('activation_type_id', $product->deactivate_plan_id)->where('email_id', '>', '')->pluck('email_id')->first();
                            if (! empty($email_id)) {
                                $customer = dbgetaccount($sub->account_id);

                                $mail_data['detail'] = $sub->detail;
                                $mail_data['product'] = ucwords(str_replace('_', ' ', $product->code));
                                $mail_data['product_code'] = $product->code;
                                $mail_data['product_description'] = $product->name;
                                $activation_data = get_activation_email_data($provision_plan_name, $sub, $customer, $provision, $service_table);
                                if (! empty($activation_data) && count($activation_data) > 0) {
                                    foreach ($activation_data as $k => $v) {
                                        $mail_data[$k] = $v;
                                    }
                                }
                            }
                        }
                    }

                    try {
                        $mail_data['subscription_id'] = $provision->id;
                        $mail_data['activation_email'] = true;

                        if ($customer->partner_id != 1) {
                            $mail_data['reseller_user_company'] = $customer->company;
                            $mail_result = erp_process_notification($customer->partner_id, $mail_data);
                        } else {
                            $mail_result = erp_process_notification($customer->id, $mail_data);
                        }
                        //aa($mail_result);
                    } catch (\Throwable $ex) {
                        exception_log($ex);
                        exception_email($ex, 'Provision Email Error');

                        return json_alert('Mail error', 'warning');
                    }

                    $step_update_data['result'] = $mail_result;
                    \DB::table('sub_activation_steps')
                        ->where('deactivate_plan_id', $request->deactivate_plan_id)
                        ->where('provision_id', $request->provision_id)
                        ->where('service_table', $service_table)
                        ->update($step_update_data);
                } elseif ($provision_plan->type == 'Function' && $provision_plan->automated && $current_step == $provision_plan->step) {
                    if (! empty($provision->detail)) {
                        // return json_alert('Provision detail already set1', 'error');
                    }
                    $step_record = \DB::table('sub_activation_steps')
                        ->where('deactivate_plan_id', $provision_plan->id)
                        ->where('provision_id', $provision->id)
                        ->where('service_table', $service_table)
                        ->get()->first();

                    if (empty($step_record)) {
                        \DB::table('sub_activation_steps')
                            ->insert([
                                'service_table' => $service_table,
                                'provision_id' => $provision->id,
                                'deactivate_plan_id' => $provision_plan->id,
                                'created_at' => date('Y-m-d H:i:s'),
                            ]);
                    }

                    $step_update_data['updated_at'] = date('Y-m-d H:i:s');
                    $provision_function = 'provision_'.function_format($provision_plan->name);
                    if (! empty($provision_plan->function_name)) {
                        $provision_function = $provision_plan->function_name;
                    }
                    if (! function_exists($provision_function)) {
                        return json_alert('Provision function does not exists', 'error');
                    }

                    $customer = dbgetaccount($provision->account_id);

                    if ($provision->is_test) {
                        $step_update_data['result'] = 'test';
                        $provision_result['detail'] = 'test';
                    } else {
                        $provision_result = $provision_function($provision, '', $customer, $product);
                        $step_update_data['result'] = (! empty($provision_result['detail'])) ? $provision_result['detail'] : '';
                    }

                    if ($step_update_data['result'] === true || is_array($provision_result)) {
                        $step_update_data['result'] = 'complete';
                    }
                    if (! empty($provision_result['detail'])) {
                        $step_update_data['subscription_detail'] = $provision_result['detail'];
                    }
                    if (! empty($provision_result['info'])) {
                        $step_update_data['subscription_info'] = json_encode($provision_result['info'], true);
                    }

                    if (! empty($provision_result['table_data'])) {
                        unset($provision_result['table_data']['service_table']);
                        $step_update_data['table_data'] = json_encode($provision_result['table_data'], true);
                    }

                    if (! empty($provision_result['detail'])) {
                        if ($service_table != 'sub_service_topups') {
                            \DB::table($service_table)->where('id', $provision->id)->update(['detail' => $provision_result['detail'], 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default()]);
                        }
                    }

                    \DB::table('sub_activation_steps')
                        ->where('deactivate_plan_id', $provision_plan->id)
                        ->where('provision_id', $provision->id)
                        ->where('service_table', $service_table)
                        ->update($step_update_data);

                    if (! is_array($provision_result) && $provision_result !== true) {
                        return json_alert($provision_result, 'error');
                    }
                }

                if ($provision_plan->automated && $current_step == $provision_plan->step) {
                    if ($current_step == $num_steps) {
                        $user_guide_id = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('user_guide_id')->first();
                        if ($user_guide_id) {
                            send_user_guide_to_customer($provision->account_id, $user_guide_id);
                        }
                        $date_activated = date('Y-m-d H:i:s');
                        if (! empty($subscription_info['date_activated'])) {
                            $date_activated = $subscription_info['date_activated'];
                        }

                        if ($service_table == 'sub_services') {
                            \DB::table($service_table)
                                ->where('id', $provision->id)
                                ->update(['bundle_id' => $provision->bundle_id, 'status' => 'Enabled', 'date_activated' => $date_activated, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default()]);
                            if (! empty($provision->bundle_id)) {
                                $pending_bundle_activations = \DB::table('sub_activations')->where('bundle_id', $provision->bundle_id)->where('status', 'Pending')->count();
                                if ($pending_bundle_activations == 0) {
                                    \DB::table('sub_services')->where('id', $provision->bundle_id)->where('status', 'Pending')->update(['status' => 'Enabled']);
                                }
                            }
                        } else {
                            \DB::table($service_table)
                                ->where('id', $provision->id)
                                ->update(['bundle_id' => $provision->bundle_id, 'status' => 'Enabled', 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default()]);
                            if (! empty($provision->bundle_id)) {
                                $pending_bundle_activations = \DB::table('sub_activations')->where('bundle_id', $provision->bundle_id)->where('status', 'Pending')->count();
                                if ($pending_bundle_activations == 0) {
                                    \DB::table('sub_services')->where('id', $provision->bundle_id)->where('status', 'Pending')->update(['status' => 'Enabled']);
                                }
                            }
                        }

                        $ErpSubs = new \ErpSubs;

                        $result = $ErpSubs->deleteSubscription($provision->subscription_id);
                        if ($result !== true) {
                            return json_alert($result, 'warning');
                        }

                        module_log(554, $provision->id, 'updated', 'activation completed');

                        return json_alert('Provision Completed', 'success', ['close_dialog' => 1, 'close_left_dialog' => 1]);
                    } elseif ($current_step < $num_steps) {
                        $plan_name = \DB::table('sub_activation_types')->where('id', $product->deactivate_plan_id)->pluck('name')->first();

                        $next_step = \DB::table('sub_activation_plans')->where('activation_type_id', $product->deactivate_plan_id)->where('step', '>', $current_step)->where('status', '!=', 'Deleted')->orderby('step')->pluck('step')->first();
                        \DB::table($service_table)
                            ->where('id', $provision->id)
                            ->update(['step' => $next_step, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default()]);
                        update_admin_only_activations();

                        module_log(554, $provision->id, 'updated', 'activation step '.$current_step.' completed');
                        $step_update_data['completed'] = 1;
                        \DB::table('sub_activation_steps')
                            ->where('deactivate_plan_id', $provision_plan->id)
                            ->where('provision_id', $provision->id)
                            ->where('service_table', $service_table)
                            ->update($step_update_data);

                        $current_step++;
                    }
                }

                if (! $provision_plan->automated) {
                    if ($current_step == $provision_plan->step) {
                        $data['deactivate_plan_id'] = $provision_plan->id;
                        $data['provision_id'] = $provision->id;
                        if ($provision_plan->type == 'Function') {
                            if ($provision->is_test) {
                                $data['provision_form'] = (! empty($provision_plan->function_name)) ? $provision_plan->function_name : $provision_plan->name.' function';
                            } else {
                                if (! empty($provision->detail)) {
                                    //  return json_alert('Provision detail already set', 'error');
                                }
                                $data['provision_form'] = build_provision_form($provision, $provision_plan, $service_table);
                                if (is_array($data['provision_form'])) {
                                    return json_alert(data['provision_form'][0], 'warning');
                                }

                                if (! $data['provision_form']) {
                                    return json_alert('Provision form does not exist', 'warning');
                                }
                            }
                        }

                        if ($provision_plan->type == 'Module') {
                            if (empty($provision_plan->module_id)) {
                                return json_alert('Activation type module_id not set', 'warning');
                            }
                            $menu_name = get_menu_url($provision_plan->module_id);

                            $model = new \App\Models\ErpModel;

                            $model->setMenuData($menu_name);

                            $model_data = $model->info;

                            $model_data['menu'] = $model->menu;
                            $form = new \ErpForm($model_data, ['account_id' => $provision->account_id, 'subscription_id' => $provision->id]);
                            $form->setEditType('add');
                            $row = $model->getRow(null);

                            $form_data = $form->getForm($row);
                            $data['provision_form'] = $form_data['form_html'].'<script>'.$form_data['form_script'].'</script>';
                            $data['exclude_input_script'] = true;
                        }

                        if ($provision_plan->type == 'Iframe') {
                            $iframe_url = str_replace('{{$account_id}}', $provision->account_id, $provision_plan->iframe_url);
                            $iframe_url = str_replace('{{$sub_id}}', $provision->id, $iframe_url);
                            $data['provision_form'] = '<iframe id="provision_iframe" src="'.$iframe_url.'" width="100%" frameborder="0px" height="600px" onerror="alert(\'Failed\')" style="margin-bottom:-5px;"><!-- //required for browser compatibility --></iframe>';
                        }

                        if ($provision_plan->type == 'Email') {
                            $mail_data = [];
                            $customer = dbgetaccount($provision->account_id);
                            $reseller = dbgetaccount($customer->partner_id);

                            $mail_data['partner_company'] = $reseller->company;
                            $mail_data['partner_email'] = $reseller->email;

                            $mail_data['parent_company'] = $mail_data['partner_company'];
                            $mail_data['account_id'] = $customer->id;
                            $mail_data['provision_id'] = $provision->id;
                            $mail_data['detail'] = $provision->detail;

                            $mail_data['customer_type'] = 'customer';
                            $mail_data['emailaddress'] = $customer->email;
                            $mail_data['ccemailaddress'] = $reseller->email;
                            $mail_data['bccemailaddress'] = 'ahmed@telecloud.co.za';
                            $mail_data['subject'] = $provision_plan->name;
                            $mail_data['customer'] = $customer;

                            $sub = \DB::table($service_table)->where('id', $provision->id)->get()->first();

                            if (! empty($product)) {
                                $provision_plan_name = \DB::table('sub_activation_types')->where('id', $product->deactivate_plan_id)->pluck('name')->first();
                                if (! empty($provision_plan_name)) {
                                    $email_id = \DB::table('sub_activation_plans')->where('activation_type_id', $product->deactivate_plan_id)->where('email_id', '>', '')->pluck('email_id')->first();
                                    if (! empty($email_id)) {
                                        $customer = dbgetaccount($sub->account_id);

                                        $mail_data['detail'] = $sub->detail;
                                        $mail_data['product'] = ucwords(str_replace('_', ' ', $product->code));
                                        $mail_data['product_code'] = $product->code;
                                        $mail_data['product_description'] = $product->name;

                                        $subscription_data = \DB::table('sub_activation_steps')
                                            ->where('provision_id', $provision->id)
                                            ->where('service_table', $service_table)
                                            ->whereNotNull('subscription_detail')->get()->first();

                                        $activation_data = get_activation_email_data($provision_plan_name, $sub, $customer, $provision, $service_table);
                                        if (! empty($activation_data) && count($activation_data) > 0) {
                                            foreach ($activation_data as $k => $v) {
                                                $mail_data[$k] = $v;
                                            }
                                        }
                                    }
                                }
                            }

                            if (! empty($provision_plan->email_id)) {
                                $newsletter = \DB::table('crm_email_manager')->where('id', $provision_plan->email_id)->get()->first();
                                $subject = $newsletter->name;
                                $mail_data['subject'] = $newsletter->name;
                                $mail_data['html'] = get_email_html($customer->id, $reseller->id, $mail_data, $newsletter);

                                $mail_data['css'] = '';
                                $template_file = '_emails.gjs';
                            }

                            $mail_data['message'] = view($template_file, $mail_data);

                            $mail_data['exclude_script'] = true;
                            $data['exclude_form_script'] = true;
                            $mail_data['subscription_id'] = $provision->id;
                            $mail_data['activation_email'] = 1;
                            $data['provision_form'] = email_form($provision_plan->email_id, $provision->account_id, $mail_data);
                        }

                        if ($provision_plan->type == 'Checklist') {
                            $product_monthly = \DB::table('crm_products')->where('is_subscription', 1)->where('id', $provision->product_id)->count();
                            if ($product_monthly && $provision_plan->name == 'products') {
                                $product_monthly = \DB::table('crm_products')->where('is_subscription', 1)->where('id', $provision->product_id)->count();

                                $data['provision_form'] = account_has_processed_debit_order($provision->account_id);
                            }
                            if (! empty($data['provision_form'])) {
                                $data['provision_form'] = $data['provision_form'].'<br>'.build_provision_checklist($provision, $provision_plan, $service_table);
                            } else {
                                $data['provision_form'] = build_provision_checklist($provision, $provision_plan, $service_table);
                            }
                        }

                        if ($provision_plan->type == 'Debitorder') {
                            $account = dbgetaccount($provision->account_id);

                            $data['provision_form'] = account_has_processed_debit_order($provision->account_id);
                        }
                    }
                }
            }

            $data['provision'] = $provision;
            $data['provision_plans'] = $steps;
            $data['num_steps'] = $num_steps;
            $data['current_step'] = $current_step;
            $num_automated = \DB::table('sub_activation_plans')
                ->where('activation_type_id', $product->deactivate_plan_id)
                ->where('automated', 1)
                ->where('status', 'Enabled')
                ->where('step', '<', $current_step)
                ->count();
            $num_deleted = \DB::table('sub_activation_plans')
                ->where('activation_type_id', $product->deactivate_plan_id)
                ->where('status', 'Deleted')
                ->count();
            $data['selected_tab'] = (($current_step - $num_automated) - 1);
            $data['menu_route'] = $this->data['menu_route'];
            if (! empty($request->topup)) {
                $data['topup'] = 1;
            } else {
                $data['topup'] = 0;
            }

            $data['service_table'] = $service_table;

            return view('__app.components.activations.deactivate_wizard_step', $data);
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'provision error');

            return response()->json(['status' => 'error', 'message' => 'An error occurred4']);
        }
    }

    public function deactivateServicePost(Request $request)
    {
        try {
            $service_table = $request->service_table;

            $provision = \DB::table($service_table)->where('id', $request->provision_id)->get()->first();

            $provision_plan = \DB::table('sub_activation_plans')->where('id', $request->deactivate_plan_id)->get()->first();
            if ($provision_plan->step < $provision->step && ! $provision_plan->repeatable) {
                return json_alert('Invalid Provision Step11', 'warning');
            }
            $product = \DB::table('crm_products')->where('id', $provision->product_id)->get()->first();
            $customer = dbgetaccount($provision->account_id);
            if (empty($product->deactivate_plan_id)) {
                $plan_name = $provision->provision_type;
                $product->deactivate_plan_id = \DB::table('sub_activation_types')->where('name', $plan_name)->pluck('id')->first();
            }

            //	return json_alert('Unavailable','error');

            $form_step = $request->form_step + 1;
            $current_step = $provision->step;
            $num_steps = $request->num_steps;
            $succes_msg = '';
            $step_record = \DB::table('sub_activation_steps')
                ->where('deactivate_plan_id', $request->deactivate_plan_id)
                ->where('provision_id', $request->provision_id)
                ->where('service_table', $service_table)
                ->get()->first();

            if (empty($step_record)) {
                \DB::table('sub_activation_steps')
                    ->insert([
                        'service_table' => $service_table,
                        'provision_id' => $request->provision_id,
                        'deactivate_plan_id' => $request->deactivate_plan_id,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            $step_update_data['updated_at'] = date('Y-m-d H:i:s');

            if ($provision_plan->type == 'Module') {
                $post_data = (array) $request->all();
                $provision_form_fields = ['deactivate_plan_id', 'provision_id', 'current_step', 'num_steps', 'service_table'];

                foreach ($post_data as $key => $val) {
                    if (in_array($key, $provision_form_fields)) {
                        unset($post_data[$key]);
                    }
                }
                $erp = new \DBEvent;
                $erp->setModule($provision_plan->module_id);
                $result = $erp->save($post_data);
                if (! is_array($result) || empty($result['id'])) {
                    return $result;
                }
            }

            if ($provision_plan->type == 'Iframe') {
                \DB::table($service_table)
                    ->where('id', $provision->id)
                    ->increment('step');

                if (empty($succes_msg)) {
                    $succes_msg = 'Provision step completed';
                }
                $provision_url = url('service_deactivate?id='.$request->provision_id);
                if ($service_table == 'sub_service_topups') {
                    $provision_url .= '&type=topup';
                }
                if ($service_table == 'sub_activations') {
                    $provision_url .= '&type=operations';
                }

                return json_alert($succes_msg, 'info', ['provision_id' => $request->provision_id, 'provision_url' => $provision_url]);
            }

            if ($provision_plan->type == 'Function') {
                $default_values = ['deactivate_plan_id', 'provision_id', 'current_step', 'num_steps', 'form_step', 'topup'];
                $inputs = [];

                foreach ($request->all() as $k => $v) {
                    if (! in_array($k, $default_values)) {
                        $inputs[$k] = $v;
                    }
                }

                $step_update_data['input'] = json_encode($inputs);
                \DB::table('sub_activation_steps')
                    ->where('deactivate_plan_id', $request->deactivate_plan_id)
                    ->where('provision_id', $request->provision_id)
                    ->where('service_table', $service_table)
                    ->update($step_update_data);

                $provision_function = 'provision_'.function_format($provision_plan->name);

                if (! empty($provision_plan->function_name)) {
                    $provision_function = $provision_plan->function_name;
                }
                if (! function_exists($provision_function)) {
                    return json_alert('Provision function does not exists', 'error');
                }

                $step_record = \DB::table('sub_activation_steps')
                    ->where('deactivate_plan_id', $request->deactivate_plan_id)
                    ->where('provision_id', $request->provision_id)
                    ->where('service_table', $service_table)
                    ->get()->first();

                $input_data = json_decode($step_record->input, true);

                if ($provision->is_test) {
                    $step_update_data['result'] = 'test';
                    $provision_result['detail'] = 'test';
                } else {
                    $provision_result = $provision_function($provision, $input_data, $customer, $product);
                    $step_update_data['result'] = (! empty($provision_result['detail'])) ? $provision_result['detail'] : $provision_result;
                }

                if ($step_update_data['result'] === true || is_array($provision_result)) {
                    $step_update_data['result'] = 'complete';
                }
                if (! empty($provision_result['detail'])) {
                    $step_update_data['subscription_detail'] = $provision_result['detail'];
                }
                if (! empty($provision_result['info'])) {
                    $step_update_data['subscription_info'] = json_encode($provision_result['info'], true);
                }

                if (! empty($provision_result['table_data'])) {
                    unset($provision_result['table_data']['service_table']);
                    $step_update_data['table_data'] = json_encode($provision_result['table_data'], true);
                }

                if (! empty($provision_result['detail'])) {
                    \DB::table($service_table)->where('id', $provision->id)->update(['detail' => $provision_result['detail'], 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default()]);
                }

                if (! empty($provision_result['ref'])) {
                    if ($service_table != 'sub_service_topups') {
                        \DB::table($service_table)->where('id', $provision->id)->update(['ref' => $provision_result['ref']]);
                    }
                }

                \DB::table('sub_activation_steps')
                    ->where('deactivate_plan_id', $request->deactivate_plan_id)
                    ->where('provision_id', $request->provision_id)
                    ->where('service_table', $service_table)
                    ->update($step_update_data);

                if (! is_array($provision_result) && $provision_result !== true) {
                    return json_alert($provision_result, 'error');
                }
            }

            if ($provision_plan->type == 'Email') {
                $mail_data['partner_company'] = $request->partner_company;
                $mail_data['partner_email'] = $request->partner_email;
                $mail_data['customer_type'] = $request->customer_type;
                $mail_data['subject'] = ucwords(str_replace('_', ' ', $request->subject));
                $mail_data['message'] = $request->messagebox;
                $mail_data['to_email'] = $request->emailaddress;
                $mail_data['cc_emails'] = $request->ccemailaddress;
                $mail_data['bcc_email'] = $request->bccemailaddress;
                $mail_data['message_template'] = 'default';
                $mail_data['formatted'] = 1;

                $mail_data['form_submit'] = 1;
                //$mail_data['test_debug'] = 1;

                $mail_data['activation_email'] = true;
                $mail_data['notification_id'] = $request->notification_id;
                try {
                    if ($customer->partner_id != 1) {
                        $mail_data['reseller_user_company'] = $customer->company;
                        $mail_result = erp_process_notification($customer->partner_id, $mail_data);
                    } else {
                        $mail_result = erp_process_notification($customer->id, $mail_data);
                    }
                } catch (\Throwable $ex) {
                    exception_log($ex);
                    exception_email($ex, 'Provision Email Error');

                    return json_alert('Mail error', 'warning');
                }

                $step_update_data['result'] = $mail_result;

                $plan_name = \DB::table('sub_activation_plans')->where('id', $request->deactivate_plan_id)->pluck('name')->first();
                $has_function_step = \DB::table('sub_activation_plans')->where('activation_type_id', $product->deactivate_plan_id)->where('type', 'Function')->count();
                if (! $has_function_step) {
                    $step_update_data['subscription_detail'] = $provision->provision_type.'_'.$provision->id;
                }

                \DB::table('sub_activation_steps')
                    ->where('deactivate_plan_id', $request->deactivate_plan_id)
                    ->where('provision_id', $request->provision_id)
                    ->where('service_table', $service_table)
                    ->update($step_update_data);

                if ($mail_result != 'Sent') {
                    return json_alert($mail_result, 'error');
                }

                $succes_msg = $mail_result;
            }

            if ($provision_plan->type == 'Checklist') {
                $product_monthly = \DB::table('crm_products')->where('is_subscription', 1)->where('id', $provision->product_id)->count();
                if ($product_monthly && $provision_plan->name == 'products') {
                    $step_update_data['input'] = account_has_processed_debit_order($provision->account_id);
                    if ($step_update_data['input'] != 'Debit order processed.') {
                        return json_alert($step_update_data['input'], 'warning', ['close_dialog' => 1, 'close_left_dialog' => 1]);
                    }
                }
                $default_values = ['deactivate_plan_id', 'provision_id', 'current_step', 'num_steps', 'form_step'];
                $checklist_post = [];

                foreach ($request->all() as $k => $v) {
                    if (! in_array($k, $default_values)) {
                        $checklist_post[] = $k;
                    }
                }
                $step_update_data['input'] = json_encode($checklist_post);
                \DB::table('sub_activation_steps')
                    ->where('deactivate_plan_id', $request->deactivate_plan_id)
                    ->where('provision_id', $request->provision_id)
                    ->where('service_table', $service_table)
                    ->update($step_update_data);
                //update checklist from post
                $checklist_plan = $provision_plan->step_checklist;
                $checklist = explode(PHP_EOL, $checklist_plan);
                foreach ($checklist as $i => $list_item) {
                    $item_id = 'checklist_item_'.$i;
                    if (! in_array($item_id, $checklist_post)) {
                        return json_alert('Complete checklist to continue', 'warning', ['close_dialog' => 1, 'close_left_dialog' => 1]);
                    }
                }
            }

            if ($provision_plan->type == 'Debitorder') {
                if (! empty($request->retry_debit_order)) {
                    $step_update_data['input'] = account_has_processed_debit_order($provision->account_id, true);
                } else {
                    $step_update_data['input'] = account_has_processed_debit_order($provision->account_id, false);
                }
                if ($step_update_data['input'] != 'Debit order processed.') {
                    return json_alert($step_update_data['input'], 'warning', ['close_dialog' => 1, 'close_left_dialog' => 1]);
                }

                \DB::table('sub_activation_steps')
                    ->where('deactivate_plan_id', $request->deactivate_plan_id)
                    ->where('provision_id', $request->provision_id)
                    ->where('service_table', $service_table)
                    ->update($step_update_data);
            }

            if ($provision_plan->step < $current_step) {
                return json_alert('Repeatable step completed', 'info', ['provision_id' => $request->provision_id]);
            }

            if ($current_step == $num_steps) {
                $user_guide_id = \DB::table('sub_activation_types')->where('id', $product->provision_plan_id)->pluck('user_guide_id')->first();
                if ($user_guide_id) {
                    send_user_guide_to_customer($provision->account_id, $user_guide_id);
                }
                $step_update_data['completed'] = 1;
                $date_activated = date('Y-m-d H:i:s');

                if (! empty($subscription_info['date_activated'])) {
                    $date_activated = $subscription_info['date_activated'];
                }
                \DB::table('sub_activation_steps')
                    ->where('deactivate_plan_id', $request->deactivate_plan_id)
                    ->where('provision_id', $request->provision_id)
                    ->where('service_table', $service_table)
                    ->update($step_update_data);
                if ($service_table == 'sub_services') {
                    \DB::table($service_table)
                        ->where('id', $provision->id)
                        ->update(['bundle_id' => $provision->bundle_id, 'status' => 'Enabled', 'date_activated' => $date_activated, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default()]);
                    if (! empty($provision->bundle_id)) {
                        $pending_bundle_activations = \DB::table('sub_activations')->where('bundle_id', $provision->bundle_id)->where('status', 'Pending')->count();
                        if ($pending_bundle_activations == 0) {
                            \DB::table('sub_services')->where('id', $provision->bundle_id)->where('status', 'Pending')->update(['status' => 'Enabled']);
                        }
                    }
                } else {
                    \DB::table($service_table)
                        ->where('id', $provision->id)
                        ->update(['bundle_id' => $provision->bundle_id, 'status' => 'Enabled', 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default()]);
                    if (! empty($provision->bundle_id)) {
                        $pending_bundle_activations = \DB::table('sub_activations')->where('bundle_id', $provision->bundle_id)->where('status', 'Pending')->count();
                        if ($pending_bundle_activations == 0) {
                            \DB::table('sub_services')->where('id', $provision->bundle_id)->where('status', 'Pending')->update(['status' => 'Enabled']);
                        }
                    }
                }
                $ErpSubs = new \ErpSubs;

                $result = $ErpSubs->deleteSubscription($provision->subscription_id);
                if ($result !== true) {
                    return json_alert($result, 'warning');
                }

                module_log(554, $provision->id, 'updated', 'activation completed');

                return json_alert('Provision Completed', 'success', ['close_dialog' => 1, 'close_left_dialog' => 1]);
            } elseif ($current_step < $num_steps) {
                $plan_name = \DB::table('sub_activation_types')->where('id', $product->deactivate_plan_id)->pluck('name')->first();

                $next_step = \DB::table('sub_activation_plans')->where('activation_type_id', $product->deactivate_plan_id)->where('step', '>', $current_step)->where('status', '!=', 'Deleted')->orderby('step')->pluck('step')->first();

                \DB::table($service_table)
                    ->where('id', $provision->id)
                    ->update(['step' => $next_step, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default()]);
                update_admin_only_activations();

                module_log(554, $provision->id, 'updated', 'activation step '.$current_step.' completed');

                $step_update_data['completed'] = 1;

                \DB::table('sub_activation_steps')
                    ->where('deactivate_plan_id', $request->deactivate_plan_id)
                    ->where('provision_id', $request->provision_id)
                    ->where('service_table', $service_table)
                    ->update($step_update_data);

                if (empty($succes_msg)) {
                    $succes_msg = 'Provision step completed';
                }
                $provision_url = url('service_deactivate?id='.$request->provision_id);
                if ($service_table == 'sub_service_topups') {
                    $provision_url .= '&type=topup';
                }
                if ($service_table == 'sub_activations') {
                    $provision_url .= '&type=operations';
                }

                return json_alert($succes_msg, 'info', ['provision_id' => $request->provision_id, 'provision_url' => $provision_url]);
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'provision error');

            return response()->json(['status' => 'error', 'message' => 'An error occurred5']);
        }
    }

    public function emailSend(Request $request)
    {
        try {
            if (empty($request->emailaddress)) {
                return json_alert('To Address Required', 'warning');
            }
            if (empty($request->subject)) {
                return json_alert('Subject Required', 'warning');
            }
            if (empty($request->messagebox)) {
                return json_alert('Message Required', 'warning');
            }

            $data['partner_company'] = $request->partner_company;
            $data['partner_email'] = $request->partner_email;
            $data['customer_type'] = $request->customer_type;
            $data['subject'] = $request->subject;
            $data['message'] = $request->messagebox;
            $data['to_email'] = $request->emailaddress;
            $data['cc_email'] = $request->ccemailaddress;
            $data['bcc_email'] = $request->bccemailaddress;
            $data['attachment'] = $request->attachment;
            $data['notification_id'] = $request->notification_id;
            $data['message_template'] = 'default';
            $data['formatted'] = 1;
            // $data['attachments'] = [];
            $data['form_submit'] = 1;

            if (! empty($request->template)) {
                $newsletter = \DB::connection('default')->table('crm_newsletters')->where('id', $request->template)->get()->first();
                $css = \Erp::decode($newsletter->stripo_css);
                $data['message'] .= '<style>'.$css.'</style>';
            }

            if (str_contains($data['attachment'], ',')) {
                $data['attachments'] = [];
                $files = explode(',', $data['attachment']);
                foreach ($files as $f) {
                    $data['attachments'][] = $f;
                }
                unset($data['attachment']);
            }

            if ($request->use_accounts_email) {
                $data['use_accounts_email'] = true;
            }

            $mail_result = erp_process_notification($request->account_id, $data);

            if (stripos($mail_result, 'failed') !== false) {
                return json_alert($mail_result, 'error');
            } else {
                return json_alert($mail_result);
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
            $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine();

            return json_alert($error, 'error');
        }
    }

    public function bulkEmailForm(Request $request, $id)
    {
        $reseller = dbgetaccount(session('account_id'));
        $data['newsletter'] = \DB::table('crm_email_manager')->where('id', $id)->get()->first();
        $data['groups'] = ['Test', 'Debit Orders', 'Admins', 'Leads', 'Resellers & Customers', 'Resellers', 'Customers', 'Vodacom LTE Customers', 'Fibre Customers', 'PBX', 'Leads'];

        if (session('account_id') != 1) {
            $data['groups'] = ['Test', 'Leads', 'Customers'];
        }
        $data['statuses'] = ['Not Deleted', 'Enabled', 'Disabled', 'Deleted', 'All'];

        $data['module_route'] = $request->segment(1);
        $data['function_name'] = $button_function;
        $data['notification_id'] = $id;
        $data['from_company'] = $reseller->company;
        $data['from_email'] = (! empty($reseller->sales_email)) ? $reseller->sales_email : $reseller->email;
        $data['pricelists'] = \DB::table('crm_pricelists')->where('partner_id', 1)->pluck('name', 'id');

        $newsletter = \DB::table('crm_email_manager')->where('id', $id)->get()->first();

        if (empty($data['preview'])) {
            $email_data = [
                'year' => date('Y'),
                'partner_company' => dbgetaccount(1)->company,
                'customer' => dbgetaccount(12),
                'reseller' => dbgetaccount(1),
            ];
            $data = array_merge($data, $email_data);
            $data['html'] = get_email_html(1, 1, $data, $newsletter);
            $link_params = \Erp::encode(['account_id' => 12]);
            $unsubscribe_url = request()->root().'/mail_unsubscribe/'.$link_params;

            // $unsubscribe_text = '<a href="'.$unsubscribe_url.'" target="_blank" style="font-size: 14px; font-family: Helvetica, Arial, sans-serif; color: #000; font-weight: bold; text-decoration: none; border-radius: 5px; background-color: #fff; border-top: 2px solid #fff; border-bottom: 2px solid #fff; border-right: 8px solid #fff; border-left: 8px solid #fff; display: inline-block;">Unsubscribe</a>';

            $data['html'] = str_replace('https://#unsubscribe', $unsubscribe_url, $data['html']);

            $data['css'] = '';
            $data['preview'] = view('_emails.gjs', $data);
        }

        return view('__app.button_views.bulkemail', $data);
    }

    public function bulkEmailSend(Request $request)
    {
        // process newsletter send
        $mail = [];
        $mail['add_unsubscribe'] = 1;
        $mail['bulk_smtp'] = 0;
        \Storage::disk('local')->put('bulkemailprogress', 0);
        $notification_id = $request->notification_id;
        $data = (array) $request->all();
        unset($data['_token']);
        unset($data['notification_id']);
        $filters = (object) $request->all();
        $mail['from_email'] = $request->from_email;
        $mail['from_company'] = $request->from_company;
        $newsletter = \DB::table('crm_email_manager')->where('id', $notification_id)->get()->first();
        $mail['newsletter'] = $newsletter;
        $mail['notification_id'] = $notification_id;
        $mail['add_unsubscribe'] = 1;

        if (! empty($newsletter->attachment_file)) {
            $mail['newsletter_attachment'] = $newsletter->attachment_file;
        }

        if ($filters->groups == 'Test') {
            \Storage::disk('local')->put('bulkemailprogress', 60);
            $mail['to_email'] = \DB::table('erp_users')->where('id', session('user_id'))->pluck('email')->first();
            if (session('instance')->id == 1 && session('user_id') != 1) {
                $mail['cc_email'] = 'ahmed@telecloud.co.za';
            }
            $mail['ignore_queue_check'] = 1;
            $mail['subject'] = $newsletter->name.' [Test]';

            if (session('instance')->directory == 'eldooffice') {
                email_queue_add(1, $mail);
            } elseif (session('instance')->directory == 'telecloud') {
                email_queue_add(1, $mail);
            } else {
                email_queue_add(session('account_id'), $mail);
            }

            \Storage::disk('local')->put('bulkemailprogress', 100);

            return json_alert('Email sent', 'success');
        }

        /// admins

        if ($filters->groups == 'Admins') {
            $admins = \DB::table('erp_users')->where('account_id', 1)->get();
            $data = [
                'subject' => $newsletter->name,
                'type' => 'email',
                'created_at' => date('Y-m-d H:i:s'),
                'email_id' => $newsletter->id,
                'bulk_email' => 1,
                'bulk_email_group' => $filters->groups,
                'bulk_email_status' => $filters->status,
                'to_email' => $newsletter->to_email,
                'cc_email' => $newsletter->cc_email,
                'bcc_email' => $newsletter->bcc_email,
            ];

            $id = \DB::table('erp_communications')->insertGetId($data);
            $mail['communication_id'] = $id;
            foreach ($admins as $i => $admin) {
                \Storage::disk('local')->put('bulkemailprogress', (($i / count($admins)) * 100));
                if (! empty($admin->email)) {
                    $mail['to_email'] = $admin->email;
                    $mail['ignore_queue_check'] = 1;
                    $mail['bulk_smtp'] = 1;
                    email_queue_add(1, $mail);
                }
            }
            session(['bulkemailprogress' => 100]);
        }

        if (empty($filters->groups) || empty($filters->status)) {
            return json_alert('Select group and status', 'error');
        }

        if ($filters->groups == 'Debit Order') {
            $account_ids = \DB::table('acc_debit_orders')->where('status', '!=', 'Deleted')->pluck('account_id')->toArray();
            $data = [
                'subject' => $newsletter->name,
                'type' => 'email',
                'created_at' => date('Y-m-d H:i:s'),
                'email_id' => $newsletter->id,
                'bulk_email' => 1,
                'bulk_email_group' => $filters->groups,
                'bulk_email_status' => $filters->status,
                'to_email' => $newsletter->to_email,
                'cc_email' => $newsletter->cc_email,
                'bcc_email' => $newsletter->bcc_email,
            ];
            $id = \DB::table('erp_communications')->insertGetId($data);
            $mail['communication_id'] = $id;

            if (! empty($account_ids)) {
                foreach ($account_ids as $i => $account_id) {
                    \Storage::disk('local')->put('bulkemailprogress', (($i / count($account_ids)) * 100));

                    email_queue_add($account_id, $mail);
                }
            }
        }

        if ($filters->groups != 'Debit Order' && ! str_contains($filters->groups, 'Lead') && $filters->groups != 'Admins') {
            unset($mail['to_email']);

            $account_ids = [];
            $query = \DB::table('crm_accounts');
            if (! str_contains($newsletter->subject, 'Ratesheet') && ! str_contains($newsletter->subject, 'Pricelist')) {
                $query->where('newsletter', 1);
            }
            $query->where('partner_id', session('account_id'));

            if ($filters->groups == 'Resellers & Customers') {
                $query->where('type', '!=', 'lead');
                $query->where('type', '!=', 'reseller_user');
            }

            if ($filters->groups == 'Resellers') {
                $query->where('type', 'reseller');
            }

            if ($filters->groups == 'Vodacom LTE Customers') {
                $lte_account_ids = \DB::table('sub_services')->where('provision_type', 'lte_sim_card')->where('status', '!=', 'Deleted')->pluck('account_id')->toArray();
                $query->whereIn('id', $lte_account_ids);
            }

            if ($filters->groups == 'Fibre Customers') {
                $fibre_account_ids = \DB::table('sub_services')->where('provision_type', 'like', '%fibre%')->where('status', '!=', 'Deleted')->pluck('account_id')->toArray();
                $query->whereIn('id', $fibre_account_ids);
            }

            if ($filters->groups == 'PBX') {
                $pbx_account_ids = \DB::table('isp_voice_pbx_domains')->pluck('account_id')->toArray();
                $query->whereIn('id', $pbx_account_ids);
            }

            if ($filters->groups == 'Customers') {
                $query->where('type', 'customer');
            }

            if ($filters->groups == 'Leads') {
                $query->where('type', 'lead');
            }
            if ($filters->status != 'All') {
                if ($filters->status == 'Not Deleted') {
                    $query->where('status', '!=', 'Deleted');
                } else {
                    $query->where('status', $filters->status);
                }
            }

            /// acounts
            $account_ids = $query->unique('email')->pluck('id');

            $data = [
                'subject' => $newsletter->name,
                'type' => 'email',
                'created_at' => date('Y-m-d H:i:s'),
                'email_id' => $newsletter->id,
                'bulk_email' => 1,
                'bulk_email_group' => $filters->groups,
                'bulk_email_status' => $filters->status,
                'to_email' => $newsletter->to_email,
                'cc_email' => $newsletter->cc_email,
                'bcc_email' => $newsletter->bcc_email,
            ];
            $id = \DB::table('erp_communications')->insertGetId($data);
            $mail['communication_id'] = $id;

            if (! empty($account_ids)) {
                foreach ($account_ids as $i => $account_id) {
                    \Storage::disk('local')->put('bulkemailprogress', (($i / count($account_ids)) * 100));

                    email_queue_add($account_id, $mail);
                }
            }
        }
        \Storage::disk('local')->put('bulkemailprogress', 100);

        return json_alert('Emails queued', 'success');
    }

    public function formChangeAjax(Request $request, $function_name)
    {
        $response = $function_name($request);

        //aa($response);
        return response()->json($response);
    }

    public function contextMenu(Request $request, $menu_name, $action, $id)
    {
        try {
            if (str_contains($action, 'supplier')) {
                $supplier = \DB::connection('default')->table('crm_suppliers')->where('id', $id)->get()->first();
            } elseif ($menu_name == 'accounts') {
                $account = dbgetaccount($id);
            } else {
                $account = dbgetaccount($id);
            }

            if (str_contains($action, 'module_view')) {
                $module_id = str_replace('module_view', '', $action);
                $url = get_menu_url($module_id);

                return redirect($url.'/view/'.$id);
            }

            if (str_contains($action, 'module_edit')) {
                $module_id = str_replace('module_edit', '', $action);
                $url = get_menu_url($module_id);

                return redirect($url.'/edit/'.$id);
            }

            if ($action == 'account') {
                if ($account->type == 'reseller') {
                    $url = get_menu_url_from_table('crm_accounts');

                    return redirect($url.'/view/'.$account->id);
                } else {
                    $url = get_menu_url_from_table('crm_accounts');

                    return redirect($url.'/view/'.$account->id);
                }
            }

            if ($action == 'accountedit') {
                return redirect($url.'/edit/'.$account->id);
            }

            $url = get_menu_url_from_table('erp_communication_lines');
            if ($action == 'communications') {
                return redirect($url.'?account_id='.$account->id);
            }

            if ($action == 'note') {
                $url = get_menu_url_from_table('crm_commitment_dates');

                return redirect($url.'/edit?type=Note&account_id='.$account->id);
            }

            if (str_contains($action, 'supplier') && str_contains($action, 'email')) {
                $data['customer_type'] = 'supplier';

                return email_form(1, $supplier->id, $data);
            } elseif ($action == 'email') {
                if (! empty($account->type) && $account->type == 'reseller_user' && session('role_level') == 'Admin') {
                    $account = dbgetaccount($account->partner_id);
                }

                return email_form(1, $account->id, $data);
            }

            if ($action == 'call' || str_contains($action, 'contact_phone') || $action == 'supplier_call') {
                $id = $account->id;
                if (! empty($account->type) && $account->type == 'reseller_user') {
                    $account = dbgetaccount($account->partner_id);
                }
                $reseller = dbgetaccount(session('account_id'));
                $pbx_extension = \DB::table('erp_users')->where('id', session('user_id'))->pluck('pbx_extension')->first();
                if (empty($reseller->pbx_domain) && empty($pbx_extension)) {
                    return json_alert('Pbx domain on settings and pbx extension on user settings is required to use click to call.', 'error');
                }
                if (! empty($reseller->pbx_domain) && empty($pbx_extension)) {
                    return json_alert('Pbx extension on user settings is required to use click to call.', 'error');
                }
                if (empty($reseller->pbx_domain) && ! empty($pbx_extension)) {
                    return json_alert('Pbx domain on settings is required to use click to call.', 'error');
                }

                if (str_contains($action, 'contact_phone')) {
                    $number = $account->{$action};
                    if (str_contains($action, 'supplier')) {
                        $action = str_replace('supplier_', '', $action);
                        $number = $supplier->{$action};
                        $id = $supplier->id;
                    }
                }

                if ($action == 'call') {
                    $number = $account->phone;
                }

                if ($action == 'supplier_call') {
                    $number = $supplier->phone;
                    $id = $supplier->id;
                }

                if (empty($number)) {
                    return json_alert('Invalid Number', 'error');
                }
                $number = urldecode($number);

                if (session('role_id') > 11) {
                    return json_alert('No Access', 'error');
                }

                if (empty($number) || empty($id)) {
                    return json_alert('Invalid Request', 'error');
                }

                $result = pbx_call($number, $id);

                if ($result === true) {
                    return json_alert('Call sent to PBX');
                } else {
                    return json_alert($result, 'error');
                }
            }
        } catch (\Throwable $ex) {
            exception_log($ex);

            return json_alert($ex->getMessage().' '.$ex->getFile().':'.$ex->getLine(), 'error');
        }
    }

    public function permissionsSave(Request $request)
    {
        $access_items = get_permission_table($request->input('id'));

        $remove_permissions = [
            'is_menu' => 0,
            'is_view' => 0,
            'is_add' => 0,
            'is_edit' => 0,
            'is_delete' => 0,
        ];
        \DB::table('erp_menu_role_access')->where('menu_id', $request->id)->whereNotIn('role_id', $request->role_id)->update($remove_permissions);
        if ($request->is_module) {
            $group_ids_module_access = [];
            foreach ($request->role_id as $gid) {
                foreach ($access_items as $t => $v) {
                    if ($t == 'is_menu') {
                        continue;
                    }

                    if (isset($request->{$t}[$gid])) {
                        $group_ids_module_access[] = $gid;
                    }
                }
            }
        }

        $module_id = \DB::table('erp_menu')->where('id', $request->id)->pluck('module_id')->first();

        $permission = [];

        $role_ids = $request->input('role_id');

        foreach ($role_ids as $role_id) {
            $arr = [];
            foreach ($access_items as $t => $v) {
                $data[$t] = (isset($request->{$t}[$role_id]) ? '1' : '0');
            }
            $data['menu_id'] = $request->input('id');
            $data['role_id'] = $role_id;

            $exists = \DB::table('erp_menu_role_access')->where('menu_id', $data['menu_id'])->where('role_id', $data['role_id'])->count();
            if ($exists) {
                \DB::table('erp_menu_role_access')->where('menu_id', $data['menu_id'])->where('role_id', $data['role_id'])->update($data);
            } else {
                \DB::table('erp_menu_role_access')->insert($data);
            }
        }

        \DB::table('erp_menu')->where('menu_type', 'none')->update(['menu_type' => 'link', 'url' => '#']);
        /*
        $top_menus = \DB::table('erp_menu')->where(['menu_type' => 'link','url'=>'#'])->get();
        foreach ($top_menus as $menu) {
            $menu_ids = get_submenu_ids($menu->id);
            $role_ids = \DB::table('erp_menu_role_access')->whereIn('menu_id', $menu_ids)->where('is_menu', 1)->pluck('role_id')->toArray();
            if (count($role_ids) > 0) {
                \DB::table('erp_menu_role_access')->whereIn('role_id', $role_ids)->where('menu_id', $menu->id)->update(['is_menu'=>1]);
                \DB::table('erp_menu_role_access')->whereNotIn('role_id', $role_ids)->where('menu_id', $menu->id)->update(['is_menu'=>0]);
            }
        }
        */

        $menus = \DB::connection('default')->table('erp_menu')->get();
        foreach ($menus as $menu) {
            if (empty($menu->module_id)) {
                $access = get_permissions_menu_item($menu->id);
                if (count($access) == 0) {
                    //  \DB::connection('default')->table('erp_menu')->where('id', $menu->id)->update(['unlisted' => 1]);
                } else {
                    \DB::connection('default')->table('erp_menu')->where('id', $menu->id)->update(['unlisted' => 0]);
                }
            }
        }
        \DB::connection('default')->table('erp_menu')->where('unlisted', 1)->update(['parent_id' => 0]);
        cache_clear();
        if ($request->ajax() == true) {
            return response()->json(['status' => 'success', 'message' => 'Permission Has Changed Successful']);
        } else {
            return Redirect::to($module_name)
                ->with('message', 'Permission Has Changed Successful')->with('status', 'success');
        }
    }

    public function deliveriesPodUpload(Request $request, $id)
    {
        try {
            if (! empty($request->remove_file)) {
                $file = \DB::table('sub_activations')->where('id', $id)->pluck('pod_file')->first();
                if (file_exists(uploads_path(554).$file)) {
                    unlink(uploads_path(554).$file);
                }
                \DB::table('sub_activations')->where('id', $id)->update(['pod_file' => '']);

                return json_alert('File Removed');
            }
            if (empty($_FILES)) {
                return json_alert('Invalid File', 'error');
            }
            if (empty($id)) {
                return json_alert('Invalid Id', 'error');
            }

            $field = 'import';
            $file_type = $request->file($field)->getMimeType();

            if ($request->file($field)) {
                if (str_contains($file_type, 'image/') || str_contains($file_type, 'doc') || str_contains($file_type, 'pdf')) {
                    $file = $request->file($field);
                    $destinationPath = uploads_path(554);
                    $filename = $file->getClientOriginalName();

                    $filename = str_replace([' ', ','], '_', $filename);

                    $uploadSuccess = $file->move($destinationPath, $filename);

                    if ($uploadSuccess) {
                        \DB::table('sub_activations')->where('id', $id)->update(['pod_file' => $filename]);

                        return json_alert('File uploaded');
                    } else {
                        return json_alert('Upload error', 'error');
                    }
                } else {
                    return json_alert('Invalid File Type', 'error');
                }
            }

            return json_alert('File Uploaded');
        } catch (\Throwable $ex) {
            exception_log($ex);

            return json_alert($ex->getMessage().' '.$ex->getFile().':'.$ex->getLine(), 'error');
        }
    }

    public function journalInvoiceUpload(Request $request, $id)
    {
        try {
            if (! empty($request->remove_file)) {
                $file = \DB::table('acc_general_journals')->where('id', $id)->pluck('invoice_file')->first();
                if (file_exists(uploads_path(181).$file)) {
                    unlink(uploads_path(181).$file);
                }
                \DB::table('acc_general_journals')->where('id', $id)->update(['invoice_file' => '']);

                return json_alert('File Removed');
            }
            if (empty($_FILES)) {
                return json_alert('Invalid File', 'error');
            }
            if (empty($id)) {
                return json_alert('Invalid Id', 'error');
            }

            $field = 'import';
            $file_type = $request->file($field)->getMimeType();

            if ($request->file($field)) {
                if (str_contains($file_type, 'image/') || str_contains($file_type, 'doc') || str_contains($file_type, 'pdf')) {
                    $file = $request->file($field);
                    $destinationPath = uploads_path(181);
                    $filename = $file->getClientOriginalName();

                    $filename = str_replace([' ', ','], '_', $filename);

                    $uploadSuccess = $file->move($destinationPath, $filename);

                    if ($uploadSuccess) {
                        \DB::table('acc_general_journals')->where('id', $id)->update(['invoice_file' => $filename]);

                        return json_alert('File uploaded');
                    } else {
                        return json_alert('Upload error', 'error');
                    }
                } else {
                    return json_alert('Invalid File Type', 'error');
                }
            }

            return json_alert('File Uploaded');
        } catch (\Throwable $ex) {
            exception_log($ex);

            return json_alert($ex->getMessage().' '.$ex->getFile().':'.$ex->getLine(), 'error');
        }
    }

    public function supplierInvoiceUpload(Request $request, $id)
    {
        try {
            if (! empty($request->remove_file)) {
                $file = \DB::table('crm_supplier_documents')->where('id', $id)->pluck('supporting_document')->first();
                if (file_exists(uploads_supplier_documents_path().$file)) {
                    unlink(uploads_supplier_documents_path().$file);
                }
                \DB::table('crm_supplier_documents')->where('id', $id)->update(['supporting_document' => '']);

                return json_alert('File Removed');
            }
            if (empty($_FILES)) {
                return json_alert('Invalid File', 'error');
            }
            if (empty($id)) {
                return json_alert('Invalid Id', 'error');
            }

            $field = 'import';
            $file_type = $request->file($field)->getMimeType();

            if ($request->file($field)) {
                if (str_contains($file_type, 'image/') || str_contains($file_type, 'doc') || str_contains($file_type, 'pdf')) {
                    $file = $request->file($field);
                    $destinationPath = uploads_supplier_documents_path();
                    $filename = $file->getClientOriginalName();

                    $filename = str_replace([' ', ','], '_', $filename);

                    $uploadSuccess = $file->move($destinationPath, $filename);

                    if ($uploadSuccess) {
                        \DB::table('crm_supplier_documents')->where('id', $id)->update(['supporting_document' => $filename]);

                        return json_alert('File uploaded');
                    } else {
                        return json_alert('Upload error', 'error');
                    }
                } else {
                    return json_alert('Invalid File Type', 'error');
                }
            }

            return json_alert('File Uploaded');
        } catch (\Throwable $ex) {
            exception_log($ex);

            return json_alert($ex->getMessage().' '.$ex->getFile().':'.$ex->getLine(), 'error');
        }
    }

    public function bankingInvoiceUpload(Request $request, $id)
    {
        try {
            if (! empty($request->remove_file)) {
                $file = \DB::table('acc_cashbook_transactions')->where('id', $id)->pluck('supporting_document')->first();
                if (file_exists(uploads_supplier_documents_path().$file)) {
                    unlink(uploads_supplier_documents_path().$file);
                }
                \DB::table('acc_cashbook_transactions')->where('id', $id)->update(['supporting_document' => '']);

                return json_alert('File Removed');
            }
            if (empty($_FILES)) {
                return json_alert('Invalid File', 'error');
            }
            if (empty($id)) {
                return json_alert('Invalid Id', 'error');
            }

            $field = 'import';
            $file_type = $request->file($field)->getMimeType();

            if ($request->file($field)) {
                if (str_contains($file_type, 'image/') || str_contains($file_type, 'doc') || str_contains($file_type, 'pdf')) {
                    $file = $request->file($field);
                    $destinationPath = uploads_supplier_documents_path();
                    $filename = $file->getClientOriginalName();

                    $filename = str_replace([' ', ','], '_', $filename);

                    $uploadSuccess = $file->move($destinationPath, $filename);

                    if ($uploadSuccess) {
                        \DB::table('acc_cashbook_transactions')->where('id', $id)->update(['supporting_document' => $filename]);

                        return json_alert('File uploaded');
                    } else {
                        return json_alert('Upload error', 'error');
                    }
                } else {
                    return json_alert('Invalid File Type', 'error');
                }
            }

            return json_alert('File Uploaded');
        } catch (\Throwable $ex) {
            exception_log($ex);

            return json_alert($ex->getMessage().' '.$ex->getFile().':'.$ex->getLine(), 'error');
        }
    }

    public function documentsInvoiceUpload(Request $request, $id)
    {
        try {
            if (! empty($request->remove_file)) {
                $file = \DB::table('crm_documents')->where('id', $id)->pluck('invoice_file')->first();
                if (file_exists(uploads_documents_path().$file)) {
                    unlink(uploads_documents_path().$file);
                }
                \DB::table('crm_documents')->where('id', $id)->update(['invoice_file' => '']);

                return json_alert('File Removed');
            }
            if (empty($_FILES)) {
                return json_alert('Invalid File', 'error');
            }
            if (empty($id)) {
                return json_alert('Invalid Id', 'error');
            }

            $field = 'import';
            $file_type = $request->file($field)->getMimeType();

            if ($request->file($field)) {
                if (str_contains($file_type, 'image/') || str_contains($file_type, 'doc') || str_contains($file_type, 'pdf')) {
                    $file = $request->file($field);
                    $destinationPath = uploads_documents_path();
                    $filename = $file->getClientOriginalName();

                    $filename = str_replace([' ', ','], '_', $filename);

                    $uploadSuccess = $file->move($destinationPath, $filename);

                    if ($uploadSuccess) {
                        \DB::table('crm_documents')->where('id', $id)->update(['invoice_file' => $filename]);

                        return json_alert('File uploaded');
                    } else {
                        return json_alert('Upload error', 'error');
                    }
                } else {
                    return json_alert('Invalid File Type', 'error');
                }
            }

            return json_alert('File Uploaded');
        } catch (\Throwable $ex) {
            exception_log($ex);

            return json_alert($ex->getMessage().' '.$ex->getFile().':'.$ex->getLine(), 'error');
        }
    }

    public function pricelistSend(Request $request)
    {
        $pricelist = \DB::table('crm_pricelists')->where('id', $request->pricelist_id)->get()->first();
        $reseller = \DB::table('crm_accounts')->where('id', $pricelist->partner_id)->get()->first();

        if (empty($request->send_to) || empty($request->pricelist_id)) {
            return json_alert('Recipients required.', 'warning');
        }

        $file_name = export_pricelist($request->pricelist_id);

        foreach ($request->send_to as $recipient) {
            if ($recipient == 'All Partners') {
                $customers = \DB::table('crm_accounts')
                    ->where('type', 'reseller')->where('partner_id', $reseller->id)->where('status', '!=', 'Deleted')
                    ->get();
                foreach ($customers as $customer) {
                    $data['subject'] = $reseller->company.' - '.$pricelist->name.' Pricelist';
                    $data['msg'] = 'Pricelist attached.';
                    $data['attachment'] = $file_name;
                    erp_process_notification($customer->id, $data);
                }
            } elseif ($recipient == 'All Customers') {
                $customers = \DB::table('crm_accounts')
                    ->where('type', 'customer')->where('partner_id', $reseller->id)->where('status', '!=', 'Deleted')
                    ->get();
                foreach ($customers as $customer) {
                    $data['to_email'] = $recipient;
                    $data['subject'] = $reseller->company.' - '.$pricelist->name.' Pricelist';
                    $data['msg'] = 'Pricelist attached.';
                    $data['attachment'] = $file_name;
                    erp_process_notification($reseller->id, $data);
                }
            } else {
                $data['to_email'] = $recipient;
                $data['subject'] = $reseller->company.' - '.$pricelist->name.' Pricelist';
                $data['msg'] = 'Pricelist attached.';
                $data['attachment'] = $file_name;
                erp_process_notification($reseller->id, $data);
            }
        }

        return json_alert('Pricelist Sent.');
    }

    public function supplierRecon(Request $request)
    {
        try {
            $filename = '';
            $suppliers_dir = uploads_path(78);
            $supplier = \DB::table('crm_suppliers')->where('id', $request->id)->get()->first();
            $balance = currency($request->statement_balance);
            if (currency($supplier->balance) != $balance) {
                return json_alert('Statement balance does not match', 'warning');
            }
            $manager_override = false;
            if (check_access('1,31') && ! empty($request->manager_override)) {
                $manager_override = true;
            }

            if (! $manager_override && empty($request->file($field)) && $balance != 0) {
                return json_alert('File required', 'error');
            }

            $field = 'statement_file';

            if ($request->file($field)) {
                $file = $request->file($field);
                $filename = $file->getClientOriginalName();
                $file_type = $request->file($field)->getMimeType();

                if (str_contains($file_type, 'image/') || str_contains($filename, '.doc') || str_contains($filename, '.pdf') || str_contains($filename, '.PDF')) {
                    $file = $request->file($field);
                    $destinationPath = $suppliers_dir;
                    $filename = $file->getClientOriginalName();

                    $filename = str_replace([' ', ','], '_', $filename);

                    $uploadSuccess = $file->move($destinationPath, $filename);
                    if (! $uploadSuccess) {
                        return json_alert('Upload error', 'error');
                    }
                } else {
                    return json_alert('Invalid File Type', 'error');
                }
            }
            \DB::table('crm_suppliers')->where('id', $request->id)->update(['statement_file' => $filename, 'reconcile_date' => date('Y-m-d H:i:s')]);

            return json_alert('Reconciled');
        } catch (\Throwable $ex) {
            exception_log($ex);

            return json_alert($ex->getMessage().' '.$ex->getFile().':'.$ex->getLine(), 'error');
        }
    }

    public function bankofxImport(Request $request)
    {
        try {
            $erp = new \DBEvent;
            if (empty($_FILES)) {
                return json_alert('Invalid File', 'error');
            }
            $file_name = $request->files->get('import')->getClientOriginalName();
            $file_ext = $request->files->get('import')->getClientOriginalExtension();
            $admin = dbgetaccount(1);
            if ($request->cashbook_id == 10 && is_main_instance()) {
                $admin->ofx_file_name = '62851344037.ofx';
            }
            if (! str_contains($file_name, str_replace('.ofx', '', $admin->ofx_file_name))) {
                return json_alert('Invalid File Name - Required: '.$admin->ofx_file_name, 'error');
            }

            if ($file_ext != 'ofx') {
                return json_alert('Invalid File Extension', 'error');
            }

            $transactions = [];
            $file_tmp = $_FILES['import']['tmp_name'];
            if (empty($file_tmp)) {
                return json_alert('Invalid File', 'error');
            }
            $handle = fopen($file_tmp, 'r');
            if ($handle) {
                while (false !== ($line = fgets($handle))) {
                    // New transaction.
                    if (strstr($line, '<STMTTRN>') !== false) {
                        $txn = new \stdClass;
                        $txn->trxdate = null;
                        $txn->total = null;
                        $txn->memo = null;
                    } elseif (strstr($line, '<DTPOSTED>') !== false) {
                        $txn->trxdate = date('Y-m-d', strtotime(substr($line, 10)));
                    } elseif (strstr($line, '<TRNAMT>') !== false) {
                        $txn->total = floatval(substr($line, 8));
                    } elseif (strstr($line, '<MEMO>') !== false) {
                        $txn->memo = substr($line, 6);
                    } elseif (strstr($line, '</STMTTRN>') !== false) {
                        // End of transaction.
                        $transactions[] = $txn;
                    }
                }
                // Return.
                fclose($handle);
            } else {
                return json_alert('Faulty file', 'error');
            }
            $txn->memo = str_replace(PHP_EOL, '', $txn->memo);

            if (empty($transactions) || count($transactions) == 0) {
                return json_alert('No transactions to process', 'error');
            }

            $reconciled_date = \DB::table('acc_register_bank')
                ->where('reconciled', 1)
                ->orderBy('docdate', 'desc')
                ->pluck('docdate')->first();

            if (! empty($reconciled_date)) {
                $last_reconciled_date = $reconciled_date;
            } else {
                $last_reconciled_date = '0000-00-00';
            }
            krsort($transactions);

            $file_references = [];
            $duplicate_references = [];
            foreach ($transactions as $transaction) {
                if (in_array($transaction->memo, $file_references)) {
                    $duplicate_references[] = $transaction->memo;
                } else {
                    $file_references[] = $transaction->memo;
                }
            }
            foreach ($transactions as $transaction) {
                $transaction->memo = str_replace(["\n", "\r"], '', $transaction->memo);
                $transaction->reference = $transaction->memo;

                if ($transaction->total == 0) {
                    continue;
                }

                if ($transaction->trxdate < $last_reconciled_date) {
                    continue;
                }

                $row = [];
                $row['docdate'] = $transaction->trxdate;
                $row['reference'] = $transaction->memo;
                $row['total'] = $transaction->total;
                $row['approved'] = 1;
                $row['prepayment'] = 1;
                $row['cashbook_id'] = $request->cashbook_id;

                if ($row['cashbook_id'] == 10) {
                    $row['document_currency'] = 'USD';
                }
                // process auto allocation
                $exists = \DB::table('acc_cashbook_transactions')
                    ->where('docdate', $transaction->trxdate)
                    ->where('reference', $transaction->memo)
                    ->where('total', $transaction->total)
                    ->where('cashbook_id', $request->cashbook_id)
                    ->count();
                $api_trx_exists = \DB::table('acc_cashbook_transactions')
                    ->where('docdate', $transaction->trxdate)
                    ->where('api_id', '>', 0)
                    ->where('total', $transaction->total)
                    ->where('cashbook_id', $request->cashbook_id)
                    ->count();

                $fnb_trx_exists = \DB::table('acc_cashbook_transactions')
                    ->where('docdate', $transaction->trxdate)
                    ->where('api_data', '>', '')
                    ->where('total', $transaction->total)
                    ->where('cashbook_id', $request->cashbook_id)
                    ->count();

                if ($exists || $api_trx_exists || $fnb_trx_exists) {
                    continue; // skip duplicates in file and existing transactions
                } else {
                    $bank_id = dbinsert('acc_cashbook_transactions', $row);
                }
            }
            allocate_bank_transactions();
            cashbook_reconcile($request->cashbook_id);
            \DB::connection('default')->table('acc_cashbook')->where('id', $request->cashbook_id)->update(['fnb_last_import' => date('Y-m-d H:i:s')]);

            return json_alert('File Imported');
        } catch (\Throwable $ex) {
            return json_alert('import exception - check logs', 'error');
        }
    }

    public function cashbookAllocate(Request $request)
    {
        try {
            $cashbook_account_ids = \DB::table('acc_cashbook')->where('allow_allocate', 1)->pluck('id')->toArray();

            $trx = \DB::table('acc_cashbook_transactions')->where('id', $request->bank_id)->get()->first();
            if (! is_dev() && ! in_array($trx->cashbook_id, $cashbook_account_ids)) {
                return json_alert('Manual allocations are not allowed for this cashbook.', 'error');
            }

            // if (empty($request->supplier_id) && empty($request->account_id) && empty($request->ledger_account_id)) {
            //     // return json_alert('Invalid allocate account.', 'error');
            // }

            if (! empty($request->ledger_account_id) && ! empty($request->control_account_id) && is_cashbook_ledger_account($request->control_account_id)) {
                if ($trx->total < 0) {
                    return json_alert('Only incoming payments can be allocated to cashbook control account', 'error');
                }
            }

            $allocate_count = 0;
            if (! empty($request->supplier_id)) {
                $allocate_count++;
            }
            if (! empty($request->account_id)) {
                $allocate_count++;
            }
            if (! empty($request->ledger_account_id)) {
                $allocate_count++;
            }
            if ($allocate_count > 1) {
                return json_alert('Can only allocate to a single account.', 'error');
            }

            if (! empty($request->ledger_account_id) && $request->ledger_account_id == 4) {
                return json_alert('Invalid ledger account.', 'error');
            }

            if (! empty($request->ledger_account_id) && $request->ledger_account_id == 57 && empty($request->control_account_id)) {
                return json_alert('Control account required.', 'error');
            }

            if (! empty($request->reference_match_id) && ! empty($request->reference_match)) {
                \DB::table('acc_bank_references')->where('id', $request->reference_match_id)->update(['reference' => $request->reference_match]);
            } elseif (! empty($request->reference_match)) {
                $reference_exists = \DB::table('acc_bank_references')->where('reference', $request->reference_match)->count();

                if ($reference_exists) {
                    if ($request->account_id > 0) {
                        $same_account = \DB::table('acc_bank_references')->where('reference', $request->reference_match)->where('account_id', $request->account_id)->count();

                        if (! $same_account) {
                            \DB::table('acc_bank_references')->where('reference', $request->reference_match)
                                ->update(['supplier_id' => 0, 'ledger_account_id' => 0, 'account_id' => $request->account_id]);
                        }
                    }
                    if ($request->supplier_id > 0) {
                        $same_account = \DB::table('acc_bank_references')->where('reference', $request->reference_match)->where('supplier_id', $request->supplier_id)->count();
                        if (! $same_account) {
                            \DB::table('acc_bank_references')->where('reference', $request->reference_match)
                                ->update(['supplier_id' => $request->supplier_id, 'ledger_account_id' => 0, 'account_id' => 0]);
                        }
                    }
                    if ($request->ledger_account_id > 0) {
                        $same_account = \DB::table('acc_bank_references')->where('reference', $request->reference_match)->where('ledger_account_id', $request->ledger_account_id)->count();
                        if (! $same_account) {
                            \DB::table('acc_bank_references')->where('reference', $request->reference_match)
                                ->update(['supplier_id' => 0, 'ledger_account_id' => $request->ledger_account_id, 'control_account_id' => $request->control_account_id, 'account_id' => 0]);
                        }
                    }
                } else {
                    if ($request->account_id > 0) {
                        $reference_data = ['reference' => $request->reference_match, 'account_id' => $request->account_id];
                        $reference_data['created_by'] = get_user_id_default();
                        $reference_data['created_at'] = date('Y-m-d H:i:s');
                        \DB::table('acc_bank_references')->insert($reference_data);
                    }
                    if ($request->supplier_id > 0) {
                        $reference_data = ['reference' => $request->reference_match, 'supplier_id' => $request->supplier_id];
                        $reference_data['created_by'] = get_user_id_default();
                        $reference_data['created_at'] = date('Y-m-d H:i:s');
                        \DB::table('acc_bank_references')->insert($reference_data);
                    }
                    if ($request->ledger_account_id > 0) {
                        $reference_data = ['reference' => $request->reference_match, 'ledger_account_id' => $request->ledger_account_id, 'control_account_id' => $request->control_account_id];
                        if (empty($reference_data['control_account_id'])) {
                            $reference_data['control_account_id'] = 0;
                        }

                        $reference_data['created_by'] = get_user_id_default();
                        $reference_data['created_at'] = date('Y-m-d H:i:s');

                        \DB::table('acc_bank_references')->insert($reference_data);
                    }
                }
            }

            $erp = new \DBEvent;

            $trx = \DB::table('acc_cashbook_transactions')->where('id', $request->bank_id)->get()->first();
            if ($trx->account_id > 0) {
                if (! str_contains($trx->reference, 'SWIFT') && $trx->total > 0) {
                    $account = dbgetaccount($trx->account_id);
                    if ($account->bank_allocate_airtime) {
                        return json_alert('Cannot re-allocate transactions applied to airtime customers.', 'error');
                    }
                }
            }

            $trx_data = (array) $trx;
            $period = date('Y-m', strtotime($trx->docdate));
            $period_status = dbgetcell('acc_periods', 'period', $period, 'status');
            if ($period_status != 'Open') {
                return json_alert('Period closed', 'warning');
            }

            // if (0 == $trx->total) {
            //     return json_alert('Cannot allocate zero total transactions', 'error');
            // }

            \DB::table('acc_cashbook_transactions')
                ->where('id', $trx->id)
                ->update([
                    'account_id' => 0,
                    'ledger_account_id' => 0,
                    'supplier_id' => 0,
                ]);

            unset($trx_data['account_id']);
            unset($trx_data['ledger_account_id']);
            unset($trx_data['supplier_id']);

            if ($trx->ledger_account_id) {
                delete_journal_entry_by_cashbook_transaction_id($trx->id);
            }

            if (! empty($request->account_id)) {
                $account_id = $request->account_id;
                $trx_data['account_id'] = $account_id;
                $trx_data['doctype'] = 'Cashbook Customer Receipt';
                unset($trx_data['supplier_id']);
                unset($trx_data['ledger_account_id']);
            } elseif (! empty($request->supplier_id)) {
                $supplier_id = $request->supplier_id;

                $trx_data['supplier_id'] = $supplier_id;
                $trx_data['doctype'] = 'Cashbook Supplier Payment';
                unset($trx_data['ledger_account_id']);
                unset($trx_data['account_id']);
            } elseif (! empty($request->ledger_account_id)) {
                if (! empty($request->control_account_id) && is_cashbook_ledger_account($request->control_account_id)) {
                    $trx_data['control_account_id'] = $request->control_account_id;
                    \DB::table('acc_cashbook_transactions')->where('cashbook_transaction_id', $trx->id)->delete();
                    cashbook_control_transfer($request->control_account_id, $trx->total, $trx->docdate, $trx->id);
                }

                $taxable = dbgetcell('acc_ledger_accounts', 'id', $request->ledger_account_id, 'taxable');
                if (! $taxable && ! empty($request->vat_invoice)) {
                    $taxable = true;
                }
                if ($taxable) {
                    if ($trx->docdate <= '2018-03-31') {
                        $tax = $trx->total * 14 / 114;
                    } else {
                        $tax = $trx->total * 15 / 115;
                    }
                } else {
                    $tax = 0;
                }

                $trx_data['tax'] = $tax;
                $trx_data['ledger_account_id'] = $request->ledger_account_id;
                $trx_data['doctype'] = 'Cashbook Expense';
                unset($trx_data['supplier_id']);
                unset($trx_data['account_id']);
            }
            $trx_data['allocate_reference'] = '';
            $trx_data['bank_reference_id'] = 0;
            if (! empty($trx->bank_reference_id)) {
                \DB::table('acc_bank_references')->where('id', $trx->bank_reference_id)->update(['is_deleted' => 1]);
            }

            \DB::table('acc_cashbook_transactions')->where('id', $trx->id)->update($trx_data);

            // set doctypes

            $cashbook_bank_ids = \DB::table('acc_cashbook')->where('yodlee_account_id', '>', '')->pluck('id')->toArray();
            \DB::table('acc_cashbook_transactions')->whereIn('cashbook_id', $cashbook_bank_ids)->whereNull('doctype')->where('account_id', '>', 0)->update(['doctype' => 'Cashbook Customer Receipt']);
            \DB::table('acc_cashbook_transactions')->whereIn('cashbook_id', $cashbook_bank_ids)->whereNull('doctype')->where('supplier_id', '>', 0)->update(['doctype' => 'Cashbook Supplier Payment']);
            \DB::table('acc_cashbook_transactions')->whereIn('cashbook_id', $cashbook_bank_ids)->whereNull('doctype')->where('ledger_account_id', '>', 0)->update(['doctype' => 'Cashbook Expense']);

            $erp = new \DBEvent;
            $erp->setTable('acc_cashbook_transactions');

            if (! empty($request->account_id)) {
                \DB::table('crm_documents')->where('account_id', $request->account_id)->where('total', $trx->total)->where('doctype', 'Quotation')->update(['doctype' => 'Order']);
                $erp->setDebtorBalance($request->account_id);
            }
            if (! empty($request->supplier_id)) {
                $erp->setCreditorBalance($request->supplier_id);
            }

            $erp->postDocument($request->bank_id);
            $erp->postDocumentCommit();

            $cashbooks = \DB::table('acc_cashbook')->where('allow_allocate', 1)->get();
            foreach ($cashbooks as $cashbook) {
                cashbook_reconcile($cashbook->id);
            }

            return json_alert('Transaction allocated', 'success', ['row_id' => $request->bank_id]);
        } catch (\Throwable $ex) {
            exception_log($ex);

            return json_alert($ex->getMessage().' '.$ex->getFile().':'.$ex->getLine(), 'error');
        }
    }

    public function subscriptionMigrate(Request $request)
    {
        try {
            $subscription = \DB::table('sub_services')->where('id', $request->subscription_id)->get()->first();
            if ($subscription->provision_type == 'airtime_contract') {
                $new_package_amount = $request->line_qty * $request->package_amount;
                $data = [
                    'qty' => $request->line_qty,
                    'usage_allocation' => $new_package_amount,
                    'detail' => intval($new_package_amount * 2).' minutes',
                ];
                \DB::table('sub_services')->where('id', $request->subscription_id)->update($data);

                return json_alert('Airtime Subscription updated');
            } else {
                if (empty($request->new_product_id) || empty($request->subscription_id)) {
                    return json_alert('Select a product to migrate', 'error');
                }

                $require_file = true;

                if (! is_superadmin() && ! empty($request->manager_override)) {
                    $require_file = false;
                }
                if (is_superadmin() || is_dev()) {
                    $require_file = false;
                }

                if (session('role_level') == 'Admin' && $require_file) {
                    if (empty($_FILES)) {
                        return json_alert('Migration document required', 'error');
                    }
                    if (empty($request->subscription_id)) {
                        return json_alert('Invalid Id', 'error');
                    }
                    $id = $request->subscription_id;
                    $field = 'migration_document';

                    if ($request->file($field)) {
                        $file_type = $request->file($field)->getMimeType();

                        if (str_contains($file_type, 'image/') || str_contains($file_type, 'txt') || str_contains($file_type, 'doc') || str_contains($file_type, 'pdf')) {
                            $file = $request->file($field);
                            $destinationPath = uploads_path(334);
                            $filename = $file->getClientOriginalName();

                            $filename = str_replace([' ', ','], '_', $filename);

                            $uploadSuccess = $file->move($destinationPath, $filename);

                            if ($uploadSuccess) {
                                \DB::table('sub_services')->where('id', $id)->update(['migration_document' => $filename]);
                            } else {
                                return json_alert('Upload error', 'error');
                            }
                        } else {
                            return json_alert('Invalid File Type', 'error');
                        }
                    } else {
                        return json_alert('Migration document required', 'error');
                    }
                }

                $subscription = \DB::table('sub_services')->where('id', $request->subscription_id)->get()->first();
                $sub = new \ErpSubs;
                $sub->migrate($request->subscription_id, $request->new_product_id);
                $result = $sub->processMigrationByAccountId($subscription->account_id);

                if ($result === true) {
                    return json_alert('Subscription updated');
                } else {
                    return json_alert('Could not update Subscription', 'error');
                }
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_email($ex, 'Migrate Error');

            return json_alert('An error occured. Please contact support.', 'error');
        }
    }

    public function postMailbox(Request $request)
    {
        $data = $request->all();

        $site = \DB::table('isp_host_websites')->where('domain', $request->domain)->get()->first();

        // SEND PASSWORD EMAIL
        if (! empty($request->mailboxuser_send)) {
            $exists = \DB::table('isp_hosting_emails')->where('subscription_id', $request->subscription_id)->where('email', $request->mailboxuser_send.'@'.$site->domain)->count();
            if (! $exists) {
                return json_alert('Password needs to be changed and saved first.', 'warning');
            } else {
                $hosting_email = \DB::table('isp_hosting_emails')->where('subscription_id', $request->subscription_id)->where('email', $request->mailboxuser_send.'@'.$site->domain)->get()->first();

                $email_data['email_address'] = $hosting_email->email;
                $email_data['password'] = $hosting_email->pass;
                $email_data['domain'] = $site->domain;

                $function_variables = get_defined_vars();
                $email_data['internal_function'] = 'send_interworx_email_details';
                $result = erp_process_notification($site->account_id, $email_data, $function_variables);

                return json_alert('Email sent');
            }
        }

        // DELETE EMAIL
        if (! empty($request->mailboxuser_delete)) {
            $result = (new \Interworx)->setServer($site->server)->setDomain($site->domain)->deleteEmail($request->mailboxuser_delete);

            if ($result['status'] != 0) {
                $error = 'Error saving mailbox.';
                if (! is_array($result['payload'])) {
                    $error = $result['payload'];
                }

                return json_alert($error, 'warning');
            }

            return json_alert($result['payload']);
        }

        // ADD EMAIL
        if (! empty($request->mailbox_username)) {
            if (str_contains($request->mailbox_username, '@')) {
                $error = 'Remove @'.$request->domain.' from the username.';

                return json_alert($error, 'warning');
            }
            if (empty($request->mailbox_password)) {
                return json_alert('Fill all fields.', 'warning');
            }

            $result = (new \Interworx)->setServer($site->server)->setDomain($site->domain)->createEmail($request->mailbox_username, $request->mailbox_password);

            if ($result['status'] != 0) {
                $error = 'Error saving mailbox.';
                if (! is_array($result['payload'])) {
                    $error = $result['payload'];
                }
                if (str_contains($error, ' is not between 6 and 128')) {
                    $error = 'Password needs to be at least 6 characters';
                }

                return json_alert($error, 'warning');
            }
            $email_data['email_address'] = $request->mailbox_username.'@'.$site->domain;
            $email_data['password'] = $request->mailbox_password;
            $email_data['domain'] = $site->domain;

            $email_data['test_debug'] = 1;

            $function_variables = get_defined_vars();
            $email_data['internal_function'] = 'send_interworx_email_details';
            erp_process_notification($site->account_id, $email_data, $function_variables);

            $data = [
                'email' => $request->mailbox_username.'@'.$site->domain,
                'pass' => $request->mailbox_password,
                'subscription_id' => $request->subscription_id,
            ];

            \DB::table('isp_hosting_emails')->insert($data);

            return json_alert($result['payload']);
        }

        // UPDATE PASSWORD
        $update_password = false;
        $mail_list = explode(',', $request->mail_list);
        foreach ($data as $key => $val) {
            if (! in_array($key, $mail_list)) {
                continue;
            }
            if (! empty($val)) {
                $result = (new \Interworx)->setServer($site->server)->setDomain($site->domain)->editEmail($key, $val);

                if ($result['status'] != 0) {
                    $error = 'Error updating mailbox.';
                    if (! is_array($result['payload'])) {
                        $error = $result['payload'];
                    }
                    if (str_contains($error, ' is not between 6 and 128')) {
                        $error = 'Password needs to be at least 6 characters';
                    }

                    return json_alert($error, 'warning');
                }
                $email_data['email_address'] = $key.'@'.$site->domain;
                $email_data['password'] = $val;
                $email_data['domain'] = $site->domain;
                $email_data['test_debug'] = 1;

                $function_variables = get_defined_vars();
                $email_data['internal_function'] = 'send_interworx_email_details';
                // erp_process_notification($site->account_id, $email_data, $function_variables);
                $exists = \DB::table('isp_hosting_emails')->where('subscription_id', $request->subscription_id)->where('email', $key.'@'.$site->domain)->count();
                if (! $exists) {
                    $data = [
                        'email' => $key.'@'.$site->domain,
                        'pass' => $val,
                        'subscription_id' => $request->subscription_id,
                    ];

                    \DB::table('isp_hosting_emails')->insert($data);
                } else {
                    $data = [
                        'pass' => $val,
                    ];

                    \DB::table('isp_hosting_emails')->where('subscription_id', $request->subscription_id)->where('email', $key.'@'.$site->domain)->update($data);
                }
                $update_password = true;
            }
        }

        if ($update_password) {
            return json_alert('Updated successfully.');
        }
    }

    public function postFtp(Request $request)
    {
        $data = $request->all();

        $site = \DB::table('isp_host_websites')->where('domain', $request->domain)->get()->first();

        // DELETE FTP
        if (! empty($request->ftpuser_delete)) {
            $result = (new \Interworx)->setServer($site->server)->setDomain($site->domain)->deleteFtp($request->ftpuser_delete);

            if ($result['status'] != 0) {
                $error = 'Error saving ftp.';
                if (! is_array($result['payload'])) {
                    $error = $result['payload'];
                }

                return json_alert($error, 'warning');
            }

            return json_alert($result['payload']);
        }

        // CREATE FTP
        if (! empty($request->ftp_username)) {
            if (empty($request->ftp_password) || empty($request->ftp_homedir)) {
                return json_alert('Fill all fields.', 'warning');
            }
            if (str_contains($request->ftp_username, '@')) {
                $error = 'Remove @'.$request->domain.' from the username.';

                return json_alert($error, 'warning');
            }
            $result = (new \Interworx)->setServer($site->server)->setDomain($site->domain)->createFtp($request->ftp_username, $request->ftp_password, $request->ftp_homedir);

            if ($result['status'] != 0) {
                $error = 'Error saving ftp.';
                if (! is_array($result['payload'])) {
                    $error = $result['payload'];
                }
                if (str_contains($error, ' is not between 6 and 128')) {
                    $error = 'Password needs to be at least 6 characters';
                }

                return json_alert($error, 'warning');
            }
            $email_data['ftp'] = $request->ftp_username.'@'.$site->domain;
            $email_data['password'] = $request->ftp_password;
            $email_data['domain'] = 'ftp.'.$site->domain;
            $function_variables = get_defined_vars();
            $email_data['internal_function'] = 'send_interworx_ftp_details';
            erp_process_notification($site->account_id, $email_data, $function_variables);

            return json_alert($result['payload']);
        }

        // UPDATE PASSWORD
        $update_password = false;
        foreach ($data as $key => $val) {
            if ($key == '_token') {
                continue;
            }
            if (str_contains($key, 'homedir')) {
                continue;
            }

            if (str_starts_with($key, 'ftp_')) {
                continue;
            }

            if ($key !== 'domain') {
                if (! empty($val)) {
                    $result = (new \Interworx)->setServer($site->server)->setDomain($site->domain)->editFtp($key, $val, $data[$key.'homedir']);

                    if ($result['status'] != 0) {
                        $error = 'Error updating ftp.';
                        if (! is_array($result['payload'])) {
                            $error = $result['payload'];
                        }
                        if (str_contains($error, ' is not between 6 and 128')) {
                            $error = 'Password needs to be at least 6 characters';
                        }

                        return json_alert($error, 'warning');
                    }
                    $email_data['ftp'] = $key.'@'.$site->domain;
                    $email_data['password'] = $val;
                    $email_data['domain'] = 'ftp.'.$site->domain;
                    $function_variables = get_defined_vars();
                    $email_data['internal_function'] = 'send_interworx_ftp_details';

                    erp_process_notification($site->account_id, $email_data, $function_variables);

                    $update_password = true;
                }
            }
        }

        if ($update_password) {
            return json_alert('Updated successfully.');
        }
    }

    public function smsReport(Request $request, $account_id)
    {
        $account = dbgetaccount($account_id);
        $list = \DB::table('isp_sms_messages')
            ->select('numbers', 'message', 'queuetime', 'charactercount')
            ->where('queuetime', '>', date('Y-m-d', strtotime('-1 year')))
            ->where('account_id', $account_id)
            ->get();

        if (empty($list) || count($list) == 0) {
            return json_alert('No records to export.', 'warning');
        }
        $file_title = clean($account->company).' - SMS Report';
        $file_name = clean($account->company).' - SMS Report.xls';

        $excel_list = [];
        foreach ($list as $item) {
            $excel_list[] = [
                'Queuetime' => $item->queuetime,
                'Number' => $item->numbers,
                'Message' => $item->message,
                'Charactercount' => $item->charactercount,
            ];
        }

        $export = new App\Exports\CollectionExport;
        $export->setData($excel_list);

        Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');

        $file = attachments_path().$file_name;
        $headers = [
            'Content-Type: application/octet-stream',
        ];

        return response()->download($file, $file_name, $headers);
    }

    public function saveGridView(Request $request)
    {
        $grid_view = \DB::table('erp_grid_views')->where('id', $request->id)->get()->first();
        $settings = $request->settings;

        if (! empty($request->query_string)) {
            $query_string = $request->query_string;
            $settings = json_decode($request->settings);

            if (! empty($settings->persistData)) {
                $settings = $settings->persistData;
                $settings = json_decode($settings);
            }

            foreach ($settings->columns as $i => $col) {
                $settings->columns[$i]->index = $i;
            }

            foreach ($query_string as $field => $val) {
                foreach ($settings->filterSettings->columns as $i => $f) {
                    if ($f->field == $field) {
                        unset($settings->filterSettings->columns[$i]);
                    }
                }
            }

            if ($grid_view->module_id) {
                $db_table = \DB::table('erp_cruds')->where('id', $grid_view->module_id)->pluck('db_table')->first();
                $cols = get_columns_from_schema($db_table);
                if (in_array('sort_order', $cols)) {
                    unset($settings->sortSettings);
                }
            }

            //aa($settings);
            $settings = json_encode((object) ['persistData' => json_encode($settings)]);
        }

        \DB::table('erp_grid_views')->where('id', $request->id)->update(['settings' => $settings]);
        $erp = new \DBEvent;
        $erp->setTable('erp_grid_views');
        $erp->setProperties(['request' => (object) ['id' => $request->id]]);

        return json_alert('Saved');
    }

    public function updateUsage()
    {
        $result = \DB::connection('pbx')->select("select context, sum(carrier_cost) as total from v_xml_cdr where start_stamp > (NOW() - INTERVAL '30 days') group by context");
        foreach ($result as $row) {
            \DB::table('erp_users')->where('pabx_domain', $row->context)->update(['voip_usage_30_days' => $row->total]);
        }
    }

    public function processBilling(Request $request)
    {
        //    return json_alert('Unavailable', 'error');
        if (empty($request->billing_date)) {
            return json_alert('Invalid docdate', 'error');
        }
        /*
        if (session('instance')->id == 2) {


            if($request->water_bill < 1500){
                return json_alert('Water bill needs to at least 1500','error');
            }

            $service_balances = \DB::table('sub_service_balances')->where('is_deleted',0)->orderBy('id','desc')->get()->first();
            $period_id = \DB::table('acc_periods')->where('period',date('Y-m',strtotime($request->billing_date)))->pluck('id')->first();
            $service_balances_data = [];
            $service_balances_data['period_id'] = $period_id;
            foreach ($service_balances as $k => $v) {
                if (isset($request->{$k})) {
                    $service_balances_data[$k] = $request->{$k};
                }
            }
            \DB::table('sub_service_balances')->updateOrInsert(['period_id'=>$period_id],$service_balances_data);
        }
        */

        if (session('instance')->id == 2) {
            // if(date('d') > 2 && date('d') < 20){
            //     return json_alert('Billing cannot be created mid month','warning');
            // }
            $billing = new \EldoBilling;

            $billing->monthly_billing($request->billing_date);

            schedule_update_billing_details();
            $last_billing_id = \DB::table('acc_billing')->where('billing_type', 'Monthly')->orderBy('id', 'desc')->pluck('id')->first();

            \DB::table('acc_billing')->where('id', $last_billing_id)->update(['processed' => 1]);
            send_billing_summary($last_billing_id);

            return json_alert('Billing processed.', 'success');
        } else {
            return json_alert('Only for eldo office.', 'success');
        }
    }

    public function electricityRecovered(Request $request)
    {
        $id = \DB::table('sub_service_balances')->where('is_deleted', 0)->orderBy('id', 'desc')->pluck('id')->first();

        \DB::table('sub_service_balances')->where('id', $id)->update(['electricity_balance' => $request->electricity_balance]);

        return json_alert('Electricity Balance updated.', 'success');
    }
}
