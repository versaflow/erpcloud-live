<?php

use App\Http\Controllers\CoreController;
use App\Http\Controllers\CustomController;
use App\Http\Controllers\ModuleController;
use App\Models\_UriValidator;
use Illuminate\Routing\Matching\UriValidator;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Route;

$validators = IlluminateRoute::getValidators();
$validators[] = new _UriValidator;
IlluminateRoute::$validators = array_filter($validators, function ($validator) {
    return get_class($validator) != UriValidator::class;
});

include 'api.php';
include 'website_api.php';
include '_custom.php';
include 'auth.php';
include 'integrations.php';
include 'dashboard.php';
include 'tests.php';
include 'migrations.php';
include 'sidebar.php';
include 'workboard.php';
include 'content_sidebar.php';
include 'store.php';
include 'supportboard.php';
include 'filemanager.php';

Route::any('/', [CoreController::class, 'index']);

Route::get('install', [CoreController::class, 'getInstall']);
Route::post('install', [CoreController::class, 'postInstall']);

Route::any('menu_permissions/{menu_id?}/{module_id?}', function ($menu_id = null, $module_id = null) {
    if ($module_id > 0) {
        $module_forms = get_menu_url_from_table('erp_forms');

        return redirect()->to($module_forms.'?module_id='.$module_id);
    }
    $menu = \DB::connection('default')->table('erp_menu')->where('id', $menu_id)->get()->first();
    if (str_contains($menu->menu_type, 'module') && $menu->module_id > 0) {
        $module_forms = get_menu_url_from_table('erp_forms');

        return redirect()->to($module_forms.'?module_id='.$menu->module_id);
    }
    $id = $menu_id;
    $access_items = get_permission_table($id);

    $view_data['id'] = $id;
    $row = \DB::table('erp_menu')->where('id', $id)->get();

    if (count($row) >= 1) {
        $rows = $row[0];
        $view_data['row'] = (array) $rows;

        $view_data['is_module'] = 0;
        if ($rows->module_id > 0) {
            $view_data['is_module'] = 1;
        }
    }
    if (isset($config['tasks'])) {
        foreach ($config['tasks'] as $row) {
            $access_items[$row['item']] = $row['title'];
        }
    }

    $view_data['tasks'] = $access_items;
    $view_data['groups'] = \DB::table('erp_user_roles')->orderby('sort_order')->get();

    foreach ($view_data['groups'] as $groups) {
        $exists = \DB::table('erp_menu_role_access')->where('menu_id', $id)->where('role_id', $groups->id)->count();
        if (! $exists) {
            \DB::table('erp_menu_role_access')->insert(['menu_id' => $id, 'role_id' => $groups->id]);
        }
    }

    $access = [];
    foreach ($view_data['groups'] as $groups) {
        $access_data = \DB::select("SELECT * FROM erp_menu_role_access where menu_id = '".$id."' and role_id ='".$groups->id."'");

        if (count($access_data) >= 1) {
            $access_data = $access_data[0];
        }

        $rows = [];
        $rows['role_id'] = $groups->id;
        $rows['group_name'] = $groups->name;

        foreach ($access_items as $item => $val) {
            $rows[$item] = (isset($access_data->$item) && $access_data->$item == 1 ? 1 : 0);
        }
        $access[$groups->name] = $rows;
    }
    $view_data['access'] = $access;

    return view('__app.components.permissions', $view_data);
});

Route::any('sidebar_datasource_reports/{module_id?}', function ($module_id) {
    $json = Erp::getSidebarReports($module_id);

    return response()->json($json);
});

