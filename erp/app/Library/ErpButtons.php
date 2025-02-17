<?php

class ErpButtons
{
    public static function getHTML($grid_id, $menu_id, $admin_btn = 0)
    {
        $main_btn = '';
        $group_btn = '';

        $module_id = \DB::connection('default')->table('erp_menu')->where('id', $menu_id)->pluck('module_id')->first();
        if ($admin_btn) {
            $buttons = self::getevents_menu($menu_id, '');
        } else {
            $buttons = self::getButtons($menu_id, '', 0);
        }
        if ($admin_btn) {
            $button_groups = self::getAdminButtonGroups($menu_id);
        } else {
            $button_groups = self::getButtonGroups($menu_id);
        }

        if (!empty($button_groups)) {
            foreach ($button_groups as $button_group) {
                $group_btn_visible = '';
                $group = str_replace(' ', '_', strtolower($button_group)).$module_id;
                if ($admin_btn) {
                    $group_btn .= "<button id='dropbtnadmin_".$grid_id.$group."'  class='k-button'>".($button_group).'</button>';
                } else {
                    $group_btn .= "<button id='dropbtn_".$grid_id.$group."'  class='k-button'>".($button_group).'</button>';
                }
            }
        }

        if (!empty($buttons)) {
            foreach ($buttons as $btn) {
                $allow = self::check_button_access($btn);
                if (!$allow) {
                    continue;
                }

                if (!empty($btn->menu_id) && $btn->menu_id != $menu_id) {
                    continue;
                }

                $disabled = '';
                if ($btn->require_grid_id) {
                    $disabled = 'disabled';
                }


                $main_btn .= "
                <button id='mainbtn_".$grid_id.$btn->id."'  class='k-button' ".$disabled.">".($btn->name).'</button>
                ';
            }
        }



        if (!empty($main_btn) || !empty($group_btn)) {
            return $group_btn.$main_btn;
        }
    }

    public static function getInlineButtons($grid_id, $menu_id)
    {
        $menu = \DB::connection('default')->table('erp_menu')->where('id', $menu_id)->get()->first();
        $module_id = $menu->module_id;

        $buttons = \DB::connection('default')->table('erp_grid_buttons')->where('inline_grid_button', 1)->where('module_id', $module_id)->orderby('sort_order', 'asc')->get();


        $inline_buttons = [];
        foreach ($buttons as $btn) {
            $allow = self::check_button_access($btn);
            if (!$allow) {
                continue;
            }
            if (empty($btn->icon)) {
                $btn->icon = 'fas fa-info';
            }
            $inline_buttons[] = (object) [
                'type' => $btn->name,
                'buttonOption' => (object) [
                    'content' => $btn->name,
                    'title' => $btn->name,
                    //'iconCss' => $btn->icon,
                    'cssClass' => 'e-flat e-outline e-small inline-btn inline-btn-'.$btn->id
                ]
            ];
        }

        return $inline_buttons;
    }

    public static function getInlineButtonsCreated($grid_id, $menu_id)
    {
        $module_id = \DB::connection('default')->table('erp_menu')->where('id', $menu_id)->pluck('module_id')->first();

        $buttons = \DB::connection('default')->table('erp_grid_buttons')->where('inline_grid_button', 1)->where('module_id', $module_id)->orderby('sort_order', 'asc')->get();

        $route_name = get_menu_url($module_id);
        $filter_script = '';
        if (!empty($buttons)) {
            foreach ($buttons as $button) {
                $btn = $button;
                if (!self::check_button_access($button)) {
                    continue;
                }

                if (empty($button->custom_button)) {
                    $button->url = '/'.$route_name.'/button/'.$button->id.'/';
                }


                if (!empty($btn->read_only_logic)) {
                    $btn->read_only_logic = str_replace('selected.', 'args.data.', $btn->read_only_logic);
                    if (str_contains($btn->read_only_logic, '@show')) {
                        $filter_script .= "
                var show_inline_btn = false;
                ".str_replace('@show', 'var show_inline_btn = true;', $btn->read_only_logic)."
                if(!show_inline_btn){
                   var inline_btn = $(args.row).find('.inline-btn-".$btn->id."');
                   $(inline_btn).addClass( 'e-disabled' );
                   $(inline_btn).prop( 'disabled', 'disabled');
                }
                ";
                    } else {
                        $filter_script .= "
                var show_inline_btn = false;
                if(".$btn->read_only_logic."){
                    var show_inline_btn = true;
                }
                if(!show_inline_btn){
                   var inline_btn = $(args.row).find('.inline-btn-".$btn->id."');
                   $(inline_btn).addClass( 'e-disabled' );
                   $(inline_btn).prop( 'disabled', 'disabled');
                }
                
                ";
                    }
                }
            }
        }

        echo $filter_script;
    }

