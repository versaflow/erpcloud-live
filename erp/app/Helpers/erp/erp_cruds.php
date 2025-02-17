<?php

function test_erpserviceprovider(){
    $configTables = app('erp_config');
$table1 = $configTables['modules'];
dd($table1);
}


function get_site_colors_templates(){
    $colors = [
        'Red' => ['background_color' => '#E97777','text_color' => '#000'],
        'Yellow' => ['background_color' =>'#FCF9BE','text_color' => '#000'],
        'Green' => ['background_color' =>'#B5D5C5','text_color' => '#000'],
        'Blue' => ['background_color' =>'#8FBDD3','text_color' => '#000'],
        'Grey' => ['background_color' =>'#EEEEEE','text_color' => '#000'],
        'Black' => ['background_color' =>'#555555','text_color' => '#fff'],
        'Orange' => ['background_color' =>'#FEBE8C','text_color' => '#000'],
        'Lightblue' => ['background_color' =>'#e6f5ff','text_color' => '#000'],
        'Lightred' => ['background_color' =>'#f08080','text_color' => '#000'],
    ];   
    return $colors;
}

function rebuild_system_colors(){
    $colors = get_site_colors_templates();
    $conns = db_conns();
    foreach($conns as $c){
      
        $styles = \DB::connection($c)->table('erp_grid_styles')->where('template','>','')->get();
        foreach($styles as $style){
            \DB::connection($c)->table('erp_grid_styles')->where('id', $style->id)->update($colors[$style->template]);
        }
    }
   cache_clear();    
}

function beforesave_update_alias($request)
{
    if (!empty($request->id)) {
        $db_table = \DB::connection('default')->table('erp_cruds')->where('id', $request->id)->pluck('db_table')->first();
        if ($db_table != $request->db_table) {
            \DB::connection('default')->table('erp_module_fields')->where('module_id', $request->id)->where('alias', $db_table)->update(['alias' => $request->db_table]);
        }
    }
}
function aftersave_conditional_styles_set_style_from_template($request)
{
    $colors = get_site_colors_templates();
    
    if(!empty($request->template)){
        \DB::table('erp_grid_styles')->where('id', $request->id)->update($colors[$request->template]);
    }
}

function aftersave_conditional_styles_default_color($request)
{
    \DB::table('erp_grid_styles')->where('background_color', '#000000')->update(['background_color' => '']);
    \DB::table('erp_grid_styles')->where('text_color', '#000000')->update(['text_color' => '']);
}

function aftersave_module_update_menu_name($request){
    
    if(!empty($request->name)){
        \DB::table('erp_menu')->where('module_id', $menu->module_id)->whereIn('menu_type',['module','module_filter'])->update(['menu_name'=>$request->name]);
    }
}


// conditional styles taget field select
function select_options_target_fields($row)
{
    if (!empty($row['module_id'])) {
        $result = \DB::connection('default')->table('erp_module_fields')->where('module_id', $row['module_id'])->pluck('label','field')->toArray();
        
        return $result;
    }

    if (!empty(request()->module_id)) {
        $result = \DB::connection('default')->table('erp_module_fields')->where('module_id', request()->module_id)->pluck('label','field')->toArray();
       
        return $result;
    }
    return  [];
}