Route::any('menu_datasource/{location?}/{module_id?}', function ($location, $module_id) {

    $menus = [];
    if ($location == 'gridtab' || $location == 'grid_menu' || $location == 'related_items_menu') {

        $menu_ids = \DB::connection('default')->table('erp_menu')->where('render_module_id', $module_id)->where('location', $location)->pluck('id')->toArray();
    } else {

        $menu_ids = \DB::connection('default')->table('erp_menu')->where('location', $location)->pluck('id')->toArray();
        // \DB::connection('default')->table('erp_menu')->where('location', $location)->where('parent_id', '!=', 0)->whereNotIn('parent_id', $menu_ids)->update(['parent_id' => 0]);

    }
    if ($location == 'gridtab' || $location == 'grid_menu' || $location == 'module_actions' || $location == 'related_items_menu') {

        $top_parents = \DB::select('select * from erp_menu  where location ="'.$location.'" and  render_module_id="'.$module_id.'" and active=1 order by parent_id, sort_order');
    } else {

        $top_parents = \DB::select('select * from erp_menu  where location ="'.$location.'" and  active=1 order by parent_id, sort_order');
    }

    $menu_url = get_menu_url_from_table('erp_menu');
    if (! empty(request()->where)) {
        $parent_id = request()->where[0]['value'];
    }

    if (! empty($parent_id)) {

        if ($location == 'gridtab' || $location == 'grid_menu' || $location == 'module_actions' || $location == 'related_items_menu') {

            $top_parents = \DB::select('select * from erp_menu  where location ="'.$location.'" and  render_module_id="'.$module_id.'" and  active=1 and parent_id="'.$parent_id.'" order by parent_id, sort_order');

        } else {
            $top_parents = \DB::select('select * from erp_menu  where location ="'.$location.'" and  render_module_id="'.$module_id.'" and  active=1 and parent_id="'.$parent_id.'" order by parent_id, sort_order');
        }

    }

    foreach ($top_parents as $menu) {
        $access = get_permissions_menu_item($menu->id);

        $access_levels = get_permissions_menu_item_role_ids($menu->id);

        if (! empty(session('menu_sort_level')) && ! empty($access_levels) && is_array($access_levels) && count($access_levels) > 0) {
            if (session('menu_sort_level') != 'all') {
                $skip = true;
                if (session('menu_sort_level') == 'customer') {
                    foreach ($access_levels as $access_level) {
                        if ($access_level == 21) {
                            $skip = false;
                        }
                    }
                }
                if (session('menu_sort_level') == 'reseller') {
                    foreach ($access_levels as $access_level) {
                        if ($access_level == 11) {
                            $skip = false;
                        }
                    }
                }
                if (session('menu_sort_level') == 'admin') {
                    foreach ($access_levels as $access_level) {
                        if ($access_level < 10) {
                            $skip = false;
                        }
                    }
                }

                if ($skip) {
                    continue;
                }
            }
        }

        $subitemsCount = \DB::connection('default')->table('erp_menu')->where('parent_id', $menu->id)->count();
        $menuObj = (object) [];
        $menuObj->id = $menu->id;
        $menuObj->access = (! empty($access)) ? implode(' | ', $access) : '';
        $menuObj->disabled_access = (! empty($disabled_access)) ? 'Disabled' : '';
        $menuObj->parentID = (! empty($menu->parent_id)) ? $menu->parent_id : null;
        $menuObj->text = $menu->menu_name;
        $menuObj->hasChildren = ($subitemsCount > 0) ? true : false;
        $menuObj->target = '';
        if ($menu->menu_type == 'link' && $menu->url == '#') {
            $menuObj->target = '';
        } elseif ($menu->modal == 'view') {
            $menuObj->target = 'view modal';
        } elseif ($menu->modal == 'form') {
            $menuObj->target = 'form modal';
        } elseif ($menu->modal > '') {
            $menuObj->target = $menu->modal;
        } elseif ($menu->new_tab == 1) {
            $menuObj->target = '';
        }
        if ($menuObj->access) {
            $menus[] = $menuObj;
        }
    }

    echo json_encode($menus);
});

Route::any('menu_sort_tabs/{module_id?}', function ($module_id) {
    // set unlisted

    $menus = \DB::connection('default')->table('erp_menu')->get();
    foreach ($menus as $menu) {
        $access = get_permissions_menu_item($menu->id);
        if (count($access) == 0) {
            //\DB::connection('default')->table('erp_menu')->where('id', $menu->id)->update(['unlisted' => 1]);
        } else {
            \DB::connection('default')->table('erp_menu')->where('id', $menu->id)->update(['unlisted' => 0]);
        }
    }
    \DB::connection('default')->table('erp_menu')->where('unlisted', 1)->update(['parent_id' => 0]);

    $data['locations'] = \DB::table('erp_menu')->orderBy('render_module_id', 'desc')->orderBy('location', 'asc')->pluck('location')->unique()->filter()->toArray();
    $data['module_id'] = $module_id;

    return view('__app.components.menu_sort_tabs', $data);
});

Route::any('menu_sort_levels/{level?}', function ($level = 'all') {
    session(['menu_sort_level' => $level]);

    return json_alert('Done');
});

Route::any('menu_sort/{location?}', function ($location = 'top_main_menu') {
    $data = [];
    $data['location'] = $location;
    $data['menu_data'] = \DB::connection('default')->table('erp_menu')->select('id', 'parent_id', 'menu_name', 'sort_order')->where('unlisted', 0)->where('location', $location)->orderBy('sort_order')->get()->toArray();
    foreach ($data['menu_data'] as $i => $v) {
        if (! $v->parent_id) {
            $data['menu_data'][$i]->parent_id = null;
        }
    }

    return view('__app.components.syncfusion_treegrid', $data);
});

Route::any('pbx_menu_sort', function () {
    if (is_superadmin()) {
        $data['menu_data'] = \DB::connection('pbx')->table('v_menu_items')
            ->select('menu_item_uuid', 'menu_item_parent_uuid', 'menu_item_title', 'menu_item_order')
            ->orderBy('menu_item_order')
            ->get()->toArray();

        return view('__app.components.pbx_menu_sort', $data);
    }
});
Route::any('menu_mvr/{menu_id?}', function ($id) {
    if (is_superadmin()) {
        \DB::connection('default')->table('erp_menu')->where('id', $id)->update(['parent_id' => 0]);
    }

});

Route::any('menu_show_toolbar/{menu_id?}/{show_on_toolbar?}', function ($menu_id = 0, $show_on_toolbar = 0) {
    if (session('role_level') != 'Admin') {
        return json_alert('No access', 'warning');
    }
    if (! $menu_id) {
        return json_alert('Invalid menu id', 'warning');
    }
    \DB::connection('default')->table('erp_menu')
        ->where('id', $menu_id)
        ->update(['show_directly_on_toolbar' => $show_on_toolbar]);

    cache_clear();

    return json_alert('Done');

});

