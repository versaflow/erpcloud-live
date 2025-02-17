<?php

//Music on hold can be in WAV or MP3 format. For best performance upload 16 bit, 8/16/32/48 kHz mono WAV files.
function aftersave_moh_copy_upload_file($request){
    
    /*
        v_music_on_hold - db entries for domain folder
        p_music_on_hold - db entries for uploaded files
    */
    $moh = \DB::connection('pbx')->table('p_music_on_hold')->where('id',$request->id)->get()->first();
    $file = public_path().'/uploads/telecloud/1995/'.$moh->audio_filename; 
    if(!empty($moh->domain_uuid)){
        $domain_uuid = $moh->domain_uuid;
        $domain_name = \DB::connection('pbx')->table('v_domains')->where('domain_uuid',$domain_uuid)->pluck('domain_name')->first();
        // create Music on hold directory and db entry for folder
        $stream_path = '/usr/share/freeswitch/sounds/music/'.$domain_name.'/'.$moh->category_name.'/'.$moh->khz_category;
        $exists = \DB::connection('pbx')->table('v_music_on_hold')->where('domain_uuid',$domain_uuid)->where('music_on_hold_name',$moh->category_name)->where('music_on_hold_rate',$moh->khz_category)->count();
        if(!$exists){
            $data = [
                'music_on_hold_uuid' => pbx_uuid('v_music_on_hold','music_on_hold_uuid'),
                'domain_uuid' => $domain_uuid,
                'music_on_hold_name' => $moh->category_name,
                'music_on_hold_path' => $stream_path,
                'music_on_hold_rate' => $moh->khz_category,
                'music_on_hold_shuffle' => 'false',
                'music_on_hold_channels' => 1,
                'music_on_hold_interval' => 20,
                'music_on_hold_timer_name' => 'soft',
            ];
            \DB::connection('pbx')->table('v_music_on_hold')->insert($data);
        }
    	//check target folder exists
        $cmd = 'mkdir -p '.$stream_path.' && chmod 777 '.$stream_path;
        $permissions_result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
    	
        //aa($stream_path);
        // copy file to freeswitch path
    
    	    $file = public_path().'/uploads/telecloud/1995/'.$moh->audio_filename;
    	    if($domain_name){
    	    if(!str_contains($file,$domain_name)){
        	    $upfile = public_path().'/uploads/telecloud/1995/'.$moh->audio_filename;
        	    
        	    $domain_upload_dir = public_path().'/uploads/telecloud/1995/'.$domain_name;
            	if (!is_dir($domain_upload_dir)) {
            		mkdir($domain_upload_dir, 0777, true);
            	}
        	    
                $file = $domain_upload_dir.'/'.$moh->audio_filename;
                
                if(!file_exists($file) && file_exists($upfile)){
                    File::move($upfile,$file);
                    DB::connection('pbx')->table('p_music_on_hold')->where('id',$request->id)->update(['audio_filename'=>$domain_name.'/'.$moh->audio_filename]);
                }
    	    }
    	    }
            $pbx_path = $stream_path.'/'.str_replace($domain_name.'/','',$moh->audio_filename);
        //aa($file);
        //aa($pbx_path);
    }else{
        
        $stream_path = '/usr/share/freeswitch/sounds/music/default/8000';
        $pbx_path = $stream_path.'/'.$moh->audio_filename;
    }
    copy_file_to_pbx($file,$pbx_path);
        
	
}

function afterdelete_moh_remove_audo_file($request){
    
    // delete remote file
    if(!empty($request->audio_filename)){
        if(empty($request->domain_uuid)){
            
            $stream_path = '/usr/share/freeswitch/sounds/music/default/8000';
            $pbx_path = $stream_path.'/'.$request->audio_filename;
            $cmd = 'rm -rf '.$pbx_path;
           
            $permissions_result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
        }else{
            $stream_path = \DB::connection('pbx')->table('v_music_on_hold')
            ->where('domain_uuid',$moh_category->domain_uuid)
            ->where('music_on_hold_name',$request->category_name)
            ->where('music_on_hold_rate',$request->khz_category)
            ->pluck('music_on_hold_path')->first();
            $pbx_path = $stream_path.'/'.$request->audio_filename;
            $cmd = 'rm -rf '.$pbx_path;
           
            $permissions_result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
        }
    }
    
    // delete local file
    if(!empty($request->audio_filename)){
        $file = public_path().'/uploads/telecloud/1995/'.$request->audio_filename;
       
        if(file_exists($file)){
            File::delete($file);
        } 
    }
    
    // delete empty categories
    $moh_categories = \DB::connection('pbx')->table('v_music_on_hold')->whereNotNull('domain_uuid')->get();
    foreach($moh_categories as $moh_category){
        $files_exists = \DB::connection('pbx')->table('p_music_on_hold')->where('domain_uuid',$moh_category->domain_uuid)->where('category_name',$moh_category->music_on_hold_name)->where('khz_category',$moh_category->music_on_hold_rate)->count();
        if(!$files_exists){
            // remove category
            $cmd = 'rm -rf '.$moh_category->music_on_hold_path;
          
            $permissions_result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd);
            \DB::connection('pbx')->table('v_music_on_hold')->where('music_on_hold_uuid',$moh_category->music_on_hold_uuid)->delete();
            
        }
    }
}