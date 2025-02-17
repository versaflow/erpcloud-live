<?php

function aftersave_layout_update_workflow($request){
    $layout = \DB::table('erp_grid_views')->where('id',$request->id)->get()->first();
    $beforesave_row = session('event_db_record');
   
    if (!empty($request->new_record) && $request->track_layout == 1) {
        workboard_layout_tracking_enable($request->id);
        
    } elseif(empty($request->new_record) && $beforesave_row->track_layout  != $request->track_layout) {
        if($request->track_layout == 1){
            workboard_layout_tracking_enable($request->id);
        }else{
            workboard_layout_tracking_disable($request->id);
        }
    }
}



function onload_workboard_log_populate_active_tasks(){
    \DB::connection('system')->table('crm_staff_timesheet')->where('active_task',1)->delete();
    $tasks = \DB::connection('default')->table('crm_staff_tasks')->where('progress_status', 'In Progress')->get();
    foreach($tasks as $task){
        $date = \Carbon\Carbon::parse($task->start_time);
        $now = \Carbon\Carbon::now();
        if($task->layout_id > 0){
        $task->result = workboard_layout_row_count($task->layout_id,$task->instance_id);
        }
        $duration = $date->diffInMinutes($now);
        $data = [
            'start_time' => $task->start_time,
            'duration' => $duration,
            'duration_hours' => $duration/60,
            'name' => $task->name,
            'progress_status' => $task->progress_status,
            'instance_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'created_day' => date('Y-m-d'),
            'created_by' => get_user_id_default(),
            'type' => 'Task',
            'module_id'=> 2018,
            'row_id' => $task->id,
            'user_id' => $task->user_id,
            'result_start' => $task->result_before_start,
            'result_end' => $task->result,
            'active_task' => 1,
        ];
        \DB::connection('system')->table('crm_staff_timesheet')->insert($data);
    }
    
  //  \DB::connection('system')->table('crm_staff_timesheet')->whereRaw(\DB::raw('result_end <= result_start'))->update(['progress' => \DB::raw('((result_start-result_end)/result_start)*100')]);
  //  \DB::connection('system')->table('crm_staff_timesheet')->whereRaw(\DB::raw('result_end > result_start'))->update(['progress' => 0]);
}

function get_workspace_role_from_module_id($module_id){
    $master_module_id = \DB::connection('default')->table('erp_cruds')->where('detail_module_id',$module_id)->pluck('id')->first();
    if($master_module_id){
  
        $module_id = $master_module_id;
    }
    $role_id = \DB::connection('default')->table('erp_menu')
    ->where('workspace_role_id','>',0)
    ->where('unlisted',0)
    ->where('module_id',$module_id)
    ->pluck('workspace_role_id')->first();
 
    if(empty($role_id)){
        $role_id = 1;
    }
    return \DB::connection('default')->table('erp_user_roles')
    ->select('id','name')
    ->where('id',$role_id)
    ->get()->first();
}


function beforesave_task_name_unique($request){
    /*
    $c = 0;
    if(empty($request->id)){
        $c = \DB::table('crm_staff_tasks')->where('name',$request->name)->where('user_id',$request->user_id)->where('is_deleted',0)->count();
    }else{
        $task = \DB::table('crm_staff_tasks')->where('id',$request->id)->get()->first();
        if(empty($task->layout_id))
        $c = \DB::table('crm_staff_tasks')->where('id','!=',$request->id)->where('name',$request->name)->where('user_id',$request->user_id)->where('is_deleted',0)->count();
    }
   
    if($c){
       return 'Name needs to be unique';
    }
    */
}

