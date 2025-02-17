<?php

function is_services_menu_module($module_id){
    $c = \DB::connection('default')->table('erp_menu')->where('location','services_menu')->where('module_id',$module_id)->count();
    if($c == 0){
        return false;
    }
    return true;
}

function menu_validate_parent_links(){
    return false;
    $locations = \DB::table('erp_menu')->pluck('location')->unique()->toArray();
    foreach($locations as $location){
        $parent_ids = \DB::table('erp_menu')->where('unlisted',0)->where('location',$location)->pluck('parent_id')->unique()->toArray();
        foreach($parent_ids as $parent_id){
            $menu_item = \DB::table('erp_menu')->where('id',$parent_id)->get()->first();
            $is_module_link = (in_array($menu_item->menu_type,['module','module_filter'])) ? true : false;
            if($is_module_link){
                // create dropdown link
                $data = (array) $menu_item;
                $data['module_id'] = 0;
                $data['menu_type'] = 'link';
                $data['url'] = '#';
                $data['app_id'] = \DB::table('erp_cruds')->where('id',$menu_item->module_id)->pluck('app_id')->first();
                unset($data['id']);
                $new_parent_id = dbinsert('erp_menu',$data);
                
                // update parent id
                \DB::table('erp_menu')->where('location',$location)->where('parent_id',$parent_id)->update(['parent_id' => $new_parent_id]);
                
                // move module dropdown link
                \DB::table('erp_menu')->where('id',$parent_id)->update(['sort_order'=>0,'parent_id' => $new_parent_id]);
            }
        }
    }
}

function beforesave_menu_check_access_role_ids_set($request){
    if($request->location == 'main_menu' && $request->parent_id = 0){
        if(empty($request->access_role_ids)){
            return 'View and Add Permissions required';       
        }
    }
}

function pbx_menu_update(){
    if(!is_main_instance()){
        return false;    
    }
    
    // create backup table
    $backup_table = 'v_menu_items_'.date('Ymd',strtotime('monday this week'));
    if(!\Schema::connection('pbx')->hasTable($backup_table)){
        schema_clone_db_table($backup_table, 'v_menu_items', 'pbx');
    }
   
    $menu_ids = get_submenu_ids(5214);   
    $menus = \DB::connection('default')->table('erp_menu')->whereIn('id',$menu_ids)->where('unlisted',0)->get();
    foreach($menus as $m){
        if(empty($m->menu_item_uuid)){
            // create new menu item
            $menu_item_parent_uuid = null;
            if($m->parent_id != 5214){
                $parent_uuid = $menus->where('id',$m->parent_id)->pluck('menu_item_uuid')->first();
                if($parent_uuid){
                    $menu_item_parent_uuid = $parent_uuid;
                }
            }
            $new_menu_item_uuid = pbx_uuid('v_menu_items','menu_item_uuid');
            $data = [
                'menu_uuid' => 'b4750c3f-2a86-b00d-b7d0-345c14eca286',
                'menu_item_title' => $m->menu_name,
                'menu_item_uuid' => $new_menu_item_uuid,
                'menu_item_order' => $m->sort_order,
                'menu_item_parent_uuid' => $menu_item_parent_uuid,
                'menu_item_category' => 'internal',
                'menu_item_protected' => 'false',
                'update_user' => '89c7f16d-6dab-4dbc-be0a-68b751a28bf6',
                'update_date' => date('Y-m-d H:i:s'),
            ];
            if(str_contains($m->menu_type,'module')){
                $url = 'https://'.session('instance')->domain_name.'/'.get_menu_url_from_module_id($m->module_id);
                $data['menu_item_category'] = 'external';
                $data['menu_item_link'] = $url;
            } 
            if($m->menu_type == 'link' && $m->url == '#'){
                $data['menu_item_link'] = NULL;   
            }
            \DB::connection('pbx')->table('v_menu_items')->insert($data);
            \DB::connection('default')->table('erp_menu')->where('id',$m->id)->update(['menu_item_uuid'=>$new_menu_item_uuid]);
        }else{
            // update existing
            $menu_item_parent_uuid = null;
            if($m->parent_id != 5214){
                $parent_uuid = $menus->where('id',$m->parent_id)->pluck('menu_item_uuid')->first();
                if($parent_uuid){
                    $menu_item_parent_uuid = $parent_uuid;
                }
            }
         
            $data = [
                'menu_item_title' => $m->menu_name,
                'menu_item_order' => $m->sort_order,
                'menu_item_parent_uuid' => $menu_item_parent_uuid,
                'menu_item_category' => 'internal',
                'menu_item_protected' => 'false',
                'update_user' => '89c7f16d-6dab-4dbc-be0a-68b751a28bf6',
                'update_date' => date('Y-m-d H:i:s'),
            ];
            if(str_contains($m->menu_type,'module')){
                $url = 'https://'.session('instance')->domain_name.'/'.get_menu_url_from_module_id($m->module_id);
                $data['menu_item_category'] = 'external';
                $data['menu_item_link'] = $url;
            } 
            if($m->menu_type == 'link' && $m->url == '#'){
                $data['menu_item_link'] = NULL;   
            }
            \DB::connection('pbx')->table('v_menu_items')->where('menu_item_uuid',$m->menu_item_uuid)->update($data);
        }
    }
    
    // delete removed menu_items
    $menu_item_uuids = \DB::connection('default')->table('erp_menu')->whereIn('id',$menu_ids)->pluck('menu_item_uuid')->toArray();
    \DB::connection('pbx')->table('v_menu_items')->whereNotIn('menu_item_uuid',$menu_item_uuids)->whereNotIn('menu_item_title',['Login','Logout'])->delete();
    
    // add superadmin to all menu permissions
    $menu_item_uuids = \DB::connection('pbx')->table('v_menu_items')->pluck('menu_item_uuid')->toArray();
    foreach($menu_item_uuids as $menu_item_uuid){
        $c = \DB::connection('pbx')->table('v_menu_item_groups')->where('menu_item_uuid',$menu_item_uuid)->count();
        if(!$c){
            $menu_item_group_uuid = pbx_uuid('v_menu_item_groups','menu_item_group_uuid');
            $data = [
                'menu_item_uuid' => $menu_item_uuid,
                'menu_uuid' => 'b4750c3f-2a86-b00d-b7d0-345c14eca286',
                'menu_item_group_uuid' => $menu_item_group_uuid,
                'group_name' => 'superadmin',
                'group_uuid' => 'c5494cc0-1a15-4334-9f24-3c02b288b545',
            ];
            \DB::connection('pbx')->table('v_menu_item_groups')->insert($data);  
        }
    }
    
    
    // update all menu languages
    $menu_items = \DB::connection('pbx')->table('v_menu_items')->select('menu_item_title','menu_item_uuid')->get();
    foreach($menu_items as $menu_item){
        $data = [
            'menu_item_uuid' => $menu_item->menu_item_uuid,
            'menu_uuid' => 'b4750c3f-2a86-b00d-b7d0-345c14eca286',
            'menu_item_title' => $menu_item->menu_item_title,
            'menu_language' => 'en-us',
            'update_user' => '89c7f16d-6dab-4dbc-be0a-68b751a28bf6',
            'update_date' => date('Y-m-d H:i:s'),
        ];
        
        $menu_language_uuid = \DB::connection('pbx')->table('v_menu_languages')->where('menu_item_uuid',$menu_item->menu_item_uuid)->pluck('menu_language_uuid')->first();
        if(!$menu_language_uuid){
            $data['menu_language_uuid'] = pbx_uuid('v_menu_languages','menu_language_uuid');
            $data['insert_user'] = '89c7f16d-6dab-4dbc-be0a-68b751a28bf6';
            $data['insert_date'] = date('Y-m-d H:i:s');
            \DB::connection('pbx')->table('v_menu_languages')->insert($data);
        }else{
            \DB::connection('pbx')->table('v_menu_languages')->where('menu_language_uuid',$menu_language_uuid)->update($data);
            
        }
    }
    
    
    //delete unused menu items
    \DB::connection('pbx')->table('v_menu_items')->whereNotIn('menu_item_uuid',$menu_item_uuids)->whereNotIn('menu_item_title',['Login','Logout'])->delete();
    
    
    //move logout and login links last
    $max_sort_order = \DB::connection('pbx')->table('v_menu_items')->max('menu_item_order');
    $max_sort_order++; 
    \DB::connection('pbx')->table('v_menu_items')->where('menu_item_title','Login')->update(['menu_item_order'=>$max_sort_order]);
    $max_sort_order++; 
    \DB::connection('pbx')->table('v_menu_items')->where('menu_item_title','Logout')->update(['menu_item_order'=>$max_sort_order]);
    
}

