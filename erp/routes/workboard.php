<?php

Route::any('project_tasks_update/{task_id?}', function ($task_id) {
    $action = request('action');
    $type = request('type');
    $post_data = (array) request('post_data');
    if ($action == 'add') {
        $post_data = (object) $post_data;
        $insert_data = [
            'task_id' => $task_id,
            'name' => $post_data->name,
            'sort_order' => 0,
        ];

        $id = dbinsert('crm_task_checklist', $insert_data);
        \DB::table('crm_task_checklist')->where('task_id', $task_id)->where('id', '!=', $id)->increment('sort_order');
        $checklist_count = \DB::table('crm_task_checklist')->where('task_id', $task_id)->where('is_deleted', 0)->where('completed', 0)->count();
        if ($checklist_count > 0) {
            \DB::table('crm_staff_tasks')->where('progress_status', '!=', 'In Progress')->where('id', $task_id)->update(['progress_status' => 'Incomplete', 'completed' => 0]);
        }

        $checklist_items = \DB::table('crm_task_checklist')->where('task_id', $task_id)->where('is_deleted', 0)->orderBy('sort_order')->get();
        $details = '';
        foreach ($checklist_items as $checklist_item) {
            $details .= $checklist_item->name;
            if ($checklist_item->completed) {
                $details .= ' - done';
            }
            $details .= PHP_EOL;
        }
        \DB::table('crm_staff_tasks')->where('id', $task_id)->update(['details' => $details]);
    }

    if ($action == 'edit' && $type == 'save') {

        $post_data = (object) $post_data;
        $edit_data = [
            'task_id' => $task_id,
            'name' => $post_data->name,
            'completed' => ($post_data->completed && $post_data->completed != 'false') ? 1 : 0,
        ];

        \DB::table('crm_task_checklist')->where('id', $post_data->id)->update($edit_data);

        $checklist_count = \DB::table('crm_task_checklist')->where('task_id', $task_id)->where('is_deleted', 0)->where('completed', 0)->count();
        if ($checklist_count > 0) {
            \DB::table('crm_staff_tasks')->where('progress_status', '!=', 'In Progress')->where('id', $task_id)->update(['progress_status' => 'Incomplete', 'completed' => 0]);
        }

        $checklist_items = \DB::table('crm_task_checklist')->where('task_id', $task_id)->where('is_deleted', 0)->orderBy('sort_order')->get();
        $details = '';
        foreach ($checklist_items as $checklist_item) {
            $details .= $checklist_item->name;
            if ($checklist_item->completed) {
                $details .= ' - done';
            }
            $details .= PHP_EOL;
        }
        \DB::table('crm_staff_tasks')->where('id', $task_id)->update(['details' => $details]);
    }

    if ($type == 'delete') {
        $post_data = collect($post_data)->first();
        $post_data = (object) $post_data;

        \DB::table('crm_task_checklist')->where('id', $post_data->id)->update(['is_deleted' => 1]);
    }
});

Route::any('project_tasks_datasource_treegrid/{id?}', function ($id) {
    $parent_id = str_replace('parent_id eq ', '', request('$filter'));
    if ($parent_id == 'null') {
        $parent_id = 0;
    }
    if ($parent_id) {
        $rows = \DB::table('crm_task_checklist')->where('task_id', $id)->where('parent_id', $parent_id)->where('is_deleted', 0)->orderBy('sort_order')->get();
    } else {
        $rows = \DB::table('crm_task_checklist')->where('task_id', $id)->where('is_deleted', 0)->orderBy('sort_order')->get();
    }
    $parent_ids = $rows->pluck('parent_id')->unique()->filter()->toArray();
    foreach ($rows as $i => $row) {
        if (empty($row->parent_id)) {
            $rows[$i]->parent_id = null;
        }
        if (in_array($row->id, $parent_ids)) {
            $rows[$i]->isParent = 1;
        } else {

            $rows[$i]->isParent = 0;
        }
    }

    //return response()->json($rows);

    return response()->json(['result' => $rows, 'count' => count($rows)]);
});
Route::any('project_tasks_datasource/{id?}', function ($id) {
    $parent_id = str_replace('parent_id eq ', '', request('$filter'));
    if ($parent_id == 'null') {
        $parent_id = 0;
    }
    if (! empty(request('search'))) {
        $rows = \DB::table('crm_task_checklist')->where('task_id', $id)->where('name', 'like', '%'.request('search')[0]['key'].'%')->where('is_deleted', 0)->orderBy('sort_order')->get();
    } else {

        if ($parent_id) {
            $rows = \DB::table('crm_task_checklist')->where('task_id', $id)->where('parent_id', $parent_id)->where('is_deleted', 0)->orderBy('sort_order')->get();
        } else {
            $rows = \DB::table('crm_task_checklist')->where('task_id', $id)->where('is_deleted', 0)->orderBy('sort_order')->get();
        }
    }
    $parent_ids = $rows->pluck('parent_id')->unique()->filter()->toArray();
    foreach ($rows as $i => $row) {
        if (empty($row->parent_id)) {
            $rows[$i]->parent_id = null;
        }
        if (in_array($row->id, $parent_ids)) {
            $rows[$i]->isParent = 1;
        } else {

            $rows[$i]->isParent = 0;
        }
    }

    //return response()->json($rows);

    return response()->json(['result' => $rows, 'count' => count($rows)]);
});

