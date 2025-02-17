<?php

function schedule_ipranges_stop_ad_emails(){
    
    if(is_main_instance()){
         
        $loa_emails = \DB::table('isp_data_ip_ranges')->where('is_deleted',0)->where('loa_email','>','')->pluck('loa_email')->unique()->toArray();
        foreach($loa_emails as $loa_email){
            $ip_ranges = \DB::table('isp_data_ip_ranges')->where('is_deleted',0)->where('loa_email',$loa_email)->pluck('ip_range')->unique()->toArray();
            $subject = 'Stop Advertising';
            $var = 'Hi,
            
            Please stop advertising:
            '.implode(PHP_EOL,$ip_ranges);
            $function_variables = get_defined_vars();
            $data['internal_function'] = 'debug_email';
            $data['exception_email'] = true;
        
            $data['form_submit'] = 1;
            $data['formatted'] = 1;
            $data['message'] = nl2br($var);
            $data['to_email'] = $loa_email;
            $data['cc_email'] = 'ahmed@telecloud.co.za';
            //$data['test_debug'] = 1;
            //$data['to_email'] = 'gustaf@telecloud.co.za';
            erp_process_notification(1, $data, $function_variables);
        }
    }
}


function schedule_set_router_object_status_match(){
    
    \DB::table('isp_data_ip_ranges')->where('ip_range',$s->detail)->update(['subscription_id' => 0,'subscription_status' => '']); 
    $subs = \DB::table('sub_services')->where('status','!=','Deleted')->whereIn('provision_type',['ip_range_gateway','ip_range_route'])->get();
    foreach($subs as $s){
        \DB::table('isp_data_ip_ranges')->where('ip_range',$s->detail)->update(['subscription_id' => $s->id,'subscription_status' => $s->status]);    
    }
   // \DB::table('isp_data_ip_ranges')->where('gateway','DISABLED')->where('loa_as_number','328227')->update(['router_object_status'=>'']);
    \DB::statement("UPDATE isp_data_ip_ranges SET router_object_status_match=0 WHERE router_object_status!=subscription_status");
    \DB::statement("UPDATE isp_data_ip_ranges SET router_object_status_match=1 WHERE router_object_status=subscription_status");

}

function aftersave_set_router_object_status_match(){
    \DB::table('isp_data_ip_ranges')->where('ip_range',$s->detail)->update(['subscription_id' => 0,'subscription_status' => '']); 
    $subs = \DB::table('sub_services')->where('status','!=','Deleted')->whereIn('provision_type',['ip_range_gateway','ip_range_route'])->get();
    foreach($subs as $s){
        \DB::table('isp_data_ip_ranges')->where('ip_range',$s->detail)->update(['subscription_id' => $s->id,'subscription_status' => $s->status]);    
    }
    //\DB::table('isp_data_ip_ranges')->where('gateway','DISABLED')->where('loa_as_number','328227')->update(['router_object_status'=>'']);
    \DB::statement("UPDATE isp_data_ip_ranges SET router_object_status_match=0 WHERE router_object_status!=subscription_status");
    \DB::statement("UPDATE isp_data_ip_ranges SET router_object_status_match=1 WHERE router_object_status=subscription_status");
}

function button_ipranges_auth_pdf($request){

    $iprange = \DB::table('isp_data_ip_ranges')->where('id',$request->id)->get()->first();
    $file = 'IP Authorization Letter '.str_replace('/24','',$iprange->ip_range).'.pdf';
    $filename = storage_path('exports').'/'.$file;
    if(file_exists($filename)){
        unlink($filename);    
    }
    $admin = dbgetaccount(1);
    $customer = dbgetaccount($iprange->account_id);
    $data = [
        'admin' => $admin,
        'helpdesk_email' => get_admin_setting('notification_support'),
        'customer' => $customer,
        'iprange' => $iprange
    ];
    $data['loa_company'] = (!empty($iprange->loa_company)) ? $iprange->loa_company : $customer->company;
    $data['logo_path'] = uploads_settings_path().$admin->logo;
    $data['logo'] = settings_url().$admin->logo;
    $pdf = PDF::loadView('__app.exports.ipranges_loa', $data);
    $options = [
        'orientation' => 'portrait',
        'encoding' => 'UTF-8',
        'footer-left' => 'Statement | '.$account->company,
        'footer-right' => date('Y-m-d').' | Page [page] of [topage]',
        'footer-font-size' => 8,
    ];

    //return view('__app.exports.ipranges_loa', $data);

    $pdf->setOptions($options);

    $pdf->setTemporaryFolder(attachments_path());
            $pdf->save($filename);

    return response()->download($filename, $file);

}