function get_menu_bread_crumbs($menu_id){
  //  $parent = \DB::table('erp_menu')->where('id',$menu)->get();
  //  return $bread_crumbs;
}


function ajax_menu_set_require_grid_id($request)
{
    if (!empty($request->location) && $request->location == 'grid_menu') {
      
        $response = ['require_grid_id' =>1];
     
        return $response;
    }else{
        $response = ['require_grid_id' =>$request->require_grid_id];
     
        return $response;
    }
}

function schedule_check_role_id_fields(){
    
    $role_ids = \DB::table('erp_user_roles')->pluck('id')->toArray();
    
    $menus = \DB::table('erp_menu')->where('access_role_ids','>','')->get();
    foreach($menus as $menu){
        $access_role_ids = explode(',',$menu->access_role_ids);
        $valid_ids = array_intersect($access_role_ids, $role_ids);
        \DB::table('erp_menu')->where('id',$menu->id)->update(['access_role_ids'=>$valid_ids]);
    }
}

function build_permissions_from_menu($moved_menu_ids = []){
   
    // @todo multiple modules linked on module actions, only applies permissions of last top level menu item 
    // remove deleted roles
    $roles_collection = \DB::connection('default')->table('erp_user_roles')->get();
    
    $role_ids = $roles_collection->pluck('id')->toArray();
    $access_controlled_menus = \DB::connection('default')->table('erp_menu')->select('id','access_role_ids')->where('access_role_ids','>','')->get();
    foreach($access_controlled_menus as $menu){
        $access_role_ids = explode(',',$menu->access_role_ids);
        $updated_access_role_ids = array_filter($access_role_ids, fn($value) => in_array($value, $role_ids));
        \DB::connection('default')->table('erp_menu')->where('id',$menu->id)->update(['access_role_ids'=>implode(',',$updated_access_role_ids)]);
    }
    $menu_locations = ['main_menu','top_left_menu'];
    foreach($menu_locations as $menu_location){
        // get collections
        $menu_collection = \DB::connection('default')->table('erp_menu')->get();
        $module_collection = \DB::connection('default')->table('erp_cruds')->get();
      
        $access_controlled_menus = $menu_collection->where('access_role_ids','>','');
        
        $menu_access_collection = \DB::connection('default')->table('erp_menu_role_access')->get();
        $forms_collection = \DB::connection('default')->table('erp_forms')->get();
        
        
        $module_permissions = [];
        if(count($moved_menu_ids) > 0){
            $toplevel_ids = [];
            foreach($moved_menu_ids as $moved_menu_id){
                $toplevel_ids[] = get_toplevel_menu_id($moved_id);
            }
            $toplevel_menus = $menu_collection->where('location',$menu_location)->whereIn('id',$toplevel_ids)->where('parent_id',0)->where('unlisted',0);   
        }else{
            $toplevel_menus = $menu_collection->where('location',$menu_location)->where('parent_id',0)->where('unlisted',0);
        }
        
        $role_access_data = [];
        foreach($toplevel_menus as $toplevel_menu){
            if(empty($toplevel_menu->access_role_ids)){
                continue;    
            }
            
            $access_role_ids = collect(explode(',',$toplevel_menu->access_role_ids))->filter()->toArray();
            $access_role_ids[] = $roles_collection->where('manager',1)->pluck('id')->first();
            $access_role_ids = collect($access_role_ids)->unique()->toArray();
            
            if($menu_location == 'top_left_menu'){
                if(count($access_role_ids) > 0){
                    $access_role_ids = $roles_collection->whereIn('id',$access_role_ids)->pluck('id')->toArray();
                }
                if(count($access_role_ids) > 0){
                    $role_ids_to_add = $access_role_ids;
                    $role_ids_to_delete = $roles_collection->whereNotIn('id',$access_role_ids)->pluck('id')->toArray();
                }
            }
            
            if($menu_location == 'main_menu'){
                if(count($access_role_ids) > 0){
                    $access_role_ids = $roles_collection->where('level','Admin')->whereIn('id',$access_role_ids)->pluck('id')->toArray();
                }
                if(count($access_role_ids) > 0){
                    $role_ids_to_add = $access_role_ids;
                    $role_ids_to_delete = $roles_collection->where('level','Admin')->whereNotIn('id',$access_role_ids)->pluck('id')->toArray();
                }
            }
           
          
            $menu_id = $toplevel_menu->id;
            $menu_ids = get_submenu_ids($menu_id,$menu_collection);
           
            $menu_ids[] = $toplevel_menu->id;
         
            
            $module_id_list = [];
            
            $related_module_ids = [];
            foreach($menu_ids as $id){
                $menu = $menu_collection->where('id',$id)->first();
                if($menu->module_id){
                    $module_id_list[] = $menu->module_id;
                
                    $detail_module_id = $module_collection->where('id',$menu->module_id)->pluck('detail_module_id')->first();
                    
                    if($detail_module_id){
                        $button_module_ids = $menu_collection->where('render_module_id',$detail_module_id)->whereIn('location',['grid_menu','module_menu'])->pluck('module_id')->toArray();
                        foreach($button_module_ids as $tmid){
                            $module_id_list[] = $tmid;    
                        }
                       
                        $button_menu_ids = $menu_collection->where('render_module_id',$detail_module_id)->whereIn('location',['grid_menu','module_menu'])->pluck('id')->toArray();
                        foreach($button_menu_ids as $tmid){
                            $menu_ids[] = $tmid;    
                        }
                       
                    }
                    $button_module_ids = $menu_collection->where('render_module_id',$menu->module_id)->whereIn('location',['grid_menu','module_menu'])->pluck('module_id')->toArray();
                    foreach($button_module_ids as $tmid){
                        $module_id_list[] = $tmid;    
                    }
                   
                    $button_menu_ids = $menu_collection->where('render_module_id',$menu->module_id)->whereIn('location',['grid_menu','module_menu'])->pluck('id')->toArray();
                    foreach($button_menu_ids as $tmid){
                        $menu_ids[] = $tmid;    
                    }
                }
            }
            
            $related_module_ids = collect($related_module_ids)->unique()->filter()->toArray();
            
            foreach($related_module_ids as $related_module_id){
             
                
                $detail_module_id = $module_collection->where('id',$related_module_id)->pluck('detail_module_id')->first();
                
                if($detail_module_id){
                    $button_module_ids = $menu_collection->where('render_module_id',$detail_module_id)->whereIn('location',['grid_menu','module_menu'])->pluck('module_id')->toArray();
                    foreach($button_module_ids as $tmid){
                        $module_id_list[] = $tmid;    
                    }
                   
                    $button_menu_ids = $menu_collection->where('render_module_id',$detail_module_id)->whereIn('location',['grid_menu','module_menu'])->pluck('id')->toArray();
                    foreach($button_menu_ids as $tmid){
                        $menu_ids[] = $tmid;    
                    }
                }
                $button_module_ids = $menu_collection->where('render_module_id',$related_module_id)->whereIn('location',['grid_menu','module_menu'])->pluck('module_id')->toArray();
                foreach($button_module_ids as $tmid){
                    $module_id_list[] = $tmid;    
                }
               
                $button_menu_ids = $menu_collection->where('render_module_id',$related_module_id)->whereIn('location',['grid_menu','module_menu'])->pluck('id')->toArray();
                foreach($button_menu_ids as $tmid){
                    $menu_ids[] = $tmid;    
                }
            }
            
            $module_id_list = collect($module_id_list)->unique()->filter()->toArray();
            $menu_ids = collect($menu_ids)->unique()->filter()->toArray();
            $modules = $menu_collection->whereIn('id',$module_id_list)->pluck('name')->toArray();
            
          
          
            foreach($module_id_list as $module_id){
                if($module_collection->where('id',$module_id)->count() > 0){
                if(!isset($module_permissions[$module_id])){
                    $module_permissions[$module_id] = ['role_ids_to_add'=> $role_ids_to_add,'role_ids_to_delete' => $role_ids_to_delete];
                }else{
                    foreach($role_ids_to_add as $role_id_add){
                        $module_permissions[$module_id]['role_ids_to_add'][] = $role_id_add;
                        $module_permissions[$module_id]['role_ids_to_delete'] =array_diff( $module_permissions[$module_id]['role_ids_to_delete'], [$role_id_add] );
                    }
                }
                }
            }
            
          //  \DB::table('erp_menu_role_access')->whereIn('menu_id',$menu_ids)->whereIn('role_id',$role_ids_to_add)->delete();
            \DB::table('erp_menu_role_access')->whereIn('menu_id',$menu_ids)->whereIn('role_id',$role_ids_to_delete)->delete();
            foreach($menu_ids as $menu_id){
            
                foreach($role_ids_to_add as $role_id_add){
                    $role_access_data[] = [
                        'role_id' => $role_id_add,
                        'menu_id' => $menu_id,
                        'is_menu' => 1,
                    ];
                }
                foreach($role_ids_to_delete as $role_id_add){
                    $role_access_data[] = [
                        'role_id' => $role_id_add,
                        'menu_id' => $menu_id,
                        'is_menu' => 0,
                    ];
                }
            }
            
           
          
        }
        
        
        $insert_data_collection = collect($role_access_data);
        foreach($insert_data_collection as $insert_data){
            \DB::table('erp_menu_role_access')->updateOrInsert(['role_id'=>$insert_data['role_id'],'menu_id'=>$insert_data['menu_id']],$insert_data);
        }
      
        //aa($module_permissions);
        foreach($module_permissions as $module_id => $permissions){
            $role_ids_to_add = collect($permissions['role_ids_to_add'])->unique()->filter()->toArray();
            $role_ids_to_delete = collect($permissions['role_ids_to_delete'])->unique()->filter()->toArray();
           
            foreach($role_ids_to_add as $role_id_add){
                $e =  $forms_collection->where('module_id',$module_id)->where('role_id',$role_id_add)->count();
                if(!$e){
                    $data = $forms_collection->where('module_id',$module_id)->where('role_id',1)->first();
                    if(!$data){
                        $data = ['module_id' => $module_id];
                    }else{
                        $data = (array) $data;
                    }
                    unset($data['id']);
                   
                    $data['role_id'] = $role_id_add;
                    
                    unset($data['is_add']);
                    unset($data['is_edit']);
                    unset($data['is_delete']);
                    $data['is_add'] = 1;
                    $data['is_edit'] = 1;
                    $data['is_delete'] = 1;
                  
                    \DB::table('erp_forms')->insert($data);
                   
                }else{
                  
                    \DB::table('erp_forms')->where('module_id',$module_id)->where('role_id',$role_id_add)->update(['is_view'=>1,'is_add'=>1,'is_edit'=>1]);    
                }
            }
            //aa($module_id);
            //aa($role_ids_to_delete);
           
            \DB::table('erp_forms')->where('module_id',$module_id)->whereIn('role_id',$role_ids_to_delete)->delete();
        }
        
        /*
        $toplevel_menus = $menu_collection->where('location',$menu_location)->where('parent_id','!=',0)->where('module_id','>',0)->where('access_role_ids','>','');
    
        foreach($toplevel_menus as $toplevel_menu){
           
            $access_role_ids = collect(explode(',',$toplevel_menu->access_role_ids))->filter()->toArray();
         
            $role_ids_to_add = $roles_collection->where('level','Admin')->whereIn('id',$access_role_ids)->pluck('id')->toArray();
                
            $role_ids_to_delete = $roles_collection->where('level','Admin')->whereNotIn('id',$access_role_ids)->pluck('id')->toArray();
            
            
           
            
            $menu_id = $toplevel_menu->id;
            $menu_ids = get_submenu_ids($menu_id, $menu_collection);
           
            $menu_ids[] = $toplevel_menu->id;
         
            
            $module_id_list = [];
            foreach($menu_ids as $id){
                $menu = $menu_collection->where('id',$id)->first();
                if($menu->module_id){
                    $module_id_list[] = $menu->module_id;
                }
                
                $detail_module_id = $module_collection->where('id',$menu->module_id)->pluck('detail_module_id')->first();
                
                if($detail_module_id){
                    
                    $module_id_list[] = $detail_module_id;
                    
                    
                    $button_module_ids = $menu_collection->where('render_module_id',$detail_module_id)->whereIn('location',['grid_menu','module_menu'])->pluck('module_id')->toArray();
                    foreach($button_module_ids as $tmid){
                        $module_id_list[] = $tmid;    
                    }
                   
                    $button_menu_ids = $menu_collection->where('render_module_id',$detail_module_id)->whereIn('location',['grid_menu','module_menu'])->pluck('id')->toArray();
                    foreach($button_menu_ids as $tmid){
                        $menu_ids[] = $tmid;    
                    }
                }
               
                
                $button_module_ids = $menu_collection->where('render_module_id',$menu->module_id)->whereIn('location',['grid_menu','module_menu'])->pluck('module_id')->toArray();
                foreach($button_module_ids as $tmid){
                    $module_id_list[] = $tmid;    
                }
                
             
               
                $button_menu_ids = $menu_collection->where('render_module_id',$menu->module_id)->whereIn('location',['grid_menu','module_menu'])->pluck('id')->toArray();
                foreach($button_menu_ids as $tmid){
                    $menu_ids[] = $tmid;    
                }
                
               
            }
            
            $menu_ids = collect($menu_ids)->unique()->filter()->toArray();
            $module_id_list = collect($module_id_list)->unique()->filter()->toArray();
            $modules = $module_collection->whereIn('id',$module_id_list)->pluck('name')->toArray();
          
            
            $forms_collection = \DB::connection('default')->table('erp_forms')->get();
            foreach($module_id_list as $module_id){
                
            
                foreach($role_ids_to_add as $role_id_add){
                    $e =  $forms_collection->where('module_id',$module_id)->where('role_id',$role_id_add)->count();
                    if(!$e){
                 
                        $data = $forms_collection->where('module_id',$module_id)->where('role_id',1)->first();
                        if(!$data){
                            $data = ['module_id' => $module_id];
                        }else{
                            $data = (array) $data;
                        }
                        unset($data['id']);
                       
                        $data['role_id'] = $role_id_add;
                    unset($data['is_edit']);
                    unset($data['is_delete']);
                    unset($data['is_add']);
                      
                        \DB::table('erp_forms')->insert($data);
                       
                    }else{
                        \DB::table('erp_forms')->where('module_id',$module_id)->where('role_id',$role_id_add)->update(['is_view'=>1]);    
                    }
                }
              
                \DB::table('erp_forms')->where('module_id',$module_id)->whereIn('role_id',$role_ids_to_delete)->delete();
            }
            
            $menu_access_collection = \DB::connection('default')->table('erp_menu_role_access')->get();
            foreach($menu_ids as $menu_id){
                
                foreach($role_ids_to_add as $role_id_add){
                    $e =  $menu_access_collection->where('menu_id',$menu_id)->where('role_id',$role_id_add)->count();
                    if(!$e){
                        $data = [
                            'role_id' => $role_id_add,
                            'menu_id' => $menu_id,
                            'is_menu' => 1,
                        ];
                     
                        \DB::table('erp_menu_role_access')->insert($data);
                    }else{
                        $data = [
                            'role_id' => $role_id_add,
                            'menu_id' => $menu_id,
                            'is_menu' => 1,
                        ];
                        \DB::table('erp_menu_role_access')->where('menu_id',$menu_id)->where('role_id',$role_id_add)->update(['is_menu'=>1]);    
                    }
                }
                    
            }
        }
        */
        
        
        $toplevel_menu_ids = $menu_collection->where('location',$menu_location)->where('parent_id',0)->where('unlisted',0)->pluck('id')->toArray();
        foreach($toplevel_menu_ids as $toplevel_id){
            set_menulink_permissions_from_submenu($toplevel_id);
        }
    }
    update_module_permissions();
    set_workboard_permissions();
    set_pinnedtab_permissions();
}