function aftercommit_workboard_tracking_log_time($request){
    
    if(empty($request->new_record) && !empty($request->id) && !empty($request->progress_status)){
        $beforesave_row = session('event_db_record');
	    if ($beforesave_row->progress_status != $request->progress_status) {
	    
            
            if($request->progress_status == 'Incomplete'){
                $row_id = $request->id;
                $module_id = 1898;
                
                $task = \DB::table('crm_staff_tasks')->where('id',$row_id)->get()->first();
                
                
                if($beforesave_row->progress_status == 'In Progress'){
                    
                    $task = \DB::table('crm_staff_tasks')->where('id',$row_id)->get()->first();
                    // if($task->progress_status == 'In Progress'){
                    
                    $date = \Carbon\Carbon::parse($task->start_time);
                    
                    $now = \Carbon\Carbon::now();
                    $stop_time = $now->toDateTimeString();
                    $duration = $date->diffInMinutes($now);
                    
                    
                    \DB::table('crm_staff_tasks')->where('id', $task->id)->update(['stop_time' => $stop_time, 'progress_status' => 'Incomplete']);
                    \DB::table('crm_staff_tasks')->where('id', $task->id)->increment('duration', $duration);
                    
                    staff_log_time('task',$task->start_time,$duration, $task->id,session('instance')->id,$module_id); 
                }
                
                \DB::table('crm_staff_tasks')->where('id', $task->id)->update(['progress_status' => 'Incomplete','completed'=>0,'duration'=>0]);
            
            }
            
            if($request->progress_status == 'Postponed'){
                $row_id = $request->id;
                $module_id = 1898;
                
                $task = \DB::table('crm_staff_tasks')->where('id',$row_id)->get()->first();
                
                
                if($beforesave_row->progress_status == 'In Progress'){
                    
                    $task = \DB::table('crm_staff_tasks')->where('id',$row_id)->get()->first();
                    // if($task->progress_status == 'In Progress'){
                    
                    $date = \Carbon\Carbon::parse($task->start_time);
                    
                    $now = \Carbon\Carbon::now();
                    $stop_time = $now->toDateTimeString();
                    $duration = $date->diffInMinutes($now);
                    
                    
                    \DB::table('crm_staff_tasks')->where('id', $task->id)->update(['stop_time' => $stop_time, 'progress_status' => 'Postponed']);
                    \DB::table('crm_staff_tasks')->where('id', $task->id)->increment('duration', $duration);
                    
                    staff_log_time('task',$task->start_time,$duration, $task->id,session('instance')->id,$module_id); 
                }
                
                \DB::table('crm_staff_tasks')->where('id', $task->id)->update(['progress_status' => 'Postponed','completed'=>0,'duration'=>0]);
            
            }
            
            if($request->progress_status == 'Not Done'){
                $row_id = $request->id;
                $module_id = 1898;
                
                $task = \DB::table('crm_staff_tasks')->where('id',$row_id)->get()->first();
                
                
                
                
                if($beforesave_row->progress_status == 'In Progress'){
                    
                    $task = \DB::table('crm_staff_tasks')->where('id',$row_id)->get()->first();
                    // if($task->progress_status == 'In Progress'){
                    
                    $date = \Carbon\Carbon::parse($task->start_time);
                    
                    $now = \Carbon\Carbon::now();
                    $stop_time = $now->toDateTimeString();
                    $duration = $date->diffInMinutes($now);
                    
                    
                    \DB::table('crm_staff_tasks')->where('id', $task->id)->update(['stop_time' => $stop_time, 'progress_status' => 'Not Done']);
                    \DB::table('crm_staff_tasks')->where('id', $task->id)->increment('duration', $duration);
                    
                    staff_log_time('task',$task->start_time,$duration, $task->id,session('instance')->id,$module_id); 
                }
                
                \DB::table('crm_staff_tasks')->where('id', $task->id)->update(['progress_status' => 'Not Done','duration'=>0]);
            }
            
            if($request->progress_status == 'Task Done'){
                $row_id = $request->id;
                $module_id = 1898;
                
                $task = \DB::table('crm_staff_tasks')->where('id',$row_id)->get()->first();
                
                
                
                $date = \Carbon\Carbon::parse($task->start_time);
                
                $now = \Carbon\Carbon::now();
                $stop_time = $now->toDateTimeString();
                $duration = $date->diffInMinutes($now);
                
                
                \DB::table('crm_staff_tasks')->where('id', $task->id)->update(['result'=>0,'stop_time' => $stop_time, 'progress_status' => 'Task Done']);
                \DB::table('crm_staff_tasks')->where('id', $task->id)->increment('duration', $duration);
                staff_log_time('task',$task->start_time,$duration, $task->id,session('instance')->id,$module_id,'Task Done');
                
                
                \DB::table('crm_staff_tasks')->where('id',$row_id)->update(['completed'=>1]);    
            }
            
            if($request->progress_status == 'Done'){
                $row_id = $request->id;
                $module_id = 1898;
             
                $task = \DB::table('crm_staff_tasks')->where('id',$row_id)->get()->first();
            
                $date = \Carbon\Carbon::parse($task->start_time);
                
                $now = \Carbon\Carbon::now();
                $stop_time = $now->toDateTimeString();
                $duration = $date->diffInMinutes($now);
            
                \DB::table('crm_staff_tasks')->where('id', $task->id)->increment('duration', $duration);
                if($task->type == 'Task'){
                    $progress_status = 'Task Done';
                    \DB::table('crm_staff_tasks')->where('id', $task->id)->update(['result'=>0,'stop_time' => $stop_time, 'progress_status' => $progress_status]);
                }else{
                    $progress_status = 'Done';
                    \DB::table('crm_staff_tasks')->where('id', $task->id)->update(['stop_time' => $stop_time, 'progress_status' => $progress_status]);
                }
                
                
                
                staff_log_time('task',$task->start_time,$duration, $task->id,session('instance')->id,$module_id,$progress_status);
                \DB::table('crm_staff_tasks')->where('id',$row_id)->update(['completed'=>1]);   
            } 
            
      
            
            if($request->progress_status == 'In Progress'){
                $row_id = $request->id;
               
                $module_id = 1898;
               // if($task->role_id == 62){
                    \DB::table('crm_staff_tasks')->where('id',$row_id)->update(['overdue'=>0,'user_id'=>session('user_id')]);
              //  }
                $new_task = \DB::table('crm_staff_tasks')->where('id',$row_id)->get()->first();
                $task = \DB::table('crm_staff_tasks')->where('progress_status','In Progress')
                ->where('id','!=',$row_id)
                ->where('is_deleted',0)
                ->where('user_id',$new_task->user_id)
                ->get()->first();
                if($task && $task->id){
                    $date = \Carbon\Carbon::parse($task->start_time);
                    
                    $now = \Carbon\Carbon::now();
                    $stop_time = $now->toDateTimeString();
                    $duration = $date->diffInMinutes($now);
                
                
                    \DB::table('crm_staff_tasks')->where('id', $task->id)->update(['stop_time' => $stop_time, 'progress_status' => 'Not Done']);
                    \DB::table('crm_staff_tasks')->where('id', $task->id)->increment('duration', $duration);
                   
                    staff_log_time('task',$task->start_time,$duration, $task->id,session('instance')->id,$module_id); 
                }
                
            } 
        
            update_workboard_parent_progress_status();
	    }
    }
    
}