function button_ipranges_reset_test_period(){
    \DB::table('isp_data_ip_ranges')->where('subscription_id','>',0)->update(['test_expiry'=>null]);
    \DB::table('isp_data_ip_ranges')->where('subscription_id',0)->update(['test_expiry'=>date('Y-m-d',strtotime('+3 days'))]);
    return json_alert('Done');
}

function schedule_ipranges_test_ranges_expiry_check(){
    $ip_ranges = \DB::table('isp_data_ip_ranges')
    ->where('is_deleted',0)
    ->where('subscription_id',0)
    ->where('test_expiry','<=',date('Y-m-d'))
    ->update(['account_id'=>0,'gateway'=>'DISABLED','test_expiry'=>null]);

    $ip_ranges = \DB::table('isp_data_ip_ranges')->where('account_id',0)->where('is_deleted',0)->where('subscription_id',0)->get();
    foreach($ip_ranges as $ip_range){

        $name = '';
        if(!empty($ip_range->account_id)){
        $company = dbgetaccount($ip_range->account_id);
        $name = $company->company;
        }
        if($ip_range->type=='Route Object'){
        $name .= ' (ROUTE OBJECT)';    
        }

        if(!$ip_range->subscription_id){

        $name .= ' (TEST)';    
        }

        $cmd = '/ip route set comment="'.$name.'" [find dst-address='.$ip_range->ip_range.']';


        $result = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd,'9222');
        //echo $ip_range->ip_range.'- Comment Result:'.$result.'<br>';

        if($ip_range->type!='Route Object'){
        $cmd2 = '/ip route set gateway="'.$ip_range->gateway.'" [find dst-address='.$ip_range->ip_range.']';
        $result2 = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd2,'9222');
        //echo $ip_range->ip_range.'- Gateway Result:'.$result2.'<br><br>';
        }

        //set_ip_subscription_status($ip_range->ip_range,$ip_range->status);
    }
}

function ip_range_geofeed(){
    /*
    // https://docs.ipdata.co/docs/publishing-a-geofeed
    A geofeed is simply a CSV file with the following format:
    
    csv
    
    network, iso_country code, iso_region_code, city_name, postal_code
    Here is an example of a valid and complete geofeed;
    
    csv
    
    8.8.8.0/24,US,US-CA,Mountain View,
    2001:4860:4860::/46,US,US-CA,Mountain View,    
    */

    $ip_ranges = \DB::table('isp_data_ip_ranges')->where('is_deleted',0)->orderBy('sort_order')->get();
    (new Rap2hpoutre\FastExcel\FastExcel($ip_ranges))->export(public_path().'/ip_ranges_geofeed.csv', function ($ip_range) {
        return [
            'network' => $ip_range->ip_range,
            'iso_country_code' => $ip_range->iso_country_code,
            'iso_region_code' => $ip_range->iso_region_code,
            'city_name' => $ip_range->city_name,
        ];
    });
    // copy file to host2
    $file_path = public_path().'/ip_ranges_geofeed.csv';
    if (file_exists($file_path)) {
        $ssh = new \phpseclib\Net\SSH2('host2.cloudtools.co.za');
        if ($ssh->login('root', 'Ahmed777')) {
            $scp = new \phpseclib\Net\SCP($ssh);
            $remote = '/home/da12/cloudtelecoms.co.za/html/geofeed.csv';

            $result = $scp->put($remote, $file_path, $scp->SOURCE_LOCAL_FILE);

            if ($result) {
                $cmd = 'chown da12:da12 '.$remote.' && chmod 777 '.$remote;
                $permissions_result = Erp::ssh('host2.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
            }
        }
    }
}

function select_options_gateway_list(){

    $gateway_list = [];
    $cmd = '/interface gre print';
    $result = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd,'9222');
    $gre_names = extractGreNames($result);
    foreach($gre_names as $gre_name){
       $gateway_list[] = $gre_name; 
    }

     $cmd = '/ip route print terse where gateway!=0.0.0.0';    
    $result = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd,'9222');

    $gateway_names = extractGatewayNames($result);

    foreach($gateway_names as $gateway_name){
       $gateway_list[] = $gateway_name; 
    }

    if($gateways > ''){
      $gateway_list = collect(explode(',',$gateways))->collect()->filter()->unique()->toArray();  
    }
    if(count($gateway_list) > 0){
        $gateway_list = array_combine($gateway_list, $gateway_list);

    }
    return $gateway_list;
}