    public static function getInlineButtonsActions($grid_id, $menu_id)
    {
        $module_id = \DB::connection('default')->table('erp_menu')->where('id', $menu_id)->pluck('module_id')->first();

        $buttons = \DB::connection('default')->table('erp_grid_buttons')->where('inline_grid_button', 1)->where('module_id', $module_id)->orderby('sort_order', 'asc')->get();

        $route_name = get_menu_url($module_id);
        $button_js = '';
        if (!empty($buttons)) {
            foreach ($buttons as $button) {
                if (!self::check_button_access($button)) {
                    continue;
                }

                if (empty($button->custom_button)) {
                    $button->url = '/'.$route_name.'/button/'.$button->id.'/';
                }

                $button_js .= '
    
             	$(document).on("click",".inline-btn-'.$button->id.'", function(args) {';
                $button_js .= "  
        			if($(args.srcElement).hasClass('e-disabled')){
        			    return false;
        			}
        			var row = window['grid_".$grid_id."'].getRowObjectFromUID(ej.base.closest(args.target, '.e-row').getAttribute('data-uid'));
        			window['grid_".$grid_id."'].selectRow(row.index);
					";
                if (!empty($button->confirm)) {
                    $button_js .= '
					var confirmation = confirm("'.$button->confirm.'");
		            if (confirmation) {
					';
                }

                if (1 == $button->require_grid_id) {
                    $button_js .= "
        			var selected = window['selectedrow_".$grid_id."'];
					var url = '".$button->url."'+selected.rowId;
					";
                } elseif (572 == $button->id) {
                    $button_js .= "
					var url = '".$button->url."1';
					";
                } else {
                    $button_js .= '
	                var url = "'.$button->url.'";
	                ';
                }

                if (1 == $button->in_iframe) {
                    $button_js .= "
					var url = url + '/1';
					";
                }

                if ('redirect' == $button->type) {
                    $button_js .= '
					window.open(url);
					';
                }

                if ('ajax_function' == $button->type) {
                    $button_js .= '
            	
            	   
            	    
            	    if(typeof grid_filters === "undefined"){
            	        grid_filters = null;
            	    }
					gridAjax(url,{grid_filters:grid_filters},"post");
					';
                }

                if ('grid_config' == $button->type) {
                    $button_js .= '
					load_grid_config(url);
					';
                }

                if ('grid_config_save' == $button->type) {
                    $button_js .= '
                        save_grid_config();
					';
                }

                if ('modal_form' == $button->type || 'modal_view' == $button->type || 'sidebarview' == $button->type || 'modal_transact' == $button->type) {
                    $height = 'auto';
                    if ('modal_form' == $button->type) {
                        $modal_type = 'sidebarform';
                    }
                    if ('modal_view' == $button->type) {
                        $modal_type = 'viewDialog';
                    }
                    if ('sidebarview' == $button->type) {
                        $modal_type = 'sidebarview';
                    }
                    if ('modal_transact' == $button->type) {
                        $modal_type = 'transactionDialog';
                    }
                    if ($button->id == 602 && $button->module_id == 547) {
                        $button_js .= '
                        '.$modal_type.'("'.$button->id.'" ,url, "Check IP Status", "70%", "auto","form-dialog","Submit");
                        ';
                    } elseif ($button->id == 290 && $button->module_id == 334) {
                        $button_js .= '
                        '.$modal_type.'("'.$button->id.'" ,url, "Activate", "70%", "auto","view-dialog","Submit");
                        ';
                    } else {
                        $button_js .= '
						'.$modal_type.'("'.$button->id.'" ,url,"'.$button->name.'");
						';
                    }
                }

                if (!empty($button->confirm)) {
                    $button_js .= '
		            }
					';
                }

                $button_js .= '
				});';
            }
        }

        echo $button_js;
    }

    public static function getContextMenuButtons($menu, $top)
    {
        $menu_id = \DB::connection('default')->table('erp_menu')->where('slug', $menu)->pluck('id')->first();

        $right_click_buttons = self::getButtons($menu_id, 'all', 1);

        $context_buttons = [];
        foreach ($right_click_buttons as $btn) {
            if (!$btn->require_grid_id) {
                continue;
            }

            $allow = self::check_button_access($btn);

            if (!$allow) {
                continue;
            }
            $context_buttons[] = $btn;
        }

        return $context_buttons;
    }

    public static function getContextMenuActions($grid_id, $menu_id)
    {
        $module_id = \DB::connection('default')->table('erp_menu')->where('id', $menu_id)->pluck('module_id')->first();

        $right_click_buttons = self::getButtons($menu_id, 'all', 1);

        $route_name = get_menu_url_from_id($menu_id, 'default');

        $button_js = '';
        if (!empty($right_click_buttons)) {
            foreach ($right_click_buttons as $button) {
                if (!self::check_button_access($button)) {
                    continue;
                }

                if (empty($button->custom_button)) {
                    $button->url = '/'.$route_name.'/button/'.$button->id.'/';
                }

                $button_js .= '
				if(button_id == "mainbtn_'.$grid_id.$button->id.'"){
					';
                if (!empty($button->confirm)) {
                    $button_js .= '
					var confirmation = confirm("'.$button->confirm.'");
		            if (confirmation) {
					';
                }

                if (1 == $button->require_grid_id) {
                    $button_js .= "
        			var selected = window['selectedrow_".$grid_id."'];
        			
					var url = '".$button->url."'+selected.rowId;
					";
                } elseif (572 == $button->id) {
                    $button_js .= "
					var url = '".$button->url."1';
					";
                } else {
                    $button_js .= '
	                var url = "'.$button->url.'";
	                ';
                }

                if (1 == $button->in_iframe) {
                    $button_js .= "
					var url = url + '/1';
					";
                }

                if ('redirect' == $button->type) {
                    $button_js .= '
					window.open(url);
					';
                }

                if ('ajax_function' == $button->type) {
                    $button_js .= '
            	    
            	    if(typeof grid_filters === "undefined"){
            	        grid_filters = null;
            	    }
					gridAjax(url,{grid_filters:grid_filters},"post");
					';
                }

                if ('grid_config' == $button->type) {
                    $button_js .= '
					load_grid_config(url);
					';
                }

                if ('grid_config_save' == $button->type) {
                    $button_js .= '
                        save_grid_config();
					';
                }

                if ('modal_form' == $button->type || 'modal_view' == $button->type || 'sidebarview' == $button->type || 'modal_transact' == $button->type) {
                    $height = 'auto';
                    if ('modal_form' == $button->type) {
                        $modal_type = 'sidebarform';
                    }
                    if ('modal_view' == $button->type) {
                        $modal_type = 'viewDialog';
                    }
                    if ('sidebarview' == $button->type) {
                        $modal_type = 'sidebarview';
                    }
                    if ('modal_transact' == $button->type) {
                        $modal_type = 'transactionDialog';
                    }
                    if ($button->id == 602 && $button->module_id == 547) {
                        $button_js .= '
                        '.$modal_type.'("'.$button->id.'" ,url, "Check IP Status", "70%", "auto","form-dialog","Submit");
                        ';
                    } elseif ($button->id == 290 && $button->module_id == 334) {
                        $button_js .= '
                        '.$modal_type.'("'.$button->id.'" ,url, "Activate", "70%", "auto","view-dialog","Submit");
                        ';
                    } else {
                        $button_js .= '
						'.$modal_type.'("'.$button->id.'" ,url,"'.$button->name.'");
						';
                    }
                }

                if (!empty($button->confirm)) {
                    $button_js .= '
		            }
					';
                }

                $button_js .= '
				}';
            }
        }

        echo $button_js;
    }