function update_workboard_parent_progress_status(){
    $parent_ids = \DB::table('crm_staff_tasks')->where('is_deleted',0)->pluck('parent_id')->unique()->toArray();
    foreach($parent_ids as $parent_id){
        $sub_ids = get_workboard_sub_ids($parent_id);
        $done_count = \DB::table('crm_staff_tasks')->where('is_deleted',0)->whereIn('id',$sub_ids)->whereIn('progress_status',['Done','Task Done'])->count();
        $total_count = \DB::table('crm_staff_tasks')->where('is_deleted',0)->whereIn('id',$sub_ids)->count();
        $progress_status = ($done_count == $total_count) ? 'Done' : 'Not Done';
       
        \DB::table('crm_staff_tasks')->where('is_deleted',0)->where('id',$parent_id)->update(['progress_status'=>$progress_status]);
    }
    
}

function get_workboard_sub_ids($workboard_id, $workboard_collection = false)
{
    if(!$workboard_collection){
        $workboard_collection = \DB::connection('default')->table('crm_staff_tasks')->get();
    }
    $workboard_ids = $workboard_collection->where('parent_id', $workboard_id)->where('is_deleted',0)->pluck('id')->toArray();
    $workboard_ids = collect($workboard_ids);
    $sub_workboards = $workboard_collection->where('parent_id', $workboard_id)->where('is_deleted',0);
    if (!empty($sub_workboards) && count($sub_workboards) > 0) {
        foreach ($sub_workboards as $workboard) {
            $sub_workboard_ids = get_workboard_sub_ids($workboard->id,$workboard_collection);
            $sub_workboard_ids = collect($sub_workboard_ids);
            $workboard_ids = $workboard_ids->merge($sub_workboard_ids);
        }
    }

    $workboard_ids = collect($workboard_ids)->unique()->toArray();
    return $workboard_ids;
}

