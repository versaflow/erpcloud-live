<?php

function button_payroll_update_payroll_recon()
{
    if (! is_main_instance()) {
        return false;
    }
    check_timesheet_max_hours();
    $payroll_date = date('Y-m-25');

    $completed = \DB::table('acc_payroll')->where('payroll_end_date', $payroll_date)->where('status', 'Complete')->count();

    update_payroll($payroll_date);

    return json_alert('Complete');
}

function update_payroll($payroll_date)
{
    $completed = \DB::table('acc_payroll')->where('payroll_end_date', $payroll_date)->where('status', 'Complete')->count();
    if ($completed) {
        //   return false;
    }
    build_payroll_details($payroll_date);

    $last_payroll_date = \DB::table('acc_payroll')->where('payroll_end_date', '<', $payroll_date)->orderBy('id', 'desc')->pluck('payroll_end_date')->first();
    $payroll_date_month_start = date('Y-m-d', strtotime($last_payroll_date.' +1 day'));
    $payroll_date_month_end = date('Y-m-t', strtotime($payroll_date_month_start));

    $holidays_count = 0;

    $holidays = \DB::table('hr_public_holidays')->where('holiday_date', 'like', date('Y-m', strtotime($payroll_date)).'-%')->get();
    foreach ($holidays as $holiday) {
        $holiday_date = new Carbon($holiday->holiday_date);
        if ($holiday_date->isWeekday()) {
            $holidays_count++;
        }
    }
    // if(date('m',strtotime($recon->payroll_end_date)) == 12){
    //     $holidays_count +=3;
    // }

    $employees = \DB::table('hr_employees')->where('start_date', '<', $payroll_date)->where('status', 'Enabled')->get();

    $work_days = countDays(date('Y', strtotime($payroll_date)), date('m', strtotime($payroll_date)), [0, 6]);
    // $last_payroll_date = \DB::table('acc_payroll')->where('payroll_end_date', '<', $payroll_date)->orderBy('id', 'desc')->pluck('payroll_end_date')->first();
    foreach ($employees as $employee) {
        // $payroll_date_month_start = date('Y-m-d', strtotime($last_payroll_date.' +1 day'));
        // if (date('Y-m-d', strtotime($employee->start_date)) > date('Y-m-d', strtotime($payroll_date_month_start))) {
        //     $payroll_date_month_start = $employee->start_date;
        // }

        $completed = \DB::table('acc_payroll')->where('employee_id', $employee->id)->where('payroll_end_date', $payroll_date)->where('status', 'Complete')->count();
        if ($completed) {
            continue;
        }

        $sales_total = 0;
        $work_days = 0;
        $sales = 0;
        $adjustments_total = 0;

        $daily_rate = currency($employee->monthly_rate / 22);

        $where_data = [
            'employee_id' => $employee->id,
            'payroll_end_date' => $payroll_date,
        ];

        $leave_requests = \DB::table('hr_leave')
            ->where('employee_id', $employee->id)
            ->where('date_from', '>', $last_payroll_date)
            ->where('is_deleted', 0)
            ->get();

        $leave_dates = [];
        foreach ($leave_requests as $leave_request) {
            if ($leave_request->date_to == $leave_request->date_from) {
                $leave_dates[] = Carbon::parse($leave_request->date_from);
            } else {
                $period = Carbon\CarbonPeriod::create($leave_request->date_from, $leave_request->date_to);

                // Convert the period to an array of dates
                $dates = $period->toArray();

                foreach ($dates as $date) {
                    $leave_dates[] = $date;
                }
            }
        }
        $leave_dates = collect($leave_dates)->unique()->toArray();

        $holidays = \DB::table('hr_public_holidays')->where('holiday_date', 'like', date('Y').'-%')->pluck('holiday_date')->toArray();
        foreach ($holidays as $i => $holiday) {
            $holidays[$i] = Carbon::parse($holiday);
        }
        $leave_days = 0;

        foreach ($leave_dates as $leave_date) {
            if ($leave_date->isWeekday() && ! in_array(Carbon::parse($leave_date), $holidays)) {
                $leave_days++;
            }
        }

        $work_dates = Carbon\CarbonPeriod::create($payroll_date_month_start, $payroll_date_month_end);
        $dates = $work_dates->toArray();
        $work_dates = [];

        foreach ($dates as $date) {
            if ($date->isWeekday()) {
                $work_days++;
                $work_dates[] = $date->toDateString();
            }
        }

        $total_payroll_days = $work_days;

        $timesheet_worked_days = \DB::table('hr_timesheet')->where('user_id', $employee->user_id)->where('created_date', 'like', date('Y-m', strtotime($payroll_date)).'%')->count();
        // $timesheet_worked_days = $timesheet_worked_days + $future_work_days;
        $payroll_id = \DB::table('acc_payroll')->where('employee_id', $employee->id)->where('payroll_end_date', $payroll_date)->pluck('id')->first();

        if ($employee->user_id) {
            $adjustments_total = \DB::table('acc_payroll_details')->where('payroll_recon_id', $payroll_id)->sum('total');
        }

        $days_unmet_hours = 0;
        $hours_min = 7;
        foreach ($work_dates as $work_date) {
            if ($work_date != date('Y-m-d') && $work_date > date('Y-m-d', strtotime('2024-03-06'))) {
                $c = \DB::table('hr_timesheet')->where('user_id', $employee->user_id)->where('created_date', $work_date)->count();
                if ($c) {
                    $sum_minutes = \DB::table('crm_staff_timesheet')->where('user_id', $employee->user_id)->where('created_at', 'like', $work_date.'%')->sum('duration');
                    $sum_hours = $sum_minutes / 60;

                    if ($sum_hours < $hours_min) {
                        //  $days_unmet_hours++;
                    }
                }
            }
        }

        $annual_paid_leave_days = 0;
        $annual_paid_leave_total = 0;
        if (date('Y', strtotime($employee->start_date)) == date('Y')) {
            $d1 = new Carbon($employee->start_date);
            $d2 = new Carbon(date('Y-12-t'));

            $annual_paid_leave_months = intval(round($d1->floatDiffInMonths($d2)));

            $annual_paid_leave_days = intval($annual_paid_leave_months * 1.25);
        } else {
            $annual_paid_leave_days = 15;
        }

        if (date('m', strtotime($payroll_date)) == 12) {
            $annual_paid_leave_total = $annual_paid_leave_days * $daily_rate;
        }

        $update_data = [
            'employee_id' => $employee->id,
            'start_date' => $employee->start_date,
            'payroll_start_date' => $payroll_date_month_start,
            'payroll_end_date' => $payroll_date,
            'monthly_rate' => $employee->monthly_rate,
            'work_days' => $work_days,
            'leave_days' => $leave_days,
            'timesheet_worked_days' => $timesheet_worked_days,
            'public_holidays' => $holidays_count,
            'daily_rate' => $daily_rate,
            'sales' => $sales_total,
            'adjustments_total' => $adjustments_total,
            'days_unmet_hours' => $days_unmet_hours,
            'annual_paid_leave_days' => $annual_paid_leave_days,
            'annual_paid_leave_total' => $annual_paid_leave_total,
            'is_deleted' => 0,
        ];

        if ($employee->incentive > 0) {
            $update_data['incentive_total'] -= $employee->incentive;
        }

        $exists = \DB::table('acc_payroll')->where('employee_id', $employee->id)->where('payroll_end_date', $payroll_date)->count();
        if (! $exists) {
            $update_data['status'] = 'Draft';
        }

        $update_data['pending'] = 0;
        if (date('Y-m-01') == date('Y-m-01', strtotime($payroll_date))) {
            if (date('Y-m-d') < date('Y-m-25')) {
                $update_data['pending'] = 1;
            } else {
                $update_data['pending'] = 0;
            }
        }

        \DB::table('acc_payroll')->updateOrInsert($where_data, $update_data);
    }

    $recons = \DB::table('acc_payroll')->where('payroll_end_date', $payroll_date)->get();
    foreach ($recons as $recon) {
        if ($recon->status == 'Completed') {
            continue;
        }
        //aa($recon->start_date);
        //aa($payroll_date_month_start);
        if ($recon->start_date > $payroll_date_month_start) {
            $from_date = Carbon::parse($recon->start_date);
            $to_date = Carbon::parse($payroll_date_month_end);
            $start_days = $to_date->diffInWeekdays($from_date);
            $recon->work_days = $start_days;
        }
        $deduct_days = ($recon->days_unmet_hours > $recon->leave_days) ? $recon->days_unmet_hours : $recon->leave_days;
        $total_days = $recon->work_days + $recon->extra_work_days - $deduct_days - $recon->public_holidays;

        $total = currency($total_days * $recon->daily_rate);

        $month_total = currency($total + $recon->adjustments_total);
        if (date('m', strtotime($recon->payroll_end_date)) == 12) {
            $month_total = currency($month_total + $recon->annual_paid_leave_total);
        }

        \DB::table('acc_payroll')->where('id', $recon->id)->update(['total_days' => $total_days, 'total' => $total, 'month_total' => $month_total]);
    }
}

