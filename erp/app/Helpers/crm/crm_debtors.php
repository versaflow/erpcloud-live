<?php

function schedule_send_outstanding_balance_email()
{
    $debtor_statuses = \DB::table('crm_debtor_status')->where('aging', '>', 1)->where('account_status', '!=', 'Deleted')->where('email_id', '>', 0)->get();
    foreach ($debtor_statuses as $debtor_status) {
        $accounts = \DB::table('crm_accounts')->where('partner_id', 1)->where('status', '!=', 'Deleted')->where('debtor_status_id', $debtor_status->id)->get();
        foreach ($accounts as $account) {

            $data = [];
            $data['aging'] = $account->aging;
            $data['balance'] = $account->balance;
            $data['notification_id'] = $debtor_status->email_id;
            erp_process_notification($account->id, $data);
        }
    }
}

function schedule_write_off_bad_debts()
{

    \DB::table('crm_accounts')->update(['accountability_match' => 0, 'has_address' => 0, 'written_off_balance' => 0, 'commitment_date' => null]);
    \DB::table('crm_accounts')->whereRaw('accountability_current_status_id=debtor_status_id')->update(['accountability_match' => 1]);
    \DB::table('crm_accounts')->where('address', '>', '')->update(['has_address' => 1]);

    $sql = 'UPDATE crm_accounts 
    JOIN crm_commitment_dates ON crm_commitment_dates.account_id=crm_accounts.id
    SET crm_accounts.commitment_date = crm_commitment_dates.commitment_date
    WHERE crm_commitment_dates.expired=0 AND crm_commitment_dates.approved=1  AND crm_commitment_dates.commitment_fulfilled=0';
    \DB::statement($sql);

    $debtor_status_ids = \DB::table('crm_debtor_status')->where('id', '!=', 1)->where('is_deleted', 0)->pluck('id')->toArray();

    $debt_account_ids = \DB::table('acc_general_journals')->where('ledger_account_id', 5)->where('credit_amount', '>', 0)->where('reference', 'Bad Debt Written Off')->pluck('account_id')->unique()->filter()->toArray();
    $debt_account_ids = collect($debt_account_ids);

    foreach ($debt_account_ids as $account_id) {
        $debt_total = \DB::table('acc_general_journals')->where('account_id', $account_id)->where('ledger_account_id', 5)->where('reference', 'Bad Debt Written Off')->sum('credit_amount');
        if ($debt_total > 0) {

            $account_data = \DB::table('crm_accounts')->where('id', $account_id)->get()->first();

            if ($account_data->status != 'Deleted' && ! in_array($account_data->debtor_status_id, $debtor_status_ids)) {
                continue;
            }

            $data = [

                'written_off_balance' => $debt_total,

            ];

            \DB::table('crm_accounts')->where('id', $account_id)->update($data);
        }
    }
}

function get_accountability_debtor_status_id($form)
{
    return \DB::table('crm_debtor_status')->where('accountability_form', $form)->pluck('id')->first();
}

function button_debtors_upload_accountability_upload($request)
{

    return view('__app.button_views.debtors_upload');
}

function button_download_form_a_debtors($request)
{

    //$arr = file_to_array('/home/erpcloud-live/htdocs/html/forma-template.csv');
    $username = \DB::table('erp_users')->where('id', session('user_id'))->pluck('full_name')->first();
    $csv = [];

    $form_a_debtor_status_id = get_accountability_debtor_status_id('Form A');
    $form_b_debtor_status_id = get_accountability_debtor_status_id('Form B');

    $debtors = \DB::table('crm_accounts')->where('debtor_status_id', $form_a_debtor_status_id)->where('status', '!=', 'Deleted')->get();

    if (count($debtors) == 0) {
        return 'No debtors set to form A';
    }
    foreach ($debtors as $debtor) {
        $template = [
            'company name' => $debtor->company,
            'company registration' => $debtor->company_registration_number,
            'no registration' => (empty($debtor->company_registration_number)) ? 'Yes' : 'No',
            'company vat' => $debtor->vat_number,
            'no vat' => (empty($debtor->vat_number)) ? 'Yes' : 'No',
            'contact person' => $debtor->contact,
            'account number' => '',
            'cell number' => $debtor->phone,
            'telephone number' => $debtor->phone,
            'email address' => $debtor->email,
            'fax number' => '',
            'service sms' => 'Yes',
            'service_fax' => 'No',
            'registered letter' => 'No',
            'standard post' => 'No',
            'no email available' => 'No',
            'address line 1' => $debtor->address,
            'address line 2' => '',
            'suburb' => '',
            'address line 3' => '',
            'postal code' => '',
            'member 1 name' => $debtor->contact,
            'member 1 ID number' => $debtor->id_number,
            'member 2 name' => '',
            'member 2 ID number' => '',
            'member 3 name' => '',
            'member 3 ID number' => '',
            'is dishonored cheque' => 'Yes',
            'amount outstanding' => $debtor->balance,
            'date outstanding (yyyy-mm-dd)' => date('Y-m-d'),
            'captured by' => $username,
            'captured contact number' => '0105007500',
            'special instructions' => '',
        ];
        $csv[] = $template;
    }

    return (new Rap2hpoutre\FastExcel\FastExcel($csv))->download('form_a_debtors.csv');

}