function aftercommit_layout_update_workboard_names($request){
    if ($request->track_layout == 1) {
        update_workboard_stats();
    }
}

function aftersave_workboard_set_role($request){
    $beforesave_row = session('event_db_record'); 
    if(empty($request->new_record) && $beforesave_row->user_id != $request->user_id){
        $workboard = \DB::table('crm_staff_tasks')->where('id',$request->id)->select('id','user_id')->where('is_deleted',0)->get()->first();;
        if($workboard && $workboard->id){
            $role_id = $request->role_id;
            if(empty($request->role_id)){
                $role_id = \DB::table('erp_users')->where('id',$request->user_id)->where('is_deleted',0)->pluck('role_id')->first();
                \DB::table('crm_staff_tasks')->where('id',$request->id)->update(['role_id'=>$role_id]);
            }
           
            $workboard_collection = \DB::connection('default')->table('crm_staff_tasks')->get();
       
            $sub_workboards = get_workboard_sub_ids($workboard->id,$workboard_collection);
            if(count($sub_workboards) > 0){
            \DB::table('crm_staff_tasks')->whereIn('id',$sub_workboards)->update(['user_id'=>$request->user_id,'role_id'=>$role_id]);
            }
        }
    }
    
    if(empty($request->new_record) && $beforesave_row->role_id != $request->role_id){
        \DB::table('crm_staff_tasks')->where('id',$request->id)->where('parent_id','!=',0)->update(['parent_id' => 0]);
        
        $workboard = \DB::table('crm_staff_tasks')->where('id',$request->id)->select('id','user_id')->where('is_deleted',0)->get()->first();;
        if($workboard && $workboard->id){
            $role_id = $request->role_id;
            if(empty($request->role_id)){
                $role_id = \DB::table('erp_users')->where('id',$request->user_id)->where('is_deleted',0)->pluck('role_id')->first();
                \DB::table('crm_staff_tasks')->where('id',$request->id)->update(['role_id'=>$role_id]);
            }
           
            $workboard_collection = \DB::connection('default')->table('crm_staff_tasks')->get();
       
            $sub_workboards = get_workboard_sub_ids($workboard->id,$workboard_collection);
            if(count($sub_workboards) > 0){
            \DB::table('crm_staff_tasks')->whereIn('id',$sub_workboards)->update(['user_id'=>$request->user_id,'role_id'=>$role_id]);
            }
        }
    }
    
    if(!empty($request->new_record)){
     
        
        $workboard = \DB::table('crm_staff_tasks')->where('id',$request->id)->select('id','user_id')->where('is_deleted',0)->get()->first();;
        if($workboard && $workboard->id){
            $role_id = $request->role_id;
           
            $user_id = \DB::table('erp_users')->where('role_id',$role_id)->where('is_deleted',0)->pluck('id')->first();
            \DB::table('crm_staff_tasks')->where('id',$request->id)->update(['user_id'=>$user_id]);
            
        }
    }
}

function aftersave_workboard_tracking_check($request){
    
    if(empty($request->new_record) && !empty($request->id) && !empty($request->progress_status)){
        $beforesave_row = session('event_db_record');
	    if ($beforesave_row->progress_status != $request->progress_status) {
	    
            if($request->progress_status == 'In Progress'){
                $result = workboard_tracking_in_progess($request);
            }
           
            if($request->progress_status == 'Done'){
                $result = workboard_tracking_done($request);
            }
            if($request->progress_status == 'Task Done'){
                $result = workboard_tracking_task_done($request);
            }
         
            
            if($result){
                return $result;
            }
	    }
    }
}
function aftercommit_workboard_set_duration_hours($request){
    
    \DB::connection('system')->table('crm_staff_timesheet')->update(['duration_hours' => \DB::raw('duration/60')]);
}