function extractGreNames($output) {
  $pattern = '/name="([^"]+)"/';
  preg_match_all($pattern, $output, $matches);
  return $matches[1];
}

function extractGatewayNames($output) {
  $lines = preg_split('/\r\n|\r|\n/', $output); // split the output into an array of lines
  $gateways = array(); // initialize an array to hold the gateway names
  foreach ($lines as $line) {
    $matches = array();
    preg_match('/^(\d+)\s+(\w+)\s+.*gateway=(\S+)\s/', $line, $matches); // extract the gateway name from the line
    if (!empty($matches)) {
      $gateway_name = $matches[3];
      if ($gateway_name != '0.0.0.0') {
        $gateways[] = $gateway_name;
      }
    }
  }
  return $gateways; 
}



function extractIpAndGateway($output) {
    $lines = explode("\n", $output);
    $ipGateways = array();
    foreach($lines as $line) {
        if(strpos($line, "dst-address") !== false && strpos($line, "gateway") !== false) {
            $ipGateway = array();
            preg_match('/dst-address=(\S+)\s+gateway=(\S+)/', $line, $matches);
            $ipGateway["ip"] = $matches[1];
            $ipGateway["gateway"] = $matches[2];
            $ipGateways[] = $ipGateway;
        }
    }
    return $ipGateways;
}

function schedule_ip_ranges_unreachable(){

    $account_ids = \DB::table('isp_data_ip_ranges')->where('type','Tunnel')->where('account_id','>',0)->pluck('account_id')->filter()->unique()->toArray();
    foreach($account_ids as $account_id){
        $ip_ranges = \DB::table('isp_data_ip_ranges')->where('account_id',$account_id)->where('is_deleted',0)->where('reachable',0)->pluck('ip_range')->filter()->unique()->toArray();
        if(count($ip_ranges) > 0){
            $data['internal_function'] = 'iprange_unreachable';
           //  $data['test_debug'] = 1;
            $data['ip_ranges'] = implode('<br>',$ip_ranges);

            //$data['bcc_email'] = 'ahmed@telecloud.co.za';
            // $data['force_to_email'] = 'ahmed@telecloud.co.za';

            erp_process_notification($account_id,$data); 

        }
    }
}