Route::any('menu_to_root/{menu_id?}', function ($menu_id = 0) {
    if (session('role_level') != 'Admin') {
        return json_alert('No access', 'warning');
    }
    if (! $menu_id) {
        return json_alert('Invalid menu id', 'warning');
    }
    \DB::connection('default')->table('erp_menu')
        ->where('id', $menu_id)
        ->update(['unlisted' => 0, 'parent_id' => 0]);

    cache_clear();

    return json_alert('Done');

});

Route::any('sf_menu_manager/{module_id?}/{location?}', function ($module_id = 0, $location = 'main_menu') {

    $data = [];
    $data['locations'] = \DB::table('erp_menu')->orderBy('render_module_id', 'desc')->orderBy('location', 'asc')->pluck('location')->unique()->filter()->toArray();
    $data['locations'] = array_values($data['locations']);
    \DB::table('erp_menu')->where('location', 'gridtab')->update(['unlisted' => 0]);
    $data['location'] = $location;
    $data['module_id'] = $module_id;
    $data['menu_data'] = [];

    if ($location == 'grid_menu' || $location == 'module_menu' || $location == 'module_actions' || $location == 'related_items_menu' || $location == 'gridtab') {
        $data['menu_data'] = \DB::connection('default')->table('erp_menu')
            ->select('id', 'parent_id', 'menu_type', 'module_id', 'menu_name', 'sort_order')
            ->where('unlisted', 0)->where('location', $location)
            ->where('render_module_id', $module_id)
            ->orderBy('sort_order')
            ->get()->toArray();
        if ($location == 'gridtab') {
            $primary_tab = \DB::connection('default')->table('erp_menu')
                ->select('id', 'parent_id', 'menu_type', 'module_id', 'menu_name', 'sort_order')
                ->where('location', '!=', 'gridtab')
                ->where('module_id', $module_id)
                ->get()->first();
            if ($primary_tab) {
                array_unshift($data['menu_data'], $primary_tab);
            }
        }

    } else {
        $data['menu_data'] = \DB::connection('default')->table('erp_menu')
            ->select('id', 'parent_id', 'menu_type', 'module_id', 'menu_name', 'sort_order')
            ->where('unlisted', 0)->where('location', $location)->orderBy('sort_order')
            ->get()->toArray();
    }
    foreach ($data['menu_data'] as $i => $v) {
        if (! $v->parent_id) {
            $data['menu_data'][$i]->parent_id = null;
        }
    }
    $menu_data = $data['menu_data'];
    $forms = \DB::connection('default')->table('erp_forms')->get();
    foreach ($menu_data as $i => $item) {
        if ($menu_data[$i] && ! $item->parent_id) {
            $menu_data[$i]->parent_id = null;
        }

        if ($item->module_id) {
            $customer_access = $forms->where('module_id', $item->module_id)->where('is_view', 1)->where('role_id', 21)->count();
            $reseller_access = $forms->where('module_id', $item->module_id)->where('is_view', 1)->where('role_id', 11)->count();

            if ($reseller_access && $customer_access) {
                $menu_data[$i]->menu_name .= ' (CR)';
            } elseif ($customer_access) {
                $menu_data[$i]->menu_name .= ' (C)';
            } elseif ($reseller_access) {
                $menu_data[$i]->menu_name .= ' (R)';
            }
        }
    }
    $data['menu_data'] = $menu_data;
    $data['menu_manager_url'] = get_menu_url_from_table('erp_menu');

    return view('__app.components.sf_menu_manager', $data);
});

Route::any('sf_menu_manager_datasource/{location?}/{module_id?}', function ($location, $module_id) {
    //aa('sf_menu_manager_datasource');
    if ($location == 'grid_menu' || $location == 'module_actions' || $location == 'module_menu' || $location == 'related_items_menu' || $location == 'gridtab') {
        $menu_data = \DB::connection('default')->table('erp_menu')->select('id', 'module_id', 'parent_id', 'menu_name', 'sort_order')
            ->where('unlisted', 0)->where('location', $location)->where('render_module_id', $module_id)
            ->orderBy('sort_order')->get()->toArray();
        if ($location == 'gridtab') {
            $primary_tab = \DB::connection('default')->table('erp_menu')
                ->select('id', 'module_id', 'parent_id', 'menu_type', 'module_id', 'menu_name', 'sort_order')
                ->where('location', 'main_menu')
                ->where('module_id', $module_id)
                ->get()->first();
            array_unshift($menu_data, $primary_tab);
        }
    } else {
        $menu_data = \DB::connection('default')->table('erp_menu')->select('id', 'module_id', 'parent_id', 'menu_name', 'menu_type', 'sort_order')
            ->where('unlisted', 0)->where('location', $location)
            ->orderBy('sort_order')
            ->get()->toArray();
    }
    $forms = \DB::connection('default')->table('erp_forms')->get();
    foreach ($menu_data as $i => $item) {
        if ($menu_data[$i] && ! $item->parent_id) {
            $menu_data[$i]->parent_id = null;
        }

        if ($item->module_id) {
            $customer_access = $forms->where('module_id', $item->module_id)->where('is_view', 1)->where('role_id', 21)->count();
            $reseller_access = $forms->where('module_id', $item->module_id)->where('is_view', 1)->where('role_id', 11)->count();

            if ($reseller_access && $customer_access) {
                $menu_data[$i]->menu_name .= ' (CR)';
            } elseif ($customer_access) {
                $menu_data[$i]->menu_name .= ' (C)';
            } elseif ($reseller_access) {
                $menu_data[$i]->menu_name .= ' (R)';
            }
        }
    }

    return $menu_data;
    // return response()->json($menu_data);
});