function build_payroll_details($payroll_date)
{
    sales_targets_update_current_month();

    // commission
    //build_commissions($payroll_date);

    $employees = \DB::table('hr_employees')->where('status', 'Enabled')->get();
    foreach ($employees as $employee) {
        $user = \DB::table('erp_users')->where('id', $employee->user_id)->get()->first();
        $payroll_recon = \DB::table('acc_payroll')->where('employee_id', $employee->id)->where('payroll_end_date', $payroll_date)->get()->first();
        $status = \DB::table('acc_payroll')->where('employee_id', $employee->id)->where('payroll_end_date', $payroll_date)->pluck('status')->first();

        if ($status == 'Complete') {
            continue;
        }

        $payroll_recon_id = $payroll_recon->id;
        $payroll_date = $payroll_recon->payroll_end_date;
        $payroll_date_month_start = date('Y-m-01', strtotime($payroll_date));
        $payroll_date_month_end = date('Y-m-t', strtotime($payroll_date));

        if ($payroll_recon_id) {
            \DB::table('acc_payroll_details')->where('payroll_recon_id', $payroll_recon_id)->where('type', 'Loans')->delete();
            \DB::table('acc_payroll_details')->where('payroll_recon_id', $payroll_recon_id)->where('type', 'Loans Paid')->delete();
            \DB::table('acc_payroll_details')->where('payroll_recon_id', $payroll_recon_id)->where('type', 'like', '%Incentive%')->delete();
            \DB::table('acc_payroll_details')->where('payroll_recon_id', $payroll_recon_id)->where('type', 'Commission')->delete();

            $loans = \DB::table('hr_loans')->where('employee_id', $employee->id)
                ->where('is_deleted', 0)
                ->where('payroll_id', '!=', $payroll_recon_id)
                ->where('request_date', '<', date('Y-m-t', strtotime($payroll_date)))
                ->whereRaw(\DB::raw('(amount-amount_paid) > 0'))
                ->where('approved', 1)
                ->where('is_deleted', 0)
                ->get();
            foreach ($loans as $loan) {
                $line_total = $loan->amount - $loan->amount_paid;
                $data = [
                    'type' => 'Loans',
                    'total' => abs($line_total) * -1,
                    'docdate' => $loan->request_date,
                    'instance_id' => 1,
                    'user_id' => $employee->user_id,
                    'payroll_recon_id' => $payroll_recon_id,
                    'details' => $loan->reason,
                    'loan_id' => $loan->id,
                ];
                dbinsert('acc_payroll_details', $data);
            }

            \DB::table('acc_payroll')->where('id', $payroll_recon_id)->where('employee_id', $employee->id)->update(['loans' => $loan_total]);

            //incentive
            $work_days = countDays(date('Y', strtotime($payroll_date)), date('m', strtotime($payroll_date)), [0, 6]);
            $holidays_count = 0;
            $holiday_dates = [];
            $holidays = \DB::table('hr_public_holidays')->where('holiday_date', 'like', date('Y-m', strtotime($payroll_date)).'-%')->get();
            foreach ($holidays as $holiday) {
                $holiday_date = new Carbon($holiday->holiday_date);
                if ($holiday_date->isWeekday()) {
                    $holiday_dates[] = Carbon::parse($holiday->holiday_date);
                    $holidays_count++;
                }
            }

            $work_dates = Carbon\CarbonPeriod::create($payroll_date_month_start, $payroll_date_month_end);
            $dates = $work_dates->toArray();
            $work_dates = [];
            $future_work_days = 0;
            foreach ($dates as $date) {
                if ($date->isWeekday()) {
                    $work_dates[] = $date->toDateString();
                    if ($date->toDateString() > date('Y-m-d') && ! in_array($date, $holiday_dates)) {
                        $future_work_days++;
                    }
                }
            }

            $total_payroll_days = $work_days - $future_work_days - $holidays_count;
            $role = \DB::table('erp_user_roles')->where('id', $user->role_id)->get()->first();
            if ($role->incentive_function_1) {
                $fn = $role->incentive_function_1;
                if (function_exists($fn)) {
                    $incentive_total = $role->incentive_total_1;
                    $fn($payroll_recon_id, $incentive_total, $employee, $user, $payroll_date, $total_payroll_days);
                }
            }
            if ($role->incentive_function_2) {
                $fn = $role->incentive_function_2;
                if (function_exists($fn)) {
                    $incentive_total = $role->incentive_total_2;
                    $fn($payroll_recon_id, $incentive_total, $employee, $user, $payroll_date, $total_payroll_days);
                }
            }
        }

        \DB::table('acc_payroll')->where('id', $payroll_recon_id)->update(['document_currency' => $employee->currency]);
        \DB::table('acc_payroll_details')->where('payroll_recon_id', $payroll_recon_id)->update(['document_currency' => $employee->currency]);
    }
}

