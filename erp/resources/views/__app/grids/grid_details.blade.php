@php


if(empty(request()->all()) ||count(request()->all()) == 0){
    $request_get = '';
}else{
    $request_get = http_build_query(request()->all());
}
$serverside_model = false;
@endphp

@section('detail_content')


<div id="detailcontainer{{ $grid_id }}" >
<div class="gridheader">


</div>
</div>
<div class="grid_menubtn{{$module_id}}"></div>
<div class="related_items_menubtn{{$module_id}}"></div>

@endsection


@push('page-scripts')
<script>



@if(!empty($detail_cell_renderer))
class DetailCellIFrameRenderer{{$grid_id}} {
    {!! $detail_cell_renderer !!}
}
@endif


@if($has_cell_editing)
syncfusion_data = {};

class SyncFusionCellEditor{{ $grid_id }} {

  // gets called once after the editor is created
  init(args) {
  
    this.container = document.createElement('div');
    this.container.setAttribute("id",  args.column.colId+"_container");
    this.colId = args.column.colId;
    this.rowId = args.data.{{$db_key}};
    this.colType = args.colDef.type;
    
   var post_data =  {id: args.data.{{$db_key}}, value: args.value, field: args.column.colId };   
  
           
    $.ajax({ 
        url: "/{{$menu_route}}/cell_editor", 
        type: 'post',
        data: post_data,
        beforeSend: function(){
        },
        success: function (result) { 
          
           // //////console.log('cell_editor');
            //////console.log(result);
            //////console.log(args);
            
            $('#'+args.column.colId+"_container").html(result);
            var colHeaderElement = $('.ag-header-cell[col-id="'+args.column.colId+'"]');
           
            var header_width = colHeaderElement.width();
            
            $('#'+args.column.colId+"_container").width(header_width+36);
        }, 
    });
    
   
  }

  // Return the DOM element of your editor,
  // this is what the grid puts into the DOM
  getGui() {
   return this.container;
  }

  // Gets called once by grid after editing is finished
  // if your editor needs to do any cleanup, do it here
  destroy() {
   
  }

  // Gets called once after GUI is attached to DOM.
  // Useful if you want to focus or highlight a component
  afterGuiAttached() {
    this.container.focus();
  }

  // Should return the final value to the grid, the result of the editing
  getValue() {
      
 
   setTimeout(() => {
   //////console.log('getValue2');
   //////console.log(this);
   var field_db_type = this.colType;
    var field_id = this.colId;
    var field_id = field_id.replace('join_','');
    
   //////console.log(window[field_id+"{{$module_id}}"]);
   if(field_db_type == "booleanField"){
     var field_val =  window[field_id+"{{$module_id}}"].checked;
   }else{
    var field_val = window[field_id+"{{$module_id}}"].value;
   //////console.log(field_val);
    if(this.colType == "booleanField"){
    var field_val =  $('#'+field_id+"{{$module_id}}").val();
    }
   }
       //////console.log(field_val);
    var post_data =  {id: this.rowId, value: field_val, field: field_id }; 
    syncfusion_data[field_id] = field_val;
       //////console.log(post_data);

   //////console.log(syncfusion_data);
    $.ajax({ 
        url: "/{{$menu_route}}/save_cell", 
        type: 'post',
        data: post_data,
        beforeSend: function(){
     
        },
        success: function (result) { 
            //////console.log('save_cell');
            //////console.log(result);
            if(result.status != 'success' && result.status != 'skip'){
            toastNotify(result.message,result.status);
            detail_grid_api.api.undoCellEditing();
            }
            if(result.status != 'skip'){
            refresh_detail_grid{{ $grid_id }}()
            }
        }, 
    });
      
    return $('#'+field_id+"{{$module_id}}").val();
   },200);
   return 'Saving...';
  }

  // Gets called once after initialised.
  // If you return true, the editor will appear in a popup
  isPopup() {
    return false;
  }
}
@endif

class pinnedTotalRenderer{{$grid_id}} {
  // init method gets the details of the cell to be renderer
  init(params) {
     
    this.eGui = document.createElement('span');
    if(params.node && params.node.rowPinned && params.node.rowPinned == 'bottom'){
        if(params.column.colDef.pinned_row_total){
            this.eGui.innerHTML = (params.valueFormatted != null) ? params.valueFormatted:params.value;
        }else{
            this.eGui.innerHTML = '';
        }
    }else{
        this.eGui.innerHTML = (params.valueFormatted != null) ? params.valueFormatted:params.value;
    }
  }

  getGui() {
    return this.eGui;
  }

  refresh(params) {
    return false;
  }
}
/** CONTEXT MENUS **/

/* button right click context menu*/
ej.base.enableRipple(true);



    @if(!empty($grid_menu_menu) && count($grid_menu_menu) > 0)   
    var detail_grid_menuMenuItems = @php echo json_encode($grid_menu_menu); @endphp;
    // top_menu initialization
    var grid_menu{{ $grid_id }} = new ej.navigations.Menu({
       items: detail_grid_menuMenuItems,
       orientation: 'Horizontal',
       cssClass: 'top-menu k-widget k-button-group',
       
        created: function(args){
           
            @if(is_superadmin())
            
            $('body').append('<ul id="grid_menu_context{{$grid_id}}" class="m-0"></ul>');
            var context_items = [
                {
                    id: "context_gridtab_edit",
                    text: "Edit Menu",
                    iconCss: "fas fa-list",
                    url: 'sf_menu_manager/{{$module_id}}/grid_menu',
                    data_target: 'view_modal',
                },
                {
                    id: "edit_menu_btn",
                    text: "Edit",
                    iconCss: "fas fa-list",
                },
                {
                    id: "edit_menu_btn_function",
                    text: "Edit Function",
                    iconCss: "fas fa-list",
                },
            ];
            var menuOptions = {
                target: '.grid_menubtn{{$module_id}}',
                items: context_items,
                beforeItemRender: dropdowntargetrender,
                
                beforeOpen: function(args){
                    // toggle context items on header
                   
                    if( $(args.event.target).hasClass('grid_menubtn{{$module_id}}')){
                        data_menu_id = $(args.event.target).attr('data-menu-id');
                        data_button_function = $(args.event.target).attr('data-button-function');
                    }else{
                        data_menu_id = $(args.event.target).closest('li').attr('data-menu-id');
                        data_button_function = $(args.event.target).closest('li').attr('data-button-function');
                    }
                    if(data_button_function > ''){
                        grid_menu_context{{$module_id}}.enableItems(['Edit Function'], true);        
                    }else{
                        grid_menu_context{{$module_id}}.enableItems(['Edit Function'], false); 
                    }
                },
                select: function(args){
                    if(args.item.id === 'edit_menu_btn') {
                        sidebarform('editmenubtn','{{$menu_manager_url}}/edit/'+data_menu_id);
                    }
                    if(args.item.id === 'edit_menu_btn_function') {
                        sidebarform('editfunctionbtn','/code_edit/'+data_button_function);
                    }
                }
            };
            
            // Initialize ContextMenu control
            grid_menu_context{{$module_id}} =new ej.navigations.ContextMenu(menuOptions, '#grid_menu_context{{$grid_id}}');
            //////console.log('grid_menu_context{{$module_id}}', grid_menu_context{{$module_id}});
            @endif
    
        },
        beforeOpen: function(args){
         
            @if(is_superadmin())
            grid_menu_context{{$module_id}}.refresh();
            @endif
            var popup_items = [];
            $(args.items).each(function(i, el){
                popup_items.push(el.text);
            });
        
            var selected = window['selectedrow_{{ $grid_id }}'];
            
           
            
            {!! button_menu_selected($module_id, 'grid_menu', $grid_id, 'selected', true) !!}
            
          
    	    var selected = window['mastergrid_row{{ $master_grid_id }}'];
           
            {!! button_headermenu_selected($master_module_id, 'grid_menu', $grid_id, 'selected', true) !!}
           
        },
        beforeItemRender: function(args){
            var el = args.element;   
            $(el).find("a").attr("title",args.item.title);
            if(args.item.border_top){
              
               $(el).addClass("menu_border_top");
            }
            
            $(el).attr("data-menu-id",args.item.menu_id);
            $(el).attr("data-button-function",args.item.button_function);
            if(args.item.confirm_text) {
                $(el).find("a").attr("confirm-text",args.item.confirm_text);
            }   
            if(args.item.new_tab == 1) {
            var el = args.element;
            $(el).find("a").attr("target","_blank");
            }
            if(args.item.cssClass) {
                $(el).addClass(args.item.cssClass);
            }
             
            @if(!empty($menus_newtab) && $menus_newtab === true)
            if(args.item.data_target == '' && args.item.url > '' && args.item.url != "#"){
                var el = args.element;
                $(el).find("a").attr("target","_blank");
            }
            @endif
            if(args.item.new_tab == 1) {
               var el = args.element;
               $(el).find("a").attr("target","_blank");
            }
            if(args.item.data_target == 'javascript') {
               $(el).find("a").attr("data-target",args.item.data_target);
               $(el).find("a").attr("js-target",args.item.url);
               $(el).find("a").attr("id",args.item.url);
               $(el).find("a").attr("href","javascript:void(0)");
            }else if(args.item.data_target == 'transaction' || args.item.data_target == 'transaction_modal') {
               $(el).find("a").attr("data-target",args.item.data_target);
               $(el).find("a").attr("href","javascript:void(0)");
               $(el).find("a").attr("modal_url",args.item.url);
            }else if(args.item.data_target) {
               $(el).find("a").attr("data-target",args.item.data_target);
              
            }
            
                // add row id to module menus
            
            if(args.item.require_grid_id && args.item.header_key){
                if(window['mastergrid_row{{ $master_grid_id }}'] && window['mastergrid_row{{ $master_grid_id }}'].id){
                   
                    var grid_url = args.item.original_url + window['mastergrid_row{{ $master_grid_id }}'].id; 
                    $(el).find("a").attr("href",grid_url);
                }
            }else if(args.item.require_grid_id ){
                if(window['selectedrow_{{ $grid_id }}'] && window['selectedrow_{{ $grid_id }}'].id){
                   
                    var grid_url = args.item.original_url + window['selectedrow_{{ $grid_id }}'].id; 
                    $(el).find("a").attr("href",grid_url);
                }
            }
            
        },
    },'#grid_menu_menu{{ $grid_id }}');
    @endif



    @if(!empty($status_buttons_menu) && count($status_buttons_menu) > 0)   
    var detail_status_buttonsMenuItems = @php echo json_encode($status_buttons_menu); @endphp;
    // top_menu initialization
    var status_buttons{{ $grid_id }} = new ej.navigations.Menu({
       items: detail_status_buttonsMenuItems,
       orientation: 'Horizontal',
       cssClass: 'top-menu k-widget k-button-group',
       
        created: function(args){
           
            @if(is_superadmin())
            
            $('body').append('<ul id="status_buttons_context{{$grid_id}}" class="m-0"></ul>');
            var context_items = [
                {
                    id: "context_gridtab_edit",
                    text: "Edit Menu",
                    iconCss: "fas fa-list",
                    url: 'sf_menu_manager/{{$module_id}}/status_buttons',
                    data_target: 'view_modal',
                },
                {
                    id: "edit_menu_btn",
                    text: "Edit",
                    iconCss: "fas fa-list",
                },
                {
                    id: "edit_menu_btn_function",
                    text: "Edit Function",
                    iconCss: "fas fa-list",
                },
            ];
            var menuOptions = {
                target: '.status_buttonsbtn{{$module_id}}',
                items: context_items,
                beforeItemRender: dropdowntargetrender,
                
                beforeOpen: function(args){
                    // toggle context items on header
                   
                    if( $(args.event.target).hasClass('status_buttonsbtn{{$module_id}}')){
                        data_menu_id = $(args.event.target).attr('data-menu-id');
                        data_button_function = $(args.event.target).attr('data-button-function');
                    }else{
                        data_menu_id = $(args.event.target).closest('li').attr('data-menu-id');
                        data_button_function = $(args.event.target).closest('li').attr('data-button-function');
                    }
                    if(data_button_function > ''){
                        status_buttons_context{{$module_id}}.enableItems(['Edit Function'], true);        
                    }else{
                        status_buttons_context{{$module_id}}.enableItems(['Edit Function'], false); 
                    }
                },
                select: function(args){
                    if(args.item.id === 'edit_menu_btn') {
                        sidebarform('editmenubtn','{{$menu_manager_url}}/edit/'+data_menu_id);
                    }
                    if(args.item.id === 'edit_menu_btn_function') {
                        sidebarform('editfunctionbtn','/code_edit/'+data_button_function);
                    }
                }
            };
            
            // Initialize ContextMenu control
            status_buttons_context{{$module_id}} =new ej.navigations.ContextMenu(menuOptions, '#status_buttons_context{{$grid_id}}');
            //////console.log('status_buttons_context{{$module_id}}', status_buttons_context{{$module_id}});
            @endif
    
        },
        beforeOpen: function(args){
         
            @if(is_superadmin())
            status_buttons_context{{$module_id}}.refresh();
            @endif
            var popup_items = [];
            $(args.items).each(function(i, el){
                popup_items.push(el.text);
            });
        
            var selected = window['selectedrow_{{ $grid_id }}'];
            
           
            
            {!! button_menu_selected($module_id, 'status_buttons', $grid_id, 'selected', true) !!}
            
          
    	    var selected = window['mastergrid_row{{ $master_grid_id }}'];
           
            {!! button_headermenu_selected($master_module_id, 'status_buttons', $grid_id, 'selected', true) !!}
           
        },
        beforeItemRender: function(args){
            var el = args.element;   
            $(el).find("a").attr("title",args.item.title);
            if(args.item.border_top){
              
               $(el).addClass("menu_border_top");
            }
            
            $(el).attr("data-menu-id",args.item.menu_id);
            $(el).attr("data-button-function",args.item.button_function);
            if(args.item.confirm_text) {
                $(el).find("a").attr("confirm-text",args.item.confirm_text);
            }   
            if(args.item.new_tab == 1) {
            var el = args.element;
            $(el).find("a").attr("target","_blank");
            }
            if(args.item.cssClass) {
                $(el).addClass(args.item.cssClass);
            }
             
            @if(!empty($menus_newtab) && $menus_newtab === true)
            if(args.item.data_target == '' && args.item.url > '' && args.item.url != "#"){
                var el = args.element;
                $(el).find("a").attr("target","_blank");
            }
            @endif
            if(args.item.new_tab == 1) {
               var el = args.element;
               $(el).find("a").attr("target","_blank");
            }
            if(args.item.data_target == 'javascript') {
               $(el).find("a").attr("data-target",args.item.data_target);
               $(el).find("a").attr("js-target",args.item.url);
               $(el).find("a").attr("id",args.item.url);
               $(el).find("a").attr("href","javascript:void(0)");
            }else if(args.item.data_target == 'transaction' || args.item.data_target == 'transaction_modal') {
               $(el).find("a").attr("data-target",args.item.data_target);
               $(el).find("a").attr("href","javascript:void(0)");
               $(el).find("a").attr("modal_url",args.item.url);
            }else if(args.item.data_target) {
               $(el).find("a").attr("data-target",args.item.data_target);
              
            }
            
                // add row id to module menus
            
            if(args.item.require_grid_id && args.item.header_key){
                if(window['mastergrid_row{{ $master_grid_id }}'] && window['mastergrid_row{{ $master_grid_id }}'].id){
                   
                    var grid_url = args.item.original_url + window['mastergrid_row{{ $master_grid_id }}'].id; 
                    $(el).find("a").attr("href",grid_url);
                }
            }else if(args.item.require_grid_id ){
                if(window['selectedrow_{{ $grid_id }}'] && window['selectedrow_{{ $grid_id }}'].id){
                   
                    var grid_url = args.item.original_url + window['selectedrow_{{ $grid_id }}'].id; 
                    $(el).find("a").attr("href",grid_url);
                }
            }
            
        },
    },'#status_buttons_menu{{ $grid_id }}');
    @endif
    