Route::any('fields_sort/{module_id?}', function ($module_id) {
    $data = ['module_id' => $module_id];
    $field_data = [];
    $field_groups = \DB::connection('default')->table('erp_grid_views')->where('module_id', $module_id)->orderBy('sort_order')->groupBy('group')->pluck('group')->filter()->toArray();
    foreach ($field_groups as $i => $field_group) {
        $field_data[] = ['id' => 'group_'.$field_group, 'name' => $field_group, 'sort_order' => $i, 'parent_id' => null, 'type' => 'group'];
    }
    $fields = \DB::table('erp_grid_views')->where('module_id', $module_id)->orderBy('sort_order')->get();
    foreach ($fields as $field) {
        $parent_id = null;
        if ($field->group) {
            $parent_id = 'group_'.$field->group;
        }
        $field_data[] = ['id' => 'field_'.$field->id, 'name' => $field->name, 'sort_order' => $field->sort_order, 'parent_id' => $parent_id, 'type' => 'field'];
    }
    $data['field_data'] = collect($field_data)->sortBy('sort_order');

    $data['fields_url'] = get_menu_url_from_table('erp_grid_views');

    return view('__app.forms.sf_fields_manager', $data);
});

Route::any('fields_sort_save', function (Illuminate\Http\Request $request) {
    foreach ($request->rows as $i => $row) {
        $row = (object) $row;
        if (! str_contains($row->id, 'group_')) {

            $id = str_replace('field_', '', $row->id);
            $update_data = ['sort_order' => $i];
            if (! empty($row->parent_id)) {
                $update_data['group'] = str_replace('group_', '', $row->parent_id);
            } else {
                $update_data['group'] = '';
            }

            \DB::connection('default')->table('erp_grid_views')->where('module_id', $request->module_id)->where('id', $id)->update($update_data);
        }
    }
});

Route::any('layouts_sort/{module_id?}', function ($module_id) {
    $data = ['module_id' => $module_id];
    $layout_data = [];
    $layout_groups = \DB::connection('default')->table('erp_grid_views')->where('module_id', $module_id)->orderBy('sort_order')->groupBy('group')->pluck('group')->filter()->toArray();
    foreach ($layout_groups as $i => $layout_group) {
        $layout_data[] = ['id' => 'group_'.$layout_group, 'name' => $layout_group, 'sort_order' => $i, 'parent_id' => null, 'type' => 'group'];
    }
    $layouts = \DB::table('erp_grid_views')->where('module_id', $module_id)->orderBy('sort_order')->get();
    foreach ($layouts as $layout) {
        $parent_id = null;
        if ($layout->group) {
            $parent_id = 'group_'.$layout->group;
        }
        $layout_data[] = ['id' => 'layout_'.$layout->id, 'name' => $layout->name, 'sort_order' => $layout->sort_order, 'parent_id' => $parent_id, 'type' => 'layout'];
    }
    $data['layout_data'] = collect($layout_data)->sortBy('sort_order');

    $data['layouts_url'] = get_menu_url_from_table('erp_grid_views');

    return view('__app.components.sf_layouts_manager', $data);
});

Route::any('layouts_sort_save', function (Illuminate\Http\Request $request) {
    foreach ($request->rows as $i => $row) {
        $row = (object) $row;
        if (! str_contains($row->id, 'group_')) {

            $id = str_replace('layout_', '', $row->id);
            $update_data = ['sort_order' => $i];
            if (! empty($row->parent_id)) {
                $update_data['group'] = str_replace('group_', '', $row->parent_id);
            } else {
                $update_data['group'] = '';
            }

            \DB::connection('default')->table('erp_grid_views')->where('module_id', $request->module_id)->where('id', $id)->update($update_data);
        }
    }
});

Route::any('button_sort/{module_id?}', function ($module_id) {
    $data = [];
    $data['location'] = 'grid_menu';
    $data['menu_data'] = \DB::connection('default')->table('erp_menu')->select('id', 'parent_id', 'menu_name', 'sort_order')
        ->where('unlisted', 0)
        ->where('render_module_id', $module_id)
        ->where('location', 'grid_menu')
        ->orderBy('sort_order')->get()->toArray();
    foreach ($data['menu_data'] as $i => $v) {
        if (! $v->parent_id) {
            $data['menu_data'][$i]->parent_id = null;
        }
    }

    return view('__app.components.syncfusion_treegrid', $data);
});

Route::any('tab_sort/{module_id?}', function ($module_id) {
    $data = [];
    $data['location'] = 'gridtab';
    $data['menu_data'] = \DB::connection('default')->table('erp_menu')->select('id', 'parent_id', 'menu_name', 'sort_order')
        ->where('unlisted', 0)
        ->where('render_module_id', $module_id)
        ->where('location', 'gridtab')
        ->orderBy('sort_order')->get()->toArray();
    foreach ($data['menu_data'] as $i => $v) {
        if (! $v->parent_id) {
            $data['menu_data'][$i]->parent_id = null;
        }
    }

    return view('__app.components.syncfusion_treegrid', $data);
});