function build_commissions($payroll_date = false)
{
    if (! $payroll_date) {
        $payroll_date = date('Y-m-25');
    }
    $conns = ['telecloud', 'moviemagic', 'eldooffice'];
    $adjustments_total = 0;

    $employees = \DB::table('hr_employees')->where('status', 'Enabled')->get();
    $last_payroll_date = \DB::table('acc_payroll')->where('payroll_end_date', '<', $payroll_date)->orderBy('id', 'desc')->pluck('payroll_end_date')->first();

    $stock_product_ids = \DB::table('crm_products')->where('status', '!=', 'Deleted')->where('type', 'Stock')->pluck('id')->toArray();

    foreach ($employees as $employee) {
        $payroll_recon_id = \DB::table('acc_payroll')->where('employee_id', $employee->id)->where('payroll_end_date', $payroll_date)->pluck('id')->first();

        \DB::table('acc_payroll_details')->where('payroll_recon_id', $payroll_recon_id)->where('type', 'Commission')->delete();
        foreach ($conns as $c) {
            // sales commissions is only paid after service activated
            $pending_activation_invoice_ids = \DB::connection($c)->table('sub_activations')->whereNotIn('status', ['Enabled', 'Deleted', 'Credited'])->pluck('invoice_id')->unique()->filter()->toArray();

            $instance = \DB::table('erp_instances')->where('installed', 1)->where('db_connection', $c)->get()->first();

            $username = \DB::connection('default')->table('erp_users')->where('id', $employee->user_id)->pluck('username')->first();
            $instance_user_id = \DB::connection($c)->table('erp_users')->where('username', $username)->pluck('id')->first();
            if ($instance->id == 2) {
                $sales = \DB::connection($c)->table('crm_document_lines')
                    ->join('crm_documents', 'crm_documents.id', '=', 'crm_document_lines.document_id')
                    ->select('crm_documents.id', 'crm_documents.reversal_id', 'crm_document_lines.price as total', 'crm_documents.id', 'crm_documents.docdate', 'crm_documents.doc_no', 'crm_documents.document_currency', 'crm_documents.docdate')
                    ->where('total', '!=', 0)
                    ->where('product_id', 149)
                    ->where('salesman_id', $instance_user_id)
                    ->where('doctype', 'Tax Invoice')
                    ->where('docdate', '>', $last_payroll_date)
                    ->get();
            } else {
                $sales = \DB::connection($c)->table('crm_documents')
                    ->select('crm_documents.id', 'crm_documents.reversal_id', 'crm_documents.total', 'crm_documents.tax', 'crm_documents.id', 'crm_documents.docdate', 'crm_documents.doc_no', 'crm_documents.document_currency', 'crm_documents.docdate')
                    ->where('total', '!=', 0)
                    ->whereNotIn('id', $pending_activation_invoice_ids)
                    ->where('salesman_id', $instance_user_id)
                    ->where('doctype', 'Tax Invoice')
                    ->where('docdate', '>', $last_payroll_date)
                    ->get();
            }

            if (count($sales) > 0) {
                $commission_total = 0;
                $total_excl = 0;
                $num_invoices = 0;
                foreach ($sales as $sale) {
                    $credit_sub_total = 0;
                    $stock_sale_total = \DB::table('crm_document_lines')->where('document_id', $sale->id)->whereIn('product_id', $stock_product_ids)->sum('sale_total');
                    $service_sale_total = \DB::table('crm_document_lines')->where('document_id', $sale->id)->whereNotIn('product_id', $stock_product_ids)->sum('sale_total');
                    if ($sale->document_currency != 'ZAR') {
                        $stock_sale_total = currency_to_zar($sale->document_currency, $stock_sale_total, $sale->docdate);
                        $service_sale_total = currency_to_zar($sale->document_currency, $service_sale_total, $sale->docdate);
                    }
                    $sale_total = $sale->subtotal;
                    if ($sale->reversal_id > 0) {
                        $reversal_stock_sale_total = \DB::table('crm_document_lines')->where('document_id', $sale->reversal_id)->whereIn('product_id', $stock_product_ids)->sum('sale_total');
                        $reversal_service_sale_total = \DB::table('crm_document_lines')->where('document_id', $sale->reversal_id)->whereNotIn('product_id', $stock_product_ids)->sum('sale_total');
                        if ($sale->document_currency != 'ZAR') {
                            $reversal_stock_sale_total = currency_to_zar($sale->document_currency, $reversal_stock_sale_total, $sale->docdate);
                            $reversal_service_sale_total = currency_to_zar($sale->document_currency, $reversal_service_sale_total, $sale->docdate);
                        }
                        $stock_sale_total -= $reversal_stock_sale_total;
                        $service_sale_total -= $reversal_service_sale_total;
                    }

                    if ($instance->id == 2) {
                        $commission = $stock_sale_total + $service_sale_total;
                    } else {
                        $commission = 0;
                        $commission += ($stock_sale_total / 100) * 2.5;
                        $commission += ($service_sale_total / 100) * 5;
                    }
                    $commission_total += $commission;

                    $total_excl += $sale->subtotal;
                    $num_invoices++;
                    /*

                    try{
                        if($payroll_recon_id){
                            $data = [
                                'instance_id' => $instance->id,
                                'user_id' => $employee->user_id,
                                'document_id' => $sale->id,
                                'credit_document_id' => $sale->reversal_id,
                                'sale_total' => $sale_total,
                                'credit_total' => $credit_sub_total,
                                'docdate' => $sale->docdate,
                                'total_excl' => $sale->subtotal,
                                'type' => 'Commission',
                                'total' => $commission,
                                'payroll_recon_id' => $payroll_recon_id
                            ];

                            \DB::table('acc_payroll_details')->updateOrInsert(['document_id'=>$sale->id,'instance_id'=>$instance->id],$data);
                        }


                    }catch(\Throwable $ex){
                       aa($ex->getMessage());
                       aa($ex->getTraceAsString());
                    }
                    */
                }

                if ($commission_total != 0) {
                    $data = [
                        'type' => 'Commission',
                        'total' => $commission_total,
                        'docdate' => $payroll_date,
                        'instance_id' => 1,
                        'user_id' => $employee->user_id,
                        'payroll_recon_id' => $payroll_recon_id,
                        'details' => 'Num Invoices: '.$num_invoices.' | Total Excl: '.$total_excl,
                    ];
                    dbinsert('acc_payroll_details', $data);
                }
            }
        }
    }
}

// INCENTIVE FUNCTIONS START
function incentive_timesheet($payroll_recon_id, $incentive_total, $employee, $user, $payroll_date, $total_payroll_days)
{
    $payroll = \DB::table('acc_payroll')->where('id', $payroll_recon_id)->get()->first();
    $payroll_start_date = \DB::table('acc_payroll')->where('id', $payroll_recon_id)->pluck('payroll_start_date')->first();
    $payroll_end_date = \DB::table('acc_payroll')->where('id', $payroll_recon_id)->pluck('payroll_end_date')->first();
    $incentive_timesheet = $incentive_total;
    // TIMESHEET
    if (date('Y-m-d', strtotime($employee->start_date)) > date('Y-m-d', strtotime($payroll_start_date))) {
        $payroll_start_date = $employee->start_date;
    }

    $last_payroll_date = \DB::table('acc_payroll')->where('payroll_end_date', '<', $payroll_date)->orderBy('id', 'desc')->pluck('payroll_end_date')->first();
    // if($employee->role_id == 54){
    //     $commit_dates = \DB::table('erp_github_commits')->select(\DB::raw('DATE(committed_at) as commit_date'))
    //     ->where('committer_name','oyen-bright')
    //     ->where('committed_at','>=',$payroll_start_date)
    //     ->pluck('commit_date')->unique()->toArray();
    //     $total_minutes = \DB::table('crm_staff_timesheet')
    //     ->select(\DB::raw('sum(duration) as minutes_worked'),'created_day')
    //     ->where('created_at', '>=',$payroll_start_date.' 00:00')
    //     ->where('created_at', '<=', $payroll_end_date.' 23:00')
    //     ->whereIn('created_day',$commit_dates)
    //     ->where('user_id',$employee->user_id)
    //     ->sum('duration');
    // }else{
    $total_minutes = \DB::table('crm_staff_timesheet')
        ->select(\DB::raw('sum(duration) as minutes_worked'), 'created_day')
        ->where('created_at', '>=', $payroll_start_date.' 00:00')
        ->where('created_at', '<=', $payroll_end_date.' 23:59')
        ->where('user_id', $employee->user_id)
        ->sum('duration');
    // }
    $total_days = $payroll->work_days - $payroll->public_holidays;
    $incentive_hours = $total_days * 7.5;

    $total_hours = $total_minutes / 60;

    if ($total_hours >= $incentive_hours) {
        $payroll_incentive_total = $incentive_timesheet;
    } else {
        $payroll_incentive_total = $incentive_timesheet * ($total_hours / $incentive_hours);
    }
    $average_hours_worked = currency($total_hours / $total_days);

    if ($payroll_incentive_total > 0) {
        $data = [
            'type' => 'Incentive Timesheet',
            'total' => currency($payroll_incentive_total),
            'docdate' => $payroll_date,
            'instance_id' => 1,
            'user_id' => $employee->user_id,
            'payroll_recon_id' => $payroll_recon_id,
            'total_incentive' => $incentive_timesheet,
            'incentive_calculation' => $average_hours_worked,
            'details' => '',
        ];

        dbinsert('acc_payroll_details', $data);
    }
}