function button_download_form_b_debtors($request)
{

    $arr = file_to_array(public_path().'/formb-template.csv');

    $username = \DB::table('erp_users')->where('id', session('user_id'))->pluck('full_name')->first();
    $csv = [];

    $form_b_debtor_status_id = get_accountability_debtor_status_id('Form B');
    $debtors = \DB::table('crm_accounts')->where('debtor_status_id', $form_b_debtor_status_id)->get();

    if (count($debtors) == 0) {
        return 'No debtors set to form B';
    }
    foreach ($debtors as $debtor) {
        $template = [
            'individual firstname' => $debtor->company,
            'individual lastname' => $debtor->company,
            'individual id number' => $debtor->id_number,
            'account number' => '',
            'cell number' => $debtor->phone,
            'telephone number' => $debtor->phone,
            'email address' => $debtor->email,
            'fax number' => '',
            'service sms' => 'Yes',
            'service_fax' => 'No',
            'registered letter' => 'No',
            'standard post' => 'No',
            'no email available' => 'No',
            'address line 1' => $debtor->address,
            'address line 2' => '',
            'suburb' => '',
            'address line 3' => '',
            'postal code' => '',
            'is dishonored cheque' => 'Yes',
            'amount outstanding' => $debtor->balance,
            'date outstanding (yyyy-mm-dd)' => date('Y-m-d'),
            'captured by' => $username,
            'captured contact number' => '0105007500',
            'special instructions' => '',
        ];
        $csv[] = $template;
    }

    return (new Rap2hpoutre\FastExcel\FastExcel($csv))->download('form_b_debtors.csv');

}

function set_deleted_account_aging($account_id)
{

    $account = dbgetaccount($account_id);
    if ($account->status != 'Deleted') {
        return false;
    }
    $doctypes = ['Tax Invoice'];

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
        ->select('id', 'total', 'docdate')
        ->where('account_id', $account->id)
        ->where('doctype', 'Tax Invoice')
        ->orderby('docdate', 'asc')->orderby('total')->get();

    $proforma_invoices = \DB::connection('default')->table('crm_documents')
        ->select('id', 'total')
        ->where('account_id', $account->id)
        ->where('doctype', 'Order')
        ->orderby('docdate')->orderby('total')->get();

    $last_write_off = \DB::connection('default')->table('acc_general_journals')
        ->where('acc_general_journals.account_id', $account->id)
        ->where('acc_general_journals.reference', 'Bad Debt Written Off')
        ->orderBy('id', 'desc')
        ->pluck('transaction_id')->first();
    if (! $last_write_off) {
        $last_write_off = 0;
    }

    $journals_debit_total = \DB::connection('default')->table('acc_general_journals')
        ->join('acc_general_journal_transactions', 'acc_general_journal_transactions.id', '=', 'acc_general_journals.transaction_id')
        ->where('acc_general_journals.account_id', $account->id)
        ->where('acc_general_journal_transactions.approved', 1)
        ->where('acc_general_journals.ledger_account_id', 5)
        ->where('acc_general_journals.transaction_id', '!=', $last_write_off)
        ->sum('acc_general_journals.debit_amount');

    $journals_credit_total = \DB::connection('default')->table('acc_general_journals')
        ->join('acc_general_journal_transactions', 'acc_general_journal_transactions.id', '=', 'acc_general_journals.transaction_id')
        ->where('acc_general_journals.account_id', $account->id)
        ->where('acc_general_journal_transactions.approved', 1)
        ->where('acc_general_journals.ledger_account_id', 5)
        ->where('acc_general_journals.transaction_id', '!=', $last_write_off)
        ->sum('acc_general_journals.credit_amount');

    $journals_total = $journals_credit_total - $journals_debit_total;

    $balance = $payments_total + $credit_total + $journals_total;

    $aging_date = false;
    if (! empty($tax_invoices)) {
        foreach ($tax_invoices as $doc) {
            $balance -= $doc->total;

            if ($balance < 0) {

                $aging_date = $doc->docdate;
                break;

            }
        }
    }

    if (! empty($aging_date)) {
        $date = Carbon\Carbon::parse($aging_date);
        $now = Carbon\Carbon::today();
        $aging = $date->diffInDays($now);
        \DB::connection('default')->table('crm_accounts')->where('id', $account_id)->update(['aging' => $aging]);
    }
}

function aftersave_debtors_set_debtor_match($request)
{
    \DB::table('crm_accounts')->update(['accountability_match' => 0]);
    \DB::table('crm_accounts')->whereRaw('accountability_current_status_id=debtor_status_id')->update(['accountability_match' => 1]);
}

function aftersave_customers_set_accountability_match($request)
{
    $sql = 'UPDATE crm_accounts 
    JOIN crm_commitment_dates ON crm_commitment_dates.account_id=crm_accounts.id
    SET crm_accounts.commitment_date = crm_commitment_dates.commitment_date
    WHERE crm_commitment_dates.expired=0 AND crm_commitment_dates.approved=1  AND crm_commitment_dates.commitment_fulfilled=0';
    \DB::statement($sql);
    \DB::table('crm_accounts')->update(['accountability_match' => 0]);
    \DB::table('crm_accounts')->whereRaw('accountability_current_status_id=debtor_status_id')->update(['accountability_match' => 1]);

}
function button_debtor_statuses_process_statuses()
{
    schedule_set_accounts_aging();

    return json_alert('Done');
}