Route::any('menu_manager_bulk_edit', function (Illuminate\Http\Request $request) {

    $menu_collection = \DB::table('erp_menu')->get();
    $menu = collect($request->rows);
    $moved_menu_ids = [];
    foreach ($menu as $i => $row) {
        $sort_order = $i;
        $id = $row['id'];
        $parent_id = $row['parent_id'];
        if (empty($parent_id)) {
            $parent_id = 0;
        }
        $current_parent_id = $menu_collection->where('id', $id)->pluck('parent_id')->first();
        if ($current_parent_id != $parent_id) {
            $moved_menu_ids[] = $id;
        }
        \DB::table('erp_menu')->where('id', $id)->update(['parent_id' => $parent_id, 'sort_order' => $sort_order]);
    }
    /*
    $count = 0;
    $count = \Erp::reorder_menu('main_menu', 0, $count);
    $count = \Erp::reorder_menu('top_main_menu', 0, $count);
    $count = \Erp::reorder_menu('top_right_menu', 0, $count);
    $count = \Erp::reorder_menu('context_builder', 0, $count);

    $count = \Erp::reorder_events_menu();
    */

    //  if(count($moved_menu_ids) > 0 ){
    build_permissions_from_menu($moved_menu_ids);
    cache_clear();

    // }
    return json_alert('Done');

});

Route::any('menu_manager_build_permissions', function (Illuminate\Http\Request $request) {

    build_permissions_from_menu();
    cache_clear();

    return json_alert('Done');

});
Route::any('cache_clear', function (Illuminate\Http\Request $request) {

    cache_clear();

    return json_alert('Done');

});

Route::any('menu_sort_ajax/{location?}/{id?}/{target_id?}/{position?}', function ($location, $id, $target_id, $position) {
    \DB::connection('default')->table('erp_menu')->whereNull('sort_order')->update(['sort_order' => 0]);
    if ($id != $target_id) {
        if ($position == 'middleSegment') {
            \DB::connection('default')->table('erp_menu')->where('id', $id)->update(['parent_id' => $target_id]);
        }
        if ($position == 'bottomSegment') {
            $target = \DB::connection('default')->table('erp_menu')->select('id', 'parent_id', 'sort_order')->where('id', $target_id)->get()->first();
            \DB::connection('default')->table('erp_menu')->where('sort_order', '<=', $target->sort_order)->decrement('sort_order');
            \DB::connection('default')->table('erp_menu')->where('id', $id)->update(['parent_id' => $target->parent_id, 'sort_order' => $target->sort_order]);
        }
        if ($position == 'topSegment') {
            $target = \DB::connection('default')->table('erp_menu')->select('id', 'parent_id', 'sort_order')->where('id', $target_id)->get()->first();

            \DB::connection('default')->table('erp_menu')->where('sort_order', '>=', $target->sort_order)->increment('sort_order');
            \DB::connection('default')->table('erp_menu')->where('id', $id)->update(['parent_id' => $target->parent_id, 'sort_order' => $target->sort_order]);
        }
    }

    $count = 0;
    $count = \Erp::reorder_menu('main_menu', 0, $count);
    $count = \Erp::reorder_menu('top_main_menu', 0, $count);
    $count = \Erp::reorder_menu('top_right_menu', 0, $count);
    $count = \Erp::reorder_menu('context_builder', 0, $count);
    $count = \Erp::reorder_events_menu();
    build_permissions_from_menu();
    cache_clear();

    return json_alert('Done');

});

Route::any('pbx_menu_sort_ajax/{menu_item_uuid?}/{target_id?}/{position?}', function ($menu_item_uuid, $target_id, $position) {
    if (is_superadmin()) {
        if ($menu_item_uuid != $target_id) {
            if ($position == 'middleSegment') {
                \DB::connection('pbx')->table('v_menu_items')->where('menu_item_uuid', $menu_item_uuid)->update(['menu_item_parent_uuid' => $target_id]);
            }
            if ($position == 'bottomSegment') {
                $target = \DB::connection('pbx')->table('v_menu_items')->select('menu_item_uuid', 'menu_item_parent_uuid', 'menu_item_order')->where('menu_item_uuid', $target_id)->get()->first();
                \DB::connection('pbx')->table('v_menu_items')->where('menu_item_order', '<=', $target->menu_item_order)->decrement('menu_item_order');
                \DB::connection('pbx')->table('v_menu_items')->where('menu_item_uuid', $menu_item_uuid)->update(['menu_item_parent_uuid' => $target->menu_item_parent_uuid, 'menu_item_order' => $target->menu_item_order]);
            }
            if ($position == 'topSegment') {
                $target = \DB::connection('pbx')->table('v_menu_items')->select('menu_item_uuid', 'menu_item_parent_uuid', 'menu_item_order')->where('menu_item_uuid', $target_id)->get()->first();

                \DB::connection('pbx')->table('v_menu_items')->where('menu_item_order', '>=', $target->menu_item_order)->increment('menu_item_order');
                \DB::connection('pbx')->table('v_menu_items')->where('menu_item_uuid', $menu_item_uuid)->update(['menu_item_parent_uuid' => $target->menu_item_parent_uuid, 'menu_item_order' => $target->menu_item_order]);
            }
        }

        \Erp::reorder_menu('pbx_menu', 0, $count);

        return json_alert('Done');
    }
});
// Custom Controller
//Route::any('provision_service', 'CustomController@provisionService');