function set_menulink_permissions_from_submenu($top_menu_id)
{
    $menu_ids = get_submenu_ids($top_menu_id);
    $update_role_ids = [];
    if (count($menu_ids) > 0) {
        foreach ($menu_ids as $menu_id) {
            $module_id = \DB::table('erp_menu')->where('id', $menu_id)->pluck('module_id')->first();
            if ($module_id) {
                $role_ids = \DB::connection('default')->table('erp_forms')->where('is_view', 1)->where('module_id', $module_id)->pluck('role_id')->toArray();
            } else {
                $role_ids = \DB::connection('default')->table('erp_menu_role_access')->where('is_menu', 1)->where('menu_id', $menu_id)->pluck('role_id')->toArray();
            }
            /*
            if($top_menu_id == 7047){
                    // aa($menu_id);
                if(in_array(62,$role_ids)){
                    // aa($module_id);
                    // aa($role_ids);
                }
            }
             */
           
            foreach ($role_ids as $role_id) {
                $update_role_ids[] = $role_id;
            }
            $has_sub_menu = \DB::connection('default')->table('erp_menu')->where('parent_id', $menu_id)->count();
            
            if ($has_sub_menu) {
                
                set_menulink_permissions_from_submenu($menu_id);
            }
        }
        $role_id_collections = collect($update_role_ids)->filter()->unique()->toArray();
        foreach ($role_id_collections as $role_id_collection) {
            $update_role_ids[] = $role_id_collection;
        }
        $update_role_ids = collect($update_role_ids)->filter()->unique()->toArray();


        \DB::connection('default')->table('erp_menu_role_access')->whereIn('role_id', $update_role_ids)->where('menu_id', $top_menu_id)->update(['is_menu'=>1]);
        \DB::connection('default')->table('erp_menu_role_access')->whereNotIn('role_id', $update_role_ids)->where('menu_id', $top_menu_id)->update(['is_menu'=>0]);
    }
}


