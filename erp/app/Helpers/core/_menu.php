<?php

function get_conditional_update_list_menu($menu_params)
{
    $list = [];

    $i = 20000;
    $events = \DB::connection('default')->table('erp_conditional_updates')->where('module_id', $menu_params['module_id'])->get();

    foreach ($events as $event) {

        $list[] = ['url' => get_menu_url_from_module_id(1978).'/edit/'.$event->id, 'menu_name' => 'Edit '.$event->target_field.' set to '.$event->update_value, 'menu_icon' => '', 'action_type' => 'form', 'menu_type' => 'link', 'id' => $i, 'new_tab' => 0, 'childs' => []];

        $i++;
    }

    return $list;
}

function get_event_list_menu($menu_params)
{
    $list = [];

    $i = 20000;
    $events = \DB::connection('default')->table('erp_form_events')->where('module_id', $menu_params['module_id'])->orderBy('sort_order')->get();

    foreach ($events as $event) {

        $list[] = ['url' => get_menu_url_from_module_id(385).'/edit/'.$event->id, 'menu_name' => 'Edit '.$event->name, 'menu_icon' => '', 'action_type' => 'form', 'menu_type' => 'link', 'id' => $i, 'new_tab' => 1, 'childs' => []];
        if ($event->type == 'schedule') {
            $list[] = ['url' => '/run_scheduled_event/'.$event->id, 'menu_name' => 'Run '.$event->name, 'menu_icon' => '', 'action_type' => 'ajax', 'menu_type' => 'link', 'id' => $i, 'new_tab' => 1, 'childs' => []];
        }
        $i++;
    }

    return $list;
}
function get_notifications_list_menu($menu_params)
{
    $list = [];

    $i = 20000;
    $events = \DB::connection('default')->table('crm_email_manager')->where('module_id', $menu_params['module_id'])->get();

    foreach ($events as $event) {

        $list[] = ['url' => get_menu_url_from_module_id(556).'/edit/'.$event->id, 'menu_name' => $event->name, 'menu_icon' => '', 'action_type' => 'form', 'menu_type' => 'link', 'id' => $i, 'new_tab' => 1, 'childs' => []];
        $i++;
    }

    return $list;
}

function get_edit_menu_urls()
{
    $list = [];

    $i = 20000;
    $locations = \DB::table('erp_module_fields')->where('module_id', 499)->where('field', 'location')->pluck('opts_values')->first();
    $locations = collect(explode(',', $locations))->filter()->unique()->toArray();
    foreach ($locations as $location) {

        $list[] = ['url' => 'sf_menu_manager/499/'.$location, 'menu_name' => $location, 'menu_icon' => '', 'action_type' => 'view', 'menu_type' => 'link', 'id' => $i, 'new_tab' => 1, 'childs' => []];
        $i++;
    }

    return $list;
}

function get_erp_login_urls()
{
    $list = [];
    $instance_list = get_instances_list();
    $i = 10000;

    foreach ($instance_list as $instance) {
        if ($instance->id == 1) {
            $main_instance = $instance;
        }
        if (session('instance')->id == $instance->id) {
            continue;
        }

        $user_exists = \DB::connection($instance->db_connection)->table('erp_users')->where('username', session('username'))->pluck('id')->first();
        if (! $user_exists) {
            continue;
        }

        // $instance->login_url .= '&redirect_page='.request()->path();

        $instance_access = get_admin_instance_access_session();

        if (! in_array($instance->id, $instance_access)) {
            continue;
        }

        //$list[] = ['url' => $instance->login_url, 'menu_name' => $instance->name, 'menu_icon' => '', 'menu_type' => 'link', 'id' => $i, 'new_tab' => 1, 'childs' => []];
        $list[] = ['menu_url' => $instance->login_url, 'menu_name' => $instance->name, 'menu_icon' => '', 'menu_type' => 'link', 'id' => $i, 'new_tab' => 1, 'childs' => []];

        $i++;
    }
    if (is_dev() || session('role_id') == 1) {
        //$communica_url = 'communica.erpcloud.co.za';
        //$list[] = ['menu_url' => $instance->login_url, 'menu_name' => 'Communica', 'menu_icon' => '', 'menu_type' => 'link', 'id' => $i, 'new_tab' => 1, 'childs' => []];
        //    $list[] = ['url' => url('update_instances'), 'menu_name' => 'Update Instances','action_type'=>'ajax', 'menu_type' => 'link', 'id' => $i, 'new_tab' => 0, 'childs' => []];
    }

    return $list;
}
