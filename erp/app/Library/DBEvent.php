<?php

use Illuminate\Support\Facades\DB;

class DBEvent
{
    protected $module_id;

    protected $table;

    protected $event_type;

    protected $fields;

    public $request;

    public $ledgers;

    protected $ledger_ids;

    public $validate_document = false;

    public function __construct($module_id = 2, $settings = [])
    {
        $this->request = [];
        $this->setModule($module_id);

        $this->setProperties($settings);
        $this->ledgers = [];
    }

    public function setModule($id)
    {
        $module = DB::connection('default')->table('erp_cruds')->where('id', $id)->get()->first();
        $this->module = $module;
        $this->module_id = $id;
        $this->table = $module->db_table;
        $this->connection = $module->connection;
        $this->event_type = 'get';
        $this->fields = get_columns_from_schema($module->db_table);
        session(['mod_id' => $id]);
        session(['mod_conn' => $module->connection]);

        return $this;
    }

    public function setProperties($settings = [])
    {
        if (! empty($settings) && is_array($settings) && count($settings) > 0) {
            foreach ($settings as $key => $value) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }

    public function setTable($table)
    {
        $module_id = DB::connection('default')->table('erp_cruds')->where('db_table', $table)->pluck('id')->first();
        $this->setModule($module_id);

        return $this;
    }

    private function beforeSave()
    {
        /*
        if(!is_main_instance()){
            if(in_array($this->table,['erp_cruds','erp_menu','erp_module_fields'])){
                if(empty($this->request->custom)){
                if(!empty($this->request->render_module_id)){
                    $custom_module = DB::connection('default')->table('erp_cruds')->where('id', $this->request->render_module_id)->pluck('custom')->first();
                    if(!$custom_module){
                        return 'Shared builder config can only be saved on main instance';
                    }
                }elseif(!empty($this->request->module_id)){
                    $custom_module = DB::connection('default')->table('erp_cruds')->where('id', $this->request->module_id)->pluck('custom')->first();
                    if(!$custom_module){
                        return 'Shared builder config can only be saved on main instance';
                    }
                }
                }

                if(!$this->request->custom){
                    return 'Shared builder config can only be saved on main instance';
                }

            }
        }
        */
        $validation = $this->checkPeriod();
        if ($validation) {
            return $validation;
        }

        $validation = $this->checkProvisioningConditions();
        if ($validation) {
            return $validation;
        }

        if ($this->table == 'crm_documents' || $this->table == 'crm_supplier_documents') {
            if (empty($this->request->doctype)) {
                return 'Document type required.';
            }
        }

        if ($this->table == 'crm_documents') {
            $account = dbgetaccount($this->request->account_id);

            if ($account->type == 'reseller') {
                if (! in_array(147, $this->request->product_id) && empty($this->request->reseller_user)) {
                    return 'Partner user required.';
                }
            }
            $reseller = dbgetaccount($account->partner_id);
            $tax = 0;
            $request_total = currency($this->request->total);

            $request_tax = currency($this->request->tax);

            if ($request_total > 0 && $reseller->vat_enabled && ! $account->currency == 'USD') {
                $subtotal = currency($request_total / 1.15);

                $tax = currency($request_total - $subtotal);

                if (! empty($request_tax) && $request_tax > 0) {
                    if ($tax != $request_tax) {
                        $this->request->request->add(['tax' => $tax]);
                    }
                } else {
                    $this->request->request->add(['tax' => $tax]);
                    $lines = [];
                    foreach ($this->request->lines as $line) {
                        $line['price'] = currency($line['price'] / 1.15);
                        $line['full_price'] = currency($line['full_price'] / 1.15);
                        $lines[] = $line;
                    }

                    $this->request->request->add(['lines' => $lines]);
                }
            }
        }

        // webform validation
        if (! empty(session('webform_module_id')) && $this->module->public_access) {
            if (in_array('account_id', $this->fields)) {
                $this->request->request->add(['account_id' => session('webform_account_id')]);
            }

            if (in_array('subscription_id', $this->fields)) {
                $this->request->request->add(['subscription_id' => session('webform_subscription_id')]);
            }
        }

        return false;
    }

    public function save($request)
    {
        /*
            DB Transactions will show changes for local db connection before the commit is completed, foreign db connections will only show changes after commit

            General error: 1205 Lock wait timeout exceeded; try restarting transaction
            transactions deadlock condition
            running statements on the same database with a different connection name will cause a deadlock
            eg. connection_name='default' and connection_name='cloudtelecoms' both uses the same db
            if the connection is saving on default and a aftersave/beforesave function is saving on the cloudtelecoms connection
            then the transaction will fail and throw an error
        */

        // exclude pbx and pbx_cdr from transactions -- In failed sql transaction: 7 ERROR:  current transaction is aborted, commands ignored until end of transaction block

        session(['rollback_connections' => []]);
        $exclude_rollback_tables = ['erp_instance_migrations', 'acc_cashbook_transactions', 'v_ring_group_destinations', 'crm_accounts', 'sub_services'];
        if (! in_array($this->table, $exclude_rollback_tables) && ! in_array($this->connection, ['pbx', 'pbx_cdr'])) {
            $rollback_connections = [$this->connection];
            session(['rollback_connections' => $rollback_connections]);

            \DB::connection($this->connection)->beginTransaction();
        }

        try {
            if (! $request instanceof \Illuminate\Http\Request) {
                $request = (array) $request;
                $request = new \Illuminate\Http\Request($request);
                $request->setMethod('POST');
                $this->setProperties(['array_post' => 1]);
            } else {
                $rules = $this->validateForm();

                $validator = Validator::make($request->all(), $rules);
                if (! $validator->passes()) {
                    foreach ($validator->getMessageBag()->toArray() as $key => $val) {
                        if (request()->segment(1) == 'rest_api') {
                            $message .= $key.':'.$val[0];
                        } else {
                            $message .= '<li>'.$val[0].'</li>';
                        }
                    }

                    return json_alert($message, 'warning');
                }
            }

            $this->setRequestProperties($request);

            $this->setDBRecord();

            if ($this->validate_document) {
                $validation = $this->setDocumentRequest();
                if ($validation) {
                    return $validation;
                }
            }

            $validation = $this->beforeSave();

            if ($validation) {
                return response()->json(['status' => 'error', 'message' => $validation]);
            }

            $this->event_type = 'beforesave';
            $validation = $this->processEvent();
            if ($validation instanceof \Illuminate\Http\JsonResponse) {
                return $validation;
            }
            if ($validation) {
                return response()->json(['status' => 'error', 'message' => $validation]);
            }

            $data = $this->validatePost();

            if (! is_main_instance() && in_array('custom', $this->fields) && empty($this->request->id)) {
                $data['custom'] = 1;
            }

            if ($data['validatePostError']) {
                return response()->json(['status' => 'error', 'message' => $data['validatePostError']]);
            }

            try {
                $id = $this->insert($data);
            } catch (\Throwable $ex) {
                exception_log($ex->getMessage());
                exception_log($ex->getTraceAsString());
                if (! in_array($this->table, $exclude_rollback_tables)) {
                    foreach (session('rollback_connections') as $rollback_connection) {
                        //aa('rollback1');

                        \DB::connection($rollback_connection)->rollback();
                    }
                }

                return response()->json(['status' => 'error', 'message' => $ex->getMessage()]);
            }

            if (empty($id)) {
                return response()->json(['status' => 'error', 'message' => 'Record not saved']);
            }

            if (empty($this->request->id)) {
                $this->request->request->add(['new_record' => 1]);
                $this->request->request->add(['id' => $id]);
            }

            if ($this->validate_document) {
                $this->setDocumentLines();
            }

            $this->event_type = 'aftersave';
            $validation = $this->doctypeAfterSave();

            //aa(['doctypeAfterSavevalidation' => $validation]);
            if ($validation) {
                if (! in_array($this->table, $exclude_rollback_tables)) {
                    foreach (session('rollback_connections') as $rollback_connection) {
                        //aa('rollback1');

                        \DB::connection($rollback_connection)->rollback();
                    }
                }

                return response()->json(['status' => 'error', 'message' => $validation]);
            }

            $result = $this->processEvent();
            //aa(['AfterSavevalidation' => $result]);
            if ($result instanceof \Illuminate\Http\JsonResponse) {
                $result = $result->getData()->message;
            }

            if ($result) {
                if (! in_array($this->table, $exclude_rollback_tables)) {
                    foreach (session('rollback_connections') as $rollback_connection) {
                        //aa('rollback2');

                        \DB::connection($rollback_connection)->rollback();
                    }
                }

                return response()->json(['status' => 'error', 'message' => $result]);
            }

            $doctype_tables = DB::connection('default')->table('acc_doctypes')->pluck('doctable')->toArray();

            if (in_array($this->table, $doctype_tables)) {
                $this->postDocument($id);
                $this->postDocumentCommit();
            }

            $return_document_popup = false;
            if ($this->validate_document && empty($this->array_post)) {
                if (! in_array($this->table, $exclude_rollback_tables)) {
                    foreach (session('rollback_connections') as $rollback_connection) {
                        //aa('rollback3 commit');

                        \DB::connection($rollback_connection)->commit();
                    }

                    $this->event_type = 'aftercommit';
                    $this->processEvent();
                }

                $return_document_popup = true;
            } elseif (! in_array($this->table, $exclude_rollback_tables)) {
                foreach (session('rollback_connections') as $rollback_connection) {
                    //aa('rollback4 commit');

                    \DB::connection($rollback_connection)->commit();
                }
                $this->event_type = 'aftercommit';
                $this->processEvent();
            }

            $this->afterCommit();
            $this->commitLogData();

            if (! empty($this->return_document_id)) {
                return ['id' => $id];
            } elseif ($return_document_popup) {
                return $this->getDocumentResponse($id);
            } else {
                return ['id' => $id];
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
            $post_data = (array) $this->simple_request;
            exception_email($ex, 'Save error', $post_data);
            if (! in_array($this->table, $exclude_rollback_tables)) {
                foreach (session('rollback_connections') as $rollback_connection) {
                    //aa('rollback5');
                    \DB::connection($rollback_connection)->rollback();
                }
            }

            return response()->json(['status' => 'error', 'message' => 'An error occured, please try again later.']);
        }
    }

    public function deleteRecord($request)
    {
        try {
            if (is_array($request)) {
                $request = (object) $request;
            }

            $this->setRequestProperties($request);
            $this->setDBRecord();

            if (empty($this->request->id)) {
                return response()->json(['status' => 'error', 'message' => 'Record not found']);
            }

            // delete from ledger

            if ($this->table == 'crm_documents' || $this->table == 'crm_supplier_documents') {
                $result = $this->voidRecord();
                process_document_approvals();
                $this->postDocument($request->id);
                $this->postDocumentCommit();

                return $result;
            }

            if ($this->table == 'erp_form_events' && ! empty($this->db_record->function_name)) {
                if (function_exists($this->db_record->function_name)) {
                    return 'Function code needs to be deleted first.';
                }
            }

            $this->event_type = 'beforedelete';
            $validation = $this->processEvent();
            if ($validation) {
                return $validation;
            }

            $this->deleteRow($this->request->id);

            $this->event_type = 'afterdelete';

            $validation = $this->doctypeAfterSave();

            if ($validation) {
                return response()->json(['status' => 'error', 'message' => $validation]);
            }

            $validation = $this->processEvent();

            if ($validation) {
                return $validation;
            }

            if ($afterDelete) {
                return response()->json(['status' => 'error', 'message' => $afterDelete]);
            } else {
                if ($this->module_id == '526') {
                    return response()->json(['status' => 'success', 'message' => 'Record Deleted.', 'reload_grid_views' => true]);
                } else {
                    return response()->json(['status' => 'success', 'message' => 'Record Deleted.']);
                }
            }
        } catch (\Throwable $ex) {
            exception_log($ex);
            exception_log($ex->getMessage().' '.$ex->getFile().':'.$ex->getLine());
            exception_log($ex->getTraceAsString());
            exception_email($ex, 'Delete error');

            return response()->json(['status' => 'error', 'message' => 'An error occured, please try again later.']);
        }
    }

    private function deleteRow($id)
    {
        $db_columns = $this->fields;
        $key = $this->module->db_key;
        if (in_array('is_deleted', $db_columns) && $this->module->soft_delete) {
            DB::connection($this->connection)->table($this->table)->where($key, $id)->update(['is_deleted' => 1]);
            if (in_array('deleted_at', $db_columns)) {
                DB::connection($this->connection)->table($this->table)->where($key, $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
            }

            if (in_array('updated_at', $db_columns) && in_array('updated_by', $db_columns)) {
                DB::connection($this->connection)->table($this->table)->where($key, $id)->update(['updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default()]);
                $data['updated_at'] = $time;
            }

            $this->setLogData($id, 'deleted');
            $this->commitLogData();
        } elseif (in_array('status', $db_columns) && $this->connection != 'shop') {
            DB::connection($this->connection)->table($this->table)->where($key, $id)->update(['status' => 'Deleted']);
            if (in_array('deleted_at', $db_columns)) {
                DB::connection($this->connection)->table($this->table)->where($key, $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
            }

            if (in_array('updated_at', $db_columns) && in_array('updated_by', $db_columns)) {
                DB::connection($this->connection)->table($this->table)->where($key, $id)->update(['updated_at' => date('Y-m-d H:i:s'), 'updated_by' => get_user_id_default()]);
                $data['updated_at'] = $time;
            }

            $this->setLogData($id, 'deleted');
            $this->commitLogData();
        } else {
            DB::connection($this->connection)->table($this->table)->where($key, $id)->delete();
            // delete from module log
            DB::connection('default')->table('erp_module_log')->where('module_id', $this->module_id)->where('row_id', $id)->delete();
        }
    }

    private function insert($data)
    {
        $data = (array) $data;
        $key = $this->module->db_key;

        if (empty($data[$key])) {
            // Insert Here
            $time = date('Y-m-d H:i:s');
            if (in_array('created_at', $this->fields)) {
                $data['created_at'] = $time;
            }

            if (in_array('created_by', $this->fields)) {
                $data['created_by'] = get_user_id_default();
            }

            if (str_ends_with($key, '_uuid')) {
                $data[$key] = pbx_uuid($this->table, $key);

                $result = DB::connection($this->connection)->table($this->table)->insert($data);
                //aa($result);
                $this->setLogData($data[$key], 'created');

                return $data[$key];
            } else {
                $id = DB::connection($this->connection)->table($this->table)->insertGetId($data, $key);
                $this->setLogData($id, 'created');
            }
        } else {
            $time = date('Y-m-d H:i:s');
            // Update here
            $id = $data[$key];
            if (in_array('updated_at', $this->fields)) {
                $data['updated_at'] = $time;
            }

            if (in_array('updated_by', $this->fields)) {
                $data['updated_by'] = get_user_id_default();
            }

            try {
                $updated = \DB::connection($this->connection)->table($this->table)->where($key, $data[$key])->update($data);
            } catch (\Throwable $ex) {
                exception_log($ex->getMessage());
                exception_log($ex->getTraceAsString());
            }

            // get changed values
            $changed_values = [];
            $beforesave_row = session($this->table.'_event_db_record');
            if (! empty($beforesave_row) && ! empty($beforesave_row->{$key})) {
                foreach ($data as $key => $val) {
                    if ($key == 'updated_at') {
                        continue;
                    }
                    if ($key == 'updated_by') {
                        continue;
                    }
                    if ($beforesave_row->{$key} != $val) {
                        $changed_values[] = $key.': '.$beforesave_row->{$key}.' to '.$val;
                    }
                }
                if (count($changed_values) > 0) {
                    $this->setLogData($id, 'updated', implode(PHP_EOL, $changed_values));
                }
            }
        }

        return $id;
    }

    public function setAccountAging($account_id, $process_deleted = false, $process_actions = true)
    {
        if ($account_id === 1) {
            return false;
        }
        $account_status = \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->pluck('status')->first();
        $account_type = \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->pluck('type')->first();
        $partner_id = \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->pluck('partner_id')->first();
        if (! $process_deleted && $account_status == 'Deleted') {
            return false;
        }
        if ($account_status != 'Deleted') {
            $this->updateDocumentPaymentStatus($account_id);
        } elseif ($process_deleted) {
            $this->updateDocumentPaymentStatus($account_id);
        }
        $payfast = account_has_payfast_subscription($account_id);
        $debit_order = account_has_debit_order($account_id);
        if ($payfast) {
            \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->update(['payment_method' => 'Payfast']);
        } elseif ($debit_order) {
            \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->update(['payment_method' => 'Debit Order']);
        } else {
            \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->update(['payment_method' => 'Bank']);
        }

        set_aging_data($account_id);

        if ($account_type == 'reseller') {
            $reseller_users = \DB::connection('default')->table('crm_accounts')->where('partner_id', $account_id)->pluck('id')->toArray();
            foreach ($reseller_users as $reseller_user) {
                set_aging_data($reseller_user);
            }
            \DB::connection('default')->table('crm_accounts')->where('partner_id', $account_id)->update(['balance' => 0]);
        }

        $manual_sale_statuses = ['Hot', 'Cold'];
        $account_has_quotes = \DB::connection('default')->table('crm_documents')->where('account_id', $account_id)->where('doctype', 'Quotation')->count();
        $account_has_orders = \DB::connection('default')->table('crm_documents')->where('account_id', $account_id)->where('doctype', 'Order')->count();
        $data['quote_total'] = \DB::connection('default')->table('crm_documents')->where('account_id', $account_id)->whereIn('doctype', ['Quotation'])->sum('total');
        $data['orders_total'] = \DB::connection('default')->table('crm_documents')->where('account_id', $account_id)->whereIn('doctype', ['Order'])->sum('total');

        if ($partner_id == 1) {
            $aging_date = \DB::connection('default')->table('crm_documents')->where('doctype', 'Tax Invoice')->where('account_id', $account_id)->orderby('docdate', 'desc')->pluck('docdate')->first();
        } else {
            $aging_date = \DB::connection('default')->table('crm_documents')->where('doctype', 'Tax Invoice')->where('reseller_user', $account_id)->orderby('docdate', 'desc')->pluck('docdate')->first();
        }
        $data['invoice_days'] = 0;
        if (! empty($aging_date)) {
            if (date('Y-m-d', strtotime($aging_date)) < date('Y-m-d')) {
                $date = Carbon\Carbon::parse($aging_date);
                $now = Carbon\Carbon::today();

                $data['invoice_days'] = $date->diffInDays($now);
            }
        }

        \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->update($data);

        if ($account_status != 'Deleted') {
            process_aging_actions($account_id, true, $process_actions);
        } elseif ($process_deleted) {
            process_aging_actions($account_id, false, false);
        }
        $auto_allocate_accounts = \DB::table('crm_accounts')->where('id', $account_id)->where('bank_allocate_airtime', 1)->where('status', 'Enabled')->where('balance', '<', 0)->pluck('id')->toArray();
        foreach ($auto_allocate_accounts as $auto_allocate_account) {
            airtime_invoice_from_balance($auto_allocate_account);
        }

        // delete approvals
        $debtor_status_id = \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->pluck('debtor_status_id')->first();
        if ($debtor_status_id == 1) {
            \DB::table('crm_approvals')->where('title', 'like', 'Account Delete%')->where('module_id', 343)->where('row_id', $account_id)->where('processed', 0)->delete();
        }
    }

    private function afterCommit()
    {
        $this->doctypeAfterCommit();
        $current_conn = DB::getDefaultConnection();
        set_db_connection();

        $this->cacheClear();
        if ($this->table == 'crm_documents' || $this->table == 'crm_supplier_documents') {
            process_document_approvals();
        }
        $this->createActivations();
        $this->updateRecordNoteLog();
        set_db_connection($current_conn);
    }

    /*afterCommit functions  start*/
    private function updateRecordNoteLog()
    {
        if (! empty($this->request->new_record) && ! empty($this->request->last_note)) {
            try {
                add_module_note($this->module_id, $this->request->id, $this->request->last_note);
            } catch (\Throwable $ex) {
            }
        } else {
            $beforesave_row = session($this->table.'_event_db_record');
            if (! empty($this->request->last_note) && $beforesave_row->last_note != $this->request->last_note) {
                try {
                    add_module_note($this->module_id, $this->request->id, $this->request->last_note);
                } catch (\Throwable $ex) {
                }
            }
        }
    }

    private function doctypeAfterCommit()
    {
        $this->setCreditorBalance();
        $this->setDebtorBalance();
        $this->updateAccountType();
        $this->setStockBalance();
        $this->saveProductsPricingChanges();
        $this->updateOpportunities();
    }

    private function updateOpportunities()
    {
        if ($this->table == 'crm_documents') {
            \DB::table('crm_opportunities')
                ->join('crm_documents', 'crm_documents.id', '=', 'crm_opportunities.document_id')
                ->update(['crm_opportunities.doctype' => \DB::raw('crm_documents.doctype'), 'crm_opportunities.total' => \DB::raw('crm_documents.total')]);
            if ($this->request->doctype == 'Quotation') {
                \DB::table('crm_opportunities')->where('document_id', $this->request->id)->update(['status' => 'Quoted']);
            }
            if ($this->request->doctype == 'Order') {
                \DB::table('crm_opportunities')->where('document_id', $this->request->id)->update(['status' => 'Ordered']);
            }
        }
    }

    private function createActivations()
    {
        if (! empty($this->request->account_id) && $this->table == 'crm_documents' || ($this->table == 'acc_cashbook_transactions' && $this->request->account_id > 0)) {
            $account_id = \DB::table($this->table)->where('id', $this->request->id)->pluck('account_id')->first();
        } elseif (! empty($this->request->id) && $this->table == 'crm_accounts') {
            $account_id = $this->request->id;
        }
        if ($this->table == 'crm_documents') {
            // postApprove Invoice
            $doc = \DB::table($this->table)->where('id', $this->request->id)->get()->first();
            if ($doc->completed && ! $doc->subscription_created && $doc->doctype == 'Tax Invoice') {
                provision_auto($doc->id);
            }
        }

        if ($this->table == 'crm_documents' || ($this->table == 'acc_cashbook_transactions' && $this->request->account_id > 0) || $this->table == 'crm_accounts') {
            $converted_doc_ids = $this->updateDocumentPaymentStatus($account_id);
            if ($this->table == 'crm_documents' && $this->request->id && count($converted_doc_ids) == 0) {
                email_document_pdf($this->request->id);
            } elseif ($this->table == 'crm_documents' && count($converted_doc_ids) > 0 && ! in_array($this->request->id, $converted_doc_ids)) {
                email_document_pdf($this->request->id);
            } elseif ($this->table == 'crm_documents') {
                email_document_pdf($this->request->id);
            }
            provision_invoices($account_id);
        }
    }

    /*afterCommit functions  end*/

    public function processOnLoad()
    {
        $this->event_type = 'onload';
        $this->processEvent();
    }

    private function processEvent($row = false)
    {
        if (! $row) {
            $row = $this->request;
        }

        $current_conn = DB::getDefaultConnection();
        set_db_connection();

        $helpers = DB::table('erp_form_events')
            ->select('id', 'function_name')
            ->where('module_id', $this->module_id)
            ->where('active', 1)
            ->where('type', $this->event_type)
            ->get();

        if ($this->event_type == 'beforesave' || $this->event_type == 'aftersave' || $this->event_type == 'beforedelete' || $this->event_type == 'afterdelete') {
            if (! $row) {
                return 'An error occurred, request row not set.';
            }
        }

        foreach ($helpers as $helper) {
            try {
                $function = $helper->function_name;

                if (! function_exists($function)) {
                    debug_email($this->event_type.' function missing: '.$helper->function_name);
                    system_log('event', $helper->function_name, $this->event_type.' function missing: '.$helper->function_name, $this->event_type, $this->event_type, 0, $helper->id);
                    set_db_connection($current_conn);

                    return 'An error occurred1 '.$helper->function_name;
                }

                \DB::connection('default')->table('erp_form_events')->where('id', $helper->id)->update(['last_run' => date('Y-m-d H:i:s')]);
                $startTime = \Carbon\Carbon::now();

                $result = $function($row);
                /*
                if(is_dev() && $this->event_type == 'aftersave' || $this->event_type == 'aftercommit'){
                $sort_save =  \DB::table('erp_module_fields')->where('id', $this->request->id)->pluck('sort_order')->first();
                // aa($function.' 2: '.$sort_save);
                }
                */

                $finishTime = \Carbon\Carbon::now();
                $duration = $finishTime->diff($startTime)->format('%I:%S');
                \DB::connection('default')->table('erp_form_events')->where('id', $helper->id)->update(['last_success' => date('Y-m-d H:i:s'), 'run_time' => $duration, 'last_failed' => null, 'error' => null]);
                if ($result) {
                    set_db_connection($current_conn);

                    return $result;
                }
                $log_message = 'completed';
                if (! empty($this->request->id)) {
                    $log_message .= ' id:'.$this->request->id;
                }
                system_log('event', $helper->function_name, $log_message, $this->event_type, $this->event_type, 1, $helper->id);
            } catch (\Throwable $ex) {
                exception_log($ex);
                $error = $ex->getMessage().' '.$ex->getFile().':'.$ex->getLine().PHP_EOL.$ex->getTraceAsString();
                system_log('event', $helper->function_name, $ex->getMessage(), $this->event_type, $this->event_type, 0, $helper->id);

                $event_link = url(get_menu_url_from_module_id(385)).'?id='.$helper->id;
                exception_email($ex, 'Event error', false, $event_link);

                \DB::connection('default')->table('erp_form_events')->where('id', $helper->id)->update(['last_failed' => date('Y-m-d H:i:s'), 'run_time' => 0, 'error' => $error]);

                set_db_connection($current_conn);

                return 'An error occurred '.$helper->function_name;
            }
        }
        //if( $this->event_type == 'aftersave' ){
        //    process_conditional_updates_aftersave($this->module_id);
        //}
        set_db_connection($current_conn);
    }

    private function getFormConfig()
    {
        $forms = DB::connection('default')->table('erp_module_fields')->where('module_id', $this->module_id)->get();
        $forms = json_decode(json_encode($forms, true), true);

        return $forms;
    }

    private function validateForm()
    {
        $forms = $this->getFormConfig();

        $rules = [];
        foreach ($forms as $form) {
            if ($form['required'] && $form['add']) {
                $requirements = [];
                if ($form['field_type'] == 'email') {
                    $requirements[] = 'email';
                } elseif ($form['field_type'] == 'integer' || $form['field_type'] == 'currency') {
                    $requirements[] = 'numeric';
                } elseif ($form['field_type'] == 'date' || $form['field_type'] == 'datetime') {
                    $requirements[] = 'date';
                }
                if ($form['field_type'] != 'file') {
                    $rules[$form['field']] = 'required';
                }

                if (count($requirements) > 0) {
                    $rules[$form['field']] = $requirements;
                }
            }
        }

        return $rules;
    }

    public function setValidation()
    {
        $this->setProperties(['validation_required' => 1]);

        return $this;
    }

    private function validatePost()
    {
        $validatePostError = '';
        $forms = $this->getFormConfig();

        $data = [];
        $null_fields = [];
        $nullable_fields = get_nullable_from_schema($this->table, $this->connection);

        //aa($this->request->all());

        foreach ($forms as $f) {
            if ($f['aliased_field'] == 1) {
                continue;
            }

            $field = $f['field'];

            if (isset($this->request->{$field}) && empty($this->request->{$field}) && in_array($field, $nullable_fields)) {
                $data[$field] = null;
                $null_fields[] = $field;
            }
            $field_visible = '';
            if ($f['visible'] == 'Add and Edit') {
                $field_visible = 'both';
            }
            if ($f['visible'] == 'Add') {
                $field_visible = 'add';
            }
            if ($f['visible'] == 'Edit') {
                $field_visible = 'edit';
            }
            if ($f['visible'] == 'None') {
                $field_visible = '';
            }

            if (str_contains($f['field_type'], 'hidden') || (empty($this->request->id) && in_array($field_visible, ['add', 'both'])) || (! empty($this->request->id) && in_array($field_visible, ['edit', 'both'])) || (empty($this->validation_required) && ! empty($this->array_post))) {
                if ($f['field_type'] == 'textarea_editor' || $f['field_type'] == 'textarea') {
                    $data[$field] = $this->request->input($field);
                } else {
                    if (! is_null($this->request->input($field))) {
                        $data[$field] = $this->request->input($field);
                    }

                    if ($f['field_type'] == 'signature' && ! empty($this->request->input($field))) {
                        if (str_contains($this->request->input($field), 'data:image')) {
                            $img_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $this->request->input($field)));

                            $destinationPath = uploads_path($this->module->id);
                            $filename = $field.date('Hi').'.png';
                            if (! is_dir($destinationPath)) {
                                mkdir($destinationPath);
                            }
                            $destinationPath .= $filename;
                            file_put_contents($destinationPath, $img_data);
                            $data[$field] = $filename;
                        }
                    }

                    if (empty(session('webform_module_id'))) {
                        $files = is_array($this->request->file($field)) ? $this->request->file($field) : [$this->request->file($field)];

                        if (! $f['opts_multiple'] && ($f['field_type'] == 'file' || $f['field_type'] == 'image') && $f['required'] && empty($this->request->file($field))) {
                            $validatePostError = $f['label'].': File required.';
                        }
                    }

                    //formio files
                    if ($f['field_type'] == 'file' || $f['field_type'] == 'image') {
                        $file_array = $this->request->{$field};
                        $file_names = [];
                        //if (!is_array($file_array) && $file_array instanceof \Illuminate\Http\UploadedFile) {
                        //$file_array = [$file_array];
                        //}

                        if (! empty($file_array) && is_array($file_array) && count($file_array) > 0) {
                            foreach ($file_array as $file) {
                                if ($file instanceof \Illuminate\Http\UploadedFile) {
                                    $name = $file->getClientOriginalName();
                                    $file_names[] = $name;

                                    // if(!file_exists(uploads_path($this->module_id).$name)){
                                    //    File::move(uploads_path($this->module_id).$file['name'],uploads_path($this->module_id).$name);
                                    // }
                                } elseif (is_array($file)) {
                                    $name = $file['name'];
                                    if ($file['originalName'] > '') {
                                        $name = $file['originalName'];
                                        //  if(!file_exists(uploads_path($this->module_id).$name)){
                                        //      File::move(uploads_path($this->module_id).$file['name'],uploads_path($this->module_id).$name);
                                        //  }
                                    }
                                    if (empty($file['data'])) {
                                        $file_names[] = ($file['originalName'] > '') ? $file['originalName'] : $file['name'];
                                    } elseif ($file['data']['status'] == 'success' && $file['data']['message'] == 'Saved') {
                                        $file_names[] = ($file['originalName'] > '') ? $file['originalName'] : $file['name'];
                                    }
                                }
                            }
                        }

                        $data[$field] = implode(',', $file_names);
                    }

                    if (($f['field_type'] == 'file' || $f['field_type'] == 'image') && ! empty($this->request->file($field))) {
                        $files = is_array($this->request->file($field)) ? $this->request->file($field) : [$this->request->file($field)];

                        $filenames = [];

                        foreach ($files as $file) {
                            if (is_array($file)) {
                                continue;
                            }
                            $file_type = $file->getMimeType();
                            $file_extension = $file->getClientOriginalExtension();

                            if ($f['field_type'] == 'image') {
                                if (! in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
                                    $validatePostError = 'Invalid image extension '.$file_extension.' use jpg,jpeg,png';
                                }
                            }

                            $destinationPath = uploads_path($this->module->id);
                            if (! is_dir($destinationPath)) {
                                mkdir($destinationPath);
                            }
                            $filename = $file->getClientOriginalName();

                            $filename = str_replace([' ', ','], '_', $filename);
                            $uploadSuccess = $file->move($destinationPath, $filename);
                            if ($uploadSuccess) {
                                $filenames[] = $filename;
                            }
                        }

                        $data[$field] = $filenames[0];
                    }

                    if (! empty($this->request->syncfusion_form)) {
                        if (($f['field_type'] == 'file' || $f['field_type'] == 'image') && empty($this->request->file($field))) {
                            unset($data[$field]);
                        }
                    }

                    if (empty($this->request->{$field}) && ($f['field_type'] == 'integer' || $f['field_type'] == 'currency')) {
                        $data[$field] = '0';
                    }

                    if ($f['field_type'] == 'boolean') {
                        if (! $this->request->{$field} || $this->request->{$field} === 'false' || $this->request->{$field} === 0) {
                            $checked = 0;
                        } elseif ($this->request->{$field} || $this->request->{$field} === 'true' || $this->request->{$field} === 1) {
                            $checked = 1;
                        }

                        if ($checked) {
                            $this->request->request->add([$field => 1]);
                        } else {
                            $this->request->request->add([$field => 0]);
                        }

                        if ($this->connection == 'pbx') {
                            $col_type = get_column_type($this->table, $field, $this->connection);

                            if ($field == 'toll_allow') {
                                if ($checked) {
                                    $data[$field] = 'internalonly';
                                }
                            } elseif ($col_type == 'text') {
                                if ($checked) {
                                    $data[$field] = 'true';
                                } else {
                                    $data[$field] = 'false';
                                }
                            } else {
                                if ($checked) {
                                    $data[$field] = '1';
                                } else {
                                    $data[$field] = '0';
                                }
                            }

                            //if($data[$field] == 'false'){
                            //    $data[$field] = 0;
                            //}
                        } else {
                            if ($checked) {
                                $data[$field] = '1';
                            } else {
                                $data[$field] = '0';
                            }
                        }
                    } elseif ($f['field_type'] == 'date') {
                        if (! empty($this->request->input($field))) {
                            $data[$field] = date('Y-m-d', strtotime($this->request->input($field)));
                        }
                    } elseif ($f['field_type'] == 'datetime') {
                        if (! empty($this->request->input($field))) {
                            $data[$field] = date('Y-m-d H:i:s', strtotime($this->request->input($field)));
                        }
                    } elseif (str_contains($f['field_type'], 'select')) {
                        if (! empty($f['opts_multiple'])) {
                            if (isset($this->request->{$field}) && is_array($this->request->{$field})) {
                                if (is_array($this->request->input($field))) {
                                    $multival = implode(',', $this->request->input($field));
                                }
                                $data[$field] = $multival;
                            } elseif (! empty($this->request->{$field})) {
                                $data[$field] = $this->request->{$field};
                            } elseif (isset($this->request->{$field}) && empty($this->request->{$field})) {
                                $data[$field] = '';
                            } elseif (! isset($this->request->{$field})) {
                                $data[$field] = '';
                            }
                        } else {
                            $data[$field] = $this->request->input($field);
                        }
                        /*
                        $opts_values = explode(',', $f['opts_values']);
                        $opts_values = collect($opts_values)->filter()->toArray();
                        if (is_array($opts_values) && count($opts_values) > 0) {
                            if (in_array('true', $opts_values) && $this->request->{$field}) {
                                $data[$field] = 'true';
                            }
                            if (in_array('false', $opts_values) && !$this->request->{$field}) {
                                $data[$field] = 'false';
                            }
                        }
                        */

                        if (empty($data[$field]) && isset($this->request->{$field})) {
                            $data[$field] = '';
                        } elseif (! empty($this->request->{$field}) && is_array($opts_values) && count($opts_values) > 0) {
                            if ($field == 'status' && $this->request->{$field} != 'Deleted') {
                                if (! is_array($this->request->{$field})) {
                                    if (! in_array($this->request->{$field}, $opts_values)) {
                                        $validatePostError = 'Invalid value for '.$f['label'].' field. Valid options: '.implode(', ', $opts_values);
                                    }
                                } else {
                                    foreach ($this->request->{$field} as $selected) {
                                        if (! in_array($selected, $opts_values)) {
                                            $validatePostError = 'Invalid value for '.$f['label'].' field. Valid options: '.implode(', ', $opts_values);
                                        }
                                    }
                                }
                            }
                        }
                    } elseif ($f['field_type'] == 'password') {
                        $password = $this->request->input($field);

                        if (isset($password) && $password == '') {
                            unset($data[$field]);
                        } elseif ($f['field'] == 'fnb_password' && ! empty($password)) {
                            $data[$field] = $password;
                        } elseif (! empty($password)) {
                            $data[$field] = \Hash::make($password);
                        }
                        // } elseif ('phone_number' == $f['field_type'] && !empty($this->request->{$field})) {
                        //     $number = $this->request->{$field};

                        //     try {
                        //         $number = phone($this->request->{$field}, ['ZA', 'US', 'Auto'])->formatForMobileDialingInCountry('ZA');

                        //         $data[$field] = $number;
                        //     } catch (\Throwable $ex) {
                        //         exception_log($ex);

                        //         return ['error' => $f['label'].': Invalid phone number format.'];
                        //     }
                    } elseif ($f['field_type'] == 'email' && ! empty($this->request->{$field})) {
                        $email = $this->request->{$field};
                        try {
                            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $validatePostError = $f['label'].': Invalid email format.';
                            }
                        } catch (\Throwable $ex) {
                            exception_log($ex);
                            $validatePostError = $f['label'].': Invalid email format.';
                        }
                    } elseif ($f['field_type'] == 'tags' && ! empty($this->request->{$field})) {
                        $data[$field] = (is_array($this->request->{$field})) ? implode(',', $this->request->{$field}) : $this->request->{$field};
                    }
                }
            }
            if (! empty($this->request->insert_at_id)) {
                if ($field == 'sort_order' && isset($this->request->{$field})) {
                    $data['sort_order'] = $this->request->{$field};
                }
                if ($field == 'parent_id' && isset($this->request->{$field})) {
                    $data['parent_id'] = $this->request->{$field};
                }
            }
        }

        if (! empty($validatePostError)) {
            return ['validatePostError' => $validatePostError];
        }

        $values = [];
        foreach ($data as $key => $val) {
            $values[$key] = $val;
        }
        foreach ($null_fields as $field) {
            $values[$field] = null;
        }
        foreach ($values as $k => $v) {
            if (is_null($v) && ! in_array($k, $null_fields)) {
                unset($values[$k]);
            }
        }
        if (isset($values['id']) && empty($values['id'])) {
            unset($values['id']);
        }

        return $values;
    }

    private function setRequestProperties($request)
    {
        if ($this->table == 'crm_documents' || $this->table == 'crm_supplier_documents') {
            if (! empty($this->request->product_id) && is_array($this->request->product_id) &&
            ! empty($this->request->qty) && is_array($this->request->qty) &&
            ! empty($this->request->price) && is_array($this->request->price)) {
                $this->setProperties(['validate_document' => true]);
            }

            if ($this->table == 'crm_documents') {
                if (! empty($this->request->doctype) && ($this->request->doctype == 'Tax Invoice' || $this->request->doctype == 'Credit Note')) {
                    $this->request->request->add(['completed' => 1]);
                }
            }
        }

        // set boolean field values

        $this->setProperties(['request' => $request]);
        if (is_array($request)) {
            $this->setProperties(['simple_request' => (object) $request]);
        } elseif ($request instanceof \Illuminate\Http\Request) {
            $this->setProperties(['simple_request' => (object) $request->all()]);
        }
    }

    private function setDBRecord()
    {
        if (! empty($this->request->id)) {
            $key = $this->module->db_key;

            $this->db_record = DB::connection($this->connection)->table($this->table)->where($key, $this->request->id)->get()->first();

            session(['event_db_record' => $this->db_record]);
            session([$this->table.'_event_db_record' => $this->db_record]);
        }
    }

    private function restoreDBRecord($id)
    {
        if (! empty($this->request->new_record)) {
            DB::connection($this->connection)->table($this->table)->where('id', $id)->delete();
        } else {
            $data = (array) $this->db_record;
            DB::connection($this->connection)->table($this->table)->where('id', $this->request->id)->update($data);
        }

        if (! empty(session('rollback_records'))) {
            foreach (session('rollback_records') as $conn => $table_data) {
                foreach ($table_data as $table => $rows) {
                    foreach ($rows as $row) {
                        DB::connection($conn)->table($table)->where('id', $row['id'])->update($row);
                    }
                }
            }
        }
    }

    private function checkPeriod()
    {
        // CHECK PERIOD
        $ledger_tables = \DB::connection('default')->table('acc_doctypes')->pluck('doctable')->unique()->toArray();
        if (in_array($this->table, $ledger_tables)) {
            $docdate = $this->request->docdate;
            if ($this->table == 'acc_general_journals') {
                $docdate = DB::table('acc_general_journal_transactions')->where('id', $this->request->transaction_id)->pluck('docdate')->first();
            }

            if (empty($docdate)) {
                return 'Document date not set.';
            }

            if (! accounting_year_active($docdate)) {
                return 'Accounting Period Closed';
            }

            if (! accounting_month_active($docdate)) {
                return 'Period Closed';
            }
        }

        return false;
    }

    private function checkProvisioningConditions()
    {
        /// VALIDATE PROVISIONING
        if ($this->validate_document && $this->table == 'crm_documents') {
            $invoice = (object) $this->request;

            if (! empty($invoice->reseller_user)) {
                $account = dbgetaccount($invoice->reseller_user);
            } else {
                $account = dbgetaccount($invoice->account_id);
            }
            //if (is_dev()) {
            //     return 'doc test.';
            //  }
            if (empty($account->company)) {
                return 'Company name needs to be set.';
            }

            if (str_contains($invoice->reference, 'Migrate')) {
                $has_migration = false;
                foreach ($invoice->lines as $invoice_line) {
                    $invoice_line = (object) $invoice_line;
                    if (! $has_migration) {
                        $has_migration = \DB::table('sub_services')->where('account_id', $account->id)->where('migrate_product_id', $invoice_line->product_id)->where('status', '!=', 'Deleted')->where('to_migrate', 1)->count();
                    }
                }
                if ($has_migration) {
                    return false;
                }
            }

            if ($account->type != 'reseller' && empty($account->pricelist_id)) {
                return 'No Pricelist Set.';
            }

            if ($account->type != 'reseller' && ! empty($account->pricelist_id)) {
                $valid_pricelist = DB::table('crm_pricelists')->where('id', $account->pricelist_id)->where('partner_id', $account->partner_id)->count();
                if (! $valid_pricelist) {
                    return 'Invalid Pricelist.';
                }
            }

            if ($account->type == 'lead' || $account->type == 'reseller') {
                return '';
            }

            if ($invoice->doctype != 'Credit Note' && $invoice->doctype != 'Credit Note Draft') {
                $voice_packages = get_activation_type_product_ids('airtime_contract');

                $package_exists = DB::table('sub_services')->where('account_id', $account->id)
                    ->where('status', '!=', 'Deleted')->where('provision_type', 'airtime_contract')->count();

                // check documents
                if (! $package_exists) {
                    if (! empty($invoice->reseller_user)) {
                        $line_package_exists = DB::table('crm_documents')
                            ->join('crm_document_lines', 'crm_document_lines.document_id', '=', 'crm_documents.id')
                            ->where('crm_documents.reseller_user', $invoice->reseller_user)
                            ->where('crm_documents.subscription_created', 0)
                            ->where('crm_documents.id', '!=', $invoice->id)
                            ->whereIn('crm_document_lines.product_id', $voice_packages)
                            ->count();
                    } else {
                        $line_package_exists = DB::table('crm_documents')
                            ->join('crm_document_lines', 'crm_document_lines.document_id', '=', 'crm_documents.id')
                            ->where('crm_documents.account_id', $invoice->account_id)
                            ->where('crm_documents.subscription_created', 0)
                            ->where('crm_documents.id', '!=', $invoice->id)
                            ->whereIn('crm_document_lines.product_id', $voice_packages)
                            ->count();
                    }
                }

                $extension_products = get_activation_type_product_ids('pbx_extension');

                $extension_product_id = '';
                $voice_packages_to_provision = [];

                foreach ($invoice->lines as $invoice_line) {
                    $invoice_line = (object) $invoice_line;
                    $product = dbgetrow('crm_products', 'id', $invoice_line->product_id);

                    if (in_array($invoice_line->product_id, $extension_products)) {
                        if (empty($extension_product_id) || $extension_product_id == $invoice_line->product_id) {
                            $extension_product_id = $invoice_line->product_id;
                        } else {
                            //    return 'Cannot place an order for different extension types.';
                        }
                    }

                    if ($product->code != 'vehicledbcredits') {
                        if (($invoice->doctype == 'Order' || $invoice->doctype == 'Tax Invoice') && $product->type == 'Stock' && ! $product->is_subscription) {
                            $qty_on_hand = dbgetcell('crm_products', 'id', $invoice_line->product_id, 'qty_on_hand');
                            if ($qty_on_hand < $invoice_line->qty) {
                                return 'Not enough stock on hand - '.$product->code;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    public function setCreditorBalance($supplier_ids = false)
    {
        if (! empty($this->auto_reconciled)) {
            return $this;
        }

        if (! $supplier_ids) {
            $supplier_ids = [];
            if ($this->request->supplier_id > 0 && ($this->table == 'acc_cashbook_transactions' || $this->table == 'crm_supplier_documents')) {
                $supplier_ids[] = $this->request->supplier_id;

                // if request does not match previous db value update aging for previous account
                if (! empty($this->db_record) && $this->db_record->supplier_id != $this->request->supplier_id) {
                    $supplier_ids[] = $this->db_record->supplier_id;
                }
            }
        }

        if ($supplier_ids) {
            if (! is_array($supplier_ids)) {
                $val = $supplier_ids;
                $supplier_ids = [];
                $supplier_ids[] = $val;
            }

            foreach ($supplier_ids as $supplier_id) {
                $data = [];
                $data['balance'] = get_creditor_balance($supplier_id);
                $data['aging'] = get_creditor_aging($supplier_id);

                $last_transaction_date = '0000-00-00';
                $doc_docdate = \DB::connection('default')->table('crm_supplier_documents')->select('docdate')->where('supplier_id', $supplier_id)->orderBy('docdate', 'desc')->pluck('docdate')->first();
                if ($doc_docdate) {
                    $last_transaction_date = $doc_docdate;
                }
                $data['last_transaction_date'] = $last_transaction_date;
                if ($last_transaction_date == '0000-00-00') {
                    $data['invoice_days'] = 0;
                } else {
                    $date = Carbon\Carbon::parse($last_transaction_date);
                    $now = Carbon\Carbon::today();
                    $data['invoice_days'] = $date->diffInDays($now);
                }

                $cash_reconciled = \DB::connection('default')->table('crm_suppliers')->where('id', $supplier_id)->where('terms', 'Cash')->where('balance', 0)->count();
                if ($cash_reconciled) {
                    $data['reconcile_date'] = date('Y-m-d');
                }

                dbset('crm_suppliers', 'id', $supplier_id, $data);
            }
        }

        return $this;
    }

    public function updateDocumentPaymentStatus($account_id = null)
    {
        $converted_doc_ids = [];
        if ($account_id) {
            $account = \DB::connection('default')->table('crm_accounts')->select('id', 'partner_id')->where('id', $account_id)->get()->first();
            if ($account->partner_id == 1) {
                $accounts = \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->get();
            } else {
                $accounts = \DB::connection('default')->table('crm_accounts')->where('id', $account->partner_id)->get();
            }
        } else {
            $accounts = \DB::connection('default')->table('crm_accounts')->where('partner_id', 1)->get();
        }
        $doctypes = ['Order', 'Tax Invoice'];
        $ledger_doc_ids = [];

        foreach ($accounts as $account) {
            if ($account->payment_type != 'Prepaid') {
                $credit_limit = $account->credit_limit;
            } else {
                $credit_limit = 0;
            }
            $order_ids = \DB::connection('default')->table('crm_documents')
                ->where('doctype', 'Order')
                ->where('subscription_created', 0)
                ->where('account_id', $account->id)
                ->pluck('id')->toArray();

            \DB::connection('default')->table('crm_documents')->where('account_id', $account->id)->update(['payment_status' => 'Awaiting Payment']);

            $payments_total = \DB::connection('default')->table('acc_cashbook_transactions')
                ->where('account_id', $account->id)
                ->where('approved', 1)
                ->where('api_status', '!=', 'Invalid')
                ->sum('total');

            $credit_total = \DB::connection('default')->table('crm_documents')
                ->where('account_id', $account->id)
                ->where('doctype', 'Credit Note')
                ->sum('total');

            $tax_invoices = \DB::connection('default')->table('crm_documents')
                ->select('id', 'total')
                ->where('account_id', $account->id)
                ->where('doctype', 'Tax Invoice')
                ->orderby('docdate')->orderby('total')->get();

            $proforma_invoices = \DB::connection('default')->table('crm_documents')
                ->select('id', 'total')
                ->where('account_id', $account->id)
                ->where('doctype', 'Order')
                ->orderby('docdate')->orderby('total')->get();

            $journals_debit_total = \DB::connection('default')->table('acc_general_journals')
                ->join('acc_general_journal_transactions', 'acc_general_journal_transactions.id', '=', 'acc_general_journals.transaction_id')
                ->where('acc_general_journals.account_id', $account->id)
                ->where('acc_general_journal_transactions.approved', 1)
                ->where('acc_general_journals.ledger_account_id', 5)
                ->sum('acc_general_journals.debit_amount');

            $journals_credit_total = \DB::connection('default')->table('acc_general_journals')
                ->join('acc_general_journal_transactions', 'acc_general_journal_transactions.id', '=', 'acc_general_journals.transaction_id')
                ->where('acc_general_journals.account_id', $account->id)
                ->where('acc_general_journal_transactions.approved', 1)
                ->where('acc_general_journals.ledger_account_id', 5)
                ->sum('acc_general_journals.credit_amount');

            $journals_total = $journals_credit_total - $journals_debit_total;

            $balance = $payments_total + $credit_total + $journals_total;

            if (! empty($tax_invoices)) {
                foreach ($tax_invoices as $doc) {
                    $balance -= $doc->total;
                    if ($balance >= -5 || $doc->total == 0) {
                        \DB::connection('default')->table('crm_documents')->where('id', $doc->id)->update(['payment_status' => 'Complete', 'completed' => 1]);
                    }
                }
            }

            $admin_user_ids = \DB::connection('default')->table('erp_users')->where('account_id', 1)->pluck('id')->toArray();
            if (! empty($proforma_invoices)) {
                foreach ($proforma_invoices as $doc) {
                    $balance -= $doc->total;
                    if ($balance >= -5 || $doc->total == 0 || ($balance < 0 && abs($balance) < $credit_limit)) {
                        \DB::connection('default')->table('crm_documents')->where('id', $doc->id)->update(['doctype' => 'Tax Invoice', 'payment_status' => 'Complete']);
                        \DB::connection('default')->table('crm_documents')->where('id', $doc->id)->where('completed', 0)->update(['completed' => 1]);
                        if (in_array($doc->id, $order_ids)) {
                            module_log(353, $doc->id, 'updated', 'Order converted to Tax Invoice');
                            if (in_array($doc->salesman_id, $admin_user_ids)) {
                                staff_email($doc->salesman_id, 'Order converted to Tax Invoice', 'Order converted to Tax Invoice <br>#'.$doc->id.' <br>Company'.$account->company.' <br>Reference:'.$doc->reference);
                            }
                            $ledger_doc_ids[] = $doc->id;
                            $converted_doc_ids[] = $doc->id;
                        }
                    }
                }
            }

            \DB::connection('default')->table('crm_documents')->where('account_id', $account->id)
                ->where('doctype', 'Credit Note')->update(['payment_status' => 'Complete']);
            \DB::connection('default')->table('crm_documents')->where('account_id', $account->id)
                ->where('doctype', 'Credit Note Draft')->update(['payment_status' => '']);
            \DB::connection('default')->table('crm_documents')->where('account_id', $account->id)
                ->where('doctype', 'Quotation')->update(['payment_status' => '']);
        }

        if (count($ledger_doc_ids) > 0) {
            $db = new DBEvent;
            $db->setTable('crm_documents');
            foreach ($ledger_doc_ids as $ledger_doc_id) {
                $db->postDocument($ledger_doc_id);
            }
            $db->postDocumentCommit();
        }

        return $converted_doc_ids;
    }

    public function setDebtorBalance($account_ids = false, $process_deleted = false)
    {
        if (! $account_ids) {
            $account_ids = [];
            $debtor_tables = ['acc_cashbook_transactions', 'crm_documents'];
            if (in_array($this->table, $debtor_tables)) {
                if (! empty($this->db_record) && ! empty($this->db_record->account_id)) {
                    $account_ids[] = $this->db_record->account_id;
                } elseif (! empty($this->request->account_id)) {
                    $account_ids[] = $this->request->account_id;
                }
            }
        }

        if (! $account_ids) {
            if ($this->table == 'crm_accounts') {
                $account_ids = $this->request->id;
            }
        }

        if ($account_ids) {
            if (! is_array($account_ids)) {
                $val = $account_ids;
                $account_ids = [];
                $account_ids[] = $val;
            }

            foreach ($account_ids as $account_id) {
                if ($account_id === 1) {
                    continue;
                }
                $type = dbgetaccountcell($account_id, 'type');
                if ($type == 'reseller_user') {
                    $partner_id = dbgetaccountcell($account_id, 'partner_id');
                    if ($partner_id != 1) {
                        $this->setAccountAging($partner_id, $process_deleted);
                    }
                } else {
                    check_commitments_paid($account_id);
                    $this->setAccountAging($account_id, $process_deleted);
                }
                provision_invoices($account_id);
            }
        }

        return $this;
    }

    public function updateAccountType()
    {
        if ($this->table == 'crm_documents' || ($this->table == 'acc_cashbook_transactions' && $this->request->account_id > 0)) {
            $row = DB::connection($this->connection)->table($this->table)->where('id', $this->request->id)->get()->first();
            $account = dbgetaccount($row->account_id);
        }

        if ($this->table == 'crm_accounts') {
            $account = dbgetaccount($this->request->id);
        }

        if ($account->type == 'lead') {
            $has_order = DB::table('crm_documents')->where('account_id', $account->id)->where('doctype', 'Order')->where('reversal_id', 0)->count();
            $has_invoice = DB::table('crm_documents')->where('account_id', $account->id)->where('doctype', 'Tax Invoice')->where('reversal_id', 0)->count();

            if ($has_invoice || $has_order) {
                if ($account->partner_id == 1) {
                    DB::table('crm_accounts')->where('id', $account->id)->update(['type' => 'customer', 'status' => 'Enabled']);
                } else {
                    DB::table('crm_accounts')->where('id', $account->id)->update(['type' => 'reseller_user', 'status' => 'Enabled']);
                }
                create_account_settings($account->id);
            }
        }

        return $this;
    }

    public function setStockBalance($product_ids = false)
    {
        if (! $product_ids) {
            $stock_tables = ['crm_products', 'crm_documents', 'acc_inventory', 'crm_supplier_documents'];
            if (! in_array($this->table, $stock_tables)) {
                return false;
            }

            $product_ids = [];
            if ($this->table == 'crm_products') {
                $product_ids[] = $this->request->id;
            } elseif ($this->table == 'crm_documents' || $this->table == 'crm_supplier_documents') {
                $lines_table = ($this->table == 'crm_documents') ? 'crm_document_lines' : 'crm_supplier_document_lines';
                $product_ids = \DB::table($lines_table)->where('document_id', $this->request->id)->pluck('product_id')->toArray();
            } else {
                $product_ids[] = $this->request->product_id;
            }
        }
        if ($product_ids && ! is_array($product_ids)) {
            $product_ids = [$product_ids];
        }

        if (count($product_ids) > 0) {
            if ($this->table == 'acc_inventory' || $this->table == 'crm_documents' || $this->table == 'crm_supplier_documents') {
                foreach ($product_ids as $product_id) {
                    if (empty($product_id)) {
                        continue;
                    }
                    generate_stock_history($product_id);
                }
            }
        }

        if (count($product_ids) > 0) {
            $exchange_rate = get_exchange_rate();
            foreach ($product_ids as $product_id) {
                if (empty($product_id)) {
                    continue;
                }

                if ($this->table == 'acc_inventory') {
                    rebuild_inventory_totals($product_id);
                }

                $product = \DB::table('crm_products')->where('id', $product_id)->get()->first();

                $product_type = $product->type;
                $data = (array) $product;
                $stock_data = get_stock_balance_approved($product_id);

                if (! $stock_data['qty_on_hand']) {
                    $stock_data['qty_on_hand'] = 0;
                }

                $stock_data['stock_value'] = $stock_data['qty_on_hand'] * $stock_data['cost_price'];
                if ($product_type != 'Stock') {
                    $stock_data['qty_on_hand'] = 0;
                    $stock_data['stock_value'] = 0;
                }

                foreach ($stock_data as $key => $value) {
                    $data[$key] = $value;
                }

                if ($stock_data['cost_price'] == 0) {
                    $data['cost_price_usd'] = 0;
                } else {
                    $data['cost_price_usd'] = $stock_data['cost_price'] * $exchange_rate;
                }

                \DB::table('crm_products')->where('id', $product_id)->update($data);

                if ($this->table == 'acc_inventory') {
                    validate_pricelists_cost_price($product_id);
                }
            }
        }
    }

    public function saveProductsPricingChanges()
    {
        if ($this->table == 'crm_products') {
            $beforesave_row = session($this->table.'_event_db_record');

            if (! empty($beforesave_row) && ! empty($beforesave_row->selling_price_incl)) {
                if ($beforesave_row->selling_price_incl != $this->request->selling_price_incl) {
                    $data = [
                        'product_id' => $this->request->id,
                        'selling_price_incl_old' => $beforesave_row->selling_price_incl,
                        'selling_price_incl_new' => $this->request->selling_price_incl,
                    ];

                    dbinsert('crm_products_price_history', $data);
                }
            }
        }
    }

    private function cacheClear()
    {
        if ($this->table == 'erp_instance_migrations' || $this->table == 'erp_menu_role_access' || $this->table == 'erp_forms' || $this->table == 'erp_menu' || $this->table == 'erp_cruds' || $this->table == 'erp_grid_views' || $this->table == 'erp_grid_styles' || $this->table == 'erp_module_fields') {
            cache_clear();
        }
        /*
        $user_ids = \DB::connection('default')->table('erp_users')->pluck('id')->toArray();
        foreach($user_ids as $user_id){
        Cache::forget('row_data'.$this->table.'_'.$user_id);
        }
        */
    }

    private function setLogData($id, $action, $note = '')
    {
        $this->log_data = (object) [
            'id' => $id,
            'action' => $action,
            'note' => $note,
        ];
    }

    private function commitLogData()
    {
        if ($this->log_data->action == 'updated' && $this->log_data->note > '') {
            module_log($this->module_id, $this->log_data->id, $this->log_data->action, $this->log_data->note);
        } elseif ($this->log_data->action != 'updated') {
            module_log($this->module_id, $this->log_data->id, $this->log_data->action, $this->log_data->note);
        }
    }

    /// DOCTYPE FUNCTIONS

    private function doctypeAfterSave()
    {
        if ($this->event_type == 'afterdelete') {
            $this->setRequestProperties($this->db_record);
        }
        if ($this->table == 'crm_documents') {
            \DB::connection('default')->table('crm_documents')->update(['period' => \DB::raw("DATE_FORMAT(docdate, '%Y-%m')")]);
            \DB::connection('default')->table('crm_documents')->update(['docdate_month' => \DB::raw("DATE_FORMAT(docdate, '%Y-%m-01')")]);
        }
        if ($this->table == 'crm_supplier_documents') {
            \DB::connection('default')->table('crm_supplier_documents')->update(['docdate_month' => \DB::raw("DATE_FORMAT(docdate, '%Y-%m-01')")]);
        }
        if ($this->table == 'crm_documents' || $this->table == 'crm_supplier_documents') {
            if (! empty($this->request->doctype)) {
                set_doctype_doc_no($this->request->doctype);
            }
        }

        if ($this->table == 'acc_inventory') {
            update_inventory_totals();
        }

        clear_select_options_cache($this->table);

        $this->setReportingTotals();
    }

    private function setDocumentRequest()
    {
        // build lines

        if (empty($this->request->product_id) || count($this->request->product_id) == 0) {
            return response()->json(['status' => 'error', 'message' => 'Document Lines Required']);
        }

        $lines = [];

        $has_contract_product = false;
        foreach ($this->request->product_id as $index => $value) {
            $line = [
                'qty' => $this->request->qty[$index],
                'price' => $this->request->price[$index],
                'full_price' => $this->request->price[$index],
                'product_id' => $this->request->product_id[$index],
            ];
            if (! empty($this->request->description) && ! empty($this->request->description[$index])) {
                $line['description'] = $this->request->description[$index];
            }
            if (! empty($this->request->ledger_account_id) && ! empty($this->request->ledger_account_id[$index])) {
                $line['ledger_account_id'] = $this->request->ledger_account_id[$index];
            }
            if (! empty($this->request->shipment_share) && ! empty($this->request->shipment_share[$index])) {
                $line['shipment_share'] = $this->request->shipment_share[$index];
            }
            if (! empty($this->request->cdr_destination) && ! empty($this->request->cdr_destination[$index])) {
                $line['cdr_destination'] = $this->request->cdr_destination[$index];
            }

            if (! empty($this->request->shipping_price) && ! empty($this->request->shipping_price[$index])) {
                $line['shipping_price'] = $this->request->shipping_price[$index];
            }
            if (! empty($this->request->domain_tld) && ! empty($this->request->domain_tld[$index])) {
                $line['domain_tld'] = $this->request->domain_tld[$index];
            }
            if (! empty($this->request->contract_period) && ! empty($this->request->contract_period[$index])) {
                $line['contract_period'] = $this->request->contract_period[$index];
                $has_contract_product = true;
            }
            $lines[] = $line;
        }

        $this->request->request->add(['lines' => $lines]);

        if ($this->table == 'crm_supplier_documents' || $this->table == 'crm_supplier_import_documents') {
            if (! empty($this->request->account_id) && empty($this->request->supplier_id)) {
                $this->request->request->add(['supplier_id' => $this->request->account_id]);
            }
        }

        if ($has_contract_product && empty($this->request->contract_period)) {
            $this->request->request->add(['contract_period' => 12]);
        }

        $this->request->request->add(['duedate' => date('Y-m-d', strtotime($data['docdate'].' +1 week'))]);
    }

    private function setDocumentLines()
    {
        $id = $this->request->id;
        $lines_table = ($this->table == 'crm_documents') ? 'crm_document_lines' : 'crm_supplier_document_lines';

        $lines_table = ($this->table == 'crm_supplier_import_documents') ? 'crm_supplier_import_document_lines' : $lines_table;

        $document = DB::connection($this->connection)->table($this->table)->where('id', $id)->get()->first();
        DB::table($lines_table)->where('document_id', $id)->delete();

        $document_lines = sort_product_rows($this->request->lines);

        foreach ($document_lines as $docline) {
            $line = (array) $docline;
            if ($this->table == 'crm_documents') {
                $line['cost_price'] = get_document_cost_price($document, $docline);
            }

            $line['document_id'] = $id;

            if (! empty($line['description']) && $line['product_id'] != 147) {
                // unset($line['description']);
            }

            DB::table($lines_table)->insert($line);
        }

        if ($this->table == 'crm_documents') {
            $this->setServiceInvoice();
        }
        $doc = $document;
    }

    public function setServiceInvoice($id = false)
    {
        if (! $id) {
            $id = $this->request->id;
        }

        if ($this->table == 'crm_documents') {
            $document = \DB::table('crm_documents')->where('id', $id)->get()->first();
            $account = dbgetaccount($document->account_id);

            if (($account->type == 'customer' || $account->type == 'reseller_user') && $account->partner_id != 1) {
                $admin = dbgetaccount(1);
                $reseller = dbgetaccount($account->partner_id);
                // submitted by service account
                // move all account fields to service account fields
                $document_lines = \DB::table('crm_document_lines')->where('document_id', $document->id)->get();
                $subtotal = 0;
                $tax = 0;
                $service_subtotal = 0;
                $service_tax = 0;

                foreach ($document_lines as $line) {
                    $line_data = [];

                    $line_data['service_price'] = $line->price;
                    $line_data['service_full_price'] = $line->full_price;

                    if ($document->billing_type != '') {
                        $line_data['full_price'] = $line_data['price'] = pricelist_get_price($account->partner_id, $line->product_id)->full_price;
                    } else {
                        $line_data['price'] = pricelist_get_price($account->partner_id, $line->product_id, $line->qty)->price;
                        $line_data['full_price'] = pricelist_get_price($account->partner_id, $line->product_id, $line->qty)->full_price;
                    }

                    if (empty($document->billing_type)) {
                        $product = \DB::table('crm_products')->where('id', $line->product_id)->get()->first();
                        if (! empty($product->activation_fee)) {
                            $line_data['price'] = currency($product->activation_fee);
                            $line_data['full_price'] = pricelist_get_price($account->partner_id, $line->product_id, $line->qty)->full_price;
                            $line_data['description'] = 'Activation fee.'.PHP_EOL.'The service will be invoiced fully upon activation.';
                        }
                    }

                    $line_total = currency($line_data['price'] * $line->qty);
                    $subtotal += $line_total;
                    $service_line_total = currency($line_data['service_price'] * $line->qty);
                    $service_subtotal += $service_line_total;
                    \DB::table('crm_document_lines')->where('id', $line->id)->update($line_data);
                }

                if ($admin->vat_enabled == 1) {
                    $tax = $subtotal * 0.15;
                }
                if ($reseller->vat_enabled == 1) {
                    $service_tax = $service_subtotal * 0.15;
                }

                $total = $subtotal + $tax;
                $service_total = $service_subtotal + $service_tax;
                $document_data = [
                    'reseller_user' => $document->account_id,
                    'account_id' => $account->partner_id,
                    'service_tax' => $service_tax,
                    'service_total' => $service_total,
                    'tax' => $tax,
                    'total' => $total,
                ];

                \DB::table('crm_documents')->where('id', $document->id)->update($document_data);
            }

            if ($account->type == 'reseller' && ! empty($document->reseller_user)) {
                // submitted by reseller
                $document_lines = \DB::table('crm_document_lines')->where('document_id', $document->id)->get();

                $sub_total = 0;
                $tax = 0;

                foreach ($document_lines as $line) {
                    $line_data = [];

                    $line_data['service_price'] = pricelist_get_price($document->reseller_user, $line->product_id, $line->qty)->price;
                    $line_data['service_full_price'] = pricelist_get_price($document->reseller_user, $line->product_id, $line->qty)->full_price;

                    if (empty($document->billing_type)) {
                        $product = \DB::table('crm_products')->where('id', $line->product_id)->get()->first();
                        if (! empty($product->activation_fee)) {
                            $line_data['service_price'] = currency($product->activation_fee);
                            $line_data['service_full_price'] = pricelist_get_price($document->reseller_user, $line->product_id, $line->qty)->full_price;
                            $line_data['description'] = 'Activation fee.'.PHP_EOL.'The service will be invoiced fully upon activation.';
                        }
                    }

                    $line_total = currency($line_data['service_price'] * $line->qty);
                    $subtotal += $line_total;
                    \DB::table('crm_document_lines')->where('id', $line->id)->update($line_data);
                }
                if ($account->vat_enabled == 1) {
                    $tax = $subtotal * 0.15;
                }
                $total = $subtotal + $tax;
                $document_data = [
                    'service_tax' => $tax,
                    'service_total' => $total,
                ];

                \DB::table('crm_documents')->where('id', $document->id)->update($document_data);
            }
        }
    }

    public function getDocumentResponse($id)
    {
        if ($this->table == 'crm_documents') {
            $doc = DB::table('crm_documents')->where('id', $id)->get()->first();
            $documents_url = get_menu_url_from_table('crm_documents');
            $activations_url = get_menu_url_from_table('sub_activations');
            $airtime_applied = DB::table('sub_service_topups')->where('invoice_id', $id)->count();
            $needs_activation = DB::table('sub_activations')->where('invoice_id', $id)->where('provision_type', '!=', 'product')->where('status', 'Pending')->count();
            $products_ordered = DB::table('sub_activations')->where('invoice_id', $id)->where('provision_type', 'product')->where('status', 'Pending')->count();

            $beforesave_row = session('event_db_record');
            if (! empty($beforesave_row) && ! empty($beforesave_row->doctype)) {
                if ($beforesave_row->doctype != $doc->doctype) {
                    if ($beforesave_row->doctype == 'Credit Note Draft') {
                        return ['id' => $id];
                    } else {
                        // redirect to document module
                        $doc_module_id = DB::table('acc_doctypes')->where('doctype', $doc->doctype)->pluck('module_id')->first();
                        if ($doc_module_id) {
                            $url = get_menu_url_from_module_id($doc_module_id);

                            return response()->json(['status' => 'success', 'message' => $doc->doctype.' saved.', 'id' => $id, 'new_tab' => $url.'?id='.$id]);
                        }
                    }
                }
            }
        }

        return ['id' => $id];
    }

    private function voidRecord()
    {
        if (empty($this->request->id)) {
            return json_alert('This document cannot be credited.', 'error');
        }
        $id = $this->request->id;
        $invoice = DB::connection($this->connection)->table($this->table)->where('id', $id)->get()->first();

        $type = $invoice->doctype;

        if (empty($invoice)) {
            return json_alert('Document not found', 'error');
        }

        if ($type != 'Credit Note Draft' && ! empty($invoice->reversal_id)) {
            return json_alert('Document already credited.', 'error');
        }

        if (session('role_level') != 'Admin') {
            if ($invoice->subscription_created) {
                return json_alert('No access.', 'error');
            }

            if ($invoice->doctype == 'Quotation' || $invoice->doctype == 'Order') {
            } else {
                return json_alert('No access.', 'error');
            }
        }

        $type = $invoice->doctype;

        if ($type == 'Order') {
            $updated = DB::connection($this->connection)->table($this->table)->where('id', $this->request->id)->update(['payment_status' => '', 'doctype' => 'Quotation', 'subscription_created' => 0]);

            $this->setDebtorBalance($invoice->account_id);
            if ($updated) {
                $this->setLogData($id, 'updated', 'Order to quotation');
                $this->commitLogData();

                return json_alert('Converted to a quotation.');
            } else {
                return json_alert('Update failed', 'error');
            }
        } elseif ($type == 'Credit Note') {
            DB::connection($this->connection)->table($this->table)->where('id', $invoice->reversal_id)->update(['reversal_id' => 0]);
            DB::table('crm_document_lines')->where('document_id', $id)->delete();
            DB::table('crm_documents')->where('id', $id)->delete();
            DB::table('acc_ledgers')->where('doctype', 'Credit Note')->where('docid', $id)->delete();
            $this->setDebtorBalance($invoice->account_id);
            $this->setLogData($id, 'deleted', 'credit note deleted');
            $this->commitLogData();

            return json_alert('Credit Note Deleted');
        } elseif ($type == 'Tax Invoice') {
            $invoice_lines = DB::table('crm_document_lines')->where('document_id', $id)->get();

            $void_result = void_transaction($this->table, $id, 'Credit Note');

            // aa($void_result);
            $this->setDebtorBalance($invoice->account_id);
            if ($void_result === 'draft') {
                $this->setLogData($id, 'deleted', 'draft invoice deleted');
                $this->commitLogData();

                return json_alert('Draft Transaction deleted.');
            } elseif ($void_result === 'nonrefund') {
                return json_alert('Cannot reverse airtime invoices.', 'error');
            } elseif ($void_result) {
                $this->setLogData($id, 'updated', 'credit note '.$void_result.' created');
                $this->commitLogData();
                $documents_url = get_menu_url_from_table('crm_documents');

                //aa('/'.$documents_url.'/edit/'.$void_result);
                return json_alert('Document credited', 'success');
                //return json_alert('/'.$documents_url.'/edit/'.$void_result, 'transactionDialog');
            } else {
                return json_alert('Document cannot be credited', 'error');
            }
        } elseif ($type == 'Supplier Debit Note' || $type == 'Supplier Order') {
            DB::table('crm_supplier_document_lines')->where('document_id', $id)->delete();
            DB::table('crm_supplier_documents')->where('id', $id)->delete();
            DB::table('crm_supplier_documents')->where('reversal_id', $id)->update(['reversal_id' => 0]);
            DB::table('acc_ledgers')->where('doctype', 'Supplier Debit Note')->where('docid', $id)->delete();

            $this->setLogData($id, 'deleted', 'supplier document deleted');
            $this->commitLogData();
            $this->setCreditorBalance($invoice->supplier_id);

            return json_alert($type.' deleted.');
        } elseif (! empty($this->table)) {
            if ($type == 'Supplier Invoice') {
                $type = 'Supplier Debit Note';
            }

            if (! empty($this->request->revert)) {
                $void_result = void_transaction($this->table, $id, $type, false, true);
            } else {
                $void_result = void_transaction($this->table, $id, $type);
            }
            if ($this->table == 'crm_supplier_documents') {
                $this->setCreditorBalance($invoice->supplier_id);
            } else {
                $this->setDebtorBalance($invoice->account_id);
            }
            if (! empty($this->request->revert)) {
                $this->setLogData($id, 'updated', 'Document Reverted');
                $this->commitLogData();

                return json_alert('Document reverted.');
            } elseif ($void_result === 'draft') {
                $this->setLogData($id, 'deleted', 'Draft Deleted');
                $this->commitLogData();

                return json_alert('Draft Transaction deleted.');
            } elseif ($void_result && $type == 'Supplier Debit Note') {
                $this->setLogData($id, 'updated', 'Supplier Debit Note created');
                $this->commitLogData();

                return json_alert('Document Created', 'viewDialog', ['url' => '/'.session('menu_route').'/view/'.$void_result, 'title' => $type]);
            } elseif ($void_result) {
                $this->setLogData($id, 'updated', 'Credit Note Created');
                $this->commitLogData();

                return json_alert('/'.session('menu_route').'/edit/'.$void_result, 'transactionDialog');
            } else {
                return json_alert('Document cannot be credited', 'error');
            }
        } else {
            return json_alert('Document cannot be credited', 'error');
        }
    }

    private function setReportingTotals()
    {
        if ($this->table == 'crm_documents') {
            set_document_lines_gp($this->request->id);
        }
    }

    public function processServiceInvoice()
    {
        $this->setProperties(['process_service_invoice' => 1]);

        return $this;
    }

    public function postDocument($id, $docdate = false)
    {
        //$stock_product_ids = \DB::table('crm_products')->where('type', 'Stock')->pluck('id')->toArray();

        $stock_product_ids = \DB::table('crm_products')->select('id')->where('is_supplier_product', 1)->orWhere('type', 'Stock')->pluck('id')->toArray();
        $airtime_product_ids = \DB::table('crm_products')->where('product_category_id', 961)->pluck('id')->toArray();
        $document = DB::table($this->table)->where('id', $id)->get()->first();

        if ($this->table == 'acc_general_journals') {
            $document->doctype = \DB::table('acc_general_journal_transactions')->where('id', $document->transaction_id)->pluck('doctype')->first();
        }
        $doctype = DB::table('acc_doctypes')->where('doctype', $document->doctype)->where('status', 'Enabled')->get()->first();
        $doctype_details = DB::table('acc_doctype_details')->where('doctype_id', $doctype->id)->get();

        if (empty($doctype) || empty($doctype->doctable)) {
            return $this;
        }

        if ($document->doctype == 'Payroll' && $document->status != 'Complete') {
            return $this;
        }

        if ($document->doctype == 'Inventory' && ($document->document_id > 0 || $document->supplier_document_id > 0)) {
            return $this;
        }
        if ($document->doctype == 'Inventory' && $document->approved == 0) {
            return $this;
        }

        $payment_gateway_statuses = ['Complete', 'Declined', 'Debit Order Declined', 'Debit Order Declined Fee'];
        if ($document->doctype == 'Cashbook Customer Receipt' && $document->api_status == 'Invalid') {
            return $this;
        }
        if ($document->doctype == 'Cashbook Customer Receipt' && $document->approved == 0) {
            return $this;
        }

        $this->ledger_ids[$doctype->doctype][] = $document->id;
        if (empty($doctype_details) || (is_array($doctype_details) && count($doctype_details) == 0)) {
            return $this;
        }

        $ledger['docid'] = $document->id;
        $ledger['name'] = get_ledger_name($doctype->doctype, $document->id, $doctype->doctable);
        $ledger['reference'] = get_ledger_reference($doctype->doctype, $document->id, $doctype->doctable);
        if (empty($ledger['reference'])) {
            $ledger['reference'] = '';
        }
        $ledger['docdate'] = $docdate;
        if (! empty($document->docdate)) {
            $ledger['docdate'] = $document->docdate;
        }
        $ledger['account_id'] = 0;
        if (! empty($document->account_id)) {
            $ledger['account_id'] = $document->account_id;
        }
        $ledger['supplier_id'] = 0;
        if (! empty($document->supplier_id)) {
            $ledger['supplier_id'] = $document->supplier_id;
        }
        $ledger['product_id'] = 0;
        if (! empty($document->product_id)) {
            $ledger['product_id'] = $document->product_id;
        }
        $ledger['doctype'] = $doctype->doctype;

        $ledger['retained_earnings'] = '';

        if (str_contains($ledger['reference'], 'Annual Retained Earnings')) {
            $ledger['retained_earnings'] = date('Y', strtotime($document->docdate));
        }

        if ($doctype->doctype == 'General Journal' && ! empty($document->transaction_id)) {
            $journal_header = DB::table('acc_general_journal_transactions')->where('id', $document->transaction_id)->get()->first();

            $ledger['docdate'] = $journal_header->docdate;
            if (! $journal_header->posted) {
                return $this;
            }
        }

        foreach ($doctype_details as $doctype_detail) {
            if (($doctype->doctable == 'crm_supplier_documents' || $doctype->doctable == 'crm_documents') && $doctype_detail->use_document_lines) {
                $lines_table = ($doctype->doctable == 'crm_documents') ? 'crm_document_lines' : 'crm_supplier_document_lines';

                $document_lines = DB::select('select '.$lines_table.'.id as line_id, '.$lines_table.'.* from '.$lines_table.' where document_id = '.$document->id);

                // get product bundle activations
                /*
                $bundle_lines = [];
                foreach($document_lines as $i => $line){
                    $product = dbgetrow('crm_products', 'id', $line->product_id);
                    if($product->is_bundle){
                        $template = $line;
                        $activation_products = \DB::table('crm_product_bundle_activations')->where('bundle_product_id',$line->product_id)->get();
                        foreach($activation_products as $activation_product){
                            $data = $line;
                            $data->product_id = $activation_product->product_id;
                            $data->qty = $activation_product->qty;
                            $bundle_lines[] = $data;
                        }
                        unset($document_lines[$i]);
                    }
                }

                foreach($bundle_lines as $bundle_line){
                    $invoice_lines[] = $bundle_line;
                }
                */

                foreach ($document_lines as $document_line) {
                    if (! empty($document_line->product_id)) {
                        $ledger['product_id'] = $document_line->product_id;
                    }
                    $ledger['amount'] = ledger_post_amount($doctype, $doctype_detail, $document_line, $document);
                    $ledger['ledger_account_id'] = ledger_post_ledger_account_id($doctype, $doctype_detail, $document_line, $stock_product_ids, $airtime_product_ids);

                    if ($doctype->doctable == 'crm_documents' || $doctype->doctable == 'acc_inventory') {
                        if (($ledger['ledger_account_id'] == 32 || $ledger['ledger_account_id'] == 34) && ! in_array($document_line->product_id, $stock_product_ids)) {
                            continue;
                        }
                        if ($doctype->doctable == 'acc_inventory' && $ledger['ledger_account_id'] == 25 && ! in_array($document_line->product_id, $stock_product_ids)) {
                            continue;
                        }
                    }

                    if ($ledger['amount'] != 0) {
                        $ledger['original_amount'] = $ledger['amount'];
                        $ledger['document_currency'] = 'ZAR';
                        if (! empty($document->document_currency) && $document->document_currency != 'ZAR') {
                            $ledger['document_currency'] = $document->document_currency;
                            $ledger['amount'] = currency_to_zar($document->document_currency, $ledger['amount'], $document->docdate);
                        }
                        $this->ledgers[] = $ledger;
                    }
                }
            } else {
                $ledger['amount'] = ledger_post_amount($doctype, $doctype_detail, $document);
                $ledger['ledger_account_id'] = ledger_post_ledger_account_id($doctype, $doctype_detail, $document, $stock_product_ids, $airtime_product_ids);

                if ($doctype->doctable == 'crm_documents' || $doctype->doctable == 'acc_inventory') {
                    if (($ledger['ledger_account_id'] == 32 || $ledger['ledger_account_id'] == 34) && ! in_array($document->product_id, $stock_product_ids)) {
                        continue;
                    }
                    if ($doctype->doctable == 'acc_inventory' && $ledger['ledger_account_id'] == 25 && ! in_array($document->product_id, $stock_product_ids)) {
                        continue;
                    }
                }

                if ($ledger['amount'] != 0) {
                    $ledger['original_amount'] = $ledger['amount'];
                    $ledger['document_currency'] = 'ZAR';
                    if (! empty($document->document_currency) && $document->document_currency != 'ZAR') {
                        $ledger['document_currency'] = $document->document_currency;
                        $ledger['amount'] = currency_to_zar($document->document_currency, $ledger['amount'], $document->docdate);
                    }
                    $this->ledgers[] = $ledger;
                }
            }
        }

        return $this;
    }

    public function postDocumentCommit()
    {
        if (! empty($this->ledgers) && count($this->ledgers) > 0) {
            foreach ($this->ledger_ids as $doctype => $ids) {
                if ($doctype == 'General Journal') {
                    \DB::table('acc_general_journals')->whereIn('id', $ids)->update(['posted' => 1]);
                }

                $doctable = \DB::table('acc_doctypes')->where('doctype', $doctype)->pluck('doctable')->first();
                $doctypes = \DB::table('acc_doctypes')->where('doctable', $doctable)->pluck('doctype')->toArray();
                DB::table('acc_ledgers')->whereIn('doctype', $doctypes)->whereIn('docid', $ids)->delete();
            }

            $ledgers = collect($this->ledgers);

            foreach ($ledgers->chunk(1000) as $ledger) {
                DB::table('acc_ledgers')->insert($ledger->toArray());
            }
            foreach ($this->ledger_ids as $doctype => $ids) {
                set_doctype_doc_no($doctype);
            }
            /*
            try{

                $dates = $ledgers->pluck('docdate')->unique()->toArray();
                update_ledger_totals($dates);

            }catch(\Throwable $ex){
                exception_log('update ledger totals error');
                exception_log($ex->getMessage());
            }
            */
        }
        $this->ledgers = [];
        $this->ledger_ids = [];

        return $this;
    }
}