function workboard_tracking_in_progess($request){    
   
    $row_id = $request->id;
    $module_id = 1898;
    \DB::table('crm_staff_tasks')->where('progress_status','Not Done')->update(['completed' => 0]);
    $task = \DB::table('crm_staff_tasks')->where('id',$row_id)->get()->first();
    
   
    $beforesave_row = session('event_db_record');
 
  
    if(!is_dev() && empty($request->new_record)){
       
            $parent_ids = \DB::table('crm_staff_tasks')->where('is_deleted',0)->pluck('parent_id')->unique()->toArray();
            $next_task = \DB::table('crm_staff_tasks')
            ->where('progress_status','!=','Done')
            ->where('progress_status','!=','Task Done')
            ->where('progress_status','!=','Postponed')
            
            ->whereNotIn('id',$parent_ids)
            ->where('is_deleted',0)
            ->where('role_id',$task->role_id)
            ->orderBy('sort_order')
            ->get()->first();
            
            // if($next_task->id!=$task->id){
            //     return json_alert('Tasks needs to be completed in order, next task: '.$next_task->name, 'warning',['disable_grid_refresh'=>1]);
            // }
        
    }
    
   
    
    //\DB::table('crm_staff_tasks')->where('id', $task->id)->whereIn('progress_status',['Incomplete','Not Done'])->update(['duration' => 0]);
    
    \DB::table('crm_staff_tasks')->where('id', $task->id)->update(['duration' => 0,'start_time' => date('Y-m-d H:i:s'), 'progress_status' => 'In Progress','result_before_start'=> \DB::raw('result')]);
 
   
}

function workboard_tracking_pause($request){    
   
    $row_id = $request->id;
   
    $module_id = 1898;

    $task = \DB::table('crm_staff_tasks')->where('id',$row_id)->get()->first();
 
    $beforesave_row = session('event_db_record');
  
    $beforesave_row = session('event_db_record');
    if(!in_array($beforesave_row->progress_status ,['Not Done','In Progress'])){
        return json_alert('Only in progress or not done tasks can be paused', 'warning',['disable_grid_refresh'=>1]);
    }
  
    
}

function workboard_tracking_done($request){    
    $row_id = $request->id;
    $module_id = 1898;
 
    $task = \DB::table('crm_staff_tasks')->where('id',$row_id)->get()->first();
    
    if($task->module_id == 1958){
        schedule_zammad_import_tickets();
    }
    
    $beforesave_row = session('event_db_record');
  
    $beforesave_row = session('event_db_record');
    if($beforesave_row->progress_status != 'In Progress'){
        return json_alert('Only in progress tasks can set to done1', 'warning',['disable_grid_refresh'=>1]);
    }
   
    if($task->type == 'Layout' && $task->layout_id > 0 && $task->layout_type!='Report'){
        $row_count_status = get_workboard_layout_progress_status($task->layout_id,$task->instance_id,$task->assigned_user_id);
        if($row_count_status != 'Done'){
            return json_alert('Layout items not completed.', 'warning',['disable_grid_refresh'=>1]);
        }else{
            $result = workboard_layout_row_count($task->layout_id,$task->instance_id,$task->assigned_user_id);
            \DB::table('crm_staff_tasks')->where('id',$row_id)->update(['result'=> $result]);
        }
    }
}

function workboard_tracking_task_done($request){    
    $row_id = $request->id;
    $module_id = 1898;
 
    $task = \DB::table('crm_staff_tasks')->where('id',$row_id)->get()->first();
    $beforesave_row = session('event_db_record');
   
    $beforesave_row = session('event_db_record');
    if($beforesave_row->progress_status != 'In Progress'){
        return json_alert('Only in progress tasks can set to done2', 'warning',['disable_grid_refresh'=>1]);
    }
   
   
  
   
    
}
