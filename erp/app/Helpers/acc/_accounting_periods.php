<?php

function button_rebuild_ledger_totals($request)
{
    $periods = \DB::table('acc_periods')->where('period', '<=', date('Y-m'))->orderBy('period', 'desc')->limit(1)->pluck('period')->toArray();
    update_ledger_totals($periods);

    return json_alert('Done');
}

function schedule_calculate_ledger_totals()
{
    $periods = \DB::table('acc_periods')->where('period', '<=', date('Y-m'))->orderBy('period', 'desc')->limit(24)->pluck('period')->toArray();
    update_ledger_totals($periods);
}

function aftersave_ledger_accounts_update_ledger_totals()
{

    //schedule_calculate_ledger_totals();
}

function schedule_period_check()
{
    $next_accounting_period = date('Y', strtotime('+1 year'));
    $accounting_period_exists = \DB::table('acc_accounting_periods')->where('accounting_period', $next_accounting_period)->count();
    if (! $accounting_period_exists) {
        \DB::table('acc_accounting_periods')->insert(['accounting_period' => $next_accounting_period]);
    }

    $period = date('Y-m');
    $next_period = date('Y-m', strtotime('+ 5 months'));
    for ($i = 0; $i < 5; $i++) {
        if ($i == 0) {
            $next_period = date('Y-m');
        } else {
            $next_period = date('Y-m', strtotime('+ '.$i.' months'));
        }

        $exists = \DB::table('acc_periods')->where('period', $next_period)->count();
        if (! $exists) {
            dbinsert('acc_periods', ['period' => $next_period, 'status' => 'Open']);
        }
    }

    $open_periods = [
        date('Y-m', strtotime('-1 month')),
        date('Y-m'),
        date('Y-m', strtotime('+1 month')),
    ];
    $instance_connections = DB::table('erp_instances')->where('installed', 1)->pluck('db_connection')->toArray();
    foreach ($instance_connections as $conn) {
        \DB::connection($conn)->table('acc_periods')->whereIn('period', $open_periods)->update(['status' => 'Open']);
        \DB::connection($conn)->table('acc_periods')->whereNotIn('period', $open_periods)->update(['status' => 'Closed']);
    }
}

function accounting_year_active($docdate)
{
    $year = date('Y', strtotime($docdate));
    $year_active = \DB::table('acc_accounting_periods')->where('locked', 0)->where('accounting_period', $year)->count();
    if (! $year_active) {
        return false;
    }

    return true;
}

function accounting_month_active($docdate)
{

    if (! accounting_year_active($docdate)) {
        return false;
    }

    $month = date('Y-m', strtotime($docdate));

    $month_active = \DB::table('acc_periods')->where('period', $month)->where('status', 'Open')->count();

    if (! $month_active) {
        return false;
    }

    return true;
}

function open_periods()
{
    $years = \DB::table('acc_accounting_periods')->where('locked', 0)->pluck('accounting_period')->toArray();
    foreach ($years as $year) {
        \DB::table('acc_periods')->where('period', 'LIKE', $year.'%')->update(['status' => 'Open']);
    }
}

function reset_open_periods()
{
    $open_periods = [
        date('Y-m'),
        date('Y-m', strtotime('- 1 month')),
        date('Y-m', strtotime('+ 1 month')),
    ];

    \DB::table('acc_periods')->update(['status' => 'Closed']);
    \DB::table('acc_periods')->whereIn('period', $open_periods)->update(['status' => 'Open']);
}

function generate_acc_periods($start_date, $duration = '5 years')
{
    $start_date = '2016-01-01';
    $start_date = date('Y-m-d', strtotime($start_date));
    $end_date = date('Y-m-d', strtotime('+1 year'));

    while ($start_date < $end_date) {
        $accounting_period = date('Y', strtotime($start_date));
        \DB::table('acc_accounting_periods')->insert(['accounting_period' => $accounting_period]);
        $start_date = date('Y-m-d', strtotime($start_date.'+1 year'));
    }

    $start_date = date('Y-m-d', strtotime($start_date));
    $end_date = date('Y-m-d', strtotime($start_date.' + '.$duration));
    while ($start_date < $end_date) {
        $period = date('Y-m', strtotime($start_date));
        $status = ($period <= date('Y-m')) ? 'Open' : 'Closed';
        dbinsert('acc_periods', ['period' => $period, 'status' => $status]);
        $start_date = date('Y-m-d', strtotime($start_date.' + 1 month'));
    }
}

