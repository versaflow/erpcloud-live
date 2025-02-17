<?php

function communica_users_import(){
    if(session('instance')->id != 20){
        return false;
    }
   
   
 
    // \DB::connection('supportboard')->table('sb_users')->where('user_type','agent')->whereNotIn('email',$erp_users_emails)->delete();
    
    $file_path = base_path().'/communica_users_list.xlsx';
    $users = file_to_array(base_path().'/communica_users_list.xlsx');
    $departments = collect($users)->pluck('Department')->unique()->filter()->toArray();
   
    foreach($departments as $department){
        $department = trim($department);
        $role_data = [
            'name' => $department,
            'level' => 'Admin',
            'support_department' => 1,
        ];
        if($department == 'Customer Care'){
            $role_data['default_support_department'] = 1;
        }
      
        
        $e = \DB::table('erp_user_roles')->where('name',$department)->count();
        if(!$e){
           
            dbinsert('erp_user_roles',$role_data);
        }else{
            \DB::table('erp_user_roles')->where('name',$department)->update($role_data);
        }
    }
    update_supportboard_departments();
    
    // update role permissions
    /*
    $roles = \DB::connection('default')->table('erp_user_roles')->whereIn('name',$departments)->get();
    foreach($roles as $role){
        $id = 58;
        $insert_id = $role->id;
        \DB::table('erp_menu_role_access')->where('role_id', $insert_id)->delete();
        \DB::table('erp_forms')->where('role_id', $insert_id)->delete();
       
        $permissions = \DB::table('erp_menu_role_access')->where('role_id', $id)->get();
        foreach ($permissions as $permission) {
            $permission_data = (array) $permission;
            unset($permission_data['id']);
            $permission_data['role_id'] = $insert_id;
            \DB::table('erp_menu_role_access')->insert($permission_data);
        }
        $fs = \DB::table('erp_forms')->where('role_id', $id)->get();
        foreach ($fs as $f) {
            $d = (array) $f;
            unset($d['id']);
            $d['role_id'] = $insert_id;
            \DB::table('erp_forms')->insert($d);
        }
    }
    */
    
    $roles = \DB::connection('default')->table('erp_user_roles')->get();
    foreach($users as $user){
        foreach($user as $k => $v){
            $user[$k] = trim($v);
        }
        $user_data = [
            'account_id' => 1,
            'verified' => 1,
            'full_name' => $user['First Name'].' '.$user['Surname'],
            'username' => $user['Login'],
            'email' => $user['Login'],
            'password' => \Hash::make($user['Password']),
            'role_id' => $roles->where('name',$user['Department'])->pluck('id')->first(),
            'webmail_email' => $user['Login'],
            'webmail_password' => $user['Password'],
        ];
        $e = \DB::table('erp_users')->where('username',$user['Login'])->count();
        if(!$e){
            dbinsert('erp_users',$user_data);
        }else{
            \DB::table('erp_users')->where('username',$user['Login'])->update($user_data);
        }
    }
    update_supportboard_users();
}