Route::any('provision', [CustomController::class, 'provisionWizard']);
Route::any('provision_service/{table?}/{id?}', [CustomController::class, 'provisionService']);
Route::any('provision_service_post', [CustomController::class, 'provisionServicePost']);

Route::any('service_deactivate', [CustomController::class, 'deactivateWizard']);
Route::any('deactivate_service/{table?}/{id?}', [CustomController::class, 'deactivateService']);
Route::any('deactivate_service_post', [CustomController::class, 'deactivateServicePost']);

Route::any('support_form_post', [CustomController::class, 'supportFormPost']);

Route::get('provision_test', function () {
    return 'true';
});

Route::get('send_email', function () {
    return email_form(1);
});

Route::get('email_form/{type?}/{id?}/{to_email?}', function ($type, $id, $to_email = false) {
    $data = [];

    if ($type == 'statement_email') {
        $email_id = \DB::connection('default')->table('crm_email_manager')->where('internal_function', 'statement_email')->pluck('id')->first();
        $account_id = $id;
    }

    if ($type == 'full_statement_email') {
        $email_id = \DB::connection('default')->table('crm_email_manager')->where('internal_function', 'full_statement_email')->pluck('id')->first();
        $account_id = $id;
    }

    if ($type == 'supplier_statement_email') {
        $email_id = \DB::connection('default')->table('crm_email_manager')->where('internal_function', 'supplier_statement_email')->pluck('id')->first();
        $account_id = $id;
        $data['customer_type'] = 'supplier';
    }

    if ($type == 'supplier_full_statement_email') {
        $email_id = \DB::connection('default')->table('crm_email_manager')->where('internal_function', 'supplier_full_statement_email')->pluck('id')->first();
        $account_id = $id;
        $data['customer_type'] = 'supplier';
    }

    if ($type == 'suppliers') {
        $email_id = 1;
        $account_id = $id;
        $data['customer_type'] = 'supplier';
    }

    if ($type == 'default') {
        $email_id = 1;
        $account_id = $id;
    }
    if ($type == 'lead') {
        $email_id = 1;
        $account_id = $id;
        $data['customer_type'] = 'lead';
    }

    if (! empty(request('email_id'))) {
        $email_id = request('email_id');
    }

    if ($type == 'documents') {
        $doc = \DB::connection('default')->table('crm_documents')->where('id', $id)->get()->first();

        if (session('role_id') > 10 && ! empty($doc->reseller_user)) {
            $account_id = $doc->reseller_user;
            $pdf = servicedocument_pdf($id, false);
        } else {
            $account_id = $doc->account_id;
            $pdf = document_pdf($id, false);
        }

        $doctype_label = \DB::connection('default')->table('acc_doctypes')->where('doctype', $doc->doctype)->pluck('doctype_label')->first();
        if (empty($doctype_label)) {
            $doctype_label = $doc->doctype;
        }

        $doc->doctype = $doctype_label;

        $file = str_replace(' ', '_', ucfirst($doc->doctype).' '.$doc->id).'.pdf';
        $filename = attachments_path().$file;
        $fileurl = attachments_url().$file;

        if (file_exists($filename)) {
            unlink($filename);
        }
        $pdf->save($filename);
        $email_id = \DB::connection('default')->table('crm_email_manager')->where('internal_function', 'document_email')->pluck('id')->first();

        $data['attachment'] = $file;
        $data['doctype'] = $doc->doctype;
        $data['docid'] = $doc->id;

        $account = dbgetaccount($account_id);
        if ($account->partner_id == 1) {
            $existing_debit_order = \DB::table('acc_debit_orders')->where('account_id', $account_id)->where('status', '!=', 'Deleted')->count();
            $requires_debit_order = invoice_requires_debit_order($id);
            $data['show_debit_order_link'] = false;
            if (is_main_instance() && ! $existing_debit_order && $requires_debit_order) {
                $webform_data = [];
                $webform_data['module_id'] = 390;
                $webform_data['account_id'] = $account_id;

                $link_data = \Erp::encode($webform_data);

                $data['webform_link'] = '<a href="'.request()->root().'/webform/'.$link_data.'" >Service Contract</a>';
                $data['show_debit_order_link'] = true;
            }
        }

    }

    if ($type == 'supplier_documents') {
        $doc = \DB::connection('default')->table('crm_supplier_documents')->where('id', $id)->get()->first();
        $pdf = document_pdf($id, true);
        $file = str_replace(' ', '_', ucfirst($doc->doctype).' '.$doc->id).'.pdf';
        $filename = attachments_path().$file;
        $fileurl = attachments_url().$file;

        if (file_exists($filename)) {
            unlink($filename);
        }
        $pdf->save($filename);
        $email_id = \DB::connection('default')->table('crm_email_manager')->where('internal_function', 'document_email')->pluck('id')->first();
        $account_id = $doc->account_id;
        $data['attachment'] = $file;
        $data['doctype'] = $doc->doctype;
    }

    if ($type == 'usd_documents') {
        $doc = \DB::connection('default')->table('crm_documents')->where('id', $id)->get()->first();
        $pdf = document_pdf($id);
        $file = str_replace(' ', '_', ucfirst($doc->doctype).' '.$doc->id).'.pdf';
        $filename = attachments_path().$file;
        $fileurl = attachments_url().$file;

        if (file_exists($filename)) {
            unlink($filename);
        }
        $pdf->save($filename);
        $email_id = \DB::connection('default')->table('crm_email_manager')->where('internal_function', 'document_email')->pluck('id')->first();
        $account_id = $doc->account_id;
        $data['attachment'] = $file;
        $data['doctype'] = $doc->doctype;
    }

    if (! empty($to_email)) {
        $data['force_to_email'] = $to_email;
    }
    if (! $account_id) {
        return json_alert('account not found', 'warning');
    }

    if (session('instance')->id == 11) {
        //  return redirect()->to('ticket_system_compose'.$to_email);
    }
    if (request('faq_id')) {
        $data['faq_id'] = request('faq_id');
    }
    if (request('notification_id')) {
        $data['load_notification_id'] = request('notification_id');
        $email_id = request('notification_id');
    }

    if (request('user_id')) {
        $data['user_id'] = request('user_id');
    }

    if (request('newsletter_id')) {
        $data['newsletter_id'] = request('newsletter_id');
    }

    return email_form($email_id, $account_id, $data);
});