function incentive_sales($payroll_recon_id, $incentive_total, $employee, $user, $payroll_date, $total_payroll_days)
{
    $incentive_sales = $incentive_total;
    //SALES INCENTIVE
    $last_payroll_date = \DB::table('acc_payroll')->where('payroll_end_date', '<', $payroll_date)->orderBy('id', 'desc')->pluck('payroll_end_date')->first();
    $total = 0;
    $sales_target = 30000;
    $salesman_count = \DB::table('erp_users')->where('role_id', 62)->where('is_deleted', 0)->count();
    $sales_target = $sales_target / $salesman_count;
    $sales_details_arr = [];
    $monthly_sales = 0;

    $commission_details = '';
    $salesman = \DB::table('erp_users')->where('role_id', 62)->where('id', $employee->user_id)->where('is_deleted', 0)->get();

    foreach ($salesman as $s) {
        $name_arr = explode(' ', $s->full_name);
        $name = $name_arr[0];

        $sales_total = \DB::table('crm_document_lines')
            ->join('crm_documents', 'crm_documents.id', '=', 'crm_document_lines.document_id')
            ->where('salesman_id', $s->id)
            ->where('docdate', '>', $last_payroll_date)
            ->where('billing_type', '')
            ->whereIn('doctype', ['Tax Invoice', 'Credit Note'])->sum('zar_sale_total');
        if ($sales_total) {
            $sales_details_arr[] = $name.': R'.currency($sales_total);
            $monthly_sales += $sales_total;
            $sales = \DB::table('crm_documents')
                ->join('crm_accounts', 'crm_accounts.id', '=', 'crm_documents.account_id')
                ->select('doctype', 'crm_documents.id', 'total', 'crm_accounts.company')
                ->where('crm_documents.salesman_id', $s->id)
                ->where('docdate', '>', $last_payroll_date)
                ->where('billing_type', '')
                ->whereIn('doctype', ['Tax Invoice', 'Credit Note'])->get();
            foreach ($sales as $sale) {
                $commission_details .= $sale->doctype.' #'.$sale->id.' '.$sale->total.' '.$sale->company.PHP_EOL;
            }
        }
    }

    if ($monthly_sales > $sales_target || $sales_target == 0) {
        $total = $incentive_sales;
        $percentage = 100;
    } else {
        $total = $incentive_sales * ($monthly_sales / $sales_target);
        $percentage = ($monthly_sales / $sales_target) * 100;
    }

    $details = implode(' | ', $sales_details_arr).' '.currency($monthly_sales).'/'.currency($sales_target).' sales target '.intval($percentage).'%';

    $data = [
        'type' => 'Incentive Sales',
        'total' => currency($total),
        'docdate' => $payroll_date,
        'instance_id' => 1,
        'user_id' => $employee->user_id,
        'payroll_recon_id' => $payroll_recon_id,
        'total_incentive' => $incentive_sales,
        'details' => $details,
        'commission_details' => $commission_details,
    ];

    dbinsert('acc_payroll_details', $data);
}

function incentive_leads($payroll_recon_id, $incentive_total, $employee, $user, $payroll_date, $total_payroll_days)
{
    $incentive_leads = $incentive_total;
    //LEADS INCENTIVE

    $total = 0;

    $target = 300;
    $monthly_leads = \DB::table('crm_accounts')
        ->where('created_at', 'like', date('Y-m', strtotime($payroll_date)).'%')
        ->where('partner_id', 1)
        ->count();
    if ($monthly_leads > $target || $target == 0) {
        $total = $incentive_leads;
        $percentage = 100;
    } else {
        $total = $incentive_leads * ($monthly_leads / $target);
        $percentage = ($monthly_leads / $target) * 100;
    }

    $data = [
        'type' => 'Incentive Leads',
        'total' => currency($total),
        'docdate' => $payroll_date,
        'instance_id' => 1,
        'user_id' => $employee->user_id,
        'payroll_recon_id' => $payroll_recon_id,
        'total_incentive' => $incentive_leads,
        'details' => $monthly_leads.'/'.$target.' new accounts '.intval($percentage).'%',
    ];
    dbinsert('acc_payroll_details', $data);
}

function incentive_products($payroll_recon_id, $incentive_total, $employee, $user, $payroll_date, $total_payroll_days)
{
    $incentive_products = $incentive_total;
    //productS INCENTIVE

    $last_payroll_date = \DB::table('acc_payroll')->where('payroll_end_date', '<', $payroll_date)->orderBy('id', 'desc')->pluck('payroll_end_date')->first();
    $total = 0;

    $target = 300;
    $monthly_products = \DB::table('crm_products')
        ->where('created_at', '>', $last_payroll_date)
        ->where('created_by', $employee->user_id)
        ->count();
    if ($monthly_products > $target || $target == 0) {
        $total = $incentive_products;
        $percentage = 100;
    } else {
        $total = $incentive_products * ($monthly_products / $target);
        $percentage = ($monthly_products / $target) * 100;
    }

    $data = [
        'type' => 'Incentive Products',
        'total' => currency($total),
        'docdate' => $payroll_date,
        'instance_id' => 1,
        'user_id' => $employee->user_id,
        'payroll_recon_id' => $payroll_recon_id,
        'total_incentive' => $incentive_products,
        'details' => $monthly_products.'/'.$target.' products target '.intval($percentage).'%',
    ];
    dbinsert('acc_payroll_details', $data);
}

function incentive_suppliers($payroll_recon_id, $incentive_total, $employee, $user, $payroll_date, $total_payroll_days)
{
    $incentive_suppliers = $incentive_total;
    //supplierS INCENTIVE

    $total = 0;

    $target = 20;
    $monthly_suppliers = \DB::table('acc_potential_suppliers')
        ->where('created_at', 'like', date('Y-m', strtotime($payroll_date)).'%')
        ->count();
    if ($monthly_suppliers > $target || $target == 0) {
        $total = $incentive_suppliers;
        $percentage = 100;
    } else {
        $total = $incentive_suppliers * ($monthly_suppliers / $target);
        $percentage = ($monthly_suppliers / $target) * 100;
    }

    $data = [
        'type' => 'Incentive Suppliers',
        'total' => currency($total),
        'docdate' => $payroll_date,
        'instance_id' => 1,
        'user_id' => $employee->user_id,
        'payroll_recon_id' => $payroll_recon_id,
        'total_incentive' => $incentive_suppliers,
        'details' => $monthly_suppliers.'/'.$target.' suppliers target '.intval($percentage).'%',
    ];
    dbinsert('acc_payroll_details', $data);
}