    public static function getContextMenuFilters($menu_id)
    {
        $module_id = \DB::connection('default')->table('erp_menu')->where('id', $menu_id)->pluck('module_id')->first();
        $filter_script = "";
        $right_click_buttons = self::getButtons($menu_id, 'all', 1);

        foreach ($right_click_buttons as $btn) {
            $allow = self::check_button_access($btn);
            if (!$allow) {
                continue;
            }
            if (!empty($btn->read_only_logic)) {
                if (str_contains($btn->read_only_logic, '@show')) {
                    $filter_script .= "
                    if(button_id == 'mainbtn_".$grid_id.$btn->id."'){
                    ".str_replace('@show', 'return true;', $btn->read_only_logic)."
                    
                    return false;
                    }
                    ";
                } else {
                    $filter_script .= "
                    if(button_id == 'mainbtn_".$grid_id.$btn->id."'){
                    if(".$btn->read_only_logic."){
                    return true;
                    }
                    return false;
                    }
                    ";
                }
            }
        }

        echo $filter_script;
    }

    public static function getAdminEventScript($grid_id, $menu_id, $event = 'created')
    {
        $module_id = \DB::connection('default')->table('erp_menu')->where('id', $menu_id)->pluck('module_id')->first();

        $buttons = self::getevents_menu($menu_id);

        $button_display_js = '';
        if (!empty($buttons)) {
            foreach ($buttons as $btn) {
                if (!self::check_button_access($btn)) {
                    continue;
                }

                if ('created' == $event) {
                    $button_display_js .= 'mainbtn_'.$grid_id.$btn->id.' = new ej.buttons.Button(';
                    if (!empty($btn->icon)) {
                        $button_display_js .= '{iconCss: "'.$btn->icon.'"}';
                    }
                    $button_display_js .= ');';
                    $button_display_js .= 'mainbtn_'.$grid_id.$btn->id.".appendTo('#mainbtn_".$grid_id.$btn->id."');".PHP_EOL;
                    if ($btn->require_grid_id || !empty($btn->read_only_logic)) {
                        $button_display_js .= 'mainbtn_'.$grid_id.$btn->id.'.disabled = true;'.PHP_EOL;
                    }
                } elseif (1 == $btn->require_grid_id || !empty($btn->read_only_logic)) {
                    if ('selected' == $event) {
                        if (!empty($btn->read_only_logic) && str_contains($btn->read_only_logic, '@show')) {
                            $button_display_js .= str_replace('@show', 'mainbtn_'.$grid_id.$btn->id.'.disabled = false;'.PHP_EOL, $btn->read_only_logic).PHP_EOL;
                        } elseif (!empty($btn->read_only_logic)) {
                            $button_display_js .= 'if('.$btn->read_only_logic.'){'.PHP_EOL.'mainbtn_'.$grid_id.$btn->id.'.disabled = false;'.PHP_EOL.'}else{'.PHP_EOL.'mainbtn_'.$grid_id.$btn->id.'.disabled = true;'.PHP_EOL.'}'.PHP_EOL;
                        } else {
                            $button_display_js .= 'mainbtn_'.$grid_id.$btn->id.'.disabled = false;'.PHP_EOL;
                        }
                    } elseif ('deselected' == $event) {
                        $button_display_js .= 'mainbtn_'.$grid_id.$btn->id.'.disabled = true;'.PHP_EOL;
                    }
                }
            }
        }

        $button_group_display_js = '';
        $button_groups = self::getAdminButtonGroups($menu_id);

        if (!empty($button_groups)) {
            foreach ($button_groups as $button_group) {
                $buttons = self::getevents_menu($menu_id, $button_group);

                $sub_items_require_grid_id = 1;
                if (!empty($buttons)) {
                    foreach ($buttons as $btn) {
                        $allow = self::check_button_access($btn);
                        if (!$allow) {
                            continue;
                        }

                        if (!$btn->require_grid_id) {
                            $sub_items_require_grid_id = 0;
                        }
                    }
                }

                $group = str_replace(' ', '_', strtolower($button_group)).$module_id;

                if ('created' == $event) {
                    $display_filter = '';
                    $button_group_display_js .= '
						var '.$grid_id.$group.'adminitems = [';

                    if (!empty($buttons)) {
                        foreach ($buttons as $btn) {
                            $allow = self::check_button_access($btn);

                            if (!$allow) {
                                continue;
                            }

                            $id = 'mainbtn_'.$grid_id.$btn->id;
                            $button_group_display_js .= '{
									id: "'.$id.'",
							        text: "'.$btn->name.'",
							    },';

                            if ($btn->require_grid_id) {
                                $display_filter .= PHP_EOL."document.getElementById('".$id."').style.display = 'none';".PHP_EOL;
                                $display_filter .= PHP_EOL."if (typeof selected !== 'undefined' && selected !== null) {".PHP_EOL;
                                if (!empty($btn->read_only_logic) && str_contains($btn->read_only_logic, '@show')) {
                                    $display_filter .= str_replace('@show', "document.getElementById('".$id."').style.display = 'list-item';".PHP_EOL, $btn->read_only_logic).PHP_EOL;
                                } elseif (!empty($btn->read_only_logic)) {
                                    $display_filter .= ''.PHP_EOL.'if('.$btn->read_only_logic.'){'.PHP_EOL."document.getElementById('".$id."').style.display = 'list-item';".PHP_EOL.'}'.PHP_EOL;
                                } else {
                                    $display_filter .= "document.getElementById('".$id."').style.display = 'list-item';".PHP_EOL;
                                }

                                $display_filter .= PHP_EOL."} else {
							document.getElementById('".$id."').style.display = 'none';
							}".PHP_EOL;
                            }
                        }
                    }

                    $init_disabled = ($sub_items_require_grid_id) ? 'true' : 'false';

                    $button_group_display_js .= '];
					var '.$grid_id.$group.'adminoptions = {
					  items: '.$grid_id.$group."adminitems,
					  cssClass: 'e-caret-down grid_dropdown',
					  disabled: ".$init_disabled.",
        			  beforeOpen: function (e){
    			        var selected = window['selectedrow_".$grid_id."'];
						".$display_filter.'
					  },
					};
					drpDownBtnadmin'.$grid_id.$group.' = new ej.splitbuttons.DropDownButton('.$grid_id.$group.'adminoptions);
					drpDownBtnadmin'.$grid_id.$group.".appendTo('#dropbtnadmin_".$grid_id.$group."');
				";
                }

                if ('selected' == $event) {
                    $requires_id = false;
                    $display_filter = '';

                    if (!empty($buttons)) {
                        foreach ($buttons as $btn) {
                            $allow = self::check_button_access($btn);
                            if (!$allow) {
                                continue;
                            }

                            $id = 'mainbtn_'.$grid_id.$btn->id;
                            if ($btn->require_grid_id) {
                                $requires_id = true;
                                $display_filter .= PHP_EOL."if (typeof selected !== 'undefined' && selected !== null) {".PHP_EOL;
                                //$display_filter .= PHP_EOL."console.log(selected);".PHP_EOL;
                                if (!empty($btn->read_only_logic) && str_contains($btn->read_only_logic, '@show')) {
                                    $display_filter .= str_replace('@show', 'var enable_group = true;'.PHP_EOL, $btn->read_only_logic).PHP_EOL;
                                } elseif (!empty($btn->read_only_logic)) {
                                    $display_filter .= 'if('.$btn->read_only_logic.'){'.PHP_EOL.'var enable_group = true;'.PHP_EOL.'}'.PHP_EOL;
                                } else {
                                    $display_filter .= 'var enable_group = true;'.PHP_EOL;
                                }

                                $display_filter .= PHP_EOL.'}'.PHP_EOL;
                            }
                        }
                    }

                    if ($requires_id) {
                        $display_filter = '
							var enable_group = false;
						'.$display_filter;
                    } else {
                        $display_filter = '
							var enable_group = true;
						'.$display_filter;
                    }
                }

                if ('selected' == $event && $sub_items_require_grid_id) {
                    $button_group_display_js .= $display_filter.'
					if(enable_group){
					drpDownBtnadmin'.$grid_id.$group.'.disabled = false;'.PHP_EOL.'drpDownBtnadmin'.$grid_id.$group.'.dataBind();
					}'.PHP_EOL;
                } elseif ('deselected' == $event && $sub_items_require_grid_id) {
                    $button_group_display_js .= 'drpDownBtnadmin'.$grid_id.$group.'.disabled = true;'.PHP_EOL.'drpDownBtnadmin'.$grid_id.$group.'.dataBind();'.PHP_EOL;
                }
            }
        }