Route::post('email_send/{any?}', [CustomController::class, 'emailSend']);

Route::get('bulkemail_form/{any?}', [CustomController::class, 'bulkEmailForm']);
Route::post('bulkemail_send', [CustomController::class, 'bulkEmailSend']);

Route::any('context_menu/{menu_name?}/{action?}/{id?}', [CustomController::class, 'contextMenu']);
Route::any('form_change_ajax/{function?}', [CustomController::class, 'formChangeAjax']);

Route::post('permissions_save', [CustomController::class, 'permissionsSave']);
Route::post('supplier_invoice_upload/{id?}', [CustomController::class, 'supplierInvoiceUpload']);
Route::post('banking_invoice_upload/{id?}', [CustomController::class, 'bankingInvoiceUpload']);
Route::post('debtors_file_upload', [CustomController::class, 'debtorsFileUpload']);
Route::post('deliveries_pod/{id?}', [CustomController::class, 'deliveriesPodUpload']);
Route::post('journal_invoice_upload/{id?}', [CustomController::class, 'journalInvoiceUpload']);

Route::post('documents_invoice_upload/{id?}', [CustomController::class, 'documentsInvoiceUpload']);

Route::post('pricelist_send', [CustomController::class, 'pricelistSend']);

Route::post('cashbook_allocate', [CustomController::class, 'cashbookAllocate']);

Route::any('subscription_migrate', [CustomController::class, 'subscriptionMigrate']);

Route::any('sms_report/{account_id?}', [CustomController::class, 'smsReport']);

Route::get('manage_mailboxes/{sub_id?}', function ($sub_id) {
    $sub = \DB::table('sub_services')->where('id', $sub_id)->get()->first();
    if ($sub->provision_type != 'hosting') {
        return json_alert('Mailbox not available for this subscription.', 'error');
    }

    $data['subscription_id'] = $sub->id;
    $data['mailbox_accounts'] = [];
    $data['domain'] = $sub->detail;
    $data['account_id'] = $sub->account_id;

    if ($sub->provision_type == 'sitebuilder' || $sub->provision_type == 'hosting') {
        $ix = new Interworx;
        $emails = $ix->setDomain($sub->detail)->listEmailBoxes();

        if ($emails && $emails['payload'] && is_array($emails['payload']) && count($emails['payload']) > 0) {
            $data['mailbox_accounts'] = collect($emails['payload'])->pluck('username')->toArray();
        }

        $data['api'] = 'interworx';
        $data['mail_list'] = implode(',', $data['mailbox_accounts']);
    }

    return view('__app.button_views.manage_mailboxes', $data);
});

Route::any('manage_mailbox_send/{sub_id?}', function ($sub_id) {
    $request = request();
    $site = \DB::table('isp_host_websites')->where('domain', $request->domain)->get()->first();

    // SEND PASSWORD EMAIL
    if (empty($request->mailboxuser_send)) {
        return json_alert('Invalid Mailbox.', 'warning');
    }

    $exists = \DB::table('isp_hosting_emails')->where('subscription_id', $sub_id)->where('email', $request->mailboxuser_send.'@'.$site->domain)->count();
    if (! $exists) {
        return json_alert('Password needs to be changed and saved first.', 'warning');
    }
    $hosting_email = \DB::table('isp_hosting_emails')->where('subscription_id', $sub_id)->where('email', $request->mailboxuser_send.'@'.$site->domain)->get()->first();

    $email_data['email_address'] = $hosting_email->email;
    $email_data['password'] = $hosting_email->pass;
    $email_data['domain'] = $site->domain;
    $email_data['test_debug'] = 1;
    $function_variables = get_defined_vars();
    $email_data['internal_function'] = 'send_interworx_email_details';

    $email_id = \DB::table('crm_email_manager')->where('internal_function', 'send_interworx_email_details')->pluck('id')->first();

    return email_form($email_id, $site->account_id, $email_data);
});

Route::any('postmailbox', [CustomController::class, 'postMailBox']);
Route::any('postftp', [CustomController::class, 'postFtp']);

Route::get('view_statement/{full_statement?}', function ($full_statement = false) {
    $account_id = session('account_id');
    $file = 'Statement_'.$account_id.'_'.date('Y_m_d').'.pdf';
    $pdf = statement_pdf($account_id, $full_statement);
    savepdf($pdf, $file);

    return view('__app.components.pdf', ['menu_name' => 'Statement', 'pdf' => attachments_url().$file]);
})->name('view_statement');