function incentive_github_commits($payroll_recon_id, $incentive_total, $employee, $user, $payroll_date, $total_payroll_days)
{
    $incentive_commits = $incentive_total;
    if ($user->role_id == 54) {
        $sql = "SELECT DATE(committed_at) AS commit_date, COUNT(*) AS commits_count
        FROM erp_github_commits
        WHERE is_deleted = 0
        AND committer_name='oyen-bright'
        AND committed_at > '".date('Y-m-01 00:00', strtotime($payroll_date))."'
        GROUP BY DATE(committed_at)
        ORDER BY commit_date DESC";
        $daily_commits = 1;
    }
    if ($user->role_id == 58) {
        $sql = "SELECT DATE(committed_at) AS commit_date, COUNT(*) AS commits_count
        FROM erp_github_commits
        WHERE is_deleted = 0
        AND committer_name!='oyen-bright'
        AND committed_at > '".date('Y-m-01 00:00', strtotime($payroll_date))."'
        GROUP BY DATE(committed_at)
        ORDER BY commit_date DESC";
        $daily_commits = 2;
    }
    if ($sql) {
        $git_commits = \DB::select($sql);
        $code_changes_daily = 0;

        foreach ($git_commits as $git_commit) {
            if ($git_commit->commits_count >= $daily_commits) {
                $code_changes_daily++;
            }
        }
        if ($code_changes_daily > $total_payroll_days) {
            $code_changes_daily = $total_payroll_days;
        }

        $core_process_totals = $code_changes_daily * $incentive_commits / $total_payroll_days;

        if ($core_process_totals > 0) {
            $data = [
                'type' => 'Incentive Github Commits',
                'total' => currency($core_process_totals),
                'docdate' => $payroll_date,
                'instance_id' => 1,
                'user_id' => $employee->user_id,
                'payroll_recon_id' => $payroll_recon_id,
                'total_incentive' => $incentive_commits,
                'details' => $code_changes_daily.'/'.$total_payroll_days.' commits',
            ];
            dbinsert('acc_payroll_details', $data);
        }
        \DB::table('acc_payroll')->where('id', $payroll_recon_id)->where('employee_id', $employee->id)->update(['code_changes_daily' => $code_changes_daily]);
    }
}
/// INCENTIVE FUNCTIONS END

function payroll_get_current_details($payroll_date = false)
{
    // aa($payroll_date);

    if (! $payroll_date) {
        $payroll_date = date('Y-m-d');
    }
    $payroll = \DB::table('acc_payroll')->where('payroll_end_date', 'like', date('Y-m', strtotime($payroll_date)).'-%')->orderBy('id', 'asc')->get()->first();
    $payroll_date_month_start = $payroll->payroll_start_date;
    $payroll_date_month_end = $payroll->payroll_end_date;

    //incentive
    $holiday_dates = [];
    $holidays_count = 0;
    $holidays = \DB::table('hr_public_holidays')->where('holiday_date', 'like', date('Y-m', strtotime($payroll_date)).'-%')->get();
    foreach ($holidays as $holiday) {
        $holiday_date = new Carbon($holiday->holiday_date);
        if ($holiday_date->isWeekday()) {
            $holiday_dates[] = Carbon::parse($holiday->holiday_date);
            $holidays_count++;
        }
    }

    if ($payroll_date_month_start && $payroll_date_month_end) {
        $work_dates = Carbon\CarbonPeriod::create($payroll_date_month_start, $payroll_date_month_end);
        $dates = $work_dates->toArray();
        $work_dates = [];
        $work_days = 0;
        $future_work_days = 0;
        foreach ($dates as $date) {
            if ($date->isWeekday()) {
                $work_dates[] = $date->toDateString();

                if ($date->toDateString() > date('Y-m-d')) {
                    if (! in_array($date, $holiday_dates)) {
                        $future_work_days++;
                    }
                } else {
                    if (! in_array($date, $holiday_dates)) {
                        $work_days++;
                    }
                }
            }
        }
        $response = [
            'total_payroll_days' => $work_days + $future_work_days,
            'current_payroll_days' => $work_days,
            'public_holidays' => $holidays_count,
            'start_date' => $payroll_date_month_start,
            'end_date' => $payroll_date_month_end,
        ];
    } else {
        $response = 'Payroll not found';
    }

    return $response;
}

function schedule_create_leave_requests()
{
    $today = Carbon::parse(date('Y-m-d'));
    if (! $today->isWeekday()) {
        return false;
    }

    $employees = \DB::table('hr_employees')->where('status', 'Enabled')->get();
    foreach ($employees as $employee) {
        if ($employee->user_id > 1) {
            $logged_in = \DB::table('hr_timesheet')->where('user_id', $employee->user_id)->where('start_time', 'like', date('Y-m-d').'%')->count();
            if (! $logged_in) {
                $e = \DB::table('hr_leave')->where('employee_id', $employee->id)->where('date_from', date('Y-m-d'))->count();
                if (! $e) {
                    $db = new DBEvent;
                    $db->setTable('hr_leave');
                    $data = [
                        'employee_id' => $employee->id,
                        'date_from' => date('Y-m-d'),
                        'date_to' => date('Y-m-d'),
                        'days' => 1,
                        'reason' => 'Login time checked by system at: '.date('Y-m-d H:i:s'),
                    ];

                    $result = $db->save($data);
                }
                //return $result;
            }
        }
    }
}

function aftersave_payroll_details_update_totals($request)
{
    $payroll_detail = \DB::table('acc_payroll_details')->where('id', $request->id)->get()->first();
    $payroll = \DB::table('acc_payroll')->where('id', $payroll_detail->payroll_recon_id)->get()->first();

    $adjustments_total = \DB::table('acc_payroll_details')->where('payroll_recon_id', $payroll_detail->payroll_recon_id)->sum('total');
    $month_total = $payroll->total + $adjustments_total;

    \DB::table('acc_payroll')->where('id', $payroll_detail->payroll_recon_id)->update(['adjustments_total' => $adjustments_total, 'month_total' => $month_total]);
}

function afterdelete_payroll_details_update_totals($request)
{
    $payroll_detail = \DB::table('acc_payroll_details')->where('id', $request->id)->get()->first();
    $payroll = \DB::table('acc_payroll')->where('id', $payroll_detail->payroll_recon_id)->get()->first();

    $adjustments_total = \DB::table('acc_payroll_details')->where('payroll_recon_id', $payroll_detail->payroll_recon_id)->sum('total');
    $month_total = $payroll->total + $adjustments_total;

    \DB::table('acc_payroll')->where('id', $payroll_detail->payroll_recon_id)->update(['adjustments_total' => $adjustments_total, 'month_total' => $month_total]);
}

function update_mobile_developer_timesheet()
{
    \DB::table('crm_staff_timesheet')->where('created_at', 'like', date('Y-m'))->update(['has_leave' => 0]);

    $employees = \DB::table('hr_employees')->where('status', 'Enabled')->get();
    $leave = \DB::table('hr_leave')->where('is_deleted', 0)->where('date_from', 'like', date('Y-m').'%')->get();

    foreach ($leave as $l) {
        $user_id = $employees->where('id', $l->employee_id)->pluck('user_id')->first();

        \DB::table('crm_staff_timesheet')->where('user_id', $user_id)->where('created_at', 'like', $l->date_from.'%')->update(['has_leave' => 1]);
    }
}