function set_workboard_permissions(){
    if(session('instance')->id == 1){
    $forms_collection = \DB::connection('default')->table('erp_forms')->get();
    $module_ids = \DB::connection('default')->table('erp_cruds')->pluck('id')->toArray();
    
    \DB::table('crm_staff_tasks')
    ->whereNotIn('module_id',$module_ids)
    ->where('layout_id','>',0)
    ->where('instance_id',session('instance')->id)
    ->update(['is_deleted'=>1]);
    
    $user_ids = \DB::table('crm_staff_tasks')->where('is_deleted',0)->pluck('user_id')->unique()->toArray();
    $users = \DB::table('erp_users')->whereIn('id',$user_ids)->where('account_id',1)->where('is_deleted',0)->get();
    
    foreach($users as $user){
        $role_id_add = $user->role_id;
        if($role_id_add){
            
            $module_ids = \DB::table('crm_staff_tasks')->where('instance_id',session('instance')->id)->where('is_deleted',0)->where('role_id',$role_id_add)->where('layout_id','>',0)->pluck('module_id')->toArray();

            foreach($module_ids as $module_id){
                
                $e = \DB::connection('default')->table('erp_forms')->where('module_id',$module_id)->where('role_id',$role_id_add)->count();
                if(!$e){
                    $data = $forms_collection->where('module_id',$module_id)->where('role_id',1)->first();
                    if(!$data){
                        $data = ['module_id' => $module_id];
                    }else{
                        $data = (array) $data;
                    }
                    unset($data['id']);
                   
                    $data['role_id'] = $role_id_add;
                    $data['is_edit'] = 1;
                    $data['is_view'] = 1;
                  
                   
                    unset($data['is_delete']);
                  
                    \DB::table('erp_forms')->insert($data);
                   
                }else{
                    \DB::table('erp_forms')->where('module_id',$module_id)->where('role_id',$role_id_add)->update(['is_view'=>1,'is_edit'=>1]);    
                }
                /*
                $main_menu_id = \DB::table('erp_menu')->where('location','main_menu')->where('module_id',$module_id)->where('unlisted',0)->pluck('id')->first();
                $top_menu_id = get_toplevel_menu_id($main_menu_id);
                $access = \DB::table('erp_menu')->where('id',$top_menu_id)->pluck('access_role_ids')->first();
                $access_role_ids = explode(',',$access);
                */
            }
        }
    }
    }
}