filter_cleared{{ $grid_id }} = 0;
show_deleted{{ $grid_id }} = 0;

    function detail_layout_save(){
        
        var layout = {};
        layout.colState = detail_grid_api.columnApi.getColumnState();
        layout.groupState = detail_grid_api.columnApi.getColumnGroupState();
      
        layout.filterState = detail_grid_api.api.getFilterModel();
        
        var pivot = {};
        var pivotMode = detail_grid_api.columnApi.isPivotMode();
        if(pivotMode){
            var pivot_mode = 1;
          
        }else{
            var pivot_mode = 0;
        }
           
        var data = {grid_id: '{{$master_grid_id}}', layout : layout, master_layout_id: window['layout_id{{ $master_grid_id }}'], layout_id: window['layout_id{{ $grid_id }}'], pivot: pivot, pivot_mode: pivot_mode,  query_string: {!! $query_string !!}};
       //////console.log(data);
        $.ajax({
            type: 'post',
            url: '{{ url($menu_route."/aggrid_layout_save") }}',
            data: data,
    		success: function(data) { 
    		    //////console.log('detail_layout_save');
    		    //////console.log(data);
    		    if(data.detail_col_defs){
    		    window['detail_col_defs{{$master_grid_id}}'] = data.detail_col_defs;  
    		    }
    		    if(data.detail_settings){
    		    window['detail_settings{{$master_grid_id}}'] = data.detail_settings;  
    		    }
    		 
    		    window['layout_id{{ $grid_id }}'] = data.layout_id;
    		    toastNotify('Detail Layout saved.','success');
    		}
    	});
    }
    


function getMainMenuItems{{$grid_id}}(params) {
    
    @if(session('role_level') == 'Admin')
    // you don't need to switch, we switch below to just demonstrate some different options
    // you have on how to build up the menu to return

   // var menuItems = params.defaultItems.slice(0);
    var menuItems = [];
    var colId =  params.column.getId();
    
 
    menuItems.push({
    name: '<b>'+params.column.colDef.headerName+'</b>',
    });
    menuItems.push({
    name: '<b>'+params.column.colDef.db_field+'</b>',
    });
    menuItems.push('separator');
   
    
    @endif
    
    @if($module_fields_access['is_add'] || $module_fields_access['is_edit'] || $module_fields_access['is_delete'])
        menuItems.push('separator');
    @endif
    
    @if($module_fields_access['is_view'])
        menuItems.push({
            name: 'Fields',
            action: function () {
                viewDialog("","{{ url($module_fields_url.'?module_id='.$module_id) }}");
            },
        });
    @endif
    
    @if($module_fields_access['is_add'])
        menuItems.push({
            name: 'Add Field',
            action: function () {
                sidebarform('coladd',"{{ url($module_fields_url.'/edit?module_id='.$module_id) }}","{{$fields_module_title}}","{{$fields_module_description}}");
            },
        });
    @endif
    
    
    @if($module_fields_access['is_add'])
        menuItems.push({
            name: 'Duplicate Field',
            action: function () {
                gridAjaxConfirm('/{{ $module_fields_url }}/duplicate', 'Duplicate column?', {"id" : params.column.colDef.dbid}, 'post');
            },
        });
    @endif
    
    @if($module_fields_access['is_edit'])
        
        menuItems.push({
            name: 'Edit Field',
            action: function () {
                sidebarform('coladd',"{{ url($module_fields_url.'/edit') }}"+'/'+params.column.colDef.dbid ,"{{$fields_module_title}}","{{$fields_module_description}}");
            },
        });
         
    @endif
    @if($module_fields_access['is_delete'])
        menuItems.push({
            name: 'Delete Field',
            action: function () {
                gridAjaxConfirm('/{{ $module_fields_url }}/delete', 'Delete Column?', {"id" : params.column.colDef.dbid}, 'post');
            },
        });
    @endif
    
    @if($condition_styles_access['is_view'])
        menuItems.push('separator');
        
        var condition_styles_submenu = [];
         condition_styles_submenu.push({
            name: 'List',
            action: function () {
             
                var field_name = params.column.colDef.db_field;
                var dbid = params.column.colDef.dbid;
                field_name.replace('join_');
         
                viewDialog('condition_styles',"{{ url($condition_styles_url.'?module_id='.$module_id) }}"+'&field_id='+dbid);
            },
        });
        condition_styles_submenu.push({
            name: 'Bold',
            action: function () {
                var field_name = params.column.colDef.db_field;
                field_name.replace('join_');
                gridAjax('set_field_style_template/{{$module_id}}/'+field_name+'/bold');
            },
        });
        @foreach($condition_styles_templates as $template)
            condition_styles_submenu.push({
                name: '{{$template}}',
                action: function () {
                    var field_name = params.column.colDef.db_field;
                    field_name.replace('join_');
                    gridAjax('set_field_style_template/{{$module_id}}/'+field_name+'/{{$template}}');
                },
            });
        @endforeach
        
        
        menuItems.push({
            name: 'Conditional Styles',
            subMenu: condition_styles_submenu,
            action: function () {
                viewDialog('condition_styles',"{{ url($condition_styles_url.'?module_id='.$module_id) }}"+'&field='+params.column.colDef.field);
            },
        });
    @endif
   
    menuItems.push('separator');
    menuItems.push('pinSubMenu');
    return menuItems;
}

function  getContextMenuItems{{$grid_id}}(params) {
   
    var result = [];
    if(params && params.node && params.node.data){
        var selected = params.node.data;
    }else{
        var selected = null;
    }
    if(selected){
 
  
    
    
    @if($tree_data)
        result.unshift(
        {
        name: "Move to Root",
        action: function () {
        var updatedRows = [];
        moveToPath([], params.node, updatedRows);
       
        gridOptions.api.applyTransaction({
        update: updatedRows,
        });
        gridOptions.api.clearFocusedCell();
        
        
        // clear node to highlight
        setPotentialParentForNode(event.api, null);
        
        if(params.node.data.{{$db_key}}){
        var post_data =  {id: params.node.data.{{$db_key}}, value: 0, field: 'parent_id' };    
        $.ajax({ 
        url: "/{{$menu_route}}/save_cell", 
        type: 'post',
        data: post_data,
        beforeSend: function(){
        
        },
        success: function (result) { 
            window['grid_{{ $grid_id }}'].gridOptions.refresh();
        }, 
        });
        }
        }  
        }
        );
    @endif
    }
   
    @if($grid_menu_context)
    {!! $grid_menu_context !!}
    @endif
    
    
    /* ADD STATUS BUTTONS */
   /*
    @if(!empty($context_statuses) && is_array($context_statuses) && count($context_statuses) > 0)
        var status_context = [];
        @foreach($context_statuses as $context_status)
        var status_btn = {
            name: '{{$context_status}}',
            action: function(){
              var row_id = params.node.data.{{$db_key}};
              gridAjaxConfirm('set_status/{{ $module_id }}/'+row_id+'/{{$context_status}}', 'Set status to {{$context_status}}?');
            }
        };
        status_context.push(status_btn);
        @endforeach
        
        
        var status_btns = {
          name: 'Status',
          subMenu: status_context
        };
        result.push(status_btns);
    @endif
    */
    
    // grid extra buttons
    var functions_context = [];
 
   
    
    var clearfilter_btn = {
        name: 'Clear Filters',
        action: function(){
            
            if($("#grid_{{ $master_grid_id }}").hasClass('detailgrid-focus')){
                filtercleardetail{{ $master_grid_id }}();
            }
        }
    };
    functions_context.push(clearfilter_btn);
    /*
     var deselect_btn = {
        name: 'Deselect Rows',
        action: function(){
            window['selectedrow_{{ $grid_id }}'] = null;
            window['selectedrow_node_{{ $grid_id }}'] = null;
            onDetailRowDeselected{{$grid_id}}();
            detail_grid_api.api.deselectAll();
        }
    };
    functions_context.push(deselect_btn);
    */
    
    var layout_to_form_btn = {
        name: 'Set form tabs from layout',
        action: function(){
            $.get( "form_set_tabs_from_layout/"+window['layout_id{{ $master_grid_id }}']+"/{{$module_id}}/1", function( data ) {
            toastNotify('Form updated','success');
            });
        }
    };
    functions_context.push(layout_to_form_btn);
    
    if(show_deleted{{ $grid_id }} == 0){
        var show_deleted_btn = {
            name: 'Show Deleted',
            action: function(){
               showdeleted{{ $grid_id }}();
            }
        };
        functions_context.push(show_deleted_btn);
    }else{
        var hide_deleted_btn = {
            name: 'Hide Deleted',
            action: function(){
               showdeleted{{ $grid_id }}();;
            }
        };
        functions_context.push(hide_deleted_btn);
    }
   
    var copy_row_btn = {
        name: 'Copy Row',
        action: function(){
            detail_grid_api.api.copySelectedRowsToClipboard();
        }
    };
    var copy_rowheaders_btn = {
        name: 'Copy Row with Headers',
        action: function(){
            detail_grid_api.api.copySelectedRowsToClipboard({includeHeaders:true});
        }
    };
    functions_context.push('copy');
    functions_context.push(copy_row_btn);
    functions_context.push(copy_rowheaders_btn);
    @if($access['is_view'])
    var print_btn = {
        name: 'Print',
        action: function(){
            onBtnPrint();
        }
    };
    functions_context.push(print_btn);
    var export_btn = {
        name: 'Export',
        action: function(){
            
            if($("#grid_{{ $master_grid_id }}").hasClass('detailgrid-focus')){
                detail{{ $master_grid_id }}Export();
            }
        }
    };
    functions_context.push(export_btn);
    @endif
    
  
    @if($access['is_import'])
        
        var import_btn = {
            name: 'Import',
            action: function(){
                if($("#grid_{{ $master_grid_id }}").hasClass('detailgrid-focus')){
                    detail{{ $master_grid_id }}Import();
                }
            }
        };
        functions_context.push(import_btn);
    @endif
          
       
  
        
    @if(is_superadmin() && ($db_table == 'crm_accounts' || $db_table == 'sub_services'))
        
        var mdelete_btn = {
            name: 'Manager Delete',
            action: function(){
                if($("#grid_{{ $master_grid_id }}").hasClass('detailgrid-focus')){
                    detail{{ $master_grid_id }}ManagerDelete();
                }
            }
        };
        functions_context.push(mdelete_btn);    
    
    @endif
    /*
    var editmenu_btn = {
        name: 'Edit Grid Menu',
        action: function(){
            viewDialog('editrowcontext{{$grid_id}}','sf_menu_manager/{{$module_id}}/grid_menu');
        }
    };
    functions_context.push(editmenu_btn);
   */
   /*
    var copy_cell_btn = {
        name: 'Copy Cell',
        action: function(e){
            copyToClipboard(params.value);
        }
    };
    result.push(copy_cell_btn);
*/
    //result.push('copy');
    //result.push(copy_row_btn);
    result.push('separator');
      @if(is_dev())
    result.push('chartRange');
    result.push('separator');
    @endif
     $.each(functions_context, function(k,v){
   
        result.push(v);
        
    })
    /*
    var functions_context_btn = {
        name: 'Actions',
        subMenu: functions_context
    };
    result.push(functions_context_btn);
    */
    
   
    return result;
}