function check_timesheet_max_hours()
{
    \DB::table('crm_staff_timesheet')->where('created_at', 'like', date('Y-m').'%')->where('name', 'Over max 8 hours')->delete();
    \DB::table('crm_staff_timesheet')->where('created_at', 'like', date('Y-m').'%')->where('name', 'Extra time for tasks, max 30 minutes')->delete();
    $user_ids = \DB::table('crm_staff_timesheet')->where('created_at', 'like', date('Y-m').'%')->pluck('user_id')->unique()->toArray();
    $days = \DB::table('crm_staff_timesheet')->where('created_at', 'like', date('Y-m').'%')->pluck('created_day')->unique()->toArray();
    foreach ($user_ids as $user_id) {
        foreach ($days as $day) {
            $total = \DB::table('crm_staff_timesheet')->where('created_day', $day)->where('user_id', $user_id)->sum('duration');

            if ($total > 480) {
                $data = [
                    'user_id' => $user_id,
                    'created_at' => $day,
                    'created_day' => $day,
                    'duration' => ($total - 480) * -1,
                    'name' => 'Over max 8 hours',
                ];

                \DB::table('crm_staff_timesheet')->insert($data);
            } elseif ($total < 480 && $day != date('Y-m-d')) {
                $has_tasks = \DB::table('crm_staff_timesheet')->where('created_day', $day)->where('user_id', $user_id)->count();

                if ($has_tasks) {
                    $remaining = 480 - $total;

                    if ($remaining > 30) {
                        $remaining = 30;
                    }
                    $data = [
                        'user_id' => $user_id,
                        'created_at' => $day,
                        'created_day' => $day,
                        'duration' => $remaining,
                        'name' => 'Extra time for tasks, max 30 minutes',
                    ];

                    \DB::table('crm_staff_timesheet')->insert($data);
                }
            }
        }
    }
}

function button_payroll_send_approve_email($request)
{
    $payroll_date = \DB::table('acc_payroll')->where('approved', 0)->where('payroll_end_date', '<', date('Y-m-d'))->orderBy('id', 'desc')->pluck('payroll_end_date')->first();
    if (! $payroll_date) {
        return json_alert('Payroll already approved');
    }
    $payrolls = \DB::table('acc_payroll')->where('payroll_end_date', $payroll_date)->where('is_deleted', 0)->get();

    $msg = '';
    foreach ($payrolls as $payroll) {
        $payroll_date = $payroll->payroll_end_date;
        $employee = \DB::table('hr_employees')->where('id', $payroll->employee_id)->get()->first();
        $msg .= $employee->name;
        $msg .= '<br> Basic: '.$payroll->total;
        $msg .= '<br> Adjustments: '.$payroll->adjustments_total;
        $msg .= '<br> Total: '.$payroll->month_total;
        $msg .= '<br><br>';
    }
    $msg .= '<a href="'.url('/payroll_approve/'.$payroll_date).'" target="_blank"> Approve payroll</a>';

    admin_email('Payroll '.$payroll_date, $msg);

    return json_alert('Approval email sent');
}

function payroll_approve($payroll_date)
{
    $payrolls = \DB::table('acc_payroll')->where('payroll_end_date', $payroll_date)->where('is_deleted', 0)->get();

    foreach ($payrolls as $payroll) {
        DB::table('acc_payroll')->where('id', $payroll->id)->update(['approved' => 1, 'status' => 'Complete']);
        payroll_complete_and_email_payslip($payroll);
    }
}

function button_payroll_complete_and_email_all($request)
{
    if (! is_superadmin()) {
        return json_alert('No Access', 'warning');
    }

    $payroll_date = \DB::table('acc_payroll')->where('payroll_end_date', '<', date('Y-m-d'))->orderBy('id', 'desc')->pluck('payroll_end_date')->first();

    $payrolls = \DB::table('acc_payroll')->where('payroll_end_date', $payroll_date)->where('is_deleted', 0)->get();
    foreach ($payrolls as $payroll) {
        \DB::table('acc_payroll')->where('id', $payroll->id)->update(['status' => 'Complete']);
        $recon = \DB::table('acc_payroll')->where('id', $payroll->id)->get()->first();

        $payroll = \DB::table('acc_payroll')->where('id', $payroll->id)->get()->first();
        $payroll_details = \DB::table('acc_payroll_details')->where('payroll_recon_id', $payroll->id)->get();
        $company = dbgetaccount(1);
        $employee = \DB::table('hr_employees')->where('id', $payroll->employee_id)->get()->first();
        $user = \DB::table('erp_users')->where('id', $employee->user_id)->get()->first();
        $data['payroll'] = $payroll;
        $data['payroll_details'] = $payroll_details;
        $data['employee'] = $employee;
        $data['company'] = $company;

        $pdf = PDF::loadView('__app.components.pages.payslip', $data);
        $pdf_name = str_replace(' ', '_', 'Payslip '.date('Y-m-d', strtotime($payroll->payroll_end_date)).'.pdf');

        $filename = attachments_path().$pdf_name;
        if (file_exists($filename)) {
            unlink($filename);
        }
        $pdf->setTemporaryFolder(attachments_path());
        $pdf->save($filename);

        $mail_data = [];
        $mail_data['attachment'] = $pdf_name;
        $mail_data['internal_function'] = 'payslip_email';
        $mail_data['force_to_email'] = $user->email;
        //$mail_data['test_debug'] = 1;
        //$mail_data['force_to_email'] = 'ahmed@telecloud.co.za';
        erp_process_notification(1, $mail_data);
    }
    update_loan_amounts_paid();

    return json_alert('Complete');
}

function payroll_complete_and_email_payslip($request)
{
    $approved = \DB::table('acc_payroll')->where('id', $request->id)->pluck('approved')->first();
    if (! $approved) {
        return false;
    }

    \DB::table('acc_payroll')->where('id', $request->id)->update(['status' => 'Complete']);
    update_loan_amounts_paid();
    $recon = \DB::table('acc_payroll')->where('id', $request->id)->get()->first();

    $payroll = \DB::table('acc_payroll')->where('id', $request->id)->get()->first();
    $payroll_details = \DB::table('acc_payroll_details')->where('payroll_recon_id', $request->id)->get();
    $company = dbgetaccount(1);
    $employee = \DB::table('hr_employees')->where('id', $payroll->employee_id)->get()->first();
    $user = \DB::table('erp_users')->where('id', $employee->user_id)->get()->first();
    $data['payroll'] = $payroll;
    $data['payroll_details'] = $payroll_details;
    $data['employee'] = $employee;
    $data['company'] = $company;

    $pdf = PDF::loadView('__app.components.pages.payslip', $data);
    $pdf_name = str_replace(' ', '_', 'Payslip '.date('Y-m-d', strtotime($payroll->payroll_end_date)).'.pdf');

    $filename = attachments_path().$pdf_name;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->save($filename);

    $mail_data = [];
    $mail_data['attachment'] = $pdf_name;
    $mail_data['internal_function'] = 'payslip_email';
    $mail_data['force_to_email'] = $user->email;
    //$mail_data['test_debug'] = 1;
    //$mail_data['force_to_email'] = 'ahmed@telecloud.co.za';
    erp_process_notification(1, $mail_data);

    return json_alert('Complete');
}

