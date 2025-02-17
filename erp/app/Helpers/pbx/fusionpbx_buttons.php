<?php

function button_recording_download($request){
    
    
        // mp3_howler_placeholder - howler requires url to end with .mp3 to work
    $recording = \DB::connection('pbx')->table('v_recordings')
    ->select('v_recordings.recording_filename','v_domains.domain_name')
    ->join('v_domains','v_domains.domain_uuid','=','v_recordings.domain_uuid')
    ->where('recording_uuid',$request->id)
    ->get()->first();
    $filename = $recording->recording_filename;
    if(empty($filename)){
        $filename = str_replace(' ','',$recording->name).'.mp3';
        \DB::connection('pbx')->table('v_recordings')
        ->where('recording_uuid',$recording->recording_uuid)
        ->update(['recording_filename'=>$filename]);
    }
    $file = '/var/lib/freeswitch/recordings/'.$recording->domain_name.'/'.$filename;
   
    $ssh = new \phpseclib\Net\SSH2('pbx.cloudtools.co.za');
    if ($ssh->login('root', 'Ahmed777')) {
        // Check if the file exists on the remote server
       
        // Get the content of the remote file
        $fileContent = $ssh->exec('cat ' . $file);
        
        $ssh->disconnect();
        // Output the content
        Storage::disk('pbx_recordings')->put($filename, $fileContent);
        return response()->download(storage_path('pbx_recordings/'. $filename));
        
    }
		
}

/*
function button_pbx_iframe_edit($request){
    $url = 'https://156.0.96.60/app/time_conditions/time_condition_edit.php';
   
    $time_condition = \DB::connection('pbx')->table('v_dialplans')->where('dialplan_uuid',$request->id)->get()->first();
    
    if(!empty($time_condition->domain_uuid)){

        $pbx_row = \DB::connection('pbx')->table('v_users as vu')
        ->join('v_domains as vd', 'vd.domain_uuid', '=', 'vu.domain_uuid')
        ->where('vd.domain_uuid', $time_condition->domain_uuid)
        ->get()->first();
        $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid', $time_condition->domain_uuid)->pluck('domain_name')->first();
        $url = str_replace('156.0.96.60',$domain_name,$url);
        $key = $pbx_row->api_key;
        $url .= '?id='.$request->id.'&key='.$key;
       
        
   
         echo '<div class="vh-100" style="margin:0px;padding:0px;overflow:hidden">
         <iframe src="'.$url.'"  frameborder="0px" onerror="alert(\'Failed\')" style="overflow:hidden;height:100%;width:100%" height="100%" width="100%"><!-- //required for browser compatibility --></iframe>
         </div>';
    }
   
    // echo '<iframe src="'.$url.'" width="100%" frameborder="0px" height="400px" onerror="alert(\'Failed\')" style="margin-bottom:-5px;"><!-- //required for browser compatibility --></iframe> ';

    //return redirect()->to($url);
}
*/