builderdiv{{ $grid_id }} = $("#builderdiv{{ $grid_id }}");
layoutsdiv{{ $grid_id }} = $("#layoutsdiv{{ $grid_id }}");
detail_grid_api = null;
window['grid_{{ $grid_id }}'] = null;


$("#{{ $grid_id }}Refresh").click(function() {
	window['grid_{{ $master_grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
	    var master_id = detailGridApi.id.replace('detail_','');
	    
        var post_data = { detail_value: master_rowid{{$master_grid_id}}, detail_field: '{{ $master_module_key }}' };
     
        $.ajax({ 
            url: "/{{ $detail_menu_route }}/aggrid_detail_data", 
            type: 'post',
            data: post_data,
            beforeSend: function(){
            },
            success: function (result) { 
                window['detail_row_data{{$master_grid_id}}'] = result;
                detailGridApi.api.setRowData(result);
               //return result;
            }, 
        });
	});
});

function refresh_detail_grid{{ $grid_id }}(){
    //////console.log('refresh_detail_grid');
	window['grid_{{ $master_grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
	    var master_id = detailGridApi.id.replace('detail_','');
	    
        var post_data = { detail_value: master_rowid{{$master_grid_id}}, detail_field: '{{ $master_module_key }}' };
     
        $.ajax({ 
            url: "/{{ $detail_menu_route }}/aggrid_detail_data", 
            type: 'post',
            data: post_data,
            beforeSend: function(){
                detailGridApi.api.showLoadingOverlay();
            },
            success: function (result) { 
            
                window['detail_row_data{{$master_grid_id}}'] = result;
                detailGridApi.api.setRowData(result);
                detailGridApi.api.hideOverlay();
               //return result;
            }, 
        });
	});
	//////console.log('refresh master rows');
	
    window['grid_{{ $master_grid_id }}'].gridOptions.api.forEachNodeAfterFilter((rowNode, index) => {
        //////console.log('node ');
        //////console.log(rowNode.data);
        
        refreshRowData{{ $master_grid_id }}(rowNode.data.rowId);
    });
	
}

filterclear{{ $grid_id }} = () => {  
    //////console.log('filterclear{{ $grid_id }}');
    //////console.log(filter_cleared{{ $grid_id }});
    if(filter_cleared{{ $grid_id }} == 0){
        // save temp state
        var temp_state = {};
        temp_state.colState = detail_grid_api.columnApi.getColumnState();
        temp_state.groupState = detail_grid_api.columnApi.getColumnGroupState();
       
        temp_state.filterState = detail_grid_api.api.getFilterModel();
        //temp_state.search = searchtext{{ $grid_id }}.value;
        //searchtext{{ $grid_id }}.value = '';
        window['detailgridstate_{{$grid_id}}'] = temp_state;
        
        detail_grid_api.api.setFilterModel(null);
     
      
        filter_cleared{{ $grid_id }} = 1;
     
       
    }else{
        // restore temp state
        if( window['detailgridstate_{{$grid_id}}']){
       
            if(window['detailgridstate_{{$grid_id}}'].colState){ 
                detail_grid_api.columnApi.applyColumnState({state:window['detailgridstate_{{$grid_id}}'].colState,applyOrder: true,});
            }
            if(window['detailgridstate_{{$grid_id}}'].groupState){
                detail_grid_api.columnApi.setColumnGroupState(window['detailgridstate_{{$grid_id}}'].groupState);
            }
          
            if(window['detailgridstate_{{$grid_id}}'].filterState){
                detail_grid_api.api.setFilterModel(window['detailgridstate_{{$grid_id}}'].filterState);
            }
            //if(window['detailgridstate_{{$grid_id}}'].search){
            //searchtext{{ $grid_id }}.value = window['detailgridstate_{{$grid_id}}'].search;
            //}
        }
      
        
        filter_cleared{{ $grid_id }} = 0;
    }
}
   
function detailGridReady(params){
    setTimeout(function(){detail_layout_load{{ $master_grid_id }}();},800);
    setTimeout(function(){
        if(searchtext{{ $master_grid_id }}.value > ''){
            searchgrid{{$master_grid_id}}();
        }
    },700);
    
    @if(session('role_level') == 'Admin')
        $(document).off('click', '#copyrow{{ $grid_id }}').on('click', '#copyrow{{ $grid_id }}', function() {  
            detail_grid_api.api.copySelectedRowsToClipboard();
        });
        
        showdeleted{{ $grid_id }} = () => {
            if(show_deleted{{ $grid_id }} == 0){
                $.get( "filter_soft_delete/{{$module_id}}/1", function( data ) {
                   refresh_detail_grid{{ $grid_id }}();
                });
                show_deleted{{ $grid_id }} = 1;
            } else {
                $.get( "filter_soft_delete/{{$module_id}}/0", function( data ) {
                   refresh_detail_grid{{ $grid_id }}();
                });
                show_deleted{{ $grid_id }} = 0;
            }
        }
    @endif
    
    filter_cleared{{ $grid_id }} = 0;
   
    row_data{{$grid_id}} = {!! json_encode($row_data) !!};
            
    detail_grid_api = params;
    window['grid_{{ $grid_id }}'] = params;
    window['selectedrow_{{ $grid_id }}'] = null;
    onDetailRowDeselected{{$grid_id}}();
    
    // swap toolbar
    
        /*
        $("#grid_menu_menu{{ $master_grid_id }}").addClass('d-none');
        $("#grid_menu_menu{{ $grid_id }}").removeClass('d-none');
        
        $("#status_buttons_menu{{ $master_grid_id }}").addClass('d-none');
        $("#status_buttons_menu{{ $grid_id }}").removeClass('d-none');
        $(".status_dropdown{{ $master_grid_id }}").addClass('d-none');
        $(".status_dropdown{{ $grid_id }}").removeClass('d-none');
        
        $("#grid_{{$master_grid_id}}").addClass('detailgrid-focus').removeClass('mastergrid-focus');
        window['grid_{{ $master_grid_id }}'].gridOptions.api.setSideBarVisible(false);
    */
    
    // right click grid header
    //showColumnMenuAfterMouseClick
    
  
    $(document).on("contextmenu", ".ag-details-grid .ag-header-cell", function (e) {
      
        e.preventDefault();
        var col_id = $(this).attr('col-id');
        detail_grid_api.api.showColumnMenuAfterMouseClick(col_id,e);
        return false;
    });

}

    

    

	
/** LAYOUTS **/
 

/** LAYOUT EVENTS **/    

	    

	
/** LAYOUT FUNCTIONS **/ 
    function detail_layout_load{{ $master_grid_id }}(){
        
       console.log('detail_layout_load');
        if(window['detail_col_defs{{$master_grid_id}}']){
            // set column defs for colmenu buttons
            if(detailGridOptions)
            detailGridOptions.columnDefs = window['detail_col_defs{{$master_grid_id}}'];
        }
       
              
        var state = JSON.parse(window['detail_settings{{$master_grid_id}}']);
       //////console.log(state);
        window['detail_grid_state'] = state;
        window['grid_{{ $master_grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
            if(state){
                if(state.colState){
                   detailGridApi.columnApi.applyColumnState({state:state.colState,applyOrder: true,});
                }
                if(state.groupState){
                   detailGridApi.columnApi.setColumnGroupState(state.groupState);
                }
             
                if(state.filterState){ 
                    //////console.log(state.filterState);
                   detailGridApi.api.setFilterModel(state.filterState);
                   
                }
            }
            
            detailGridApi.columnApi.autoSizeAllColumns();
            detailGridApi.api.redrawRows();
        
        });
    }
    
    
	
/** AGGRID COMPONENTS **/
// https://www.ag-grid.com/javascript-data-grid/component-tool-panel/



@foreach($detail_col_defs as $i => $detailcolDef)
    @if(!empty($detailcolDef['children']))
        @foreach($detailcolDef['children'] as $i => $col)
            @if(!empty($col['filter_options']))
            window["detail_field_values{{ $col['field'] }}{{ $module_id}}"] = {!! json_encode($col['filter_options']) !!};
            @endif
        @endforeach
    @else
        @if(!empty($detailcolDef['filter_options']))
        window["detail_field_values{{ $detailcolDef['field'] }}{{ $module_id}}"] = {!! json_encode($detailcolDef['filter_options']) !!};
        @endif
    @endif
@endforeach