// Module Controller
Route::any('{module}', [ModuleController::class, 'index']);
Route::any('{module}/minigrid', [ModuleController::class, 'miniGrid']);
Route::any('{module}/data', [ModuleController::class, 'data']);

Route::any('{module}/linked_modules', [ModuleController::class, 'linkedModules']);
Route::any('{module}/kanban/{layout_id?}', [ModuleController::class, 'kanban']);
Route::any('{module}/kanban_data', [ModuleController::class, 'kanbanData']);
Route::any('{module}/kanban_update', [ModuleController::class, 'kanbanUpdate']);
Route::any('{module}/aggrid_data', [ModuleController::class, 'aggridData']);
Route::any('{module}/aggrid_refresh_row', [ModuleController::class, 'aggridRefreshRow']);
Route::any('{module}/aggrid_refresh_data', [ModuleController::class, 'aggridRefreshData']);
Route::any('{module}/aggrid_detail_data', [ModuleController::class, 'aggridDetailData']);
Route::any('{module}/aggrid_detail_search', [ModuleController::class, 'aggridDetailSearch']);
Route::any('{module}/aggrid_layout_data', [ModuleController::class, 'aggridLayoutData']);
Route::any('{module}/aggrid_sidebar_data', [ModuleController::class, 'aggridSidebarData']);
Route::any('{module}/aggrid_layout_save', [ModuleController::class, 'aggridLayoutSave']);
Route::any('{module}/aggrid_communications_panel', [ModuleController::class, 'aggridCommunicationsPanel']);

Route::any('{module}/get_report_config/{id?}', [ModuleController::class, 'aggridReportConfig']);
Route::any('{module}/view_report/{id?}', [ModuleController::class, 'aggridReport']);

Route::any('{module}/report_data/{id?}', [ModuleController::class, 'aggridReportData']);
Route::any('{module}/report_client_data/{id?}', [ModuleController::class, 'aggridReportClientData']);
Route::any('{module}/report_state_save/{id?}', [ModuleController::class, 'aggridReportStateSave']);
Route::any('{module}/report_state_load/{id?}', [ModuleController::class, 'aggridReportStateLoad']);

Route::any('{module}/report_calculated_fields/{action?}/{report_id?}', [ModuleController::class, 'aggridReportCalculatedFields']);

Route::any('{module}/layoutdata', [ModuleController::class, 'getLayoutData']);

Route::get('{module}/view/{id?}', [ModuleController::class, 'getView']);

Route::get('{module}/getnotes/{id?}', [ModuleController::class, 'getRecordNotes']);
Route::any('{module}/addnote', [ModuleController::class, 'addRecordNote']);
Route::any('{module}/deletenote', [ModuleController::class, 'deleteRecordNote']);

Route::get('{module}/getcontacts/{account_type?}/{id?}', [ModuleController::class, 'getRecordContacts']);
Route::any('{module}/addcontact', [ModuleController::class, 'addRecordContact']);
Route::any('{module}/deletecontact', [ModuleController::class, 'deleteRecordContact']);

Route::get('{module}/getfiles/{id?}', [ModuleController::class, 'getRecordFiles']);
Route::any('{module}/addfile', [ModuleController::class, 'addRecordFile']);
Route::any('{module}/deletefile', [ModuleController::class, 'deleteRecordFile']);

Route::get('{module}/getchangelog/{id?}', [ModuleController::class, 'getRecordChangeLog']);
Route::get('{module}/edit/{id?}', [ModuleController::class, 'getEdit']);
Route::get('{module}/cancelform/{id?}', [ModuleController::class, 'getCancelForm']);
Route::get('{module}/import', [ModuleController::class, 'getImport']);
Route::post('{module}/postimport/{data?}', [ModuleController::class, 'postImport']);

Route::post('{module}/save/{data?}', [ModuleController::class, 'postSave']);

Route::post('{module}/cell_editor/{data?}', [ModuleController::class, 'getCellEditor']);
Route::post('{module}/save_cell/{data?}', [ModuleController::class, 'postSaveCell']);
Route::post('{module}/update_tree_data/{data?}', [ModuleController::class, 'updateTreeData']);
Route::post('{module}/save_row/{data?}', [ModuleController::class, 'postSaveRow']);
Route::post('{module}/save_header/{data?}', [ModuleController::class, 'postSaveHeader']);
Route::post('{module}/duplicate/{data?}', [ModuleController::class, 'postDuplicate']);
Route::post('{module}/approve/{data?}', [ModuleController::class, 'postApproveTransaction']);
Route::post('{module}/delete/{data?}', [ModuleController::class, 'postDelete']);
Route::post('{module}/manager_delete/{data?}', [ModuleController::class, 'postManagerDelete']);
Route::post('{module}/restore/{data?}', [ModuleController::class, 'postRestore']);

Route::any('{module}/cancel/{data?}', [ModuleController::class, 'postCancel']);
Route::post('{module}/cancelform/{data?}', [ModuleController::class, 'postCancelForm']);
Route::post('{module}/sort/{data?}', [ModuleController::class, 'postSort']);
Route::post('{module}/password_confirmed_action/{data?}', [ModuleController::class, 'passwordConfirmedAction']);

Route::any('{module}/button/{button_id?}/{grid_id?}/{is_iframe?}', [ModuleController::class, 'button']);