function button_payroll_draft($request)
{
    \DB::table('acc_payroll')->where('id', $request->id)->update(['status' => 'Draft']);
}

function button_payroll_complete($request)
{
    \DB::table('acc_payroll')->where('id', $request->id)->update(['status' => 'Complete']);
    update_loan_amounts_paid();
}

function button_payroll_email_payslip($request)
{
    $approved = \DB::table('acc_payroll')->where('id', $request->id)->pluck('approved')->first();
    if (! $approved) {
        return json_alert('Payroll needs to be approved', 'warning');
    }

    update_loan_amounts_paid();
    $recon = \DB::table('acc_payroll')->where('id', $request->id)->get()->first();

    $payroll = \DB::table('acc_payroll')->where('id', $request->id)->get()->first();
    $payroll_details = \DB::table('acc_payroll_details')->where('payroll_recon_id', $request->id)->get();
    $company = dbgetaccount(1);
    $employee = \DB::table('hr_employees')->where('id', $payroll->employee_id)->get()->first();
    $user = \DB::table('erp_users')->where('id', $employee->user_id)->get()->first();
    $data['payroll'] = $payroll;
    $data['payroll_details'] = $payroll_details;
    $data['employee'] = $employee;
    $data['company'] = $company;

    $pdf = PDF::loadView('__app.components.pages.payslip', $data);
    $pdf_name = str_replace(' ', '_', 'Payslip '.date('Y-m-d', strtotime($payroll->payroll_end_date)).'.pdf');

    $filename = attachments_path().$pdf_name;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->save($filename);

    $mail_data = [];
    $mail_data['attachment'] = $pdf_name;
    $mail_data['internal_function'] = 'payslip_email';
    $mail_data['force_to_email'] = $user->email;
    $mail_data['force_cc_email'] = 'ahmed@telecloud.co.za';
    //$mail_data['test_debug'] = 1;
    //$mail_data['force_to_email'] = 'ahmed@telecloud.co.za';
    erp_process_notification(1, $mail_data);

    return json_alert('Complete');
}

function button_payroll_download_payslip($request)
{
    $payroll = \DB::table('acc_payroll')->where('id', $request->id)->get()->first();
    $payroll_details = \DB::table('acc_payroll_details')->where('payroll_recon_id', $request->id)->get();
    $company = dbgetaccount(1);
    $employee = \DB::table('hr_employees')->where('id', $payroll->employee_id)->get()->first();
    $data['payroll'] = $payroll;
    $data['payroll_details'] = $payroll_details;
    $data['employee'] = $employee;
    $data['company'] = $company;

    $pdf = PDF::loadView('__app.components.pages.payslip', $data);
    $pdf_name = str_replace(' ', '_', 'Payslip '.date('Y-m-d', strtotime($payroll->payroll_end_date)).'.pdf');

    $filename = attachments_path().$pdf_name;
    if (file_exists($filename)) {
        unlink($filename);
    }
    $pdf->setTemporaryFolder(attachments_path());
    $pdf->save($filename);

    return response()->download($filename, $pdf_name);
}

function weekdaysRemainingInMonth(Carbon $date)
{
    // Get the current month and year
    $currentMonth = $date->month;
    $currentYear = $date->year;

    // Calculate the last day of the current month
    $lastDayOfMonth = Carbon::create($currentYear, $currentMonth, 1)->endOfMonth();

    // Initialize a counter for weekdays
    $weekdaysCount = 0;

    // Loop through each day from the specified date until the end of the month
    while ($date->lte($lastDayOfMonth)) {
        // Check if the current day is a weekday (Monday to Friday)
        if ($date->isWeekday()) {
            $weekdaysCount++;
        }

        // Move to the next day
        $date->addDay();
    }

    return $weekdaysCount;
}

function getSouthAfricaHolidays($year)
{
    $client = new Google_Client;
    $client->setAuthConfig(base_path().'/google_creds.json'); // Path to your credentials file
    $client->addScope(Google_Service_Calendar::CALENDAR_READONLY);

    $service = new Google_Service_Calendar($client);
    $calendarId = 'en.sa#holiday@group.v.calendar.google.com';

    $params = [
        'calendarId' => $calendarId,
        'timeMin' => $year.'-01-01T00:00:00Z',
        'timeMax' => $year.'-12-31T23:59:59Z',
        'orderBy' => 'startTime',
        'singleEvents' => true,
    ];

    $events = $service->events->listEvents($calendarId, $params)->getItems();

    $holidays = [];

    foreach ($events as $event) {
        if ($event->description == 'Public holiday') {
            $start = $event->start->date;
            $summary = $event->summary;
            $holidays[] = ['holiday_date' => $start, 'title' => $summary];
        }
    }

    return $holidays;
}
function schedule_import_public_holidays()
{
    if (date('m') == '01') {
        $holidays = getSouthAfricaHolidays(date('Y'));
        $next_year_holidays = getSouthAfricaHolidays(date('Y', strtotime('+1 year')));
        foreach ($holidays as $row) {
            $data = (array) $row;
            \DB::table('hr_public_holidays')->insert($data);
        }
        foreach ($next_year_holidays as $row) {
            $data = (array) $row;
            \DB::table('hr_public_holidays')->insert($data);
        }
    }
}

function work_days_month($payroll_date_month_start, $payroll_date_month_end)
{
    $start = new Carbon($payroll_date_month_start);
    $end = new Carbon($payroll_date_month_end);

    $days = $start->diffInDaysFiltered(function (Carbon $date) {
        return $date->isWeekday();
    }, $end);

    return $days;
}

function button_payroll_update_payroll_recon_lastmonth()
{
    if (! is_main_instance()) {
        return false;
    }
    $payroll_date = date('Y-m-25');
    $payroll_date = \DB::table('acc_payroll')->where('payroll_end_date', '<', $payroll_date)->orderBy('payroll_end_date', 'desc')->pluck('payroll_end_date')->first();

    update_payroll($payroll_date);

    return json_alert('Complete');
}

function aftersave_set_daily_rate() {}

function countDays($year, $month, $ignore)
{
    $count = 0;
    $counter = mktime(0, 0, 0, $month, 1, $year);
    while (date('n', $counter) == $month) {
        if (in_array(date('w', $counter), $ignore) == false) {
            $count++;
        }
        $counter = strtotime('+1 day', $counter);
    }

    return $count;
}

function button_payroll_cloudtelecoms_commission($request)
{
    $domain = \DB::table('erp_instances')->where('installed', 1)->where('id', 1)->pluck('domain_name')->first();
    $route = get_menu_url_from_module_id(353);
    $url = 'https://'.$domain.'/'.$route.'?layout_id=1962';

    return redirect()->to($url);
}

function button_payroll_energy_commission($request)
{
    $domain = \DB::table('erp_instances')->where('installed', 1)->where('id', 1)->pluck('domain_name')->first();
    $route = get_menu_url_from_module_id(353);
    $url = 'https://'.$domain.'/'.$route.'?layout_id=1962';

    return redirect()->to($url);
}
function button_payroll_netstream_commission($request)
{
    $domain = \DB::table('erp_instances')->where('installed', 1)->where('id', 1)->pluck('domain_name')->first();
    $route = get_menu_url_from_module_id(353);
    $url = 'https://'.$domain.'/'.$route.'?layout_id=1962';

    return redirect()->to($url);
}
function button_payroll_eldo_commission($request)
{
    $domain = \DB::table('erp_instances')->where('installed', 1)->where('id', 2)->pluck('domain_name')->first();
    $route = get_menu_url_from_module_id(353);
    $url = 'https://'.$domain.'/'.$route.'?layout_id=1962';

    return redirect()->to($url);
}