        if (!empty($button_display_js)) {
            echo $button_display_js;
        }

        if (!empty($button_group_display_js)) {
            echo $button_group_display_js;
        }
    }

    public static function getEventScript($grid_id, $menu_id, $event = 'created')
    {
        $module_id = \DB::connection('default')->table('erp_menu')->where('id', $menu_id)->pluck('module_id')->first();

        $buttons = self::getButtons($menu_id);

        $button_display_js = '';
        if (!empty($buttons)) {
            foreach ($buttons as $btn) {
                if (!self::check_button_access($btn)) {
                    continue;
                }

                if ('created' == $event) {
                    $button_display_js .= 'mainbtn_'.$grid_id.$btn->id.' = new ej.buttons.Button(';
                    if (!empty($btn->icon)) {
                        // $button_display_js .= '{iconCss: "'.$btn->icon.'"}';
                    }
                    $button_display_js .= ');';
                    $button_display_js .= 'mainbtn_'.$grid_id.$btn->id.".appendTo('#mainbtn_".$grid_id.$btn->id."');".PHP_EOL;
                    if ($btn->require_grid_id || !empty($btn->read_only_logic)) {
                        $button_display_js .= 'mainbtn_'.$grid_id.$btn->id.'.disabled = true;'.PHP_EOL;
                    }
                } elseif (1 == $btn->require_grid_id || !empty($btn->read_only_logic)) {
                    if ('selected' == $event) {
                        if (!empty($btn->read_only_logic) && str_contains($btn->read_only_logic, '@show')) {
                            $button_display_js .= str_replace('@show', 'mainbtn_'.$grid_id.$btn->id.'.disabled = false;'.PHP_EOL, $btn->read_only_logic).PHP_EOL;
                        } elseif (!empty($btn->read_only_logic)) {
                            $button_display_js .= 'if('.$btn->read_only_logic.'){'.PHP_EOL.'mainbtn_'.$grid_id.$btn->id.'.disabled = false;'.PHP_EOL.'}'.PHP_EOL;
                        } else {
                            $button_display_js .= 'mainbtn_'.$grid_id.$btn->id.'.disabled = false;'.PHP_EOL;
                        }
                    } elseif ('deselected' == $event) {
                        $button_display_js .= 'mainbtn_'.$grid_id.$btn->id.'.disabled = true;'.PHP_EOL;
                    }
                }
            }
        }

        $button_group_display_js = '';
        $button_groups = self::getButtonGroups($menu_id);

        if (!empty($button_groups)) {
            foreach ($button_groups as $button_group) {
                $buttons = self::getButtons($menu_id, $button_group);

                $sub_items_require_grid_id = 1;
                if (!empty($buttons)) {
                    foreach ($buttons as $btn) {
                        $allow = self::check_button_access($btn);
                        if (!$allow) {
                            continue;
                        }

                        if (!$btn->require_grid_id) {
                            $sub_items_require_grid_id = 0;
                        }
                    }
                }

                $group = str_replace(' ', '_', strtolower($button_group)).$module_id;

                if ('created' == $event) {
                    $display_filter = '';
                    $button_group_display_js .= '
						var '.$grid_id.$group.'items = [';

                    if (!empty($buttons)) {
                        foreach ($buttons as $btn) {
                            $allow = self::check_button_access($btn);

                            if (!$allow) {
                                continue;
                            }

                            $id = 'mainbtn_'.$grid_id.$btn->id;
                            $button_group_display_js .= '{
									id: "'.$id.'",
							        text: "'.$btn->name.'",
							    },';

                            if ($btn->require_grid_id) {
                                $display_filter .= PHP_EOL."$('#".$id."').attr('disabled','disabled');$('#".$id."').addClass('e-disabled');".PHP_EOL;
                                $display_filter .= PHP_EOL."if (typeof selected !== 'undefined' && selected !== null) {".PHP_EOL;
                                if (!empty($btn->read_only_logic) && str_contains($btn->read_only_logic, '@show')) {
                                    $display_filter .= str_replace('@show', "$('#".$id."').removeAttr('disabled');$('#".$id."').removeClass('e-disabled');".PHP_EOL, $btn->read_only_logic).PHP_EOL;
                                } elseif (!empty($btn->read_only_logic)) {
                                    $display_filter .= ''.PHP_EOL.'if('.$btn->read_only_logic.'){'.PHP_EOL."$('#".$id."').removeAttr('disabled');$('#".$id."').removeClass('e-disabled');".PHP_EOL.'}'.PHP_EOL;
                                } else {
                                    $display_filter .= "$('#".$id."').removeAttr('disabled');$('#".$id."').removeClass('e-disabled');".PHP_EOL;
                                }

                                $display_filter .= PHP_EOL."} else {
							    $('#".$id."').attr('disabled','disabled');$('#".$id."').addClass('e-disabled');
							}".PHP_EOL;
                            }
                        }
                    }

                    $init_disabled = ($sub_items_require_grid_id) ? 'true' : 'false';

                    $button_group_display_js .= '];
					var '.$grid_id.$group.'options = {
					  items: '.$grid_id.$group."items,
					  cssClass: 'e-caret-down grid_dropdown',
					  disabled: ".$init_disabled.",
        			  beforeOpen: function (e){
    			        var selected = window['selectedrow_".$grid_id."'];
						".$display_filter.'
					  },
					};
					drpDownBtn'.$grid_id.$group.' = new ej.splitbuttons.DropDownButton('.$grid_id.$group.'options);
					drpDownBtn'.$grid_id.$group.".appendTo('#dropbtn_".$grid_id.$group."');
					
				";
                }


                if ('selected' == $event) {
                    $requires_id = false;
                    $display_filter = '';

                    if (!empty($buttons)) {
                        foreach ($buttons as $btn) {
                            $allow = self::check_button_access($btn);
                            if (!$allow) {
                                continue;
                            }

                            $id = 'mainbtn_'.$grid_id.$btn->id;
                            if ($btn->require_grid_id) {
                                $requires_id = true;
                                $display_filter .= PHP_EOL."if (typeof selected !== 'undefined' && selected !== null) {".PHP_EOL;
                                if (!empty($btn->read_only_logic) && str_contains($btn->read_only_logic, '@show')) {
                                    $display_filter .= str_replace('@show', 'var enable_group = true;'.PHP_EOL, $btn->read_only_logic).PHP_EOL;
                                } elseif (!empty($btn->read_only_logic)) {
                                    $display_filter .= 'if('.$btn->read_only_logic.'){'.PHP_EOL.'var enable_group = true;'.PHP_EOL.'}'.PHP_EOL;
                                } else {
                                    $display_filter .= 'var enable_group = true;'.PHP_EOL;
                                }

                                $display_filter .= PHP_EOL.'}'.PHP_EOL;
                            }
                        }
                    }

                    if ($requires_id) {
                        $display_filter = '
							var enable_group = false;
						'.$display_filter;
                    } else {
                        $display_filter = '
							var enable_group = true;
						'.$display_filter;
                    }
                }

                if ('selected' == $event && $sub_items_require_grid_id) {
                    $button_group_display_js .= $display_filter.'
					if(enable_group){
					drpDownBtn'.$grid_id.$group.'.disabled = false;'.PHP_EOL.'drpDownBtn'.$grid_id.$group.'.dataBind();
					}'.PHP_EOL;
                } elseif ('deselected' == $event && $sub_items_require_grid_id) {
                    $button_group_display_js .= 'drpDownBtn'.$grid_id.$group.'.disabled = true;'.PHP_EOL.'drpDownBtn'.$grid_id.$group.'.dataBind();'.PHP_EOL;
                }
            }
        }

        if (!empty($button_display_js)) {
            echo $button_display_js;
        }

        if (!empty($button_group_display_js)) {
            echo $button_group_display_js;
        }
    }

    public static function getToolbarButtonActions($grid_id, $menu_id)
    {
        $module_id = \DB::connection('default')->table('erp_menu')->where('id', $menu_id)->pluck('module_id')->first();

        $buttons = self::getButtons($menu_id, 'all');
        $admin_buttons = self::getevents_menu($menu_id, 'all');
        foreach ($admin_buttons as $btn) {
            $buttons[] = $btn;
        }

        $route_name = get_menu_url($module_id);
        if (749 != $module_id && 389 != $module_id && 385 != $module_id  && 488 != $module_id && 500 != $module_id) {
            $dialogclass = '';
        } else {
            $dialogclass = 'coreDialog';
        }
        $button_js = '';
        if (!empty($buttons)) {
            foreach ($buttons as $button) {
                if (!self::check_button_access($button)) {
                    continue;
                }

                if (empty($button->custom_button)) {
                    $button->url = '/'.$route_name.'/button/'.$button->id.'/';
                }


                $button_js .= '
				$(document).off("click","#mainbtn_'.$grid_id.$button->id.'").on("click","#mainbtn_'.$grid_id.$button->id.'",function(){
					';
                if (!empty($button->confirm)) {
                    $button_js .= '
					var confirmation = confirm("'.$button->confirm.'");
		            if (confirmation) {
					';
                }

                if (1 == $button->require_grid_id) {
                    $button_js .= "
        			var selected = window['selectedrow_".$grid_id."'];
					var url = '".$button->url."'+selected.rowId;
					";
                } elseif (572 == $button->id) {
                    $button_js .= "
					var url = '".$button->url."1';
					";
                } else {
                    $button_js .= '
	                var url = "'.$button->url.'";
	                ';
                }

                if (1 == $button->in_iframe) {
                    $button_js .= "
					var url = url + '/1';
					";
                }

                if ('redirect' == $button->type) {
                    $button_js .= '
					window.open(url);
					';
                }

                if ('ajax_function' == $button->type) {
                    $button_js .= '
            	
            	    if(typeof grid_filters === "undefined"){
            	        grid_filters = null;
            	    }
					gridAjax(url,{grid_filters:grid_filters},"post");
					';
                }

                if ('grid_config' == $button->type) {
                    $button_js .= '
					load_grid_config(url);
					';
                }

                if ('grid_config_save' == $button->type) {
                    $button_js .= '
                        save_grid_config();
					';
                }

                if ('modal_form' == $button->type || 'modal_view' == $button->type || 'sidebarview' == $button->type || 'modal_transact' == $button->type) {
                    $height = 'auto';
                    if ('modal_form' == $button->type) {
                        $modal_type = 'sidebarform';
                    }
                    if ('modal_view' == $button->type) {
                        $modal_type = 'viewDialog';
                    }
                    if ('sidebarview' == $button->type) {
                        $modal_type = 'sidebarview';
                    }
                    if ('modal_transact' == $button->type) {
                        $modal_type = 'transactionDialog';
                    }
                    if ($button->id == 602 && $button->module_id == 547) {
                        $button_js .= '
                        '.$modal_type.'("'.$button->id.'" ,url, "Check IP Status", "70%", "auto","form-dialog","Submit");
                        ';
                    } elseif ($button->id == 290 && $button->module_id == 334) {
                        $button_js .= '
                        '.$modal_type.'("'.$button->id.'" ,url, "Activate", "70%", "auto","view-dialog","Submit");
                        ';
                    } else {
                        $button_js .= '
						'.$modal_type.'("'.$button->id.'" ,url,"'.$button->name.'");
						';
                    }
                }

                if (!empty($button->confirm)) {
                    $button_js .= '
		            }
					';
                }

                $button_js .= '
				});';
            }
        }
        //dd($button_js);
        echo $button_js;
    }




    public static function getAggridContextMenu($menu_id)
    {
        $context_js = '';
        $module_id = \DB::connection('default')->table('erp_menu')->where('id', $menu_id)->pluck('module_id')->first();

        $right_click_buttons = self::getButtons($menu_id, 'all', 1);
        $right_click_groups = $right_click_buttons->where('button_group', '>', '')->pluck('button_group')->unique()->toArray();
        $r = $right_click_buttons->where('button_group', 'Manage');

        foreach ($right_click_buttons as $btn) {
            $allow = self::check_button_access($btn);
            if (!$allow) {
                continue;
            }
            $context_js .= 'var disabled'.$btn->id.' = false;
            ';
            if (!empty($btn->read_only_logic)) {
                $context_js .= "
               
                if(".$btn->read_only_logic."){
                    var disabled".$btn->id." = false;
                }else{
                    var disabled".$btn->id." = true;
                }
                
                ";
            }
        }
        $button_js = 'var contextbuttons = [';
        $route_name = get_menu_url_from_id($menu_id, 'default');

        $right_click_groups = $right_click_buttons->where('button_group', '>', '')->pluck('button_group')->unique()->toArray();

        if (!empty($right_click_groups) && is_array($right_click_groups) && count($right_click_groups) > 0) {
            foreach ($right_click_groups as $btn_group) {
                $group_buttons = $right_click_buttons->where('button_group', $btn_group);
                if (!empty($group_buttons)) {
                    $button_js .= "{
                    name: '".$btn_group."',
                    subMenu: [";

                    foreach ($group_buttons as $button) {
                        if (!self::check_button_access($button)) {
                            continue;
                        }
                        $button_js .= self::getAggridContextMenuAction($route_name, $button);
                    }
                    $button_js .= "
                    ]
                    },
                    ";
                }
            }
        }

        $right_click_buttons = $right_click_buttons->where('button_group', '');
        if (!empty($right_click_buttons)) {
            foreach ($right_click_buttons as $button) {
                if (!self::check_button_access($button)) {
                    continue;
                }
                $button_js .= self::getAggridContextMenuAction($route_name, $button);
            }
        }

        $button_js .= "
        ];
        result.push(...contextbuttons);
        ";
        echo $context_js.$button_js;
    }

    public static function getAggridContextMenuAction($route_name, $button)
    {
        $allow = self::check_button_access($button);
        if (!$allow) {
            return false;
        }
        $button_js = '
            {
            name: "'.$button->name.'",
            disabled: disabled'.$button->id.',
            action: function () {
        ';


        if (empty($button->custom_button)) {
            $button->url = '/'.$route_name.'/button/'.$button->id.'/';
        }


        if (!empty($button->confirm)) {
            $button_js .= '
			var confirmation = confirm("'.$button->confirm.'");
            if (confirmation) {
			';
        }

        if (1 == $button->require_grid_id) {
            $button_js .= "
		
			
			var url = '".$button->url."'+selected.id;
			";
        } elseif (572 == $button->id) {
            $button_js .= "
			var url = '".$button->url."1';
			";
        } else {
            $button_js .= '
            var url = "'.$button->url.'";
            ';
        }

        if (1 == $button->in_iframe) {
            $button_js .= "
			var url = url + '/1';
			";
        }

        if ('redirect' == $button->type) {
            $button_js .= '
			window.open(url);
			';
        }

        if ('ajax_function' == $button->type) {
            $button_js .= '
    	
			gridAjax(url,{},"post");
			';
        }

        if ('grid_config' == $button->type) {
            $button_js .= '
			load_grid_config(url);
			';
        }

        if ('grid_config_save' == $button->type) {
            $button_js .= '
                save_grid_config();
			';
        }

        if ('modal_form' == $button->type || 'modal_view' == $button->type || 'sidebarview' == $button->type || 'modal_transact' == $button->type) {
            $height = 'auto';
            if ('modal_form' == $button->type) {
                $modal_type = 'sidebarform';
            }
            if ('modal_view' == $button->type) {
                $modal_type = 'viewDialog';
            }
            if ('sidebarview' == $button->type) {
                $modal_type = 'sidebarview';
            }
            if ('modal_transact' == $button->type) {
                $modal_type = 'transactionDialog';
            }
            if ($button->id == 602 && $button->module_id == 547) {
                $button_js .= '
                '.$modal_type.'("'.$button->id.'" ,url, "Check IP Status", "70%", "auto","form-dialog","Submit");
                ';
            } elseif ($button->id == 290 && $button->module_id == 334) {
                $button_js .= '
                '.$modal_type.'("'.$button->id.'" ,url, "Activate", "70%", "auto","view-dialog","Submit");
                ';
            } else {
                $button_js .= '
				'.$modal_type.'("'.$button->id.'" ,url,"'.$button->name.'");
				';
            }
        }

        if (!empty($button->confirm)) {
            $button_js .= '
            }
			';
        }
        $button_js .= '
        }
        },
        ';
        return $button_js;
    }


    private static function getevents_menu($menu_id, $group = '')
    {
        $menu = \DB::connection('default')->table('erp_menu')->where('id', $menu_id)->get()->first();
        $module_id = $menu->module_id;


        if ('all' == $group) {
            $buttons = \DB::connection('default')->table('erp_grid_buttons')->where('admin_button', 1)->where('module_id', $module_id)->orderby('sort_order', 'asc')->get();
        } elseif (!empty($group)) {
            $buttons = \DB::connection('default')->table('erp_grid_buttons')->where('admin_button', 1)->where('button_group', $group)->where('module_id', $module_id)->orderby('sort_order', 'asc')->get();
        } else {
            $buttons = \DB::connection('default')->table('erp_grid_buttons')->where('admin_button', 1)->where('button_group', '')->where('module_id', $module_id)->orderby('sort_order', 'asc')->get();
        }



        return $buttons;
    }

    private static function getButtons($menu_id, $group = '', $admin_button = 0)
    {
        $menu = \DB::connection('default')->table('erp_menu')->where('id', $menu_id)->get()->first();
        $module_id = $menu->module_id;


        if ('all' == $group) {
            $buttons = \DB::connection('default')->table('erp_grid_buttons')->where('admin_button', $admin_button)->where('module_id', $module_id)->orderby('sort_order', 'asc')->get();
        } elseif (!empty($group)) {
            $buttons = \DB::connection('default')->table('erp_grid_buttons')->where('admin_button', $admin_button)->where('button_group', $group)->where('module_id', $module_id)->orderby('sort_order', 'asc')->get();
        } else {
            $buttons = \DB::connection('default')->table('erp_grid_buttons')->where('admin_button', $admin_button)->where('button_group', '')->where('module_id', $module_id)->orderby('sort_order', 'asc')->get();
        }

        /*
        if ('all' == $group) {
            $buttons = \DB::connection('default')->table('erp_grid_buttons')->where('require_grid_id', $require_grid_id)->where('inline_grid_button', 0)->where('module_id', $module_id)->orderby('sort_order', 'asc')->get();
        } elseif (!empty($group)) {
            $buttons = \DB::connection('default')->table('erp_grid_buttons')->where('require_grid_id', $require_grid_id)->where('inline_grid_button', 0)->where('button_group', $group)->where('module_id', $module_id)->orderby('sort_order', 'asc')->get();
        } else {
            $buttons = \DB::connection('default')->table('erp_grid_buttons')->where('require_grid_id', $require_grid_id)->where('inline_grid_button', 0)->where('button_group', '')->where('module_id', $module_id)->orderby('sort_order', 'asc')->get();
        }
       */


        return $buttons;
    }


    private static function getAdminButtonGroups($menu_id)
    {
        //return [];
        $menu = \DB::connection('default')->table('erp_menu')->where('id', $menu_id)->get()->first();
        $module_id = $menu->module_id;

        if (session('role_level') == 'Admin') {
            $button_groups = \DB::connection('default')->table('erp_grid_buttons')->where('admin_button', 1)->where('module_id', $module_id)->orderby('sort_order', 'asc')->pluck('button_group')->filter()->unique()->toArray();
        } else {
            $button_groups = \DB::connection('default')->table('erp_grid_buttons')->where('admin_button', 1)->where('access', 'LIKE', '%'.session('role_id').'%')->where('module_id', $module_id)->orderby('sort_order', 'asc')->pluck('button_group')->filter()->unique()->toArray();
        }
        /*
         if (session('role_level') == 'Admin') {
             $button_groups = \DB::connection('default')->table('erp_grid_buttons')->where('require_grid_id', 0)->where('module_id', $module_id)->orderby('sort_order', 'asc')->pluck('button_group')->filter()->unique()->toArray();
         } else {
             $button_groups = \DB::connection('default')->table('erp_grid_buttons')->where('require_grid_id', 0)->where('access', 'LIKE', '%'.session('role_id').'%')->where('module_id', $module_id)->orderby('sort_order', 'asc')->pluck('button_group')->filter()->unique()->toArray();
         }
         */
        //$pivots = \DB::connection('default')->table('erp_reports')->orderby('name')->get();
        //$grid_views = \DB::connection('default')->table('erp_grid_views')->where('module_id', $module_id)->orderby('name')->get();
        /*
        if (!empty($grid_views) && count($grid_views) > 0) {
            $button_groups[] = 'Views';
        }

        if (!empty($pivots) && count($pivots) > 0 && session('role_level') == 'Admin') {
            $button_groups[] = 'Reports';
        }
*/

        return $button_groups;
    }

    private static function getButtonGroups($menu_id)
    {
        //return [];
        $menu = \DB::connection('default')->table('erp_menu')->where('id', $menu_id)->get()->first();
        $module_id = $menu->module_id;

        if (session('role_level') == 'Admin') {
            $button_groups = \DB::connection('default')->table('erp_grid_buttons')->where('admin_button', 0)->where('inline_grid_button', 0)->where('module_id', $module_id)->orderby('sort_order', 'asc')->pluck('button_group')->filter()->unique()->toArray();
        } else {
            $button_groups = \DB::connection('default')->table('erp_grid_buttons')->where('admin_button', 0)->where('inline_grid_button', 0)->where('access', 'LIKE', '%'.session('role_id').'%')->where('module_id', $module_id)->orderby('sort_order', 'asc')->pluck('button_group')->filter()->unique()->toArray();
        }
        /*
         if (session('role_level') == 'Admin') {
             $button_groups = \DB::connection('default')->table('erp_grid_buttons')->where('require_grid_id', 0)->where('module_id', $module_id)->orderby('sort_order', 'asc')->pluck('button_group')->filter()->unique()->toArray();
         } else {
             $button_groups = \DB::connection('default')->table('erp_grid_buttons')->where('require_grid_id', 0)->where('access', 'LIKE', '%'.session('role_id').'%')->where('module_id', $module_id)->orderby('sort_order', 'asc')->pluck('button_group')->filter()->unique()->toArray();
         }
         */
        //$pivots = \DB::connection('default')->table('erp_reports')->orderby('name')->get();
        //$grid_views = \DB::connection('default')->table('erp_grid_views')->where('module_id', $module_id)->orderby('name')->get();
        /*
        if (!empty($grid_views) && count($grid_views) > 0) {
            $button_groups[] = 'Views';
        }

        if (!empty($pivots) && count($pivots) > 0 && session('role_level') == 'Admin') {
            $button_groups[] = 'Reports';
        }
*/

        return $button_groups;
    }

    private static function check_button_access($btn)
    {
        if (!empty($btn->redirect_module_id)) {
            $menu_access = get_menu_access_from_module($btn->redirect_module_id);
            if (!$menu_access['is_view']) {
                return false;
            }
        }
        $access = $btn->access;
        $access = array_filter(explode(',', $access));
        if (empty($access) || 0 == count($access)) {
            $allow = false;
        }

        $role_id = session('role_id');
        if (in_array($role_id, $access)) {
            $allow = true;
        }

        return $allow;
    }
}