function button_ipranges_update_gateways(){

    $ip_ranges = \DB::table('isp_data_ip_ranges')->where('is_deleted',0)->get();
    foreach($ip_ranges as $ip_range){

        $name = '';
        if(!empty($ip_range->account_id)){
        $company = dbgetaccount($ip_range->account_id);
        $name = $company->company;
        }
        if($ip_range->type=='Route Object'){
        $name .= ' (ROUTE OBJECT)';    
        }

        if(!$ip_range->subscription_id){

        $name .= ' (TEST)';    
        }

        $cmd = '/ip route set comment="'.$name.'" [find dst-address='.$ip_range->ip_range.']';


        $result = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd,'9222');
        //echo $ip_range->ip_range.'- Comment Result:'.$result.'<br>';

        if($ip_range->type!='Route Object'){
        $cmd2 = '/ip route set gateway="'.$ip_range->gateway.'" [find dst-address='.$ip_range->ip_range.']';
        $result2 = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd2,'9222');
        //echo $ip_range->ip_range.'- Gateway Result:'.$result2.'<br><br>';
        }
    }

}



function button_ipranges_remove_account_from_test_ranges(){
    \DB::table('isp_data_ip_ranges')->where('subscription_id',0)->update(['account_id'=>0,'gateway' => 'DISABLED']);
    schedule_import_ip_ranges();
    return json_alert('Done');
}



function aftersave_ipranges_set_tunnel_status($request){
    $row = \DB::table('isp_data_ip_ranges')->where('id',$request->id)->get()->first();
    if($row->type != 'Route Object'){
        set_ip_subscription_status($row->ip_range,$row->router_object_status);
    }
}

function button_ipranges_set_test_ranges($request){
    $data = [];
    $data['gateways'] = get_module_field_options(1809,'gateway');
    $data['account_ids'] = get_module_field_options(1809,'account_id');
   
    return view('__app.button_views.ipranges_test_ranges',$data);
}

function button_ipranges_test_ranges_disable($request){

    $data = ['test_expiry'=>NULL,'account_id'=>0,'gateway'=>'DISABLED', 'router_object_status' => 'Disabled'];

    \DB::table('isp_data_ip_ranges')->where('subscription_id',0)->where('is_deleted',0)->update($data);

    $ip_ranges = \DB::table('isp_data_ip_ranges')->where('subscription_id',0)->where('is_deleted',0)->get();
    foreach($ip_ranges as $ip_range){

        $name = '';
        if(!empty($ip_range->account_id)){
        $company = dbgetaccount($ip_range->account_id);
        $name = $company->company;
        }
        if($ip_range->type=='Route Object'){
        $name .= ' (ROUTE OBJECT)';    
        }

        if(!$ip_range->subscription_id){

        $name .= ' (TEST)';    
        }

        $cmd = '/ip route set comment="'.$name.'" [find dst-address='.$ip_range->ip_range.']';


        $result = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd,'9222');
        //echo $ip_range->ip_range.'- Comment Result:'.$result.'<br>';

        if($ip_range->type!='Route Object'){
        $cmd2 = '/ip route set gateway="'.$ip_range->gateway.'" [find dst-address='.$ip_range->ip_range.']';
        $result2 = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd2,'9222');
        //echo $ip_range->ip_range.'- Gateway Result:'.$result2.'<br><br>';
        }
        
        set_ip_subscription_status($ip_range->ip_range,$ip_range->router_object_status);
    }

}

function ip_reachable($ip){
    $cmd = '/ip route print where dst-address='.$ip.' and active';
    $result = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd,'9222');  

    if(!str_contains($result,$ip)){
        return false;    
    }
    $result_arr = explode(PHP_EOL,$result);

    foreach($result_arr as $row){
        if(str_contains($row,$ip)){
            $row_arr = explode(' ',$row);
            if(str_contains($row_arr[1],'A') && !str_contains($row_arr[1],'U')){
                return true;    
            }
        }    
    }
    return false;
}