function beforesave_module_menu_unique($request){
    if ($request->menu_type == 'module'){
        if (!empty($request->new_record)) {
            $exists = \DB::table('erp_menu')->where('module_id',$request->module_id)->where('menu_type','module')->count();
        }else{
            $exists = \DB::table('erp_menu')->where('id','!=',$request->id)->where('module_id',$request->module_id)->where('menu_type','module')->count();
        }
        if($exists > 1){
            return 'Module menu already exists. Use module filter menu type';
        }
    }
}

function beforesave_menu_module_button($request)
{
    if ($request->location == 'grid_menu' || $request->location == 'related_items_menu'){
        if(empty($request->render_module_id)) {
            return 'Render module id required';
        }
        if($request->menu_type =='link' && empty($request->module_id) && (empty($request->url) || $request->url == "#") && empty($request->ajax_function_name)){
            
        }else{
            if(empty($request->action_type)) {
                return 'Action type required';
            }
        }
    }
}

function button_menu_selected($module_id, $location, $grid_id, $event, $sub_items = false)
{
    /*
    if ($sub_items) {
        $buttons = \DB::connection('default')->table('erp_menu')->select('menu_name','require_grid_id','grid_logic')->where('location', $location)->where('render_module_id', $module_id)->where('parent_id', '>', 0)->get();
    } else {
        $buttons = \DB::connection('default')->table('erp_menu')->select('menu_name','require_grid_id','grid_logic')->where('location', $location)->where('render_module_id', $module_id)->get();
    }
    */
    if($location != 'grid_menu' && $location != 'status_buttons'){
        $buttons = app('erp_config')['menus']->where('location', $location);
    }else{
        $buttons = app('erp_config')['menus']->where('location', $location)->where('render_module_id', $module_id);
    }
  
    $button_display_js = 'var '.$location.$grid_id.'_enable_items = [];'.PHP_EOL;
    $button_display_js .= 'var '.$location.$grid_id.'_disable_items = [];'.PHP_EOL;
   
    foreach ($buttons as $btn) {
        

        if ($sub_items) {
            $button_display_js .= 'if($.inArray("'.$btn->menu_name.'", popup_items) !== -1){'.PHP_EOL;
        }
        if (1 == $btn->require_grid_id || !empty($btn->grid_logic)) {
            if ('selected' == $event) {
                $button_display_js .= 'if(typeof selected !== "undefined" && selected){'.PHP_EOL;
          
 //if(is_dev()){
// $button_display_js .= 'window["debug_row"] = selected;'.PHP_EOL;
 //$button_display_js .= 'console.log("'.$btn->menu_name.' selected");console.log(selected);'.PHP_EOL;
// }
              
                if (!empty($btn->grid_logic)) {
                    $button_display_js .= 'if('.$btn->grid_logic.'){'.PHP_EOL;
// if(is_dev()){
// $button_display_js .= 'console.log("grid_logic passed");'.PHP_EOL;
// }
                    $button_display_js .= $location.$grid_id.'_enable_items.push("'.$btn->menu_name.'");'.PHP_EOL;
                    $button_display_js .= '}else{'.PHP_EOL;
                    $button_display_js .= $location.$grid_id.'_disable_items.push("'.$btn->menu_name.'");'.PHP_EOL;
                    $button_display_js .= '}'.PHP_EOL;
                } else {
                    $button_display_js .= $location.$grid_id.'_enable_items.push("'.$btn->menu_name.'");'.PHP_EOL;
                }

                $button_display_js .= '}'.PHP_EOL;
            } elseif ('deselected' == $event) {
                $button_display_js .= $location.$grid_id.'_disable_items.push("'.$btn->menu_name.'");'.PHP_EOL;
            }
        }else{
            $button_display_js .= $location.$grid_id.'_enable_items.push("'.$btn->menu_name.'");'.PHP_EOL;
        }
        if ($sub_items) {
            $button_display_js .= '}'.PHP_EOL;
        }
    }

   // $button_display_js .= 'console.log('.$location.$grid_id.');';
    //$button_display_js .= 'console.log('.$location.$grid_id.'_disable_items);';
   // $button_display_js .= 'console.log('.$location.$grid_id.'_enable_items);';
   // $button_display_js .= 'console.log('.$location.$grid_id.');';

    $button_display_js .= $location.$grid_id.'.enableItems('.$location.$grid_id.'_disable_items, false, false);'.PHP_EOL;
    $button_display_js .= $location.$grid_id.'.enableItems('.$location.$grid_id.'_enable_items, true, false);'.PHP_EOL;
  //aa('$button_display_js '.$event);
 // aa($button_display_js);
    return $button_display_js;
}

function button_headermenu_selected($module_id, $location, $grid_id, $event)
{
    $buttons = \DB::connection('default')->table('erp_menu')->select('menu_name','require_grid_id','grid_logic')->where('location', $location)->where('render_module_id', $module_id)->get();


    $button_display_js = 'var '.$location.$grid_id.'_enable_items = [];'.PHP_EOL;
    $button_display_js .= 'var '.$location.$grid_id.'_disable_items = [];'.PHP_EOL;
    foreach ($buttons as $btn) {

        $button_display_js .= 'if($.inArray("'.$btn->menu_name.'", popup_items) !== -1){'.PHP_EOL;


        if (1 == $btn->require_grid_id || !empty($btn->grid_logic)) {
            if ('selected' == $event) {
                $button_display_js .= 'if(typeof selected !== "undefined" && selected){'.PHP_EOL;

                //if(is_dev()){
                //$button_display_js .= 'console.log("selected");console.log(selected);'.PHP_EOL;
                //}

                if (!empty($btn->grid_logic)) {
                    $button_display_js .= 'if('.$btn->grid_logic.'){'.PHP_EOL;
                    $button_display_js .= $location.$grid_id.'_enable_items.push("'.$btn->menu_name.'");'.PHP_EOL;
                    $button_display_js .= '}'.PHP_EOL;
                } else {
                    $button_display_js .= $location.$grid_id.'_enable_items.push("'.$btn->menu_name.'");'.PHP_EOL;
                }

                $button_display_js .= '}'.PHP_EOL;
            } elseif ('deselected' == $event) {
                $button_display_js .= $location.$grid_id.'_disable_items.push("'.$btn->menu_name.'");'.PHP_EOL;
            }
        }

        $button_display_js .= '}'.PHP_EOL;
    }

    //$button_display_js .= 'console.log('.$location.$grid_id.');';
    //$button_display_js .= 'console.log('.$location.$grid_id.'_enable_items);';

    $button_display_js .= $location.$grid_id.'.enableItems('.$location.$grid_id.'_disable_items, false, false);'.PHP_EOL;
    $button_display_js .= $location.$grid_id.'.enableItems('.$location.$grid_id.'_enable_items, true, false);'.PHP_EOL;

    return $button_display_js;
}




