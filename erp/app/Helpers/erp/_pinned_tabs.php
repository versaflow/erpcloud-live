<?php

function aftersave_pinned_tabs_copy($request)
{
    if (is_main_instance()) {
        $pinned_tabs = DB::table('erp_favorites')->get();
        $instances = \DB::table('erp_instances')->where('id', '!=', 1)->where('sync_erp', 1)->get();
        foreach ($instances as $instance) {
            DB::connection($instance->db_connection)->table('erp_favorites')->truncate();
            $pinned_tab_rows = [];
            foreach ($pinned_tabs as $pinned_tab) {
                if (! empty($pinned_tab->instance_id)) {
                    $copy_instance_ids = explode(',', $pinned_tab->instance_id);
                    if (in_array($instance->id, $copy_instance_ids)) {
                        $data = (array) $pinned_tab;
                        $pinned_tab_rows[] = (array) $data;
                    }
                }
            }

            $pinned_tab_collections = collect($pinned_tab_rows);
            foreach ($pinned_tab_collections->chunk(50) as $pinned_tab_collection) {
                DB::connection($i->db_connection)->table('erp_favorites')->updateorinsert(['id' => $pinned_tab_collection->toArray()->id], $pinned_tab_collection->toArray());
            }
        }
    }
}

function aftersave_pinned_tabs_set_permissions($request)
{
    set_pinnedtab_permissions();
}

function set_pinnedtab_permissions()
{

    return false;
    $forms_collection = \DB::connection('default')->table('erp_forms')->get();
    $erp_module_ids = \DB::connection('default')->table('erp_cruds')->pluck('id')->toArray();

    $role_ids = \DB::table('erp_favorites')->pluck('role_id')->filter()->unique()->toArray();

    foreach ($role_ids as $role_id) {
        $role_id_add = $role_id;

        $module_ids = \DB::table('erp_favorites')->where('role_id', $role_id_add)->orWhere('role_id', 0)->pluck('module_id')->filter()->unique()->toArray();

        foreach ($module_ids as $module_id) {
            if (! in_array($module_id, $erp_module_ids)) {
                continue;
            }
            $e = \DB::connection('default')->table('erp_forms')->where('module_id', $module_id)->where('role_id', $role_id_add)->count();
            if (! $e) {
                $data = $forms_collection->where('module_id', $module_id)->where('role_id', 1)->first();
                if (! $data) {
                    $data = ['module_id' => $module_id];
                } else {
                    $data = (array) $data;
                }
                unset($data['id']);

                $data['role_id'] = $role_id_add;
                $data['is_edit'] = 1;
                $data['is_view'] = 1;

                unset($data['is_delete']);

                \DB::table('erp_forms')->insert($data);

            } else {
                \DB::table('erp_forms')->where('module_id', $module_id)->where('role_id', $role_id_add)->update(['is_view' => 1, 'is_edit' => 1]);
            }
        }
    }
}