function schedule_ledger_accounts_set_targets()
{
    $ledger_accounts = \DB::table('acc_ledger_accounts')->get();
    foreach ($ledger_accounts as $ledger_account) {
        if (in_array($ledger_account->ledger_account_category_id, [30, 31, 40])) {
            // 30 - Fixed Assets
            // 31 - Current Assets
            // 40 - Current Liabilities
            continue;
        } elseif ($ledger_account->ledger_account_category_id == 21) {
            // Transactional Expenses
            $target = get_transactional_expenses_target($ledger_account->id);
        } elseif ($ledger_account->id == 34) {
            // Cost of Sales
            $target = get_cost_of_sales_target();
        } elseif ($ledger_account->id == 128) {
            // Cost of Services
            $target = get_cost_of_services_target();
        } else {
            $target_three_months = \DB::table('acc_ledger_totals')
                ->where('ledger_account_id', $ledger_account->id)
                ->where('period', '<', date('Y-m-01'))
                ->where('period', '>=', date('Y-m-01', strtotime('-3 months')))
                ->sum('total');
            $target = ($target_three_months != 0) ? $target_three_months / 3 : 0;
        }

        \DB::table('acc_ledger_accounts')->where('id', $ledger_account->id)->update(['calculated_target' => $target]);
    }
}

function get_transactional_expenses_target($ledger_account_id, $period = false)
{
    if (! $period) {
        $period = date('Y-m');
    } else {
        $period = date('Y-m', strtotime($period));
    }
    $last_period = date('Y-m', strtotime($period.'-01 -1 month'));
    // Make cost of sales target - 50% of sales target

    $lastmonth_total = \DB::table('acc_ledger_totals')->where('period', $last_period)->where('ledger_account_id', $ledger_account_id)->pluck('total')->first();
    $sales = \DB::table('acc_ledger_totals')->where('period', $last_period)->where('ledger_account_id', 1)->sum('total');
    $percentage = abs($lastmonth_total) / $sales;

    $current_sales = \DB::table('acc_ledger_totals')->where('period', $period)->where('ledger_account_id', 1)->sum('total');

    $target = $current_sales * $percentage;

    return $target;
}

function get_cost_of_sales_target($period = false)
{
    if (! $period) {
        $period = date('Y-m');
    } else {
        $period = date('Y-m', strtotime($period));
    }
    $last_period = date('Y-m', strtotime($period.'-01 -1 month'));
    // Make cost of sales target - 50% of sales target
    $sales_total = \DB::table('acc_ledger_totals')->where('period', $period)->where('ledger_account_id', 1)->sum('total');
    $target = $sales_total / 2;

    /*
    $cost_of_sales = \DB::table('acc_ledger_totals')->where('period',$last_period)->where('ledger_account_id',34)->pluck('total')->first();
    $sales = \DB::table('acc_ledger_totals')->where('period',$last_period)->where('ledger_account_id',1)->sum('total');
    $percentage = abs($cost_of_sales) / $sales;

    $current_sales_total = \DB::table('acc_ledger_totals')->where('period',$period)->where('ledger_account_id',1)->sum('target');

    $target = $current_sales_total * $percentage;
    */
    return $target * -1;
}

function get_cost_of_services_target($period = false)
{
    if (! $period) {
        $period = date('Y-m');
    } else {
        $period = date('Y-m', strtotime($period));
    }
    $last_period = date('Y-m', strtotime($period.'-01 -1 month'));

    // Make cost of sales target - 50% of sales target
    $sales_total = \DB::table('acc_ledger_totals')->where('period', $period)->where('ledger_account_id', 54)->sum('total');
    $target = $sales_total / 2;

    /*
    $cost_of_sales = \DB::table('acc_ledger_totals')->where('period',$last_period)->where('ledger_account_id',128)->pluck('total')->first();
    $sales = \DB::table('acc_ledger_totals')->where('period',$last_period)->where('ledger_account_id',54)->sum('total');
    $percentage = abs($cost_of_sales) / $sales;

    $current_sales_total = \DB::table('acc_ledger_totals')->where('period',$period)->where('ledger_account_id',54)->sum('target');

    $target = $current_sales_total * $percentage;
    */
    return $target * -1;
}

