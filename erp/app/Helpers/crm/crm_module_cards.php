<?php

function rebuild_sales_cards()
{
    \DB::table('crm_module_cards')->where('module_id', 2018)->where('role_id', 62)->delete();

    $cards = \DB::table('crm_module_cards')->where('module_id', 2018)->where('role_id', 58)->get();
    $users = \DB::table('erp_users')->where('is_deleted', 0)->where('role_id', 62)->get();
    foreach ($cards as $c) {
        foreach ($users as $i => $user) {
            $data = (array) $c;
            $email_arr = explode('@', $user->email);
            $data['title'] = ucwords($email_arr[0]).' - '.$data['title'];
            $data['sql_query'] = $c->sql_query;
            $data['sql_query'] = str_replace('erp_users.role_id=58', 'erp_users.id='.$user->id, $data['sql_query']);
            $data['sql_query'] = str_replace('user_id IN (SELECT id FROM erp_users WHERE role_id=58)', 'user_id='.$user->id, $data['sql_query']);
            $data['module_id'] = 2018;
            $data['role_id'] = 62;
            $data['footer_line'] = $i + 1;
            unset($data['id']);
            dbinsert('crm_module_cards', $data);
        }
    }
}

function copy_workspace_cards()
{

    return false;
    $cards = \DB::table('crm_module_cards')->where('module_id', 2018)->where('id', 267)->where('is_deleted', 0)->get();

    $roles = \DB::connection('default')->table('erp_user_roles')
        ->where('level', 'Admin')
        ->get();

    foreach ($roles as $role) {

        foreach ($cards as $c) {

            $data = (array) $c;
            unset($data['id']);

            $role_id = $role->id;
            $user_id = \DB::table('erp_users')->where('account_id', 1)->where('is_deleted', 0)->where('role_id', $role_id)->pluck('id')->first();
            if (! $user_id) {
                $user_id = 1;
            }
            $data['sql_query'] = $c->sql_query;
            $data['sql_query'] = str_replace('role_id=58', 'role_id='.$role_id, $data['sql_query']);
            $data['role_id'] = $role_id;
            $data['user_id'] = $user_id;
            dbinsert('crm_module_cards', $data);

        }
    }

}
