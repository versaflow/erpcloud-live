<?php




function aftersave_create_default_group_access($request)
{
    $id = (!empty($request->id)) ? $request->id : null;
    $new_record = (!empty($request->new_record)) ? 1 : 0;
    $request->request->remove('new_record');

    $modules = dbgetrows('erp_menu');
    foreach ($modules as $modules) {
        $exists = \DB::select('select * from erp_menu_role_access where role_id = '.$id.' and menu_id = '.$modules->id);
        if (!$exists) {
            \DB::insert('insert into erp_menu_role_access (role_id,menu_id) values ('.$id.','.$modules->id.')');
        }
    }
    $request->request->add(['new_record' => $new_record]);
}

function is_admin_accounts()
{
    if ((check_access('1,31')) && session('role_level') == 'Admin') {
        return true;
    } else {
        return false;
    }
}



function beforedelete_roles_delete_permissions($request)
{
    
    $assigned_count = \DB::table('erp_users')->where('role_id',$request->id)->where('is_deleted',0)->count();
    if($assigned_count > 0){
        return 'Role cannot be deleted. Role assigned to '.$assigned_count.' users';
    }else{
        \DB::table('erp_menu_role_access')->where('role_id', $request->id)->delete();
        \DB::table('erp_forms')->where('role_id', $request->id)->delete();
    }
}


function schedule_remove_deleted_role_permissions(){
    $role_ids = \DB::table('erp_user_roles')->pluck('id')->toArray();
    \DB::table('erp_forms')->whereNotIn('role_id',$role_ids)->delete();
    \DB::table('erp_menu_role_access')->whereNotIn('role_id', $role_ids)->delete();
    $doctypes = \DB::table('acc_doctypes')->get();
    foreach($doctypes as $doctype){
            
    }
}