function update_ledger_totals_by_year($year)
{
    $periods = \DB::table('acc_periods')->where('period', 'like', $year.'%')->where('period', '<=', date('Y-m-01'))->pluck('period')->toArray();
    update_ledger_totals($periods);
}

function update_ledger_totals($dates)
{
    if (! empty($dates) && is_array($dates) && count($dates) > 0) {
        $periods = [];
        foreach ($dates as $date) {
            $periods[] = date('Y-m', strtotime($date));
        }

        $ledger_accounts = \DB::table('acc_ledger_accounts')->get();
        $active_account_ids = \DB::table('crm_accounts')->where('partner_id', 1)->where('status', '!=', 'Deleted')->pluck('id')->toArray();
        $periods = collect($periods)->unique()->filter()->toArray();

        foreach ($periods as $period) {
            \DB::table('acc_ledger_totals')->where('period', $period)->delete();
            $period_date = date('Y-m-t', strtotime($period.'-01'));
            foreach ($ledger_accounts as $ledger_account) {
                if ($ledger_account->id == 5) {
                    $running_total = \DB::table('acc_ledgers')->whereIn('account_id', $active_account_ids)->where('ledger_account_id', $ledger_account->id)->where('docdate', '<=', date('Y-m-t', strtotime($period.'-01')))->sum('amount');

                } else {
                    $running_total = \DB::table('acc_ledgers')->where('ledger_account_id', $ledger_account->id)->where('docdate', '<=', date('Y-m-t', strtotime($period.'-01')))->sum('amount');

                }
                $total = \DB::table('acc_ledgers')->where('ledger_account_id', $ledger_account->id)->where('docdate', 'LIKE', $period.'%')->sum('amount');

                $target = $ledger_account->target;
                /*
                if($period == date('Y-m')){
                    $target = $ledger_account->target;
                }elseif(in_array($ledger_account->ledger_account_category_id,[30,31,40])){
                    // 30 - Fixed Assets
                    // 31 - Current Assets
                    // 40 - Current Liabilities
                    $target = $ledger_account->target;
                }elseif($ledger_account->ledger_account_category_id == 21){
                    // Transactional Expenses
                    $target = get_transactional_expenses_target($ledger_account->id,$period);
                }elseif($ledger_account->id == 34){
                    // Cost of Sales
                    $target = get_cost_of_sales_target($period);
                }elseif($ledger_account->id == 128){
                    // Cost of Services
                    $target = get_cost_of_services_target($period);
                }else{
                    $target_three_months = \DB::table('acc_ledger_totals')
                    ->where('ledger_account_id', $ledger_account->id)
                    ->where('period', '<', date('Y-m-01'))
                    ->where('period', '>=', date('Y-m-01', strtotime('-3 months')))
                    ->sum('total');
                    $target = ($target_three_months!=0) ? $target_three_months/3 : 0;
                }
                */
                $target = currency($target);
                if ($total != 0) {
                    $total = currency($total * -1);
                }
                $difference = 0;

                $difference = $total - $target;

                if ($difference != 0) {
                    $difference = $difference * -1;
                }
                $target_achieved = 0;
                if ($target < 0 && $total <= $target) {
                    $target_achieved = 1;
                }
                if ($target >= 0 && $total >= $target) {
                    $target_achieved = 1;
                }

                $total_days = intval(date('t', strtotime($period)));

                if ($difference != 0) {
                    $difference = $difference * -1;
                }

                $target_achieved = 0;
                if ($target < 0 && $total >= $target) {
                    $target_achieved = 1;
                }
                if ($target >= 0 && $total >= $target) {
                    $target_achieved = 1;
                }

                $difference = currency($difference);

                //$financial_year
                $month = date('m', strtotime($period));

                $month = (int) $month;

                $start_year = date('Y', strtotime($period));
                if ($month < 3) {
                    $start_year = date('Y', strtotime($period.' -1 year'));
                }

                $start_date = $start_year.'-03';

                $financial_year = $start_date.' to '.date('Y-02', strtotime($start_date.'-01'.' +1 year'));

                $data = [
                    'ledger_account_id' => $ledger_account->id,
                    'ledger_account_category_id' => $ledger_account->ledger_account_category_id,
                    'period_year' => date('Y', strtotime($period)),
                    'period_month' => date('m', strtotime($period)),
                    'period' => date('Y-m', strtotime($period)),
                    'total' => $total,
                    'running_total' => $running_total,
                    'target' => $target,
                    'difference' => $difference,
                    'target_achieved' => $target_achieved,
                    'period_date' => date('Y-m-01', strtotime($period)),
                    'financial_year' => $financial_year,
                ];
                \DB::table('acc_ledger_totals')->insert($data);
            }
        }
    }

    $ledger_totals = \DB::table('acc_ledger_totals')->get();
    foreach ($ledger_totals as $ledger_total) {
        \DB::table('acc_ledgers')
            ->where('docdate', 'like', date('Y-m', strtotime($ledger_total->period)).'%')
            ->where('ledger_account_id', $ledger_total->ledger_account_id)
            ->update(['ledger_total_id' => $ledger_total->id]);
    }
    set_ledger_totals_financial_year();

    $accounts = \DB::table('acc_ledger_accounts')->get();
    foreach ($accounts as $account) {
        $target_three_months = \DB::table('acc_ledger_totals')
            ->where('ledger_account_id', $account->id)
            ->where('period', '<', date('Y-m-01'))
            ->where('period', '>=', date('Y-m-01', strtotime('-3 months')))
            ->sum('total');
        $target = ($target_three_months != 0) ? $target_three_months / 3 : 0;

        \DB::table('acc_ledger_accounts')->where('id', $account->id)->update(['target' => $target]);
    }

}