function schedule_import_ip_ranges(){
    /*
    $ip_ranges = \DB::table('isp_data_ip_ranges')->where('subscription_id',0)->where('is_deleted',0)->get();
    foreach($ip_ranges as $ip_range){
        set_ip_subscription_status($ip_range->ip_range, 'Enabled');
        \DB::table('isp_data_ip_ranges')->where('id',$ip_range->id)->update(['status'=>'Enabled']);
    }
    */
    \DB::table('isp_data_ip_ranges')->where('subscription_id',0)->update(['loa_company' => '','loa_as_number' => 328227]);
    \DB::table('isp_data_ip_ranges')->where('type','Route Object')->update(['gateway' => 'DISABLED']);

    $subs = \DB::table('sub_services')->where('status','!=','Deleted')->whereIn('provision_type',['ip_range_gateway','ip_range_route'])->get();
    foreach($subs as $s){
        \DB::table('isp_data_ip_ranges')->where('ip_range',$s->detail)->update(['subscription_id' => $s->id,'subscription_status' => $s->status]);    
    }
    $subs = \DB::table('sub_services')->where('status','Deleted')->whereIn('provision_type',['ip_range_gateway','ip_range_route'])->get();
    foreach($subs as $s){
        \DB::table('isp_data_ip_ranges')->where('account_id',$s->account_id)->where('ip_range',$s->detail)->update(['subscription_id' => 0,'subscription_status' => '']);    
    }

     \DB::table('isp_data_ip_ranges')->where('account_id',0)->update(['subscription_id' =>0,'subscription_status' => '']); 
    // insert comments

    $ip_ranges = \DB::table('isp_data_ip_ranges')->get();
    foreach($ip_ranges as $ip_range){

        $name = '';
        if(!empty($ip_range->account_id)){
            $company = dbgetaccount($ip_range->account_id);
            $name = $company->company;
        }
        if($ip_range->type=='Route Object'){
            $name .= ' (ROUTE OBJECT)';    
        }

        if(!$ip_range->subscription_id){
            $name .= ' (TEST)';    
        }

        $cmd = '/ip route check '.str_replace('/24','',$ip_range->ip_range).' once';

        $reachable = ip_reachable($ip_range->ip_range);
        if($reachable){
            \DB::table('isp_data_ip_ranges')->where('id',$ip_range->id)->update(['reachable'=>1]);
        }else{
            \DB::table('isp_data_ip_ranges')->where('id',$ip_range->id)->update(['reachable'=>0]);
        }
    }


    // import

    $cmd = '/ip route print where dst-address in 156.0.96.0/19';
    $result = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd,'9222');


    if($result > ""){
        $lines = explode("\r\n",trim($result));

        $ip_list = [];
        foreach($lines as $i => $l){

            if(!str_contains($l,'DAC') && str_contains($l,'/24')){
                $ip_line = $lines[$i-1].' '.$l;
                $test_range = 0;
                if(str_contains($ip_line,'TEST')){
                $test_range = 1;    
                }
                $cols = preg_split('/\s+/',trim($ip_line));
                $ip_range = false;

                foreach($cols as $j => $c){
                    if(str_contains($c,'/24')){
                        $ip_range = $c; 
                        $gateway_index = $j+1;
                    }
                }
                $gateway = $cols[$gateway_index];

                if(str_contains($ip_line,'X S',)){
                    $enabled = 0;
                }elseif(str_contains($ip_line,'As')){
                    $enabled = 1;
                }elseif(str_contains($ip_line,' S')){
                    $enabled = 1;
                }
                if($ip_range){
                   // $ip_list[] = ['ip_range' => $ip_range,'status' => ($enabled) ? 'Enabled' : 'Disabled']; 
                    $ip_list[] = ['ip_range' => $ip_range];   
                }
            }
        }


        if(count($ip_list) > 0){
            $ip_ranges = collect($ip_list);

            foreach($ip_ranges as $ip_range){
                $c = \DB::table('isp_data_ip_ranges')->where('is_deleted',0)->where('ip_range',$ip_range['ip_range'])->count();
                if($c){
                    \DB::table('isp_data_ip_ranges')->updateOrInsert(['ip_range'=>$ip_range['ip_range']], $ip_range);
                }
            }

            //\DB::table('isp_data_ip_ranges')->where('type', 'Route Object')->where('account_id','>',0)->update(['status'=>'Enabled']);
        } 

        //remove deleted


        if(count($ip_list) > 10){
            $ip_ranges = collect($ip_list)->pluck('ip_range')->toArray();
            $db_ip_ranges = \DB::table('isp_data_ip_ranges')->where('is_deleted',0)->get();
            foreach($db_ip_ranges as $ip_range){
                if(!in_array($ip_range->ip_range,$ip_ranges)){
                    // send email 
                    staff_email(3696, 'IP Deleted on Winbox '.$ip_range->ip_range, 'IP Deleted on Winbox '.$ip_range->ip_range.'. This Ip needs to be deleted on FlexERP');

                }
            }
        }  

        /*
        // update gateway
        doesnt return correct test gateway
        $cmd = '/ip route print detail where dst-address!="0.0.0.0/0"';
        $result = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd,'9222');
        $ip_and_gateways = extractIpAndGateway($result);
        $ip_and_gateways = collect($ip_and_gateways)->where('ip','>','')->all();
        foreach($ip_and_gateways as $ip_and_gateway){
            \DB::table('isp_data_ip_ranges')->where('ip_range',$ip_and_gateway['ip'])->update(['gateway'=>$ip_and_gateway['gateway']]);    
        }
        */
    }
    ip_range_geofeed();
}