var detailGridOptions = { 
        @if(!empty($detail_grid['serverside_model']))
        pagination: true, 
        paginationPageSize:5,
        @endif
       
        @if(!empty($is_row_master))
        isRowMaster: function (rowNode) {
           {!! $is_row_master !!}
        },
        @endif
        
        @if($detail_cell_renderer > '') 
        masterDetail: true,
        detailCellRenderer: DetailCellIFrameRenderer{{$grid_id}},
        @endif
       
        accentedSort: true,
        @if(!$serverside_model)
        statusBar: {
            statusPanels: [
                {
                    statusPanel: 'agTotalAndFilteredRowCountComponent',
                    align: 'left',
                },
                {
                    statusPanel: ClickableStatusBarComponent{{ $master_grid_id }},
                    align: 'right',
                },
               
            ]
        },
        @endif
        onRowGroupOpened: function(params){
            //////console.log('onRowGroupOpened');
            //////console.log(params);
           
            if(params.expanded){
            
                detail_grid_api.api.forEachNode(function (node) {
                    
              
                    if(node.expanded && node.id != params.node.id && node.groupData == null){
                       
                        node.setExpanded(false);
                    }
                });
                detailSortToggle{{ $master_grid_id }}('on');
            }else{
                detailSortToggle{{ $master_grid_id }}('off');
            }
          
        },
        tooltipShowDelay:1,
       // enableBrowserTooltips: true,
        suppressPropertyNamesCheck: true,
        suppressCopyRowsToClipboard:true,
        suppressMoveWhenRowDragging: true,
        suppressScrollOnNewData: true,
        debounceVerticalScrollbar: true,
        @if($serverside_model)
            pagination: true,
            paginationAutoPageSize:true,
            rowModelType: 'serverSide',
            serverSideInfiniteScroll: true,
            @if($pinned_totals)
            pinnedBottomRowData: [{}],
            @endif
        @else
            @if($allow_sorting && $has_sort)
            suppressRowDrag: (isMobile()) ? true : false,
            rowDragEntireRow: (isMobile()) ? false : true,
            rowDragManaged: false,
            suppressMoveWhenRowDragging: true,
            @endif
            @if($pinned_totals)
            pinnedBottomRowData: [{}],
            @endif
        @endif
        getMainMenuItems: getMainMenuItems{{$grid_id}},
        
        @if(is_superadmin() || is_manager())
        getContextMenuItems: getContextMenuItems{{$grid_id}},
        @endif
        rowHeight: 26,
        headerHeight: 30,
        @if(session('role_level') != 'Admin')
        suppressContextMenu:true,
        @endif
        @if($access['is_edit'])
        
        onRowDoubleClicked: function(args){
            
              
            if ($(args.event.target).hasClass('detail-expand-field') === true || $(args.event.target).closest('.ag-cell').hasClass('detail-expand-field') === true){
                args.node.setExpanded(true);
            }else{
               
                @if($has_cell_editing)
                if($(args.event.target).hasClass('ag-cell') && $(args.event.target).hasClass('grid-editable-cell') === false){
                    {{ $grid_id }}Edit();
                }else if(!$(args.event.target).hasClass('ag-cell') && $(args.event.target).closest('.ag-cell').hasClass('grid-editable-cell') === false){
                    {{ $grid_id }}Edit();
                }
                @else
                {{ $grid_id }}Edit();
                @endif
            }
            
          
        },
        
        @endif
        columnDefs: {!! json_encode($detail_col_defs) !!},
        columnTypes: {
        defaultField: {
          
            filter: 'agTextColumnFilter',
            filterParams: {
                suppressAndOrCondition: true
            },
           
            cellStyle : { 'text-overflow':'ellipsis','white-space':'nowrap', 'overflow': 'hidden' }
        },
        
        booleanField: {
            filter: 'agSetColumnFilter',
            filterParams: {
                suppressAndOrCondition: true,
                defaultToNothingSelected: true,
               
                values: ['0','1'],
                //suppressSelectAll: true,
                cellRenderer: booleanCellRenderer,
            },
            cellRenderer: booleanCellRenderer,
            
        },
        dateField: {
          
            filter: 'agDateColumnFilter',
            filterParams: {
              
                allowTextInput: true,
                
                includeBlanksInLessThan: true,
                comparator: function(filterLocalDateAtMidnight, cellValue) {
                    
               
                //using moment js
                var dateAsString = moment(cellValue).format('DD/MM/YYYY');
                var dateParts = dateAsString.split("/");
                var cellDate = new Date(Number(dateParts[2]), Number(dateParts[1]) - 1, Number(dateParts[0]));
                
                if (filterLocalDateAtMidnight.getTime() == cellDate.getTime()) {
                return 0
                }
                
                if (cellDate < filterLocalDateAtMidnight) {
                return -1;
                }
                
                if (cellDate > filterLocalDateAtMidnight) {
                return 1;
                }
                },
                browserDatePicker: false,
                minValidYear: 2000,
                filterOptions: [
                    'equals',
                    'notEqual',
                    'lessThan',
                    'greaterThan',
                    'blank',
                    'notBlank',
                    {
                        displayKey: 'currentDay',
                        displayName: 'Current Day',
                        predicate: ([filterValue], cellValue) => {
                            if(!cellValue){
                                return false;
                            }
                            var dateParts = cellValue.split(/[- :]/);
                           
                            if(dateParts[2] == cur_day && dateParts[1] == cur_month && dateParts[0] == cur_year){
                                return true;    
                            }else{
                                return false;    
                            }
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'notCurrentDay',
                        displayName: 'Not Current Day',
                        predicate: ([filterValue], cellValue) => {
                            if(!cellValue){
                                return false;
                            }
                            var dateParts = cellValue.split(/[- :]/);
                           
                            if(dateParts[2] == cur_day && dateParts[1] == cur_month && dateParts[0] == cur_year){
                                return false;    
                            }else{
                                return true;    
                            }
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'lessEqualToday',
                        displayName: 'Current Day and before',
                        predicate: ([filterValue], cellValue) => {
                            
                            if(!cellValue || cellValue == '' || cellValue == null){
                               return true;
                            }
                            var celldate = new Date(cellValue);
                            var dateParts = cellValue.split(/[- :]/);
                           
                            if((dateParts[2] == cur_day && dateParts[1] == cur_month && dateParts[0] == cur_year) || (celldate < date_today)){
                                return true;    
                            } else {
                                return false;    
                            }
                      
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'greaterEqualToday',
                        displayName: 'Current Day and after',
                        predicate: ([filterValue], cellValue) => {
                            
                           
                           var celldate = new Date(cellValue);
                         
                           var dateParts = cellValue.split(/[- :]/);
                           if((dateParts[2] == cur_day && dateParts[1] == cur_month && dateParts[0] == cur_year) || (celldate > date_today)){
                                return true;    
                            }else{
                                return false;    
                            }
                      
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'currentWeek',
                        displayName: 'Current Week',
                        predicate: ([filterValue], cellValue) => {
                            if(!cellValue){
                                return false;
                            }
                            
                            var dateWeek = moment(cellValue).isoWeek();
                            var dateParts = cellValue.split(/[- :]/);
                           
                            if(dateParts[0] == cur_year && dateWeek == cur_week){
                                return true;    
                            }else{
                                return false;    
                            }
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'currentMonth',
                        displayName: 'Current Month',
                        predicate: ([filterValue], cellValue) => {
                            
                            if(!cellValue){
                                return false;
                            }
                            try{
                            var dateParts = cellValue.split(/[- :]/);
                           
                            if(dateParts[1] == cur_month && dateParts[0] == cur_year){
                                return true;    
                            }else{
                                return false;    
                            }
                            }catch(e){
                                //////console.log('currentmonth compare error');
                                //////console.log(e);
                                //////console.log(cellValue);
                                
                                return false;
                            }
                     
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'currentYear',
                        displayName: 'Current Year',
                        predicate: ([filterValue], cellValue) => {
                            
                            if(!cellValue){
                                return false;
                            }
                            var dateParts = cellValue.split(/[- :]/);
                           
                            if(dateParts[0] == cur_year){
                                return true;    
                            }else{
                                return false;    
                            }
                     
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'currentMonthLastYear',
                        displayName: 'Current Month Last Year',
                        predicate: ([filterValue], cellValue) => {
                            
                            if(!cellValue){
                                return false;
                            }
                            try{
                            var dateParts = cellValue.split(/[- :]/);
                         
                            if(dateParts[1] == cur_month && dateParts[0] == last_year){
                                return true;    
                            }else{
                                return false;    
                            }
                            }catch(e){
                                //////console.log('currentmonth compare error');
                                //////console.log(e);
                                //////console.log(cellValue);
                                
                                return false;
                            }
                     
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'currentMonthLastThreeYears',
                        displayName: 'Current Month Last Three Years',
                        predicate: ([filterValue], cellValue) => {
                            
                            if(!cellValue){
                                return false;
                            }
                            try{
                            var dateParts = cellValue.split(/[- :]/);
                           
                            
                            
                            if(dateParts[1] == cur_month && ($.inArray(parseInt(dateParts[0]), last_3_years) !== -1)){
                                return true;    
                            }else{
                                return false;    
                            }
                            }catch(e){
                                //////console.log('currentmonth compare error');
                                //////console.log(e);
                                //////console.log(cellValue);
                                
                                return false;
                            }
                     
                        },
                        numberOfInputs: 0,
                    },
                    
                    {
                        displayKey: 'lessEqualNextMonth',
                        displayName: 'Next month and before',
                        predicate: ([filterValue], cellValue) => {
                            
                           
                           var celldate = new Date(cellValue);
                          
                            if(celldate <= nextmonthlastday){
                              
                                return true;    
                            }else{
                                
                            //////console.log(celldate);
                            //////console.log(nextmonthlastday);
                                
                                return false;    
                            }
                      
                        },
                        numberOfInputs: 0,
                    },
                    
                    {
                        displayKey: 'previousDay',
                        displayName: 'Last Day',
                        predicate: ([filterValue], cellValue) => {
                            
                            if(!cellValue){
                                return false;
                            }
                            var dateParts = cellValue.split(/[- :]/);
                           
                            if(dateParts[2] == yesterday_day && dateParts[1] == yesterday_month && dateParts[0] == yesterday_year){
                                return true;    
                            }else{
                                return false;    
                            }
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'previousWeekDay',
                        displayName: 'Last Week Day',
                        predicate: ([filterValue], cellValue) => {
                            
                            if(!cellValue){
                                return false;
                            }
                            var dateParts = cellValue.split(/[- :]/);
                            var lastweekday_month = date_last_week_day.getMonth() + 1;
                            var lastweekday_year = date_last_week_day.getFullYear();
                            var lastweekday_day = date_last_week_day.getDate();
                            if(dateParts[2] == lastweekday_day && dateParts[1] == lastweekday_month && dateParts[0] == lastweekday_year){
                                return true;    
                            }else{
                                return false;    
                            }
                        },
                        numberOfInputs: 0,
                    },
                    /*
                    {
                        displayKey: 'notBlankDate',
                        displayName: 'Not Blank',
                        predicate: ([filterValue], cellValue) => {
                            
                           
                            if(cellValue == null || cellValue == ''){
                                return false;    
                            }else{
                                return true;    
                            }
                     
                        },
                        numberOfInputs: 0,
                    },
                    */
                   
                    
                    {
                        displayKey: 'lastMonth',
                        displayName: 'Last Month',
                        predicate: ([filterValue], cellValue) => {
                            
                            if(!cellValue){
                                return false;
                            }
                            var dateParts = cellValue.split(/[- :]/);
                           
                            if(dateParts[1] == lastmonth_month && dateParts[0] == lastmonth_year){
                                return true;    
                            }else{
                                return false;    
                            }
                     
                        },
                        numberOfInputs: 0,
                    },
                    
                    {
                        displayKey: 'lastMonthLastThreeYears',
                        displayName: 'Last Month Last Three Years',
                        predicate: ([filterValue], cellValue) => {
                            
                            if(!cellValue){
                                return false;
                            }
                            try{
                            var dateParts = cellValue.split(/[- :]/);
                           
                            
                            
                            if(dateParts[1] == lastmonth_month && ($.inArray(parseInt(dateParts[0]), last_3_years) !== -1)){
                                return true;    
                            }else{
                                return false;    
                            }
                            }catch(e){
                                //////console.log('currentmonth compare error');
                                //////console.log(e);
                                //////console.log(cellValue);
                                
                                return false;
                            }
                     
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'lastThreeDays',
                        displayName: 'Last Three Days',
                        predicate: ([filterValue], cellValue) => {
                            
                           
                           var celldate = new Date(cellValue);
                            if(celldate >= date_3days){
                                return true;    
                            }else{
                                return false;    
                            }
                      
                        },
                        numberOfInputs: 0,
                    },
                    
                    {
                        displayKey: 'lastSevenDays',
                        displayName: 'Last Seven Days',
                        predicate: ([filterValue], cellValue) => {
                            
                           
                           var celldate = new Date(cellValue);
                            if(celldate >= date_7days){
                                return true;    
                            }else{
                                return false;    
                            }
                      
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'lastThirtyDays',
                        displayName: 'Last Thirty Days',
                        predicate: ([filterValue], cellValue) => {
                            
                            if(cellValue == null || cellValue == ''){
                                return true;    
                           }else{
                           var celldate = new Date(cellValue);
                            if(celldate >= date_30days){
                                return true;    
                            }else{
                                return false;    
                            }
                           }
                      
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'lastThreeMonths',
                        displayName: 'Last Three Months',
                        predicate: ([filterValue], cellValue) => {
                            
                            if(cellValue == null || cellValue == ''){
                                return true;    
                           }else{
                           var celldate = new Date(cellValue);
                            if(celldate >= date_3months){
                               
                                return true;    
                            }else{
                                return false;    
                            }
                           }
                      
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'lastSixMonths',
                        displayName: 'Last Six Months',
                        predicate: ([filterValue], cellValue) => {
                            
                            if(cellValue == null || cellValue == ''){
                                return true;    
                           }else{
                           var celldate = new Date(cellValue);
                            if(celldate >= date_6months){
                               
                                return true;    
                            }else{
                                return false;    
                            }
                           }
                      
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'lastTwelveMonths',
                        displayName: 'Last Twelve Months',
                        predicate: ([filterValue], cellValue) => {
                            
                            if(cellValue == null || cellValue == ''){
                                return true;    
                           }else{
                           var celldate = new Date(cellValue);
                            if(celldate >= date_12months){
                               
                                return true;    
                            }else{
                                return false;    
                            }
                           }
                      
                        },
                        numberOfInputs: 0,
                    },
                     {
                        displayKey: 'notCurrentMonth',
                        displayName: 'Not Current Month',
                        predicate: ([filterValue], cellValue) => {
                            
                            if(!cellValue){
                                return false;
                            }
                            var dateParts = cellValue.split(/[- :]/);
                            lastmonth_lastday_month = lastmonth_lastday.getMonth() + 1;
                            lastmonth_lastday_year = lastmonth_lastday.getFullYear();
                            lastmonth_lastday_day = lastmonth_lastday.getDate();
                            if(dateParts[1] == cur_month && dateParts[0] == cur_year){
                                return false;    
                            }else if(dateParts[2] == lastmonth_lastday_day && dateParts[1] == lastmonth_lastday_month && dateParts[0] == lastmonth_lastday_year){
                                return false;
                            }else{
                                return true;    
                            }
                     
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'notlastThreeDays',
                        displayName: 'Not Last Three Days',
                        predicate: ([filterValue], cellValue) => {
                            
                           if(cellValue == null || cellValue == ''){
                                return true;    
                           }else{
                           var celldate = new Date(cellValue);
                            if(celldate < date_3days){
                                return true;    
                            }else{
                                return false;    
                            }
                           }
                      
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'notlastSevenDays',
                        displayName: 'Not Last Seven Days',
                        predicate: ([filterValue], cellValue) => {
                             if(cellValue == null || cellValue == ''){
                                return true;    
                           }else{
                            var celldate = new Date(cellValue);
                            if(celldate < date_7days){
                                return true;    
                            }else{
                                return false;    
                            }
                           }
                      
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'notlastThirtyFiveDays',
                        displayName: 'Not Last Thirty Five Days',
                        predicate: ([filterValue], cellValue) => {
                            
                            if(cellValue == null || cellValue == ''){
                                return true;    
                           }else{
                           var celldate = new Date(cellValue);
                            if(celldate < date_35days){
                                return true;    
                            }else{
                                return false;    
                            }
                           }
                      
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'notlastThirtyDays',
                        displayName: 'Not Last Thirty Days',
                        predicate: ([filterValue], cellValue) => {
                            
                            if(cellValue == null || cellValue == ''){
                                return true;    
                           }else{
                           var celldate = new Date(cellValue);
                            if(celldate < date_30days){
                                return true;    
                            }else{
                                return false;    
                            }
                           }
                      
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'notlastSixtyDays',
                        displayName: 'Not Last Sixty Days',
                        predicate: ([filterValue], cellValue) => {
                            
                            if(cellValue == null || cellValue == ''){
                                return true;    
                           }else{
                           var celldate = new Date(cellValue);
                            if(celldate < date_60days){
                             
                                return true;    
                            }else{
                                return false;    
                            }
                           }
                      
                        },
                        numberOfInputs: 0,
                    },
                ],
            }
        },
    
        @if($serverside_model)
        checkboxField: {
           
            filter: 'agSetColumnFilter',
            filterParams: {
                values: params =>  {
                    params.success(params.colDef.filter_options);
                },
                buttons: ['reset']
            },
        },
        @else
        @foreach($detail_col_defs as $i => $detailcolDef)
            @if($detailcolDef['children'])
                @foreach($detailcolDef['children'] as $i => $col)
                    @if(!empty($col['filter_options']))
                    {{ $col['field'].$module_id }}Field: {
                        
                        filter: 'agSetColumnFilter',
                        filterParams: {
                            values: params =>  {
                                params.success(params.colDef.filter_options);
                            },
                            refreshValuesOnOpen: true,
                            defaultToNothingSelected: true,
                            suppressSelectAll: true,
                            buttons: ['reset']
                        },
                        @if($col['select_multiple'])
                        valueGetter: multiValueGetter,
                        @endif
                        comparator: function (valueA, valueB, nodeA, nodeB, isInverted) {
                       
                        var detail_field_values = window["detail_field_values{{ $col['field'] }}{{ $module_id}}"];
                       
                      
                        var key1 = detail_field_values.indexOf(valueA);
                        
                        var key2 = detail_field_values.indexOf(valueB);
                         
                       
                      
                        if (key1 === null && key2 === null) {
                      
                            return 0;
                        }
                        if (key1 === null) {
                       
                            return -1;
                        }
                        if (key2 === null) {
                       
                            return 1;
                        }
                        
                       
                       
                        return key1 - key2;
                    
                        },
                     
                        cellStyle : { 'text-overflow':'ellipsis','white-space':'nowrap', 'overflow': 'hidden' },
                    },
                    @endif  
                @endforeach
            @else
                @if(!empty($detailcolDef['filter_options']))
                {{ $detailcolDef['field'].$module_id }}Field: {
                    
                    
                    filter: 'agSetColumnFilter',
                    filterParams: {
                        values: params =>  {
                            params.success(params.colDef.filter_options);
                        },
                        refreshValuesOnOpen: true,
                        defaultToNothingSelected: true,
                        suppressSelectAll: true,
                        buttons: ['reset']
                    },
                    @if($detailcolDef['select_multiple'])
                    valueGetter: multiValueGetter,
                    @endif
                    comparator: function (valueA, valueB, nodeA, nodeB, isInverted) {
                   
                    var detail_field_values = window["detail_field_values{{ $detailcolDef['field'] }}{{ $module_id}}"];
                   
                  
                    var key1 = detail_field_values.indexOf(valueA);
                    
                    var key2 = detail_field_values.indexOf(valueB);
                    ////console.log(valueA);
                    ////console.log(valueB);
                    ////console.log(key1);
                    ////console.log(key2);
                    
                  
                    if (key1 === null && key2 === null) {
                        return 0;
                    }
                    if (key1 === null) {
                        return -1;
                    }
                    if (key2 === null) {
                        return 1;
                    }
                    
                   
                    return key1 - key2;
                
                    },
                   
                    cellStyle : { 'text-overflow':'ellipsis','white-space':'nowrap', 'overflow': 'hidden' },
                },
                @endif
            @endif
        @endforeach
        checkboxField: {
            filter: 'agSetColumnFilter',
            filterParams: {
            buttons: ['reset']
            }
        },
        
        @endif
        
        textareaField:{
            @if(is_dev())
            cellRenderer: function (params) {
                return params.value ? params.value : '';
            }
            @endif
        },
        intField: {
            filter: 'agNumberColumnFilter',
           
           
          
            headerClass: 'ag-right-aligned-header',
            cellClass: 'ag-right-aligned-cell',
            comparator: (valueA, valueB, nodeA, nodeB, isInverted) => valueA - valueB,
            filterParams: {
            filterOptions: [
                'equals',
                'notEqual',
                'lessThan',
                'lessThanOrEqual',
                'greaterThan',
                'greaterThanOrEqual',
                'inRange',
                {
                    displayKey: 'notInRange',
                    displayName: 'Not In Range',
                    predicate: ([fv1, fv2], cellValue) => cellValue == null || (fv1 > cellValue || fv2 < cellValue),
                    numberOfInputs: 2,
                },
                'blank',
                'notBlank',
            ]
            },
            valueFormatter: params => {
                if (params.value == null) {
                    return null; // or any other default value for null/undefined
                }
                return Math.floor(params.value); // Format as integer without decimals
            }
            
        },
        
        decimalField: {
            filter: 'agNumberColumnFilter',
           
            headerClass: 'ag-right-aligned-header',
            cellClass: 'ag-right-aligned-cell',
            comparator: (valueA, valueB, nodeA, nodeB, isInverted) => valueA - valueB,
            filterParams: {
                filterOptions: [
                    'equals',
                    'notEqual',
                    'lessThan',
                    'lessThanOrEqual',
                    'greaterThan',
                    'greaterThanOrEqual',
                    'inRange',
                    
                    {
                        displayKey: 'notInRange',
                        displayName: 'Not In Range',
                        predicate: ([fv1, fv2], cellValue) => cellValue == null || (fv1 > cellValue || fv2 < cellValue),
                        numberOfInputs: 2,
                    },
                    'blank',
                    'notBlank',
                ]
            },
            valueFormatter: function(params){
                return parseFloat(params.value).toFixed(2);
            },
        },
        idField: {
            filter: 'agNumberColumnFilter',
           
            headerClass: 'ag-right-aligned-header',
            cellClass: 'ag-right-aligned-cell',  
        },
        currencyField: {
            //cellRenderer: pinnedTotalRenderer{{$grid_id}},
            filter: 'agNumberColumnFilter',
            valueFormatter: function(params){
              //  if(!params.node.footer){
                   
                    var currency_decimals = params.colDef.currency_decimals;
                    var currency_symbol = params.colDef.currency_symbol;
                    var row_data_currency = params.colDef.row_data_currency;
                   
                    if(!row_data_currency && params.data && params.data['document_currency']){
                        row_data_currency = 'document_currency';
                    }
                    if(row_data_currency){
                        if(params.data[row_data_currency]){
                            if(params.data[row_data_currency].toLowerCase() == 'zar'){
                                var currency_decimals = 2;
                                var currency_symbol = 'R';
                            }
                          
                            if(params.data[row_data_currency].toLowerCase() == 'usd'){
                                var currency_decimals = 3;
                                var currency_symbol = '$';
                            }
                        }
                        
                       
                        if(row_data_currency.indexOf('master-') >= 0){
                            var mfield = row_data_currency.replace('master-','');
                            
                            if(window['mastergrid_row{{ $master_grid_id }}'][mfield].toLowerCase() == 'zar'){
                                var currency_decimals = 2;
                                var currency_symbol = 'R';
                            }
                         
                            if(window['mastergrid_row{{ $master_grid_id }}'][mfield].toLowerCase() == 'usd'){
                                var currency_decimals = 3;
                                var currency_symbol = '$';
                            }
                        }
                    }
                    
                    if(!currency_decimals){
                        currency_decimals = 2;
                    }
                    if(!params.value){
                        params.value = 0;
                    }
                    
                  
                    return currency_symbol + ' ' + parseFloat(params.value).toFixed(currency_decimals);
                  
                    //return parseFloat(params.value).toFixed(currency_decimals);
                   
              //  }
            },
            headerClass: 'ag-right-aligned-header',
            cellClass: 'ag-right-aligned-cell',
            comparator: (valueA, valueB, nodeA, nodeB, isInverted) => valueA - valueB
        },
        sortField:{
            rowDrag: params => !params.node.group,
        },
        fileField: {
            filter: 'agTextColumnFilter',
            cellRenderer: function(params){
                if ($.isArray(params.value)){
                    var files = params.value;
                    var cell_value = '';
                    var url = "{{ uploads_url($module_id) }}";
                    @if($module_id == 365)
                    var url = "{{ attachments_url() }}";
                    @endif
                  
                    for(var key in files)
                    {
                        var filename = (files[key]['originalName'] > '') ? files[key]['originalName'] : files[key]['name'];
                        cell_value += '<a target="new" href="'+url+files[key]['name']+'"> <span class="fas fa-file"></span> </a> ';
                    }
                    return cell_value;
                }else if(params.value > ''){
                    var files = params.value.split(",");
                    var cell_value = '';
                    var url = "{{ uploads_url($module_id) }}";
                    @if($module_id == 365)
                    var url = "{{ attachments_url() }}";
                    @endif
                  
                    for(var key in files)
                    {
                        cell_value += '<a target="new" href="'+url+files[key]+'"> <span class="fas fa-file"></span> </a> ';
                    }
                    return cell_value;
                }else{
                    return params.value;
                }
            }
        },
        imageField: {
            cellRenderer: function(params){
                console.log('imageField',params);
                if(params.value > ''){
                    var files = params.value.split(",");
                    var cell_value = '';
                    var url = "{{ uploads_url($module_id) }}";
                   
                    if(files.length > 0  && files[0] > ''){
                        for(var key in files)
                        {
                            if(files[key] > '')
                            cell_value += '<img src="'+url+files[key]+'" class="gridimage" height="10px" style="margin-left:10px" /> ';
                        }
                    }
                    return cell_value;
                }else{
                    return params.value;
                }
            }
        },
        
        
    },
        defaultColDef: {
            getQuickFilterText: function(params) {
                return (!params.column.visible) ? '' : params.value; 
            },
            minWidth: 80,
            
            @if($module_id!=1932)
            maxWidth:300,
            @endif
            // allow every column to be aggregated
        
        @if($serverside_model)
        aggFunc: 'max',
        @else
        aggFunc: 'value',
        @endif  
        allowedAggFuncs: ['value', 'percentage', 'calc', 'sum', 'min', 'max', 'count', 'avg'],
        enableValue: true,
        enableRowGroup: true,
            // allow every column to be pivoted
            enablePivot: false,
            sortable: true,
            filter: true,
            filterParams: {
                suppressAndOrCondition: true,
                newRowsAction: 'keep',
                buttons: ['reset']
            },
            //menuTabs: ['filterMenuTab','generalMenuTab','columnsMenuTab'],
            @if(session('role_level') == 'Admin')
            menuTabs: ['filterMenuTab','columnsMenuTab','generalMenuTab'],
            @else
            menuTabs: [],
            @endif
       
        }, 
        
    aggFuncs: {
        // this overrides the grids built-in sum function
        
        calc: params => {
             
            let val = '';
            let calc_data = {data: {}, node:{rowPinned: false}};
            if(params.values.length > 1){
            calc_data.data = params.rowNode.aggData;
            return params.colDef.valueGetter(calc_data);
            }else{
            params.values.forEach(value => val = value);
            return val;
            }
        
        },
        value: params => {
            //if(params.rowNode.level === 0){
            //    return '';
            //}
           
           // if(params && params.rowNode && params.rowNode.group === true){
               // return '';
            //}
            let val = '';
           // if(params.values.length === 1){
            params.values.forEach(value => val = value);
           // }
            return val;
        },
        percentage: params => {
           
            let val = '';
            params.values.forEach(value => val = value);
            return val;
        }
    },
        @if(!empty($rowClassRules))
        rowClassRules: {!! json_encode($rowClassRules) !!},
        @endif
        icons: {
        layouts_icon: '<i class="far fa-bookmark"/>',
        forms_icon: '<i class="far fa-bookmark"/>',
        communications_icon: '<i class="far fa-envelope"/>',
        pivotmode_icon: '<i class="fa fa-toggle-on "/>',
        builder_icon: '<i class="far fa-caret-square-right"/>',
        },
        
    
     
        rowSelection: 'single',
   
        onGridReady: detailGridReady,
        
        components: {
            
            booleanCellRenderer: booleanCellRenderer,
            @if($has_cell_editing)
            SyncFusionCellEditor{{ $grid_id }}:SyncFusionCellEditor{{ $grid_id }},
            @endif
            
            @if(!empty($detail_grid['row_tooltips']))
            rowtooltip{{$module_id}}: rowtooltip{{$$module_id}},
            @endif
        },    
        onFirstDataRendered: function(){
           
        
      
        },
        onFilterChanged: function(){
            
      
        },
        onModelUpdated: function(args){
        //////console.log('detail onModelUpdated',args);
            window['grid_{{ $master_grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
                
                detail_grid_api = detailGridApi;
                    window['grid_{{ $grid_id }}'] = detailGridApi;
                
            });
            @if($pinned_totals)
            if(detail_grid_api){
                let pinnedBottomData = generatePinnedBottomData{{ $grid_id }}();
                detail_grid_api.api.setPinnedBottomRowData([pinnedBottomData]);
            }
            @endif
        },
        onRowSelected: function(event){
           
    /*
            if(!event.node.isSelected()){
                var deselected = event.node.data;
                if(deselected.{{$db_key}} == window['selectedrow_{{ $grid_id }}'].rowId){
                  
                    window['selectedrow_{{ $grid_id }}'] = null;
                    
                    window['selectedrow_node_{{ $grid_id }}'] = null;
                   
                    onDetailRowDeselected{{$grid_id}}();
                }
            }
      */
            if(event.node.isSelected() && event.node.group == false){
         
                // set selected for button events
                window['selectedrow_{{ $grid_id }}'] = event.node.data;
                window['selectedrow_{{ $grid_id }}'].rowId = window['selectedrow_{{ $grid_id }}'].{{$db_key}};
                window['selectedrow_node_{{ $grid_id }}'] = event.node;
              
                
                // deselect all rows
                window['grid_{{ $master_grid_id }}'].gridOptions.api.deselectAll();
                window['grid_{{ $master_grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
                 
                   
                        detail_grid_api = detailGridApi;
                    window['grid_{{ $grid_id }}'] = detailGridApi;
                });
                
                // swap sidebar
                // swap toolbar
                
             
                $("#grid_menu_menu{{ $master_grid_id }}").addClass('d-none');
                $("#adminbtns_menu{{ $master_grid_id }}").removeClass('d-none');
                $("#grid_menu_menu{{ $grid_id }}").removeClass('d-none');
                
                $("#status_buttons_menu{{ $master_grid_id }}").addClass('d-none');
                $("#status_buttons_menu{{ $grid_id }}").removeClass('d-none');
                
                $(".status_dropdown{{ $master_grid_id }}").addClass('d-none');
                $(".status_dropdown{{ $grid_id }}").removeClass('d-none');
                
                $("#grid_{{$master_grid_id}}").addClass('detailgrid-focus').removeClass('mastergrid-focus');
                window['grid_{{ $master_grid_id }}'].gridOptions.api.setSideBarVisible(false);
                
               
                
                // set buttons
                onDetailRowSelected();
                
            }
            
         
           
            
        },
        onViewportChanged: function(){
           
            this.columnApi.autoSizeAllColumns();
            window['grid_{{ $master_grid_id }}'].gridOptions.columnApi.autoSizeAllColumns();
            
        },
        onRowDataUpdated:  function(args){
            //////console.log('detail rowDataUpdated',args);
            
            @if($module_id == 1923)
               setTimeout(function(){
                   
                //var selected_nodes = detail_grid_api.api.getSelectedNodes();
               
                //if(selected_nodes.length == 0){
                    //var firstNode =detail_grid_api.api.getDisplayedRowAtIndex(0);
                    //////console.log(firstNode);
                    //if(firstNode){
                    //firstNode.setSelected(true, true);
                    //}
                   
               // }
               },500) 
                
            @endif
        },
        onRowDataChanged: function(args){
            
           
            this.columnApi.autoSizeAllColumns();
        },
        
        @if($allow_sorting && $has_sort)
            onRowDragEnd: onDetailRowDragEnd{{$grid_id}},
            onRowDragMove: onDetailRowDragMove{{$grid_id}},
        @endif
    
        getRowId: getDetailRowNodeId{{$grid_id}},
     
        multiSortKey: 'ctrl',
    };


    @if($pinned_totals)
    function generatePinnedBottomData{{ $grid_id }}(){
     
        // generate a row-data with null values
        let result = {};
       
        detail_grid_api.columnApi.getAllGridColumns().forEach(item => {
            result[item.colId] = null;
        });
       
        return calculatePinnedBottomData{{ $grid_id }}(result);
    }
    
    function calculatePinnedBottomData{{ $grid_id }}(target){
        
        
        //list of columns for aggregation
        let columnsWithAggregation = {!! json_encode($pinned_total_cols) !!}
       
       
        
        columnsWithAggregation.forEach(element => {
           
            detail_grid_api.api.forEachNodeAfterFilter((rowNode) => {
               
                if (rowNode && rowNode.data && rowNode.data[element])
                    target[element] += Number(parseFloat(rowNode.data[element]).toFixed(2));
            });
           
            if (target[element])
                target[element] = `${target[element].toFixed(2)}`;
        })
     
        return target;
    }
    @endif
    
   @if(session('role_level') == 'Admin')
        function onDetailRowDragEnd{{$grid_id}} (e) {
           
            if(e.node && e.node.group == false && e.node.data && e.node.data.id && overData){
                var start_id = e.node.data.id;
                var target_id = overData.id;
                var sort_data = JSON.stringify({"start_id" : start_id, "target_id" : target_id});
                if($("#tabs_container").length > 0){
                    var spinner_ref = "#"+$("#detailcontainer{{ $grid_id }}").closest(".gridtabid").attr('id');
                }else{
                    spinner_ref = false;    
                }
             
                $.ajax({ 
                    type: "POST",
                    url: "/{{$menu_route}}/sort", 
                    datatype: "json", 
                    contentType: "application/json; charset=utf-8", 
                    data: sort_data, 
                    beforeSend: function(){
                         
                    },
                    success: function (result) { 
                        
                        
                    
                      refresh_detail_grid{{ $grid_id }}();
        	          // window['grid_{{$master_grid_id}}'].gridOptions.refresh();
                     //  window['grid_{{$master_grid_id}}'].gridOptions.api.getDetailRowData();
                    }, 
                    error: function(){
                        
                    }
                });
            }
        }

        function onDetailRowDragMove{{$grid_id}}(event) {
           
            //var immutableStore = window['detail_row_data{{$master_grid_id}}'];
            var allRowData = [];
           
            detail_grid_api.api.forEachNode(function(node) {
            allRowData.push(node.data);
            });
            var immutableStore = allRowData;
         
            var movingNode = event.node;
            var overNode = event.overNode;
            
            var rowNeedsToMove = movingNode !== overNode;
            
            
            if (rowNeedsToMove) {
                // the list of rows we have is data, not row nodes, so extract the data
                var movingData = movingNode.data;
                overData = overNode.data;
                
                if(movingData.rowId != overData.rowId){
                    
                    
                    var fromIndex = findWithAttr(immutableStore,'id',movingData.id);
                    var toIndex = findWithAttr(immutableStore,'id',overData.id);
                    
                    
                    var newStore = immutableStore.slice();
                    detailMoveInArray{{$grid_id}}(newStore, fromIndex, toIndex);
                    
                    
                    immutableStore = newStore;
                    detail_grid_api.api.setRowData(newStore);
                    
                    detail_grid_api.api.clearFocusedCell();
                }
            }
        
            function detailMoveInArray{{$grid_id}}(arr, fromIndex, toIndex) {
              
                
                var to_sort = arr[toIndex].{{$sort_field}}
                arr[toIndex].{{$sort_field}} = arr[fromIndex].{{$sort_field}};
                arr[fromIndex].{{$sort_field}} = to_sort;
                var element = arr[fromIndex];
                arr.splice(fromIndex, 1);
                arr.splice(toIndex, 0, element);
            }
            
            function findWithAttr(array, attr, value) {
                for(var i = 0; i < array.length; i += 1) {
                    if(array[i][attr] === value) {
                        return i;
                    }
                }
                return -1;
            }
        }
      
    @endif
    
    function getDetailRowNodeId{{$grid_id}}(data) {
     
      return data.data.rowId;
    }


  

function onDetailRowSelected() {
    //////console.log('onDetailRowSelected');
    
    @if($tab_load || !request()->ajax())
   get_sidebar_row_info{{ $master_grid_id }}();
   @endif
    ///window['grid_{{ $master_grid_id }}'].gridOptions.api.closeToolPanel();
    detail_grid_api.api.closeToolPanel();

   
  
    
    @if(!empty($status_dropdown) && !empty($status_dropdown['status_key']))
   
    window['status_dropdown{{ $grid_id }}'].value = window['selectedrow_{{ $grid_id }}'].{{$status_dropdown['status_key']}};
  
    @endif
    
@if($check_doctype)
    doctypes = {!! json_encode($doctypes) !!};
@endif
var selected = window['selectedrow_{{ $grid_id }}'];
//////console.log(selected);
selected_doctype_el = null;


detail_row_selected{{$master_grid_id}} = true;


@if(!empty($grid_menu_menu) && count($grid_menu_menu) > 0) 
    grid_menu{{ $grid_id }}.refresh();
    @if(is_superadmin())
    grid_menu_context{{$module_id}}.refresh();
    @endif
@endif


@if(!empty($status_buttons_menu) && count($status_buttons_menu) > 0) 
    status_buttons{{ $grid_id }}.refresh();
    @if(is_superadmin())
    status_buttons_context{{$module_id}}.refresh();
    @endif
@endif

@if($check_doctype)

    $(doctypes).each(function(i,el){

        if((selected.doctype == el.doctype) || (el.doctype_label > '' && selected.doctype == el.doctype_label )){
            selected_doctype_el = el;
            if(el.doctype_label == ''){
                selected_doctype_el.doctype_label = selected_doctype_el.doctype;
            }
        }
    });
  
@endif
@if($access['is_edit'])

if(selected_doctype_el != null){
if(selected_doctype_el.editable == 1){
$('#{{ $master_grid_id }}Edit').removeAttr("disabled");
}else{
$('#{{ $master_grid_id }}Edit').attr("disabled","disabled");
}
}else{
$('#{{ $master_grid_id }}Edit').removeAttr("disabled");
}
@else
$('#{{ $master_grid_id }}Edit').attr("disabled","disabled");
@endif

@if($access['is_approve'])

if(selected_doctype_el != null){

if(selected_doctype_el.approve_manager == 1){
$('#{{ $grid_id }}Approve').removeAttr("disabled");
detail_toolbar_button_icon('{{ $grid_id }}Approve','approve_manager', 'Approve '+selected_doctype_el.doctype_label);
}else if(selected_doctype_el.approve == 1){
$('#{{ $grid_id }}Approve').removeAttr("disabled");
detail_toolbar_button_icon('{{ $grid_id }}Approve','approve', 'Approve '+selected_doctype_el.doctype_label);
}else{
$('#{{ $grid_id }}Approve').attr("disabled","disabled");
}
}else{
$('#{{ $grid_id }}Approve').attr("disabled","disabled");
}

@endif



@if($access['is_add'])
$('#{{ $master_grid_id }}Duplicate').removeAttr("disabled");
@endif

@if($access['is_delete'])

   @if($db_table == 'crm_accounts')
            if(selected.status  != 'Deleted'){
                if(selected.cancelled == "Yes"){
                    detail_toolbar_button_icon('{{ $master_grid_id }}Delete','restore', 'Undo Cancel');
                    $('#{{ $master_grid_id }}Delete').removeAttr("disabled");
                }else{
                    detail_toolbar_button_icon('{{ $master_grid_id }}Delete','cancel', 'Cancel Account');
                    $('#{{ $master_grid_id }}Delete').removeAttr("disabled");
                }
            }
        @if(is_superadmin())
            if(selected.status  == 'Deleted'){
            detail_toolbar_button_icon('{{ $master_grid_id }}Delete','restore', 'Restore Account');
            $('#{{ $master_grid_id }}Delete').removeAttr("disabled");
            }
        @endif
    @elseif($db_table == 'sub_services')
        if(selected.status  != 'Deleted'){
        
            if(selected.to_cancel == "Yes"){
                detail_toolbar_button_icon('{{ $master_grid_id }}Delete','restore', 'Undo Cancel');
                $('#{{ $master_grid_id }}Delete').removeAttr("disabled");
            }else{
                detail_toolbar_button_icon('{{ $master_grid_id }}Delete','cancel', 'Cancel Subscription');
                $('#{{ $master_grid_id }}Delete').removeAttr("disabled");
            }
        }
    @else
   
    if(selected_doctype_el != null){
       
        if(selected_doctype_el.deletable == 1){
            $('#{{ $master_grid_id }}Delete').removeAttr("disabled");
            detail_toolbar_button_icon('{{ $master_grid_id }}Delete','delete', 'Delete '+selected_doctype_el.doctype_label);
        }else if(selected_doctype_el.creditable == 1){
            $('#{{ $master_grid_id }}Delete').removeAttr("disabled");
            detail_toolbar_button_icon('{{ $master_grid_id }}Delete','reverse', 'Credit '+selected_doctype_el.doctype_label);
        }else{
            $('#{{ $master_grid_id }}Delete').attr("disabled","disabled");
        }
    
    }else{
        
  
        @if($db_table == 'crm_suppliers')
            if(selected.status == "Deleted"){
                detail_toolbar_button_icon('{{ $master_grid_id }}Delete','restore', 'Restore');
                $('#{{ $master_grid_id }}Delete').removeAttr("disabled");
            }else{
                detail_toolbar_button_icon('{{ $master_grid_id }}Delete','delete', 'Delete');
                $('#{{ $master_grid_id }}Delete').removeAttr("disabled");
            }
        @else
      
       
        if(selected && selected.is_deleted  == 1){
  
            detail_toolbar_button_icon('{{ $master_grid_id }}Delete','restore', 'Restore');
            $('#{{ $master_grid_id }}Delete').removeAttr("disabled");
        }else if(selected && selected.status == "Deleted"){
  
            detail_toolbar_button_icon('{{ $master_grid_id }}Delete','restore', 'Restore');
            $('#{{ $master_grid_id }}Delete').removeAttr("disabled");
        }else if(selected && selected.is_deleted == 0){
            detail_toolbar_button_icon('{{ $master_grid_id }}Delete','delete', 'Delete');
            $('#{{ $master_grid_id }}Delete').removeAttr("disabled");
        }else if(selected && selected.status && selected.status != "Deleted"){
            detail_toolbar_button_icon('{{ $master_grid_id }}Delete','delete', 'Delete');
            $('#{{ $master_grid_id }}Delete').removeAttr("disabled");
        }else if(selected && selected.status == undefined){
            detail_toolbar_button_icon('{{ $master_grid_id }}Delete','delete', 'Delete');
            $('#{{ $master_grid_id }}Delete').removeAttr("disabled");
        }else if(selected && selected.status == ""){
            detail_toolbar_button_icon('{{ $master_grid_id }}Delete','delete', 'Delete');
            $('#{{ $master_grid_id }}Delete').removeAttr("disabled");
        }
        @endif
    }
           
    @endif

@endif
@if(!empty($grid_menu_menu) && count($grid_menu_menu) > 0) 
{!! button_menu_selected($module_id, 'grid_menu', $grid_id, 'selected', false) !!}
@endif
@if(!empty($status_buttons_menu) && count($status_buttons_menu) > 0) 
{!! button_menu_selected($module_id, 'status_buttons', $grid_id, 'selected', false) !!}
@endif



}

function onDetailRowDeselected{{$grid_id}}(){
    @if(!empty($grid_menu_menu) && count($grid_menu_menu) > 0) 
    {!! button_menu_selected($module_id, 'grid_menu', $grid_id, 'deselected', false) !!}
    @endif
    @if(!empty($status_buttons_menu) && count($status_buttons_menu) > 0) 
    {!! button_menu_selected($module_id, 'status_buttons', $grid_id, 'deselected', false) !!}
    @endif
        
    @if(!empty($status_dropdown) && !empty($status_dropdown['status_key']))
    if(window['status_dropdown{{ $grid_id }}']){
    window['status_dropdown{{ $grid_id }}'].value = null;
    }
    @endif
       
        
}

function detail_toolbar_button_icon(id, icon, title = ''){
    ////console.log(id);
    ////console.log(icon);
    ////console.log(title);
    if(icon == 'delete'){
       $("#"+id).html('<span  class="e-btn-icon fa fa-trash"></span>'); 
       if(title == ''){
        title = 'Delete';
       }
    }
    if(icon == 'credit'){
       $("#"+id).html('<span  class="e-btn-icon fa fa-undo"></span>'); 
       if(title == ''){
        title = 'Delete';
       }
    }
    if(icon == 'catch'){
       $("#"+id).html('<span  class="e-btn-icon fa fa-window-close"></span>'); 
       if(title == ''){
        title = 'Cancel';
       }
    }
    
    if(icon == 'approve'){
       $("#"+id).html('<span  class="e-btn-icon fa fa-check"></span>'); 
       if(title == ''){
       title = 'Approve';
       }
    }
    
    if(icon == 'approve_manager'){
       $("#"+id).html('<span  class="e-btn-icon fa fa-check-double"></span>'); 
       if(title == ''){
       title = 'Manager Approve';
       }
    }
    
    if(icon == 'restore'){
       $("#"+id).html('<span  class="e-btn-icon fa fa-trash-restore"></span>'); 
       if(title == ''){
       title = 'Restore';
       }
    }
    
    if(icon == 'reverse'){
       $("#"+id).html('<span  class="e-btn-icon fa fa-history"></span>'); 
       if(title == ''){
       title = 'Reverse';
       }
    }
    
    $("#"+id).attr('title',title); 
}
    var dialogclass = '';
/** BUTTON EVENTS **/
    @if($access['is_import'])
        {{ $grid_id }}Import = () => {
         sidebarform('{{ $menu_route }}import' , '/{{ $menu_route }}/import');
        }
    @endif
    
    ////console.log("detail add1 {{ $access['is_add'] }}");
    @if($access['is_add'])
    ////console.log('detail add2 {{ $grid_id }}Add');
        {{ $grid_id }}Add = () => {
           
            @if($menu_route == 'pbx_menu')
                sidebarform('{{ $menu_route }}add' , 'pbx_menuedit', 'PBX menu - Add');
            @elseif(!empty(request()->account_id) && $documents_module)
                transactionDialog('{{ $menu_route }}add' , '/{{ $menu_route }}/edit?account_id={{request()->account_id}}', 'Documents - Add', '80%', 'auto');
            @elseif($documents_module)
            transactionDialog('{{ $menu_route }}add' , '/{{ $menu_route }}/edit/', 'Documents - Add', '80%', 'auto');
            @elseif(!empty($request_get))
                if(master_rowid{{$master_grid_id}}){
                    sidebarform('{{ $menu_route }}add' , '/{{ $menu_route }}/edit?{{ $master_module_key }}='+master_rowid{{$master_grid_id}}+'&layout_id='+window['layout_id{{ $master_grid_id }}']+'&detail_layout=1'  , '{!! $menu_name !!} - Add','', '60%');
                }else{
                    sidebarform('{{ $menu_route }}add' , '/{{ $menu_route }}/edit?{!! $request_get !!}'+'&layout_id='+window['layout_id{{ $master_grid_id }}']+'&detail_layout=1' , '{!! $menu_name !!} - Add','', '60%');
                }
            @elseif(!$documents_module)
                if(master_rowid{{$master_grid_id}}){
                    sidebarform('{{ $menu_route }}add' , '/{{ $menu_route }}/edit?{{ $master_module_key }}='+master_rowid{{$master_grid_id}}+'&layout_id='+window['layout_id{{ $master_grid_id }}']+'&detail_layout=1' , '{!! $menu_name !!} - Add','', '60%');
                }else{
                    sidebarform('{{ $menu_route }}add' , '/{{ $menu_route }}/edit'+'?layout_id='+window['layout_id{{ $master_grid_id }}']+'&detail_layout=1' , '{!! $menu_name !!} - Add','', '60%');
                }
            @endif
            
        }
    @endif
    
    
    @if($access['is_edit'])
    //console.log('edit access');
        {{ $grid_id }}Edit = () => {
           
            var selected = window['selectedrow_{{ $grid_id }}'];
         
            @if($documents_module)
                transactionDialog('{{ $menu_route }}edit', '/{{ $menu_route }}/edit/'+ selected.rowId, 'Documents - Edit', '80%', '100%');
            @else
         
                sidebarform('{{ $menu_route }}edit' , '/{{ $menu_route }}/edit/'+ selected.rowId+'?layout_id='+window['layout_id{{ $master_grid_id }}']+'&detail_layout=1', '{!! $menu_name !!} - Edit', '{!! $form_description !!}','60%');
            @endif
        }
    @else
    //console.log('no edit access');
    @endif
    

    @if($access['is_approve'])
    {{ $grid_id }}Approve = () => {
        var selected = window['selectedrow_{{ $grid_id }}'];
        var check_access = {{ (check_access('1,2,7')) ? 1: 0 }};
       
        @if($db_table == 'crm_documents')
            if(selected.doctype == 'Credit Note Draft'){
                gridAjaxConfirm('/{{ $menu_route }}/approve', 'Approve Credit Note?', {"id" : selected.rowId}, 'post');
            }else if((selected.doctype == 'Sales Order' || selected.doctype == 'Order') && check_access == 1){
                
                gridAjaxConfirm('/{{ $menu_route }}/approve', 'Approve Sales Order?', {"id" : selected.rowId}, 'post');
            }else if(selected.doctype == 'Quotation'){
                gridAjaxConfirm('/{{ $menu_route }}/approve', 'Approve Quotation?', {"id" : selected.rowId}, 'post');
            }
        @elseif($db_table == 'crm_supplier_documents')
            if(selected.doctype == 'Supplier Order'){
                gridAjaxConfirm('/{{ $menu_route }}/approve', 'Approve Supplier Order?', {"id" : selected.rowId}, 'post');
            }
        @endif
    }
    @endif
    
	         


    
     @if($access['is_view'] && (in_array($db_table,['crm_documents','crm_supplier_documents','crm_supplier_import_documents'])))
    
	  
        {{ $grid_id }}View = () => {
            var selected = window['selectedrow_{{ $grid_id }}'];
          
            viewDialog('{{ $menu_route }}'+selected.rowId, '/{{ $menu_route }}/view/'+ selected.rowId,'','70%');
          
        }
    @endif
    
    @if($access['is_add'])
        {{ $grid_id }}Duplicate = () => {
            var selected = window['selectedrow_{{ $grid_id }}'];
            gridAjaxConfirm('/{{ $menu_route }}/duplicate', 'Duplicate record?', {"id" : selected.rowId}, 'post');
        }
    @endif
    
   
    @if($access['is_delete'])
        
        {{ $grid_id }}ManagerDelete = () => {
        
            var selected = window['selectedrow_{{ $grid_id }}'];
            if(selected && selected.status  != 'Deleted'){
                gridAjaxConfirm('/{{ $menu_route }}/manager_delete', 'Delete Record?', {"id" : selected.rowId}, 'post');
            }
        }
        
        {{ $grid_id }}Delete = () => {
            var selected = window['selectedrow_{{ $grid_id }}'];
            
            @if($db_table == 'crm_accounts')
                if(selected.status  != 'Deleted'){
                    if(selected.cancelled == "Yes"){
                        gridAjaxConfirm('/restore_account/'+selected.rowId, 'Undo Cancel?', {"id" : selected.rowId}, 'post');
                    }else{
                        gridAjaxConfirm('/{{ $menu_route }}/cancel', 'Cancel Account?', {"id" : selected.rowId}, 'post');
                    }
                }
                @if(check_access('1,31,34'))
                    if(selected.status  == 'Deleted'){
                        detail_toolbar_button_icon('{{ $master_grid_id }}Delete','restore', 'Restore Account');
                        gridAjaxConfirm('/restore_account/'+selected.rowId, 'Restore Account?', {"id" : selected.rowId}, 'post');
        		        $('#{{ $master_grid_id }}Delete').removeAttr("disabled");
                    }
                @endif
		    @elseif($db_table == 'sub_services')
                if(selected.status  != 'Deleted'){
                    if(selected.to_cancel == "Yes"){
                        gridAjaxConfirm('/restore_subscription/'+selected.rowId, 'Undo Cancel?', {"id" : selected.rowId}, 'post');
                    }else{
                        gridAjaxConfirm('/{{ $menu_route }}/cancel', 'Cancel Subscription?', {"id" : selected.rowId}, 'post');
                    }
                }
		    @else
                @if($db_table == 'crm_documents')
                    if(selected.doctype == 'Tax Invoice' || selected.doctype == 'Invoice'){
                        gridAjaxConfirm('/{{ $menu_route }}/delete', 'Create Credit Note?', {"id" : selected.rowId}, 'post');
                    }else if((selected.doctype == 'Sales Order' || selected.doctype == 'Order')){
                        gridAjaxConfirm('/{{ $menu_route }}/delete', 'Reverse to Quotation?', {"id" : selected.rowId}, 'post');
                    }else if(selected.doctype == 'Quotation'){
                        gridAjaxConfirm('/{{ $menu_route }}/delete', 'Delete Quotation?', {"id" : selected.rowId}, 'post');
                    }else if(selected.doctype == 'Credit Note Draft'){
                        gridAjaxConfirm('/{{ $menu_route }}/delete', 'Delete Credit Note Draft?', {"id" : selected.rowId}, 'post');
                    }else{
                        gridAjaxConfirm('/{{ $menu_route }}/delete', 'Delete document?', {"id" : selected.rowId}, 'post');
                    }
                @elseif($db_table == 'crm_supplier_documents')
                    if(selected.doctype == 'Supplier Order'){
                        gridAjaxConfirm('/{{ $menu_route }}/delete', 'Delete Supplier Order?', {"id" : selected.rowId}, 'post');
                    }else{
                        gridAjaxConfirm('/{{ $menu_route }}/delete', 'Delete document?', {"id" : selected.rowId}, 'post');
                    }
                @elseif(check_access('1,2,7') && ($db_table == 'crm_accounts' || $db_table == 'crm_suppliers'))
                    if(selected.status == "Deleted"){
                        @if($db_table == 'crm_accounts')
                            gridAjaxConfirm('/restore_account/'+selected.rowId, 'Restore Account?', {"id" : selected.rowId}, 'post');
                        @endif
                        @if($db_table == 'crm_suppliers')
                            gridAjaxConfirm('/restore_supplier/'+selected.rowId, 'Restore Supplier?', {"id" : selected.rowId}, 'post');
                        @endif
                    }else{
                        detail_toolbar_button_icon('{{ $master_grid_id }}Delete','delete', 'Delete');
                        gridAjaxConfirm('/{{ $menu_route }}/delete', 'Delete record?', {"id" : selected.rowId}, 'post');
                    }
                @elseif($db_table == 'sub_services')
                    gridAjax('/{{ $menu_route }}/delete',, {"id" : selected.rowId}, 'post');
                @else
                  
                    if(selected && selected.status == "Deleted"){
                        gridAjaxConfirm('/{{ $menu_route }}/restore', 'Restore record?', {"id" : selected.rowId}, 'post');
                    }else if(selected && selected.is_deleted == 1){
                        gridAjaxConfirm('/{{ $menu_route }}/restore', 'Restore record?', {"id" : selected.rowId}, 'post');
                    }else{
                        
                        deleteConfirm{{$grid_id}}('/{{ $menu_route }}/delete', 'Delete record?', {"id" : selected.rowId}, 'post');
                      
                        //gridAjaxConfirm('/{{ $menu_route }}/delete', 'Delete record?', {"id" : selected.rowId}, 'post');
                      
                    }
                @endif
            @endif
        }
    @endif


    
    @if($access['is_view'])
        {{ $grid_id }}Export = () => {
            detail_grid_api.api.exportDataAsExcel({fileName: '{{$master_grid_title}}.xlsx'});
        }
    @endif   
    
    
        detailSortToggle{{ $master_grid_id }} = (switch_type = '') => {
            
            if(switch_type == ''){
                //////console.log(detail_grid_api);
                if(!isMobile()){
                return false;    
                }
                if(detail_grid_api.api.gridOptionsWrapper.gridOptions.suppressRowDrag){ 
                
                    detail_grid_api.api.gridOptionsWrapper.gridOptions.suppressRowDrag = false;
                    detail_grid_api.api.gridOptionsWrapper.gridOptions.rowDragEntireRow = true;
                    //////console.log('Detail sort enabled');
                }else{
                
                    detail_grid_api.api.gridOptionsWrapper.gridOptions.suppressRowDrag = true;
                    detail_grid_api.api.gridOptionsWrapper.gridOptions.rowDragEntireRow = false;
                    //////console.log('Detail sort disabled');
                
                }
            }
            if(switch_type == 'off'){

                detail_grid_api.api.gridOptionsWrapper.gridOptions.suppressContextMenu = false;
                detail_grid_api.api.gridOptionsWrapper.gridOptions.suppressRowDrag = false;
                detail_grid_api.api.gridOptionsWrapper.gridOptions.rowDragEntireRow = true;
                
                window['grid_{{$master_grid_id}}'].gridOptions.suppressRowDrag = false;
                window['grid_{{$master_grid_id}}'].gridOptions.rowDragEntireRow = true;
         
                //////console.log('Detail sort enabled');
                
            }
            if(switch_type == 'on'){
               
                detail_grid_api.api.gridOptionsWrapper.gridOptions.suppressContextMenu = true;
                detail_grid_api.api.gridOptionsWrapper.gridOptions.suppressRowDrag = true;
                detail_grid_api.api.gridOptionsWrapper.gridOptions.rowDragEntireRow = false;
                
                window['grid_{{$master_grid_id}}'].gridOptions.suppressRowDrag = true;
                window['grid_{{$master_grid_id}}'].gridOptions.rowDragEntireRow = false;
              
                //////console.log('Detail sort disabled');
            }
             
            detail_grid_api.api.redrawRows();
            // window['grid_{{$master_grid_id}}'].gridOptions.api.redrawRows();
           
             
        }
    
    

function deleteConfirm{{$grid_id}}(url, confirm_text, data = null, type = 'get') {
    
    
    var confirmation = confirm(confirm_text);
    if (confirmation) {
        var delete_rows = [];
        delete_rows.push(window['selectedrow_{{ $grid_id }}']);
        var grid_id = false;
        try{
        var element = $(".gridtabid:visible:first");
        var grid_id = $(element).attr('id');
        }catch(e){}
    
        $.ajax({
            url: url,
            data: data,
            type: type,
            beforeSend: function(e) {
                
                detail_grid_api.api.applyTransaction({remove: delete_rows});
                
               
            },
            success: function(data) {
                
              
                toastNotify(data.message, data.status);
                if(data.status!='success'){
                    
                    detail_grid_api.api.applyTransaction({add: delete_rows});    
                }
                
            },
            error: function(jqXHR, textStatus, errorThrown) {
               
                detail_grid_api.api.applyTransaction({add: delete_rows}); 
                toastNotify('Error deleting record', 'error');
            },
        });
    }
}
    
    
</script>


@endpush



@push('page-styles')

<style>

@if(!empty($form_description))
#detailheadertoolbar{{ $grid_id }} {
    height:50px !important;
}
@endif
.ag-header-cell-menu-button{
display: none !important;
}
#toolbar_template_layouts_right{{ $grid_id }}{
height:100%;
display:table;
margin: 0 auto;
min-width:145px;
}
.ag-theme-alpine .ag-layout-auto-height .ag-center-cols-clipper{
min-height:0px;    
}
.ag-column-panel{

min-height:250px;    
}
#extrabtnsdropdown{{ $grid_id }}{
margin-top: 0 !important;
border: none !important;
background: none !important;
padding: 0px !important;
}
#extrabtnsdropdown{{ $grid_id }} button{
width:100%;   
justify-content: left;
border-radius:0px !important;
}
/*
#detailheadertoolbar{{ $grid_id }}, #detailheadertoolbar{{ $grid_id }} .e-toolbar-items {
    background: rgb(25 69 126 / 60%) !important;
}
*/
#gridheadertoolbar{{ $grid_id }}, #gridheadertoolbar{{ $grid_id }} .e-toolbar-items{
    background-color: #d4d4d4;
}
.e-menu-item.k-button.layout_active{
	background-color:#fff !important;
	font-weight: 600;
}
#extrabtnsdropdown{{ $grid_id }} button span{
margin-right: 5px;
}
#detailheadertoolbar{{ $grid_id }}{
    border-bottom: 1px solid #babfc7 !important;
}
.searchinputgroup .e-clear-icon-hide{
display:flex !important;    
}
</style>
@endpush