function set_ledger_totals_financial_year()
{

    $periods = \DB::table('acc_ledger_totals')->pluck('period')->unique()->toArray();
    foreach ($periods as $period) {

        $month = date('m', strtotime($period));

        $month = (int) $month;

        $start_year = date('Y', strtotime($period));
        if ($month < 3) {
            $start_year = date('Y', strtotime($period.' -1 year'));
        }

        $start_date = $start_year.'-03';

        $financial_year = $start_date.' to '.date('Y-02', strtotime($start_date.'-01'.' +1 year'));

        \DB::table('acc_ledger_totals')->where('period', $period)->update(['financial_year' => $financial_year, 'period_date' => $period.'-01']);
    }

}

function button_update_ledger_totals($request)
{
    schedule_calculate_ledger_totals();

    return json_alert('Done');
}

function schedule_income_statement_update()
{
    income_statement_update(date('Y-m'));
}

function income_statement_update($period = false)
{
    if (! is_main_instance()) {
        return false;
    }
    $instances = \DB::table('erp_instances')->where('installed', 1)->where('installed', 1)->get();

    if (! $period) {
        \DB::table('acc_income_statement')->truncate();
        $periods = \DB::table('acc_periods')->where('period', '<=', date('Y-m'))->orderBy('period', 'desc')->limit(12)->pluck('period')->toArray();
    } else {
        \DB::table('acc_income_statement')->where('period', date('Y-m', strtotime($period)))->delete();
        $periods = [date('Y-m', strtotime($period))];
    }

    foreach ($instances as $instance) {
        $conn = $instance->db_connection;
        $company = $instance->name;

        $ledger_accounts = \DB::connection($conn)->table('acc_ledger_accounts as la')
            ->select('la.id', 'la.name', 'la.target', 'lac.category', 'lac.sort_order')
            ->join('acc_ledger_account_categories as lac', 'la.ledger_account_category_id', '=', 'lac.id')
            ->get();

        foreach ($periods as $period) {
            foreach ($ledger_accounts as $ledger_account) {
                $total = \DB::connection($conn)->table('acc_ledgers')->where('ledger_account_id', $ledger_account->id)->where('docdate', 'LIKE', $period.'%')->sum('amount');
                $target = currency($ledger_account->target);

                $total = currency($total * -1);
                $difference = 0;

                $difference = $total - $target;

                $difference = currency($difference);
                $data = [
                    'ledger_account' => $ledger_account->name,
                    'ledger_account_category' => $ledger_account->sort_order.' '.$ledger_account->category,
                    'company' => $company,
                    'period_year' => date('Y', strtotime($period)),
                    'period_month' => date('m', strtotime($period)),
                    'period' => date('Y-m', strtotime($period)),
                    'total' => $total,
                    'target' => $target,
                    'difference' => $difference,
                ];
                \DB::table('acc_income_statement')->insert($data);
            }
        }
    }
}