function set_ip_subscription_status($ip, $status)
{
    if ($status == 'Enabled') {
        $set_status = 'enable';
    }
    if ($status == 'Disabled') {
        $set_status = 'disable';
    }
    
    $ip_range = \DB::table('isp_data_ip_ranges')->where('ip_range', $ip)->get()->first();
    if($ip_range->type != 'Tunnel'){
        return false;
    }
    $cmd = '/ip route '.$set_status.' [find dst-address='.$ip.']';
    //aa($cmd);
    $result = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd,'9222');
    //aa($result);
    
    return $result;
}

function update_iprange_winbox($ip_range)
{
  //  if ($ip_range->status == 'Enabled') {
  //      $set_status = 'enable';
  //  }
  //  if ($ip_range->status == 'Disabled') {
  //      $set_status = 'disable';
  //  }

   // $ip_range = \DB::table('isp_data_ip_ranges')->where('ip_range', $ip_range->ip_range)->get()->first();
  //  if($ip_range->type=='Route Object'){
  //      $set_status = 'disable';
 //   }
  //  $cmd = '/ip route '.$set_status.' [find dst-address='.$ip_range->ip_range.']';
  //  $result = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd,'9222');

    $name = '';
    if(!empty($ip_range->account_id)){
    $company = dbgetaccount($ip_range->account_id);
    $name = $company->company;
    }
    if($ip_range->type=='Route Object'){
    $name .= ' (ROUTE OBJECT)';    
    }

    if(!$ip_range->subscription_id){

    $name .= ' (TEST)';    
    }

    $cmd = '/ip route set comment="'.$name.'" [find dst-address='.$ip_range->ip_range.']';


    $result = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd,'9222');


    if($ip_range->type!='Route Object'){
    $cmd2 = '/ip route set gateway="'.$ip_range->gateway.'" [find dst-address='.$ip_range->ip_range.']';
    $result2 = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd2,'9222');
    }


}

function aftersave_ip_range_set_comment_gateway($request)
{

    $ip_range = \DB::table('isp_data_ip_ranges')->where('id', $request->id)->get()->first();
    $name = '';
    if(!empty($ip_range->account_id)){
    $company = dbgetaccount($ip_range->account_id);
    $name = $company->company;
    }
    if($ip_range->type=='Route Object'){
    $name .= ' (ROUTE OBJECT)';    
    }

    if(!$ip_range->subscription_id){

        $name .= ' (TEST)';    
    }

    $cmd = '/ip route set comment="'.$name.'" [find dst-address='.$ip_range->ip_range.']';


    $result = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd,'9222');



    $cmd2 = '/ip route set gateway="'.$ip_range->gateway.'" [find dst-address='.$ip_range->ip_range.']';
    $result2 = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd2,'9222');


}