function schedule_update_payroll_recon()
{
    if (! is_main_instance()) {
        return false;
    }
    $payroll_date = date('Y-m-25');

    update_payroll($payroll_date);
}

function aftersave_calulate_leave_days($request)
{
    try {
        $leave = \DB::table('hr_leave')->where('id', $request->id)->get()->first();

        if ($leave->date_to == $leave->date_from) {
            $days = 1;
        } else {
            $startDate = Carbon::parse($leave->date_from);
            $endDate = Carbon::parse($leave->date_to)->addDays(1);

            $holidays = \DB::table('hr_public_holidays')->where('holiday_date', 'like', date('Y').'-%')->pluck('holiday_date')->toArray();
            foreach ($holidays as $i => $holiday) {
                $holidays[$i] = Carbon::parse($holiday);
            }

            $days = $startDate->diffInDaysFiltered(function (Carbon $date) use ($holidays) {
                return $date->isWeekday() && ! in_array($date, $holidays);
            }, $endDate);
        }

        \DB::table('hr_leave')->where('id', $request->id)->update(['days' => $days]);

        $payroll_date = date('Y-m-25');

        update_payroll($payroll_date);
    } catch (\Throwable $ex) {
        aa($ex->getMessage());
        aa($ex->getTraceAsString());
    }
}

function aftersave_leave_request_email($request)
{
    $leave = \DB::table('hr_leave')->where('id', $request->id)->get()->first();
    if ($leave->approved) {
        $employee = \DB::table('hr_employees')->where('id', $leave->employee_id)->get()->first();
        $data['employee'] = $employee->name;
        $data['leave_date'] = $leave->date_from;
        if ($leave->date_to != $leave->date_from) {
            $data['leave_date'] = $leave->date_from.' to '.$leave->date_to;
        }

        $data['function_name'] = __FUNCTION__;
        //$data['test_debug'] = 1;
        //erp_process_notification(1, $data);
        $user_email = \DB::table('erp_users')->where('id', $employee->user_id)->pluck('email')->first();

        $data['force_to_email'] = $user_email;

        //erp_process_notification(1, $data);
    }
}

function get_period_id($month)
{
    return dbgetcell('acc_periods', 'period', $month, 'id');
}

function copy_payroll()
{
    $prev_date = date('Y-m', strtotime('-1 months'));
    $payrolls = \DB::table('hr_payroll')->where('docdate', 'like', $prev_date.'%')->get();

    foreach ($payrolls as $payroll) {
        unset($payroll->id);
        unset($payroll->deduction_1);
        unset($payroll->deduction_1_amount);
        unset($payroll->deduction_2);
        unset($payroll->deduction_2_amount);
        unset($payroll->addition_1);
        unset($payroll->addition_1_amount);
        unset($payroll->addition_2);
        unset($payroll->addition_2_amount);
        unset($payroll->net_salary);
        $payroll->docdate = date('Y-m-d', strtotime($payroll->docdate));
        $payroll->status = 'Draft';
        dbinsert('hr_payroll', (array) $payroll);
    }
}

///////BEFORESAVE

function beforesave_payroll_calculate($request)
{
    $id = (! empty($request->id)) ? $request->id : null;

    $payroll = (object) $request->all();
    $payroll_status = \DB::table('hr_payroll')->where('id', $payroll->id)->pluck('status')->first();
    if ($payroll_status == 'Complete') {
        // return 'Completed document cannot be changed';
    } else {
        $payroll_status = 'Draft';
    }

    $payroll->status = $payroll_status;
    /**
    http://www.sars.gov.za/Tax-Rates/Income-Tax/Pages/Rates%20of%20Tax%20for%20Individuals.aspx
     */
    $paye_tax_rates = [
        ['income_min' => 0, 'income_max' => 195850, 'tax_amount' => 0, 'tax_rate' => 18],
        ['income_min' => 195851, 'income_max' => 305850, 'tax_amount' => 35253, 'tax_rate' => 26],
        ['income_min' => 305851, 'income_max' => 423300, 'tax_amount' => 63853, 'tax_rate' => 31],
        ['income_min' => 423301, 'income_max' => 555600, 'tax_amount' => 1002630, 'tax_rate' => 36],
        ['income_min' => 555601, 'income_max' => 708310, 'tax_amount' => 147891, 'tax_rate' => 39],
        ['income_min' => 708311, 'income_max' => 1500000, 'tax_amount' => 207448, 'tax_rate' => 41],
        ['income_min' => 1500001, 'income_max' => 9999999, 'tax_amount' => 532041, 'tax_rate' => 46],
    ];

    $paye_tax_threshold = 78150;

    $paye_tax_rebate = 14067;

    /// TAXABLE INCOME

    $paye_taxable_income = $payroll->gross_salary;
    if (! empty($payroll->addition_1_amount)) {
        $paye_taxable_income += $payroll->addition_1_amount;
    }
    if (! empty($payroll->addition_2_amount)) {
        $paye_taxable_income += $payroll->addition_2_amount;
    }
    $paye_taxable_income = currency($paye_taxable_income * 12);

    /// PAYE
    if ($paye_taxable_income > $paye_tax_threshold) {
        foreach ($paye_tax_rates as $tax) {
            if ($paye_taxable_income > $tax['income_min'] && $paye_taxable_income < $tax['income_max']) {
                $paye_tax_base_amount = $tax['tax_amount'];
                if ($tax['income_min'] == 0) {
                    $paye_tax_calculated_amount = ($paye_taxable_income * $tax['tax_rate']) / 100;
                } else {
                    $paye_tax_calculated_amount = (($paye_taxable_income - ($tax['income_min'] + 1)) * $tax['tax_rate']) / 100;
                }
            }
        }
        $paye_tax_payable = $paye_tax_base_amount + $paye_tax_calculated_amount;
        $payroll->paye = currency(($paye_tax_payable - $paye_tax_rebate) / 12);
    } else {
        $payroll->paye = 0;
    }

    /// UIF
    $payroll->uif_employee = currency(($payroll->gross_salary) / 100);
    $payroll->uif_company = currency(($payroll->gross_salary) / 100);
    if ($payroll->uif_employee > '148.72') {
        $payroll->uif_employee = '148.72';
        $payroll->uif_company = '148.72';
    }

    // NET SALARY
    $payroll->net_salary = $payroll->gross_salary;
    if (! empty($payroll->addition_1_amount)) {
        $payroll->net_salary += $payroll->addition_1_amount;
    }
    if (! empty($payroll->addition_2_amount)) {
        $payroll->net_salary += $payroll->addition_2_amount;
    }
    if (! empty($payroll->deduction_1_amount)) {
        $payroll->net_salary -= currency($payroll->deduction_1_amount);
    }
    if (! empty($payroll->deduction_2_amount)) {
        $payroll->net_salary -= currency($payroll->deduction_2_amount);
    }

    $payroll->net_salary -= $payroll->paye;
    $payroll->net_salary -= $payroll->uif_employee;

    $payroll->net_salary = currency($payroll->net_salary);
    foreach ($payroll as $k => $v) {
        Input::merge([$k => $v]);
    }
}
