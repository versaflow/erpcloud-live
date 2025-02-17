<?php

function schedule_generate_recurring_loans()
{
    // runs on the 1st
    $system_user_id = get_system_user_id();
    $loans = \DB::table('hr_loans')
        ->where('recurring_monthly', 1)
        ->where('is_deleted', 0)
        ->where('request_date', 'like', date('Y-m', strtotime('-1 month')).'%')
        ->get();

    foreach ($loans as $loan) {
        $data = (array) $loan;
        unset($data['id']);
        $data['amount_paid'] = 0;
        $data['request_date'] = date('Y-m-d');
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = $system_user_id;
        $data['updated_by'] = $system_user_id;

        \DB::table('hr_loans')->insert($data);
    }
}

function update_loan_amounts_paid()
{

    $loan_ids = \DB::table('acc_payroll_details')
        ->join('acc_payroll', 'acc_payroll.id', '=', 'acc_payroll_details.payroll_recon_id')
        ->where('acc_payroll.status', 'Complete')
        ->where('type', 'Loans')->pluck('loan_id')->toArray();
    foreach ($loan_ids as $loan_id) {
        $loan_amount_paid = \DB::table('acc_payroll_details')->where('loan_id', $loan_id)->where('type', 'Loans')->sum('total');

        if ($loan_amount_paid != 0) {
            \DB::table('hr_loans')->where('id', $loan_id)->update(['amount_paid' => abs($loan_amount_paid)]);
        }
    }

    $employee_ids = \DB::table('hr_loans')->pluck('employee_id')->unique()->toArray();
    $employees = \DB::table('hr_employees')->whereIn('id', $employee_ids)->where('status', 'Enabled')->get();

    foreach ($employees as $employee) {
        $balance = 0;
        $loans = \DB::table('hr_loans')->where('employee_id', $employee->id)
            ->where('is_deleted', 0)
            ->where('approved', 1)
            ->orderBy('request_date', 'asc')->orderBy('id', 'asc')
            ->get();
        foreach ($loans as $loan) {
            $balance += $loan->amount;
            $balance -= $loan->amount_paid;
            \DB::table('hr_loans')->where('id', $loan->id)->update(['balance' => $balance]);
        }
    }
}

function onload_set_loan_balances()
{
    $employee_ids = \DB::table('hr_loans')->pluck('employee_id')->unique()->toArray();
    $employees = \DB::table('hr_employees')->whereIn('id', $employee_ids)->where('status', 'Enabled')->get();

    foreach ($employees as $employee) {
        $balance = 0;
        $loans = \DB::table('hr_loans')->where('employee_id', $employee->id)
            ->where('is_deleted', 0)
            ->where('approved', 1)
            ->orderBy('request_date', 'asc')->orderBy('id', 'asc')
            ->get();
        foreach ($loans as $loan) {
            $balance += $loan->amount;
            $balance -= $loan->amount_paid;
            \DB::table('hr_loans')->where('id', $loan->id)->update(['balance' => $balance]);
        }
    }
}

function aftersave_loans_update_payroll_recon()
{
    schedule_update_payroll_recon();
}

function aftersave_hrloans_add_approvals($request)
{
    if (is_superadmin()) {
        \DB::table('hr_loans')->where('id', $request->id)->update(['approved' => 1]);
    } else {
        $hr_loans = \DB::table('hr_loans')->where('approved', 0)->get();
        foreach ($hr_loans as $hr_loans) {
            $exists = \DB::table('crm_approvals')->where('module_id', 1866)->where('row_id', $hr_loans->id)->count();
            if (! $exists) {
                $name = dbgetcell('hr_employees', 'id', $hr_loans->employee_id, 'name');
                $title = $name.' '.$hr_loans->requested_date.' '.$hr_loans->amount;

                $data = [
                    'module_id' => 1866,
                    'row_id' => $hr_loans->id,
                    'title' => $title,
                    'processed' => 0,
                    'requested_by' => get_user_id_default(),
                ];
                (new \DBEvent)->setTable('crm_approvals')->save($data);
            }
        }
    }
}