function aftersave_ip_range_allocated_notification($request)
{
    //set_ip_subscription_status($request->ip_range, $request->status);

    /*
    $ip_ranges = \DB::table('isp_data_ip_ranges')->where('account_id', '>','')->where('subscription_id','>',0)->get();
    foreach($ip_ranges as $ip_range){
        $exists = \DB::connection('default')->table('sub_services')->where('detail',$ip_range->ip_range)->where('account_id',$ip_range->account_id)->where('status','!=','Deleted')->count();
        if(!$exists){
            \DB::connection('default')->table('sub_services')->where('detail',$ip_range->ip_range)->delete();
            $created_at = date('Y-m-d H:i:s');
            $renews_at = date('Y-m-d H:i:s',strtotime('+1 month'));
            $subscription_product = 532;
            $subscription_data = [
                'account_id' => $ip_range->account_id,
                'status' => 'Enabled',
                'bill_frequency' => 1,
                'provision_type' => ($ip_range->type == 'Tunnel') ? 'ip_range_gateway' : 'ip_range_route',
                'detail' => $ip_range->ip_range,
                'product_id' => $subscription_product,
                'created_at' => $created_at,
                'date_activated' => $created_at,
                'renews_at' => $renews_at,
            ];
                                
            $sub_id = \DB::connection('default')->table('sub_services')->insertGetId($subscription_data);
            \DB::table('isp_data_ip_ranges')->where('id',$ip_range->id)->update(['expiry_date'=>$renews_at]);
            $activation_data = [
            'account_id' => $ip_range->account_id,
            'qty' => 1,
            'product_id' => $subscription_product,
            'status' => 'Enabled',
            'created_at' => $created_at,
            'detail' => $ip_range->ip_range,
            'provision_type' => ($ip_range->type == 'Tunnel') ? 'ip_range_gateway' : 'ip_range_route',
            'bill_frequency' => 1,
            'subscription_id' => $sub_id,
            ];
            $activation_id = \DB::connection('default')->table('sub_activations')->insertGetId($activation_data);
            module_log(554, $activation_id, 'ip_range auto allocated');
            module_log(334, $sub_id,'ip_range auto allocated');
            
        }
    }
    */
}

function button_ipranges_remove_account($request){

    \DB::table('isp_data_ip_ranges')->where('id',$request->id)->update(['account_id'=>0,'expiry_date'=>null,'renew'=>0,'subscription_id'=>0]);

    $ip_range = \DB::table('isp_data_ip_ranges')->where('id',$request->id)->get()->first();

    //set_ip_subscription_status($ip_range->ip_range, 'Disabled');

    $name = '';
    if(!empty($ip_range->account_id)){
    $company = dbgetaccount($ip_range->account_id);
    $name = $company->company;
    }
    if($ip_range->type=='Route Object'){
    $name .= ' (ROUTE OBJECT)';    
    }

    if(!$ip_range->subscription_id){
    $name .= ' (TEST)';    
    }

    $cmd = '/ip route set comment="'.$name.'" [find dst-address='.$ip_range->ip_range.']';


    $result = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd,'9222');

    if($ip_range->account_id == 0 && $ip_range->subscription_id > 0){
        \DB::table('isp_data_ip_ranges')->where('id', $request->id)->update(['gateway'=>' ']);
        $ip_range->gateway = ' ';
    }

    $cmd2 = '/ip route set gateway="'.$ip_range->gateway.'" [find dst-address='.$ip_range->ip_range.']';
    $result2 = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd2,'9222');


    \DB::table('sub_services')->whereIn('provision_type',['ip_range_gateway','ip_range_route'])
    ->where('detail',$ip_range->ip_range)
    ->where('status','Deleted')
    ->delete();
    \DB::table('sub_services')
    ->whereIn('provision_type',['ip_range_gateway','ip_range_route'])
    ->where('detail',$ip_range->ip_range)
    ->where('status','!=','Deleted')
    ->update(['status'=>'Deleted','deleted_at'=>date('Y-m-d H:i:s')]);
    return json_alert('Done');
}