function badge_erp_name()
{
    if (session('role_id') > 10) {
        return 'Home';
    }
    return session('instance')->name;
}


function onload_permissions_activelocation()
{
    $module_ids = \DB::table('erp_cruds')->pluck('id')->filter()->unique()->toArray();
    \DB::table('erp_menu')->where('menu_type', 'like', 'module%')->where('module_id', '>', 0)->whereNotIn('module_id', $module_ids)->delete();
    $menu_ids = \DB::table('erp_menu')->pluck('id')->filter()->unique()->toArray();
    \DB::table('erp_menu_role_access')->whereNotIn('menu_id', $menu_ids)->delete();

    \DB::table('erp_menu_role_access')->where('is_menu', 1)->update(['active_location' => 'Menu']);
    \DB::table('erp_menu_role_access')->where('is_menu', 0)->update(['active_location' => 'None']);

    $detail_module_ids = \DB::table('erp_cruds')->where('detail_module_id', '>', 0)->pluck('detail_module_id')->filter()->unique()->toArray();
    $detail_menu_ids = \DB::table('erp_menu')->whereIn('module_id', $detail_module_ids)->pluck('id')->filter()->unique()->toArray();
    \DB::table('erp_menu_role_access')->where('active_location', 'None')->whereIn('menu_id', $detail_menu_ids)->update(['active_location' => 'Detail Module']);
    $button_module_ids = \DB::table('erp_grid_buttons')->where('redirect_module_id', '>', 0)->pluck('redirect_module_id')->filter()->unique()->toArray();
    $button_menu_ids = \DB::table('erp_menu')->whereIn('module_id', $button_module_ids)->pluck('id')->filter()->unique()->toArray();
    \DB::table('erp_menu_role_access')->where('active_location', 'None')->whereIn('menu_id', $button_menu_ids)->update(['active_location' => 'Grid Button']);



    $module_ids = \DB::table('erp_cruds')->pluck('id')->filter()->unique()->toArray();
    foreach ($module_ids as $module_id) {
        $menu_ids = \DB::table('erp_menu')->where('module_id', $module_id)->pluck('id')->filter()->unique()->toArray();
        $active = \DB::table('erp_menu_role_access')->whereIn('menu_id', $menu_ids)->where('is_menu', 1)->count();
        if ($active) {
            \DB::table('erp_menu_role_access')->where('active_location', 'None')->whereIn('menu_id', $menu_ids)->update(['active_location' => 'Menu']);
        }
    }
}

function badge_statement_balance()
{
    $account_id = session('account_id');
    if ($account_id == 1) {
        $account_id = 12;
    }
    $account = dbgetaccount($account_id);
    return '('.currency($account->balance).')';
}


function badge_approval_count()
{
    $count = \DB::connection('default')->table('crm_approvals')->where('processed', 0)->count();
   
    return '('.$count.')';
}

function badge_activation_count()
{
    if (session('role_id') == 21) {
        $count = \DB::connection('default')->table('sub_activations')->where('account_id', session('account_id'))->where('status', 'Pending')->count();
    }
    if (session('role_id') == 11) {
        $account_ids = \DB::connection('default')->table('crm_accounts')->where('partner_id', session('account_id'))->where('status', '!=', 'Deleted')->pluck('id')->toArray();
        $count = \DB::connection('default')->table('sub_activations')->whereIn('account_id', $account_ids)->where('status', 'Pending')->count();
    }
    if (session('role_level') == 'Admin') {
        $count =\DB::connection('default')->table('sub_activations')->where('status', 'Pending')->count();
    }
    if (!$count) {
        return '';
    }
    return '('.$count.')';
}

function badge_subscription_count()
{
    if (session('role_id') == 21) {
        $count = \DB::connection('default')->table('sub_services')->where('account_id', session('account_id'))->where('status', '!=', 'Deleted')->count();
    }
    if (session('role_id') == 11) {
        $account_ids = \DB::connection('default')->table('crm_accounts')->where('partner_id', session('account_id'))->where('status', '!=', 'Deleted')->pluck('id')->toArray();
        $count = \DB::connection('default')->table('sub_services')->whereIn('account_id', $account_ids)->where('status', '!=', 'Deleted')->count();
    }
    if (session('role_level') == 'Admin') {
        $count =\DB::connection('default')->table('sub_services')->where('status', '!=', 'Deleted')->count();
    }
    if (!$count) {
        return '';
    }
    return '('.$count.')';
}

function beforedelete_move_to_unlisted($request)
{
    $menu_id = $request->id;

    $menu = \DB::table('erp_menu')->where('id', $menu_id)->get()->first();

    if (str_contains($menu->menu_type, 'module') && $menu->unlisted == 0) {
        \DB::table('erp_menu')->where('id', $menu_id)->update(['unlisted' => 1]);
        \DB::table('erp_menu_role_access')->where('menu_id', $menu_id)->update(['is_menu' => 0]);

        $menu_ids = get_submenu_ids($menu_id);
        if (count($menu_ids) > 0) {
            \DB::table('erp_menu')->whereIn('id', $menu_ids)->update(['unlisted' => 1]);
            \DB::table('erp_menu_role_access')->whereIn('menu_id', $menu_ids)->update(['is_menu' => 0]);
        }

        return 'Menu item moved to unlisted';
    }
}


function afterdelete_delete_permissions($request)
{
    $menu_ids = \DB::table('erp_menu')->pluck('id')->toArray();
    \DB::table('erp_menu_role_access')->whereNotIn('menu_id', $menu_ids)->delete();
}

function beforesave_check_menu_module($request)
{
    request()->merge(['menu_name' => trim($request->menu_name)]);
    if (('module' == $request->menu_type || 'module_form' == $request->menu_type) && empty($request->module_id)) {
        return 'Module required.';
    }

    if ('link' == $request->menu_type && empty($request->app_id)) {
        if($request->location!='grid_menu' && $request->location!='related_items_menu'){
        return 'App ID required.';
        }
    }
    if ('module' != $request->menu_type && 'module_form' != $request->menu_type) {
        \DB::table('erp_menu')->where('id', $request->id)->update(['module_id' => null]);
    }
}