Route::any('project_tasks_render_treegrid/{id?}', function ($id) {
    $rows = \DB::table('crm_task_checklist')->where('task_id', $id)->where('is_deleted', 0)->orderBy('sort_order')->get();
    $parent_ids = $rows->pluck('parent_id')->unique()->filter()->toArray();
    foreach ($rows as $i => $row) {
        if (empty($row->parent_id)) {
            $rows[$i]->parent_id = null;
        }
        if (in_array($row->id, $parent_ids)) {
            $rows[$i]->isParent = 1;
        } else {

            $rows[$i]->isParent = 0;
        }
    }
    $data = [
        'task_id' => $id,
        'rows' => $rows,
    ];

    return view('__app.grids.partials.sf_inline_treegrid', $data)->render();
});

Route::any('project_tasks_render/{id?}', function ($id) {

    $rows = \DB::table('crm_task_checklist')->where('task_id', $id)->where('is_deleted', 0)->orderBy('sort_order')->get();
    $parent_ids = $rows->pluck('parent_id')->unique()->filter()->toArray();
    foreach ($rows as $i => $row) {
        if (empty($row->parent_id)) {
            $rows[$i]->parent_id = null;
        }
        if (in_array($row->id, $parent_ids)) {
            $rows[$i]->isParent = 1;
        } else {

            $rows[$i]->isParent = 0;
        }
    }
    $data = [
        'task_id' => $id,
        'rows' => $rows,
    ];

    return view('__app.grids.partials.sf_inline_grid', $data)->render();
});

Route::any('task_checklist_data/{id?}', function ($id) {
    $rows = \DB::table('crm_task_checklist')->where('task_id', $id)->where('is_deleted', 0)->orderBy('sort_order')->get();

    return $rows;
});
Route::any('task_checklist_update/{check_id?}/{checked?}', function ($id, $checked) {
    $rows = \DB::table('crm_task_checklist')->where('id', $id)->update(['completed' => $checked]);

});

Route::any('task_checklist_row_drop/{task_id?}/{id?}/{target_id?}/{position?}', function ($task_id, $start_id, $target_id) {

    $start_sort = \DB::table('crm_task_checklist')->where('id', $start_id)->pluck('sort_order')->first();
    $target_sort = \DB::table('crm_task_checklist')->where('id', $target_id)->pluck('sort_order')->first();
    $first_sort = \DB::table('crm_task_checklist')->where('task_id', $task_id)->orderby('sort_order')->pluck('sort_order')->first();
    $last_sort = \DB::table('crm_task_checklist')->where('task_id', $task_id)->orderby('sort_order')->pluck('sort_order')->last();

    if ($target_sort == $first_sort || $target_sort == 0) {

        \DB::table('crm_task_checklist')->where('task_id', $task_id)->increment('sort_order');
        \DB::table('crm_task_checklist')->where('id', $start_id)->update(['sort_order' => $target_sort]);
    } elseif ($target_sort == $last_sort) {

        \DB::table('crm_task_checklist')->where('task_id', $task_id)->decrement('sort_order');
        \DB::table('crm_task_checklist')->where('id', $start_id)->update(['sort_order' => $target_sort]);
    } elseif ($target_sort < $start_sort) {

        \DB::table('crm_task_checklist')->where('task_id', $task_id)->where('sort_order', '>=', $target_sort)->increment('sort_order');
        \DB::table('crm_task_checklist')->where('id', $start_id)->update(['sort_order' => $target_sort]);
    } else {

        \DB::table('crm_task_checklist')->where('task_id', $task_id)->where('sort_order', '<=', $target_sort)->decrement('sort_order');
        \DB::table('crm_task_checklist')->where('id', $start_id)->update(['sort_order' => $target_sort]);
    }

    $rows = \DB::table('crm_task_checklist')->where('task_id', $task_id)->where('is_deleted', 0)->orderBy('sort_order')->get();
    foreach ($rows as $i => $row) {
        \DB::table('crm_task_checklist')->where('id', $row->id)->update(['sort_order' => $i]);
    }

    $checklist_items = \DB::table('crm_task_checklist')->where('task_id', $task_id)->where('is_deleted', 0)->orderBy('sort_order')->get();
    $details = '';
    foreach ($checklist_items as $checklist_item) {
        $details .= $checklist_item->name;
        if ($checklist_item->completed) {
            $details .= ' - done';
        }
        $details .= PHP_EOL;
    }
    \DB::table('crm_staff_tasks')->where('id', $task_id)->update(['details' => $details]);

    return json_alert('Done');

});

Route::any('task_checklist_bulk_edit', function (Illuminate\Http\Request $request) {

    $task_collection = \DB::table('crm_task_checklist')->where('task_id', $request->task_id)->where('is_deleted', 0)->get();
    $task = collect($request->rows);

    $moved_task_ids = [];
    foreach ($task as $i => $row) {
        $sort_order = $i;
        $id = $row['id'];
        $parent_id = $row['parent_id'];
        if (empty($parent_id)) {
            $parent_id = 0;
        }
        $current_parent_id = $task_collection->where('id', $id)->pluck('parent_id')->first();
        if ($current_parent_id != $parent_id) {
            $moved_task_ids[] = $id;
        }

        \DB::table('crm_task_checklist')->where('id', $id)->update(['parent_id' => $parent_id, 'sort_order' => $sort_order]);
    }

    return json_alert('Done');

});