function schedule_ip_ranges_process_expiry(){

    $subs = \DB::table('sub_services')->where('status','!=','Deleted')->whereIn('provision_type',['ip_range_gateway','ip_range_route'])->get();
    foreach($subs as $s){
        \DB::table('isp_data_ip_ranges')->where('ip_range',$s->detail)->update(['expiry_date' => $s->renews_at]);    
    }

    \DB::table('isp_data_ip_ranges')->where('account_id', 0)->update(['expiry_date'=>null,'renew'=>0]);
    /*
    $ip_ranges = \DB::table('isp_data_ip_ranges')->where('account_id', '>', 0)->where('expiry_date','<=',date('Y-m-d'))->where('renew',0)->get();

    foreach($ip_ranges as $ip_range){
        \DB::table('isp_data_ip_ranges')->where('id',$ip_range->id)->update(['account_id'=>0,'expiry_date'=>null,'renew'=>0,'gateway'=>1,'subscription_id'=>0]);

        $ip_range = \DB::table('isp_data_ip_ranges')->where('id',$ip_range->id)->get()->first();
        //set_ip_subscription_status($ip_range->ip_range, 'Disabled');

        $name = '';
        if(!empty($ip_range->account_id)){
        $company = dbgetaccount($ip_range->account_id);
        $name = $company->company;
        }
        if($ip_range->type=='Route Object'){
        $name .= ' (ROUTE OBJECT)';    
        }

        if(!$ip_range->subscription_id){
        $name .= ' (TEST)';    
        }

        $cmd = '/ip route set comment="'.$name.'" [find dst-address='.$ip_range->ip_range.']';


        $result = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd,'9222');

        if($ip_range->account_id == 0 && $ip_range->subscription_id > 0){
            \DB::table('isp_data_ip_ranges')->where('id', $request->id)->update(['gateway'=>' ']);
            $ip_range->gateway = ' ';
        }

        $cmd2 = '/ip route set gateway="'.$ip_range->gateway.'" [find dst-address='.$ip_range->ip_range.']';
        $result2 = Erp::ssh('156.0.96.1', 'gustaf', 'Webmin@786', $cmd2,'9222');
        $sub_exists = \DB::table('sub_services')->whereIn('provision_type',['ip_range_gateway','ip_range_route'])
        ->where('detail',$ip_range->ip_range)
        ->where('status','!=','Deleted')
        ->count();
        if($sub_exists){

            \DB::table('sub_services')->whereIn('provision_type',['ip_range_gateway','ip_range_route'])
            ->where('detail',$ip_range->ip_range)
            ->where('status','Deleted')
            ->delete();
            \DB::table('sub_services')
            ->whereIn('provision_type',['ip_range_gateway','ip_range_route'])
            ->where('detail',$ip_range->ip_range)
            ->where('status','!=','Deleted')
            ->update(['status'=>'Deleted','deleted_at'=>date('Y-m-d H:i:s')]);
        }


    }
    */

}

function export_available_ipranges(){
    $ranges = \DB::connection('default')->table('isp_data_ip_ranges')->where('is_deleted',0)->orderBy('sort_order')->get();
    
    $available_ranges = [];
    
    foreach($ranges as $range){
        if($range->account_id == 0 ){
            $available_ranges[] = $range;    
        }    
    }
    
    
   
    $file_title = 'Available IP Ranges';
    $file_name = $file_title.'.xlsx';
    $file_path = attachments_path().$file_name;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    $excel_list = [];

    foreach ($available_ranges as $n) {
        $r = ['IP Range' => $n->ip_range];
        $excel_list[] = (array) $r;
    }


    $export = new App\Exports\CollectionExport();
    $export->setData($excel_list);

    Excel::store($export, session('instance')->directory.'/'.$file_name, 'attachments');
    $file_path = attachments_path().$file_name;    
    return $file_name;
}