function aftersave_menu_set_defaults($request)
{
    
    $menu_collection = \DB::connection('default')->table('erp_menu')->get();
    $menu_ids = \DB::table('erp_menu')->pluck('id')->toArray();
    $parent_ids = \DB::table('erp_menu')->where('parent_id','>',0)->pluck('parent_id')->toArray();
    
   // \DB::table('erp_menu')->where('parent_id','>',0)->whereNo
    
    
    $beforesave_row = session('event_db_record');
    if($request->location != $beforesave_row->location && $beforesave_row->parent_id > 0){
        \DB::table('erp_menu')->where('id',$request->id)->update(['parent_id' => 0]);
    }
    
  
    \DB::table('erp_menu')->whereNotIn('location', ['grid_menu','status_buttons','module_menu','related_items_menu','module_actions'])->update(['render_module_id'=>0]);
    \DB::table('erp_menu')->where('location','!=','customer_menu')->where('action_type', '')->where('menu_type', 'module')->where('module_id','>',0)->update(['action_type'=>'view']);
  
   
    \DB::table('erp_menu')->where('module_id', '>', 0)->update(['app_id'=>null]);
    \DB::table('erp_menu')->whereIn('menu_type', ['link','iframe'])->update(['module_id'=>null]);
    $menu = \DB::table('erp_menu')->where('id', $request->id)->get()->first();
    
    //set module slug
    if($menu->location!='customer_menu' && $menu->menu_type == 'module_filter' || $menu->menu_type == 'module' && !empty($menu->module_id)){
        \DB::table('erp_cruds')->where('id', $menu->module_id)->update(['name'=>$menu->menu_name]);
        \DB::table('erp_menu')->whereIn('menu_type',['module','module_filter'])->where('require_grid_id', 0)->where('module_id', $menu->module_id)->update(['menu_name'=>$menu->menu_name]);
        $module = \DB::table('erp_cruds')->where('id', $menu->module_id)->get()->first();
        $slug = strtolower(str_replace(['_',' '], '-', string_clean($module->name)));
        $slug_exists = \DB::table('erp_cruds')->where('id', '!=', $module->id)->where('slug', $slug)->count();
        if($slug_exists){
            $slug .= $module->id;    
        }
        \DB::table('erp_cruds')->where('id', $module->id)->update(['slug' => $slug]);
    }
    
    
    if ($menu->custom) {
        $menu_ids = get_submenu_ids($menu->id,$menu_collection);
        \DB::table('erp_menu')->whereIn('id', $menu_ids)->update(['custom' => 1]);
    }
    if (!$menu->custom) {
        $menu_ids = get_submenu_ids($menu->id,$menu_collection);
        \DB::table('erp_menu')->whereIn('id', $menu_ids)->update(['custom' => 0]);
    }
 
    $menu_ids = get_submenu_ids($menu->id,$menu_collection);
    \DB::table('erp_menu')->whereIn('id', $menu_ids)->update(['workspace_role_id' => $request->workspace_role_id]);
    
    $parent_menu = \DB::table('erp_menu')->where('id', $menu->parent_id)->count();
    if (!$parent_menu) {
        \DB::table('erp_menu')->where('id', $request->id)->update(['parent_id' => 0]);
    }

    // default menu permissions
    if (!empty($request->new_record)) {
        $data = [
            'menu_id' => $request->id,
            'role_id' => 1,
            'is_menu' => 1,
            'is_view' => 1,
            'is_edit' => 1,
            'is_delete' => 1,
        ];
        \DB::table('erp_menu_role_access')->insert($data);
        
        $data = [
            'menu_id' => $request->id,
            'role_id' => 58,
            'is_menu' => 1,
            'is_view' => 1,
            'is_edit' => 1,
            'is_delete' => 1,
        ];
        \DB::table('erp_menu_role_access')->insert($data);
        
    }

    \DB::table('erp_menu')->where('menu_type', 'none')->update(['menu_type' => 'link','url'=>'#']);
    if ($menu->parent_id != 0) {
        $parent_location = \DB::table('erp_menu')->where('id', $menu->parent_id)->pluck('location')->first();
        if ($parent_location != $menu->location) {
            \DB::table('erp_menu')->where('id', $request->id)->update(['parent_id' => 0]);
        }
    }

    $menu_collection = \DB::connection('default')->table('erp_menu')->get();
    $top_menus = \DB::table('erp_menu')->where('parent_id', 0)->where('menu_type', 'link')->get();
    foreach ($top_menus as $topmenu) {
        $menu_ids = get_submenu_ids($topmenu->id,$menu_collection);
        if(count($menu_ids) > 0){
            \DB::table('erp_menu')->whereIn('id', $menu_ids)->update(['location' => $topmenu->location]);
            if(in_array($topmenu->location,['grid_menu','module_actions','module_menu']) && $topmenu->render_module_id > 0 ){
            \DB::table('erp_menu')->whereIn('id', $menu_ids)->update(['render_module_id' => $topmenu->render_module_id]);
            }
            if($topmenu->location == 'main_menu' && $topmenu->workspace_render_id > 0 ){
            \DB::table('erp_menu')->whereIn('id', $menu_ids)->update(['workspace_render_id' => $topmenu->workspace_render_id]);
            }
        }
    }

    
    
    
    \DB::table('erp_menu')
    ->join('erp_cruds', 'erp_menu.module_id', '=', 'erp_cruds.id')
    ->where('erp_menu.module_id', '>', 0)
    ->update(['erp_menu.app_id' => DB::raw('(SELECT app_id FROM erp_cruds WHERE erp_cruds.id = erp_menu.module_id)')]);
    
    \DB::table('erp_menu')
    ->join('erp_cruds', 'erp_menu.render_module_id', '=', 'erp_cruds.id')
    ->where('erp_menu.render_module_id', '>', 0)
    ->whereNull('erp_menu.app_id')
    ->update(['erp_menu.app_id' => DB::raw('(SELECT app_id FROM erp_cruds WHERE erp_cruds.id = erp_menu.render_module_id)')]);
    
    
    
    
    \DB::table('erp_menu')->where('menu_type','module_filter')->update(['url'=>'']);   
    if(!empty($request->new_record)){
        build_permissions_from_menu([$request->id]);
    }elseif($request->location != $beforesave_row->location){
        build_permissions_from_menu([$request->id]);
    }elseif($request->access_role_ids != $beforesave_row->access_role_ids){
        build_permissions_from_menu([$request->id]);
    }
    

   // $toplevel_id = get_toplevel_menu_id($request->id);
   // set_menulink_permissions_from_submenu($toplevel_id);
    
}

function validate_menu_permissions()
{
    $menu_ids = \DB::table('erp_menu')->pluck('id')->toArray();
    $role_ids = \DB::table('erp_user_roles')->pluck('id')->toArray();
    \DB::table('erp_menu_role_access')->whereNotIn('menu_id',$menu_ids)->delete();
    \DB::table('erp_menu_role_access')->whereNotIn('role_id',$role_ids)->delete();
}


