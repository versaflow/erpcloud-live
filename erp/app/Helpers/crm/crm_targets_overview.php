<?php

function schedule_leads_overview_update(){
    
    $lead_growth_percentage = get_admin_setting('lead_growth_percentage');
   
    $time_stamp = date('Y-m-d H:i:s');
    $db_user = get_user_id_default();
    \DB::table('crm_leads_overview')->update(['is_deleted' => 1]);
    $months = \DB::select("SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') AS unique_month
    FROM crm_accounts
    ORDER BY created_at ASC;");
 
    foreach($months as $month){
        $date = $month->unique_month;
        $period = $date.'-01';
        
        $num_leads = \DB::table('crm_accounts')
        ->where('created_at','LIKE',$date.'%')->where('partner_id',1)->count();
       
      
        $range_end = $period;
        $range_start = date('Y-m-01',strtotime($range_end.' - 12 months'));
        
        $num_leads_12_months = \DB::table('crm_accounts')
        ->where('partner_id',1)
        ->where('created_at','>=',$range_start)->where('created_at','<',$range_end)->count();
     
        $target = ($num_leads_12_months) / 12;
        $target_growth = $target * currency('1.'.$lead_growth_percentage);
        $target_achieved = ($num_leads > $target_growth) ? 1 : 0;
      
       
        $data = [
            'created_at' => $time_stamp,
            'updated_at' => $time_stamp, 
            'created_by' => $db_user,
            'updated_by' => $db_user,
            'period' => $period,
            'is_deleted' => 0,
            'num_leads' => $num_leads,
            'target' => $target_growth,
            'target_achieved' => $target_achieved,
            
        ];
        
        $w_data = [
            'period' => $date.'-01',
        ];
      
        \DB::table('crm_leads_overview')->updateOrInsert($w_data,$data);
        
    }
}

function schedule_sales_overview_update(){
    
    $saleman_ids = get_salesman_user_ids();
    $total_salesman = count($saleman_ids);
    $year = date('Y');
    $sales_growth_percentage = get_admin_setting('sales_growth_percentage');
   
    $time_stamp = date('Y-m-d H:i:s');
    $db_user = get_user_id_default();
    \DB::table('crm_sales_overview')->where('period','like',$year.'%')->update(['is_deleted' => 1]);
  
 
    $months = \DB::select("SELECT DISTINCT DATE_FORMAT(docdate, '%Y-%m') AS unique_month
    FROM crm_documents
    where docdate like '".$year."-%'
    ORDER BY docdate ASC;");
   
    foreach($months as $month){
        $date = $month->unique_month;
        $period = $date.'-01';
        
        $total_sales = \DB::table('crm_document_lines')
        ->join('crm_documents','crm_documents.id','=','crm_document_lines.document_id')
        ->where('docdate','LIKE',$date.'%')->whereIn('doctype',['Tax Invoice','Credit Note'])->sum('zar_sale_total');
       
       
        $lytm = date('Y-m',strtotime($period.' - 1 year'));
        $target_lytm = \DB::table('crm_document_lines')
        ->join('crm_documents','crm_documents.id','=','crm_document_lines.document_id')
        ->where('docdate','LIKE',$lytm.'%')->whereIn('doctype',['Tax Invoice','Credit Note'])->sum('zar_sale_total');
       
        $target_lytm_achieved = ($total_sales > $target_lytm) ? 1 : 0;
        
      
        $range_end = $period;
        $range_start = date('Y-m-01',strtotime($range_end.' - 3 months'));
       
        $target_3_months = \DB::table('crm_document_lines')
        ->join('crm_documents','crm_documents.id','=','crm_document_lines.document_id')
        ->where('docdate','>=',$range_start)->where('docdate','<',$range_end)->whereIn('doctype',['Tax Invoice','Credit Note'])->sum('zar_sale_total');
        
        $target_3_months = ($target_3_months) / 3;
        $target_3_months_achieved = ($total_sales > $target_3_months) ? 1 : 0;
        
        $range_end = $period;
        $range_start = date('Y-m-01',strtotime($range_end.' - 12 months'));
        
        $target_12_months = \DB::table('crm_document_lines')
        ->join('crm_documents','crm_documents.id','=','crm_document_lines.document_id')
        ->where('docdate','>=',$range_start)->where('docdate','<',$range_end)->whereIn('doctype',['Tax Invoice','Credit Note'])->sum('zar_sale_total');
      
        $target_12_months = ($target_12_months) / 12;
        $target_12_months_achieved = ($total_sales > $target_12_months) ? 1 : 0;
        
        $total_non_billing_sales = \DB::table('crm_documents')
        ->where('billing_type','')
        ->where('docdate','LIKE',$date.'%')->where('doctype','Tax Invoice')->sum('total');
        
        $range_end = $period;
        $range_start = date('Y-m-01',strtotime($range_end.' - 3 months'));
        
        $incentive_target = \DB::table('crm_document_lines')
        ->join('crm_documents','crm_documents.id','=','crm_document_lines.document_id')
        ->where('billing_type','')
        ->where('docdate','>=',$range_start)->where('docdate','<',$range_end)->whereIn('doctype',['Tax Invoice','Credit Note'])->sum('zar_sale_total');
      
        $incentive_target = ($incentive_target) / 3;
      
        $sgp = floatval('1.'.$sales_growth_percentage);
        $incentive_target = $incentive_target * $sgp;
      
        $incentive_target_achieved = ($total_non_billing_sales > $incentive_target) ? 1 : 0;
       
       
        $data = [
            'created_at' => $time_stamp,
            'updated_at' => $time_stamp, 
            'created_by' => $db_user,
            'updated_by' => $db_user,
            'period' => $period,
            'total_sales' => $total_sales,
            'is_deleted' => 0,
            'target_lytm' => $target_lytm,
            'target_lytm_achieved' => $target_lytm_achieved,
            'target_3_months' => $target_3_months,
            'target_3_months_achieved' => $target_3_months_achieved,
            'target_12_months' => $target_12_months,
            'target_12_months_achieved' => $target_12_months_achieved,
            'total_non_billing_sales' => $total_non_billing_sales,
            'incentive_target' => $incentive_target,
            'incentive_target_achieved' => $incentive_target_achieved,
        ];
        
        $w_data = [
            'period' => $date.'-01',
        ];
        \DB::table('crm_sales_overview')->updateOrInsert($w_data,$data);
        
        $monthly_target = $incentive_target;
     
        
        // set targets on documents table for sales report
        
        $monthly_target_per_salesman = ($total_salesman > 0) ? ($monthly_target/$total_salesman) : $monthly_target;
            
        \DB::table('crm_documents')->where('docdate_month',$date.'-01')->update(['monthly_target_per_salesman'=>$monthly_target_per_salesman,'monthly_target'=>$monthly_target]);
        $docdates = \DB::table('crm_documents')
        ->select('docdate','id','salesman_id')->whereIn('doctype',['Tax Invoice','Credit Note'])
        ->where('docdate_month',$date.'-01')->orderBy('docdate')->orderBy('id')->pluck('docdate')->toArray();
       
       
        $total_daily_target = 0;
        $total_daily_target_difference = 0;
        $loop_date = '';
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, date('m',strtotime($period)),date('Y',strtotime($period)));
        foreach($docdates as $docdate) {
            $monthly_sales = \DB::table('crm_document_lines')
            ->join('crm_documents','crm_documents.id','=','crm_document_lines.document_id')
            ->where('docdate',$docdate)->whereIn('doctype',['Tax Invoice','Credit Note'])->sum('zar_sale_total');
         
            
            $daily_target = $monthly_target/$daysInMonth;
           
           
            
            if($loop_date != $d->docdate){
                $total_sales+=$monthly_sales;
                $total_daily_target+=$daily_target;
                $total_daily_target_difference = $total_sales - $total_daily_target;
                $loop_date = $d->docdate;
            }
           
            $monthly_target_achieved = ($total_sales > $target_12_months) ? 1 : 0;
           
            $up_data = [
                'monthly_sales'=>$total_sales,
                'monthly_target_achieved'=>$monthly_target_achieved,
                'daily_target'=>$total_daily_target,
                'daily_target_difference'=>$total_daily_target_difference,
            ];
            
            \DB::table('crm_documents')->where('docdate',$docdate)->update($up_data);
        }
        
    }
    
}



function sales_targets_update_current_month(){
    
    $saleman_ids = get_salesman_user_ids();
    $total_salesman = count($saleman_ids);
    $year = date('Y');
    $month = date('Y-m');
    $sales_growth_percentage = get_admin_setting('sales_growth_percentage');
   
    $time_stamp = date('Y-m-d H:i:s');
    $db_user = get_user_id_default();
    \DB::table('crm_sales_overview')->where('period','like',$month.'%')->update(['is_deleted' => 1]);
  
 
    $months = \DB::select("SELECT DISTINCT DATE_FORMAT(docdate, '%Y-%m') AS unique_month
    FROM crm_documents
    where docdate like '".$month."-%'
    ORDER BY docdate ASC;");
   
    foreach($months as $month){
        $date = $month->unique_month;
        $period = $date.'-01';
        
        $total_sales = \DB::table('crm_document_lines')
        ->join('crm_documents','crm_documents.id','=','crm_document_lines.document_id')
        ->where('docdate','LIKE',$date.'%')->whereIn('doctype',['Tax Invoice','Credit Note'])->sum('zar_sale_total');
       
       
        $lytm = date('Y-m',strtotime($period.' - 1 year'));
        $target_lytm = \DB::table('crm_document_lines')
        ->join('crm_documents','crm_documents.id','=','crm_document_lines.document_id')
        ->where('docdate','LIKE',$lytm.'%')->whereIn('doctype',['Tax Invoice','Credit Note'])->sum('zar_sale_total');
       
        $target_lytm_achieved = ($total_sales > $target_lytm) ? 1 : 0;
        
      
        $range_end = $period;
        $range_start = date('Y-m-01',strtotime($range_end.' - 3 months'));
       
        $target_3_months = \DB::table('crm_document_lines')
        ->join('crm_documents','crm_documents.id','=','crm_document_lines.document_id')
        ->where('docdate','>=',$range_start)->where('docdate','<',$range_end)->whereIn('doctype',['Tax Invoice','Credit Note'])->sum('zar_sale_total');
        
        $target_3_months = ($target_3_months) / 3;
        $target_3_months_achieved = ($total_sales > $target_3_months) ? 1 : 0;
        
        $range_end = $period;
        $range_start = date('Y-m-01',strtotime($range_end.' - 12 months'));
        
        $target_12_months = \DB::table('crm_document_lines')
        ->join('crm_documents','crm_documents.id','=','crm_document_lines.document_id')
        ->where('docdate','>=',$range_start)->where('docdate','<',$range_end)->whereIn('doctype',['Tax Invoice','Credit Note'])->sum('zar_sale_total');
      
        $target_12_months = ($target_12_months) / 12;
        $target_12_months_achieved = ($total_sales > $target_12_months) ? 1 : 0;
        
         $total_non_billing_sales = \DB::table('crm_documents')
        ->where('billing_type','')
        ->where('docdate','LIKE',$date.'%')->where('doctype','Tax Invoice')->sum('total');
        
        $range_end = $period;
        $range_start = date('Y-m-01',strtotime($range_end.' - 12 months'));
        
        $incentive_target = \DB::table('crm_document_lines')
        ->join('crm_documents','crm_documents.id','=','crm_document_lines.document_id')
        ->where('billing_type','')
        ->where('salesman_id','>','')
        ->where('docdate','>=',$range_start)->where('docdate','<',$range_end)->whereIn('doctype',['Tax Invoice','Credit Note'])->sum('zar_sale_total');
      
        $incentive_target = ($incentive_target) / 12;
      
        $sgp = floatval('1.'.$sales_growth_percentage);
        $incentive_target = $incentive_target * $sgp;
      
        $incentive_target_achieved = ($total_non_billing_sales > $incentive_target) ? 1 : 0;
       
       
        $data = [
            'created_at' => $time_stamp,
            'updated_at' => $time_stamp, 
            'created_by' => $db_user,
            'updated_by' => $db_user,
            'period' => $period,
            'total_sales' => $total_sales,
            'is_deleted' => 0,
            'target_lytm' => $target_lytm,
            'target_lytm_achieved' => $target_lytm_achieved,
            'target_3_months' => $target_3_months,
            'target_3_months_achieved' => $target_3_months_achieved,
            'target_12_months' => $target_12_months,
            'target_12_months_achieved' => $target_12_months_achieved,
            'total_non_billing_sales' => $total_non_billing_sales,
            'incentive_target' => $incentive_target,
            'incentive_target_achieved' => $incentive_target_achieved,
        ];
        
        $w_data = [
            'period' => $date.'-01',
        ];
        \DB::table('crm_sales_overview')->updateOrInsert($w_data,$data);
        
        $monthly_target = $incentive_target;
     
        
        // set targets on documents table for sales report
        
        $monthly_target_per_salesman = ($total_salesman > 0) ? ($monthly_target/$total_salesman) : $monthly_target;
            
        \DB::table('crm_documents')->where('docdate_month',$date.'-01')->update(['monthly_target_per_salesman'=>$monthly_target_per_salesman,'monthly_target'=>$monthly_target]);
        $docdates = \DB::table('crm_documents')
        ->select('docdate','id','salesman_id')->whereIn('doctype',['Tax Invoice','Credit Note'])
        ->where('docdate_month',$date.'-01')->orderBy('docdate')->orderBy('id')->pluck('docdate')->toArray();
       
       
        $total_daily_target = 0;
        $total_daily_target_difference = 0;
        $loop_date = '';
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, date('m',strtotime($period)),date('Y',strtotime($period)));
        foreach($docdates as $docdate) {
            $monthly_sales = \DB::table('crm_document_lines')
            ->join('crm_documents','crm_documents.id','=','crm_document_lines.document_id')
            ->where('docdate',$docdate)->whereIn('doctype',['Tax Invoice','Credit Note'])->sum('zar_sale_total');
         
            
            $daily_target = $monthly_target/$daysInMonth;
           
           
            
            if($loop_date != $d->docdate){
                $total_sales+=$monthly_sales;
                $total_daily_target+=$daily_target;
                $total_daily_target_difference = $total_sales - $total_daily_target;
                $loop_date = $d->docdate;
            }
           
            $monthly_target_achieved = ($total_sales > $target_12_months) ? 1 : 0;
           
            $up_data = [
                'monthly_sales'=>$total_sales,
                'monthly_target_achieved'=>$monthly_target_achieved,
                'daily_target'=>$total_daily_target,
                'daily_target_difference'=>$total_daily_target_difference,
            ];
            
            \DB::table('crm_documents')->where('docdate',$docdate)->update($up_data);
        }
        
    }
    
}
