<?php

function schedule_update_salesboard()
{
    $salesman_ids = get_salesman_user_ids();

    $field_statuses = \DB::table('erp_module_fields')->where('module_id', 1923)->where('field', 'deal_status')->pluck('opts_values')->first();
    $field_statuses = explode(',', $field_statuses);
    $statuses = \DB::table('crm_accounts')->select('deal_status')->groupBy('deal_status')->pluck('deal_status')->filter()->unique()->toArray();
    foreach ($field_statuses as $fs) {
        if (! in_array($fs, $statuses)) {
            $statuses[] = $fs;
        }
    }
    \DB::table('crm_sales_board')->whereNotIn('status', $statuses)->update(['is_deleted' => 1]);
    \DB::table('crm_sales_board')->whereNotIn('salesman_id', $salesman_ids)->update(['is_deleted' => 1]);

    \DB::table('crm_accounts')->update(['sales_board_id' => 0]);
    foreach ($salesman_ids as $salesman_id) {
        foreach ($statuses as $status) {
            if ($status > '') {
                $data = [
                    'status' => $status,
                    'salesman_id' => $salesman_id,
                    'is_deleted' => 0,
                ];
            }
            // $wdata = [
            //     'status' => $status,
            //     'salesman_id' => $salesman_id,
            // ];
            \DB::table('crm_sales_board')->updateOrInsert($data);
            $salesboard_id = \DB::table('crm_sales_board')->where($wdata)->pluck('id')->first();
            \DB::table('crm_accounts')->where($wdata)->update(['sales_board_id' => $salesboard_id]);
        }
    }
}

function schedule_salesboard_sale_stats()
{
    $salesboards = \DB::table('crm_sales_board')->where('is_deleted', 0)->get();
    foreach ($salesboards as $salesboard) {
        update_salesboard_stats($salesboard->id);
    }
}

function update_salesboard_stats($salesboard_id = false)
{

    if ($salesboard_id) {
        $salesboard_ids = [$salesboard_id];
    } else {
        $salesboard_ids = \DB::table('crm_sales_board')->where('is_deleted', 0)->pluck('id')->toArray();
    }
    $yesterday = get_previous_workday();

    foreach ($salesboard_ids as $salesboard_id) {
        $total = \DB::table('crm_accounts')->where('is_deleted', 0)->where('sales_board_id', $salesboard_id)->count();
        $called = \DB::table('crm_accounts')->where('is_deleted', 0)->where('sales_board_id', $salesboard_id)->where('called', 1)->count();
        $not_called = \DB::table('crm_accounts')->where('is_deleted', 0)->where('sales_board_id', $salesboard_id)->where('called', 0)->count();
        $called_today = \DB::table('crm_accounts')->where('sales_board_id', $salesboard_id)->where('last_call', 'like', date('Y-m-d').'%')->count();
        $called_yesterday = \DB::table('crm_accounts')->where('sales_board_id', $salesboard_id)->where('last_call', 'like', $yesterday.'%')->count();
        \DB::table('crm_sales_board')->where('id', $salesboard_id)->update(['total' => $total, 'called_today' => $called_today, 'called_yesterday' => $called_yesterday, 'total_not_called' => $not_called, 'total_called' => $called]);
    }
}

function aftersave_opps_assign_to_salesboard($request)
{
    /*
    if($request->id){
        $wdata = [
            'status' => $request->status,
            'salesman_id' => $request->salesman_id,
        ];
        $salesboard_id = \DB::table('crm_sales_board')->where($wdata)->pluck('id')->first();
        \DB::table('crm_opportunities')->where('id',$request->id)->update(['sales_board_id'=> $salesboard_id]);

        update_salesboard_stats($salesboard_id);
    }
    */
}

/*
//COPY LAYOUTS
  $statuses = \DB::table('erp_module_fields')->where('module_id',1923)->where('field','status')->pluck('opts_values')->first();
    $statuses = explode(',',$statuses);

    $gv1 = \DB::table('erp_grid_views')->where('id',2431)->get()->first();
    $gv2 = \DB::table('erp_grid_views')->where('id',2432)->get()->first();
    foreach($statuses as $s){
        if($s == 'New Enquiry'){
            continue;
        }
        $data1 = (array) $gv1;
        $data2 = (array) $gv2;
       unset($data1['id']);
       unset($data2['id']);
       $data1['name'] = str_replace('New Enquiry',$s,$data1['name']);
       $data2['name'] = str_replace('New Enquiry',$s,$data2['name']);
       $data1['aggrid_state'] = str_replace('New Enquiry',$s,$data1['aggrid_state']);
       $data2['aggrid_state'] = str_replace('New Enquiry',$s,$data2['aggrid_state']);

        \DB::table('erp_grid_views')->insert($data1);
        \DB::table('erp_grid_views')->insert($data2);

    }
*/

/*
swap users
   \DB::table('crm_accounts')->where('salesman_id',5271)->update(['salesman_id' =>4194]);
    \DB::table('crm_opportunities')->where('salesman_id',5271)->update(['salesman_id' =>4194]);


    $layouts = \DB::table('erp_grid_views')->where('name','like','%nani%')->get();

    foreach($layouts as $l){
       $aggrid_state = str_replace('Ahmed Nani', 'Mohammed Kola',$l->aggrid_state);
       $name = str_replace('Nani', 'Kola',$l->name);
       $name = str_replace('nani', 'kola',$name);

       \DB::table('erp_grid_views')->where('id',$l->id)->update(['aggrid_state'=>$aggrid_state]);
    }
*/