function button_menu_permissions($request)
{
    return redirect()->to('/menu_permissions/'.$request->id);
}

function get_menu_access_from_module($module_id)
{
 
    $slug = app('erp_config')['modules']->where('id', $module_id)->pluck('slug')->first();
    $model = new \App\Models\ErpModel();
    $model->setMenuData($slug);
    return $model->validAccess(); 
}

function get_menu_access($menu_id)
{
    $module_id = app('erp_config')['menus']->where('id', $menu_id)->pluck('id')->first();
    $slug = app('erp_config')['modules']->where('id', $module_id)->pluck('slug')->first();
    $model = new \App\Models\ErpModel();
    $model->setMenuData($slug);
    return $model->validAccess(); 
}



function get_permissions_menu_item($menu_id, $access_field = 'is_menu')
{
    $query = \DB::connection('default')->table('erp_menu_role_access');
    $query->join('erp_user_roles', 'erp_user_roles.id', '=', 'erp_menu_role_access.role_id');
    $query->where('menu_id', $menu_id);
    $query->where($access_field, 1);

    return $query->orderby('erp_user_roles.sort_order')->pluck('erp_user_roles.name')->toArray();
}

function check_menu_permission($menu_id, $role_id = false)
{
    if(!$role_id){
        $role_id = session('role_id');    
    }
    $query = \DB::connection('default')->table('erp_menu_role_access');
    $query->join('erp_user_roles', 'erp_user_roles.id', '=', 'erp_menu_role_access.role_id');
    $query->where('menu_id', $menu_id);
    $query->where('role_id', $role_id);
    $query->where('menu_id', 1);

    return $query->count();
}

function get_permissions_menu_item_role_ids($menu_id, $access_field = 'is_menu')
{
    $query = \DB::connection('default')->table('erp_menu_role_access');
    $query->join('erp_user_roles', 'erp_user_roles.id', '=', 'erp_menu_role_access.role_id');
    $query->where('menu_id', $menu_id);
    $query->where($access_field, 1);

    return $query->orderby('erp_user_roles.sort_order')->pluck('erp_user_roles.id')->toArray();
}

function check_access_level($level)
{
    return (session('role_level') == $level) ? true : false;
}

function check_access_against($access_groups, $ids_to_check)
{
    if (empty($access_groups)) {
        return true;
    }


    if (!empty($access_groups)) {
        $access_groups = explode(',', $access_groups);
        $user_groups = $ids_to_check;
        foreach ($user_groups as $user_group) {
            if (in_array($user_group, $access_groups)) {
                return true;
            }
        }
    }

    return false;
}

function check_access($access_groups = false, $original_group = false)
{
    if (empty($access_groups)) {
        return true;
    }

    $group = session('role_id');

    if ($original_group) {
        $group = session('original_role_id');
    }
    if (!empty($access_groups)) {
        $access_groups = explode(',', $access_groups);
        $user_groups = explode(',', $group);

        foreach ($user_groups as $user_group) {
            if (in_array($user_group, $access_groups)) {
                return true;
            }
        }
    }

    return false;
}

function call_mobile($number, $table, $id)
{
    /*
    originate {origination_caller_id_number=27813608644}user/101@pbx.cloudtools.co.za &bridge({origination_caller_id_number=27824119555}sofia/gateway/7441e9fa-0d98-422c-a1b6-e89ea78c84f0/27824119555)
    originate {origination_caller_id_number=9005551212}sofia/default/whatever@wherever 19005551212 XML default CALLER_ID_NAME CALLER_ID_NUMBER
    */
    
    // validate number
    $number = za_number_format($number);
    if (!$number) {
        return 'Invalid Phone Number';
    }

    // get extension
    $user_extension = \DB::table('erp_users')->where('id', session('user_id'))->whereNotNull('pbx_extension')->pluck('pbx_extension')->first();
    if (session('role_level') == 'Admin') {
        $pabx_domain = 'pbx.cloudtools.co.za';
    } else {
        $pabx_domain = \DB::table('crm_accounts')->where('id', session('account_id'))->whereNotNull('pabx_domain')->pluck('pabx_domain')->first();
    }

    if (!$pabx_domain) {
        return 'Invalid PBX Domain';
    }

    if (!$user_extension) {
        return 'Invalid User Extension';
    }

    $domain_uuid = \DB::connection('pbx')->table('v_domains')->where('domain_name', $pabx_domain)->pluck('domain_uuid')->first();
    $ext = \DB::connection('pbx')->table('v_extensions')->where(['domain_uuid' => $domain_uuid, 'extension' => $user_extension])->get()->first();

    if (empty($ext)) {
        return 'Invalid User Extension';
    }

    $gateway_uuid = '7441e9fa-0d98-422c-a1b6-e89ea78c84f0';
    $domain_uuid = \DB::connection('pbx')->table('v_domains')->where('domain_name', $pabx_domain)->pluck('domain_uuid')->first();
    $caller_id = $ext->outbound_caller_id_number;

    // execute freeswitch command
    // $fs_command = 'portal_call_originate {origination_caller_id_number='.$caller_id.'}sofia/default/'.$user_extension.'@'.$pabx_domain.' '.$number.' XML default '.$user_extension.' '.$caller_id;
    $fs_command = 'portal_call_originate {origination_caller_id_number='.$caller_id.'}user/'.$user_extension.'@'.$pabx_domain.' '.$number.' XML '.$pabx_domain;

    //$fs_command = 'portal_call_originate {origination_caller_id_number='.$caller_id.'}user/'.$user_extension.'@'.$pabx_domain.' &bridge({origination_caller_id_number='.$number.'})';
    $pbx = new FusionPBX();
    $pbx->portalCmd('cmd='.$fs_command);
    return true;
}

/*

copy menu items

 $services_menu = \DB::table('erp_menu')->where('location','services_menu')->get();
    $new_ids = [];
    foreach($services_menu as $m){
        $topid = get_toplevel_menu_id($m->id);
        if($m->id != 7453 && $topid == 7453){
            $data = (array) $m;
            unset($data['id']);
            $data['location'] = 'telecloud_menu';
            $id = \DB::table('erp_menu')->insertGetId($data);
            
            $permissions = \DB::table('erp_menu_role_access')->where('menu_id',$m->id)->get();
            foreach($permissions as $p){
                $pdata = (array) $p;
                unset($pdata['id']);
                $pdata['menu_id'] = $id;
                \DB::table('erp_menu_role_access')->insert($pdata);
            }
            $new_ids[$m->id] = $id;
        }
    }
    foreach($new_ids as $oid => $nid){
        \DB::table('erp_menu')->where('location','telecloud_menu')->where('parent_id',$oid)->update(['parent_id' =>$nid]);
    }
    \DB::table('erp_menu')->where('location','telecloud_menu')->where('parent_id',7453)->update(['parent_id' => 0]);
*/
