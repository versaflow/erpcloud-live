@php


if(empty(request()->all()) ||count(request()->all()) == 0){
    $request_get = '';
}else{
    $request_get = http_build_query(request()->all());
}
@endphp

@section('detail_content')

<div id="detailcontainer{{ $grid_id }}" >
<div class="gridheader">
<div id="detailheadertoolbar{{ $grid_id }}" class="grid-toolbar"  style="display:none" ></div>
<div id="detailtoolbar{{ $grid_id }}" class="grid-toolbar" style="display:none"></div>
</div>

<div id="toolbar_template_layouts_right{{ $grid_id }}" class="d-flex align-items-center pl-2 ml-0">

</div>

<div id="toolbar_template_layouts{{ $grid_id }}" class="pl-3">
<div class="col p-0 flex-row flex-nowrap">
<ul class="k-widget k-button-group" id="detailgridlayouts_{{ $grid_id }}"></ul>
</div>
</div>

<div id="toolbar_template_leftbuttons{{ $grid_id }}" class="pl-3">
   
 
      <!--<div class="k-button-group searchinputgroup space-right" style="height:26px;">
        <input  type="text"  id="searchtext{{ $grid_id }}" class="gridsearch k-widget k-textbox"/>
        <button class="k-button" id="search{{ $grid_id }}" style="height:26px" title="Search"><i class="search-icon fas fa-search" ></i></button>
        </div>-->
      <div class="k-widget k-button-group" style="margin-right:11px;">
       
      
          <button title="Refresh Data" id="{{ $grid_id }}Refresh" class="k-button {{ $grid_id }}Refresh"><span  class="e-btn-icon fa fa-sync-alt"></span></button>
        @if($access['is_add'])
            <button title="Create Record" id="{{ $grid_id }}Add" class="k-button" ><span  class="e-btn-icon fa fa-plus"></span></button>
        @endif
        
        @if($access['is_view'] && (in_array($db_table,['crm_documents','crm_supplier_documents','crm_supplier_import_documents'])))
            <button title="View Record" id="{{ $grid_id }}View" class="k-button" ><span  class="e-btn-icon far fa-eye"></span></button>
        @endif
        
    
        @if($access['is_edit'])
            <button title="Edit Record" id="{{ $grid_id }}Edit" class="k-button" ><span  class="e-btn-icon fas fa-pen"></span></button>
        @endif
          
      
        
        @if($access['is_add'])
            <button title="Duplicate Record" id="{{ $grid_id }}Duplicate" class="k-button" ><span  class="e-btn-icon fa fa-copy"></span></button>
        @endif
        
        @if($access['is_delete'])
            <button title="Delete Record" id="{{ $grid_id }}Delete" class="k-button" ><span  class="e-btn-icon fa fa-trash"></span></button>
        @endif
        
      
        
               
        <button class="dropdown k-button dropdown-toggle" type="button" id="extrabtns{{ $grid_id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
        <span class="e-btn-icon fa fa-caret-down"></span>
        </button>
     
        <ul class="dropdown-menu" aria-labelledby="extrabtns{{ $grid_id }}" id="extrabtnsdropdown{{ $grid_id }}">
            <li><button title="Clear All Filters" id="filterclear{{ $grid_id }}" class="k-button"><span  class="e-btn-icon e-icons e-filter-clear"></span> Clear Filters</button></li>
        @if($db_table == 'crm_documents' || $db_table == 'crm_supplier_documents')
            <li><button title="Approve" id="{{ $grid_id }}Approve" class="k-button" ><span  class="e-btn-icon fa fa-check"></span> Approve</button></li>
        @endif
        @if($access['is_view'])
            <li><button  title="Print"  onclick="onBtnPrint()"  class="k-button" ><span  class="e-btn-icon fas fa-print"></span> Print</button></li>
            <li><button title="Export Data" id="{{ $grid_id }}Export" class="k-button" ><span  class="e-btn-icon fas fa-file-import"></span> Export</button></li>
        @endif
        @if($access['is_export'])
            <!--<li><button title="Export Data" id="{{ $grid_id }}Export" class="k-button" ><span  class="e-btn-icon fas fa-file-import"></span></button></li>-->
        @endif
        @if($access['is_import'])
            <li><button title="Import Data" id="{{ $grid_id }}Import" class="k-button" ><span  class="e-btn-icon fas fa-file-export"></span> Import</button></li>
        @endif
        @if(session('role_id') == 1 || is_dev())
           <li><button title="Record Log" href="javascript:void(0)" id="{{ $grid_id }}module_log" class="k-button" ><span  class="e-btn-icon fa fa-history"></span>Record History</button></li>
        @endif
      
        </ul>
    
    </div>
</div>

<div id="toolbar_template_actionbuttons{{ $grid_id }}" class="toolbar_actionbuttons"> 
    @if(!empty($grid_menu_menu) && count($grid_menu_menu) > 0)  
    <div class=" flex-row flex-nowrap">
    <ul class="k-widget k-button-group" id="detail_grid_menu_menu{{ $grid_id }}"></ul>
    </div>
    @endif
</div>

@if(session('role_level') == 'Admin')
<div id="toolbar_template_rightbuttons{{ $grid_id }}">
    
</div>
@endif

</div>


@if(!empty($module_context_builder_menu) && count($module_context_builder_menu) > 0)
<ul id="detailcontext_builder{{ $grid_id }}" class="m-0"></ul>
@endif

@if(is_superadmin())
<ul id="detailcontextlayouts{{ $grid_id }}" class="m-0"></ul>
@endif

@endsection


@section('script-bottom')
<script>


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
        beforeOpen: function(args){
            ////console.log(args);
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
    },'#detail_grid_menu_menu{{ $grid_id }}');
    @endif


detail_init_load = true;
detail_filter_cleared{{ $grid_id }} = 0;
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
           
        var data = {layout : layout, master_layout_id: window['layout_id{{ $master_grid_id }}'], layout_id: window['layout_id{{ $grid_id }}'], pivot: pivot, pivot_mode: pivot_mode,  query_string: {!! $query_string !!}};
       
        $.ajax({
            type: 'post',
            url: '{{ url($menu_route."/aggrid_layout_save") }}',
            data: data,
    		success: function(data) { 
    		 
    		    window['layout_id{{ $grid_id }}'] = data.layout_id;
    		    toastNotify('Detail Layout saved.','success');
    		}
    	});
    }
    
 function detail_layout_load(layout_id){
    
    	var ajax_data = {aggrid: 1, layout_id: layout_id, grid_reference: 'grid_{{ $grid_id }}', query_string: {!! $query_string !!} };
      
        $.ajax({
            type: 'post',
            url: '{{ url($menu_route."/aggrid_layout_data") }}',
            data: ajax_data,
            beforeSend: function(){
                $('#layoutsbtn_delete{{$grid_id}}').attr('disabled','disabled');
                $('#layoutsbtn_save{{$grid_id}}').attr('disabled','disabled');
            },
    		success: function(data) { 
    		 
   
                @if(session('role_level') == 'Admin')
                
                window['detailgridlayouts_{{ $grid_id }}'].items = JSON.parse(data.menu);
                window['detailgridlayouts_{{ $grid_id }}'].dataBind();
                @endif
                var state = JSON.parse(data.settings);
                
    		   //console.log(state);
                if(data.columnDefs){
                  
                    // set column defs for colmenu buttons
                    detailGridOptions.columnDefs = data.columnDefs;
                }
                
                window['grid_{{ $master_grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
                  
                    
                    if(data.pivot_mode == 1){
                       detailGridApi.columnApi.setPivotMode(true);
                       
                    }else{
                       detailGridApi.columnApi.setPivotMode(false);
                    }
                    
                       
                    if(data.columnDefs){
                       
                       detailGridApi.api.setColumnDefs(data.columnDefs);
                    }
                    
                    ////console.log('load1');
                    ////console.log(state);
                    
                    if(state){
                    if(state.colState){
                       detailGridApi.columnApi.applyColumnState({state:state.colState,applyOrder: true,});
                    }
                    if(state.groupState){
                       detailGridApi.columnApi.setColumnGroupState(state.groupState);
                    }
                  
                    if(state.filterState){
                       detailGridApi.api.setFilterModel(state.filterState);
                    }
                    }
                    
                   detailGridApi.columnApi.autoSizeAllColumns();
                   
                    
                    ////console.log(detailGridApi);
                
                });
                window['layout_id{{ $grid_id }}'] = layout_id;
                window['detail_layout_id{{ $master_grid_id }}'] = layout_id;
                $('#layoutsbtn_delete{{$grid_id}}').removeAttr('disabled');
                $('#layoutsbtn_save{{$grid_id}}').removeAttr('disabled');
                //$("#title{{ $grid_id }}").text(data.name);
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
  
    if($("#gridcontainer{{ $grid_id }}").hasClass('report_mode')){
      
        if(params.column.userProvidedColDef.type == 'dateField'){
   
            
            menuItems.push('separator');
            menuItems.push({
                name: 'SQL Date Filter',
                subMenu: [
                    @foreach($date_filter_options as $date_filter_opt)
                    {
                        name: '{{ $date_filter_opt }}',
                        action: function () {
                           report_query_date_filter(colId, '{{ $date_filter_opt }}');
                        },
                    },
                    @endforeach
                ],
            });
           
        }
    }
    
    @endif
    
    @if($module_fields_access['is_add'] || $module_fields_access['is_edit'] || $module_fields_access['is_delete'])
        menuItems.push('separator');
    @endif
    
    @if($module_fields_access['is_view'])
        menuItems.push({
            name: 'Fields',
            action: function () {
                viewDialog('collist',"{{ url($module_fields_url.'?module_id='.$module_id) }}");
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
        menuItems.push({
            name: 'Conditional Styles',
            action: function () {
                viewDialog('condition_styles',"{{ url($condition_styles_url.'?module_id='.$module_id) }}"+'&field='+params.column.colDef.field);
            },
        });
    @endif
   
    return menuItems;
}

function  getContextMenuItems{{$module_id}}(params) {
   
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
    
    var standard_buttons = [
        'autoSizeAll',
        'copy',
        'copyWithHeaders',
        {
        name: 'Copy Selected Row',
        action: function () {
           detail_grid_api.api.copySelectedRowsToClipboard();
        },
        icon:'<span class="ag-icon ag-icon-copy"></span>',
        },
        'separator',
        'export',
    ];
    result.push(...standard_buttons);
    
    return result;
}

builderdiv{{ $grid_id }} = $("#builderdiv{{ $grid_id }}");
layoutsdiv{{ $grid_id }} = $("#layoutsdiv{{ $grid_id }}");
detail_grid_api = null;

@if(session('role_level') == 'Admin')
/*
searchtext{{ $grid_id }} = new ej.inputs.TextBox({
	showClearButton: true,
	width:120,
	change: function(e){
        detail_grid_api.api.setQuickFilter(searchtext{{ $grid_id }}.value);
       
        
        if(searchtext{{ $grid_id }}.value != null){
        detail_filter_cleared{{ $grid_id }} = 0;
        }
	},
},'#searchtext{{ $grid_id }}');

document.getElementById('search{{ $grid_id }}').addEventListener('click', function() {
  
    detail_grid_api.api.setQuickFilter(searchtext{{ $grid_id }}.value);
});*/
@endif

$("#{{ $grid_id }}Refresh").click(function() {
	window['grid_{{ $master_grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
	    var master_id = detailGridApi.id.replace('detail_','');
	    
        var post_data = { detail_value: master_id, detail_field: '{{ $master_module_key }}' };
	  
        $.ajax({ 
            url: "/{{ $detail_menu_route }}/aggrid_detail_data", 
            type: 'post',
            data: post_data,
            beforeSend: function(){
            },
            success: function (result) { 
                detailGridApi.api.setRowData(result);
               //return result;
            }, 
        });
	});
});

function refresh_detail_grid{{ $grid_id }}(){

	window['grid_{{ $master_grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
	    var master_id = detailGridApi.id.replace('detail_','');
	    
        var post_data = { detail_value: master_id, detail_field: '{{ $master_module_key }}' };
	  
        $.ajax({ 
            url: "/{{ $detail_menu_route }}/aggrid_detail_data", 
            type: 'post',
            data: post_data,
            beforeSend: function(){
            },
            success: function (result) { 
                detailGridApi.api.setRowData(result);
               //return result;
            }, 
        });
	});    
}

   

function detailGridReady(params){
    //console.log(detailGridReady);
    @if(session('role_level') == 'Admin')
document.getElementById('filterclear{{ $grid_id }}').addEventListener('click', function() {
    ////console.log(detail_filter_cleared{{ $grid_id }});
    ////console.log('filterclear');
    if(detail_filter_cleared{{ $grid_id }} == 0){
        // save temp state
        var temp_state = {};
        temp_state.colState = detail_grid_api.columnApi.getColumnState();
        temp_state.groupState = detail_grid_api.columnApi.getColumnGroupState();
       
        temp_state.filterState = detail_grid_api.api.getFilterModel();
        //temp_state.search = searchtext{{ $grid_id }}.value;
        //searchtext{{ $grid_id }}.value = '';
        window['detailgridstate_{{$grid_id}}'] = temp_state;
        
        detail_grid_api.api.setFilterModel(null);
        detail_grid_api.api.onFilterChanged();
        detail_filter_cleared{{ $grid_id }} = 1;
        @if($soft_delete)
            $.get( "filter_soft_delete/{{$module_id}}/1", function( data ) {
            refresh_detail_grid{{ $grid_id }}();
            });
        @endif
        
        $("#filterclear{{ $grid_id }}").html('<span  class="e-btn-icon e-icons e-filter-clear"></span> Restore Filters</button>');
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
        @if($soft_delete)
            $.get( "filter_soft_delete/{{$module_id}}/0", function( data ) {
            refresh_detail_grid{{ $grid_id }}();
            });
        @endif
        
        $("#filterclear{{ $grid_id }}").html('<span  class="e-btn-icon e-icons e-filter-clear"></span> Clear Filters</button>');
        detail_filter_cleared{{ $grid_id }} = 0;
    }
});
@endif
    

   ////console.log('detailGridReady');
   ////console.log('{{$grid_id}}');
   ////console.log('{{$master_grid_id}}');
    detail_filter_cleared{{ $grid_id }} = 0;

  
    $("#grid_{{$master_grid_id}}").addClass('detailgrid-focus').removeClass('mastergrid-focus');
    
    row_data{{$grid_id}} = {!! json_encode($row_data) !!};
            
    
    detail_grid_api = params;
    window['selectedrow_{{ $grid_id }}'] = null;
    onDetailRowDeselected();
    detail_layout_init{{ $grid_id }}();
    
    // swap toolbar
    
    if( $("#detailtoolbar{{$grid_id}}").is(":hidden")){
        $("#gridheadertoolbar{{$master_grid_id}}").hide();
        $("#gridtoolbar{{$master_grid_id}}").hide();
        $("#detailtoolbar{{$grid_id}}").show();
        $("#detailheadertoolbar{{$grid_id}}").show();
        $("#grid_{{$master_grid_id}}").addClass('detailgrid-focus').removeClass('mastergrid-focus');
        window['grid_{{ $master_grid_id }}'].gridOptions.api.setSideBarVisible(false);
    }
    
    // right click grid header
    //showColumnMenuAfterMouseClick
    
  
    $(document).on("contextmenu", ".ag-details-grid .ag-header-cell", function (e) {
      
        e.preventDefault();
        var col_id = $(this).attr('col-id');
        detail_grid_api.api.showColumnMenuAfterMouseClick(col_id,e);
        return false;
    });
   
  
    
    detail_init_load = false;
    

   
        



}

 window['detailgridlayouts_{{ $grid_id }}'] =  new ej.navigations.Menu({
    items: {!! json_encode($sidebar_layouts) !!},
    enableScrolling: true,
    showItemOnClick: true,
    orientation: 'Horizontal',
    cssClass: 'top-menu k-widget k-button-group',
    
    beforeItemRender: function(args){
    
    var el = args.element;   
    $(el).find("a").attr("title",args.item.text);
    if(args.item.border_top){
    $(el).addClass("menu_border_top");
    }
    
    if(args.item.new_tab == 1) {
    var el = args.element;
    $(el).find("a").attr("target","_blank");
    }
  
    
    if(args.item.cssClass > '') {
    var el = args.element;
    $(el).addClass(args.item.cssClass);
    }
    
    
    if(args.item.data_target == 'javascript') {
    
    $(el).find("a").attr("data-target",args.item.data_target);
    $(el).find("a").attr("js-target",args.item.url);
    $(el).find("a").attr("id",args.item.url);
    $(el).find("a").attr("href","javascript:void(0)");
    
    }else if(args.item.data_target) {
    
    $(el).find("a").attr("data-target",args.item.data_target);
    }
    
    
    },
    }, '#detailgridlayouts_{{ $grid_id }}');
    
    @if(is_superadmin())

var layout_items = [
    @if($module_id!=526)
    {
        id: "layoutsbtn_manage{{ $grid_id }}",
        text: "List",
        iconCss: "fas fa-list",
    },
    @endif
    {
        id: "layoutsbtn_create{{ $grid_id }}",
        text: "Create new layout",
        iconCss: "fa fa-plus",
    },
    {
        id: "layoutsbtn_edit{{ $grid_id }}",
        text: "Edit current layout",
        iconCss: "fas fa-pen",
    },
    {
        id: "layoutsbtn_duplicate{{ $grid_id }}",
        text: "Copy current layout",
        iconCss: "fa fa-copy",
    },
    {
        id: "layoutsbtn_delete{{ $grid_id }}",
        text: "Delete current layout",
        iconCss: "fa fa-trash",
    },
    {
        id: "layoutsbtn_save{{ $grid_id }}",
        text: "Save current layout",
        iconCss: "fa fa-save",
    },
];

var menuOptions = {
    target: '#detailgridlayouts_{{ $grid_id }}',
    items: layout_items,
    beforeItemRender: function(args){
    
    var el = args.element;   
    $(el).find("a").attr("title",args.item.text);
    if(args.item.border_top){
    $(el).addClass("menu_border_top");
    }
    
    if(args.item.new_tab == 1) {
    var el = args.element;
    $(el).find("a").attr("target","_blank");
    }
    
    
    if(args.item.cssClass > '') {
    var el = args.element;
    $(el).addClass(args.item.cssClass);
    }
    
    
    if(args.item.data_target == 'javascript') {
    
    $(el).find("a").attr("data-target",args.item.data_target);
    $(el).find("a").attr("js-target",args.item.url);
    $(el).find("a").attr("id",args.item.url);
    $(el).find("a").attr("href","javascript:void(0)");
    
    }else if(args.item.data_target) {
    
    $(el).find("a").attr("data-target",args.item.data_target);
    }
    
    
    },
};

// Initialize ContextMenu control.
new ej.navigations.ContextMenu(menuOptions, '#detailcontextlayouts{{ $grid_id }}');

@endif

@if(!empty($module_context_builder_menu) && count($module_context_builder_menu) > 0)
var menuOptions = {
    target: '#toolbar_template_leftbuttons{{ $grid_id }}',
    items: {!! json_encode($module_context_builder_menu) !!},
    beforeItemRender: function(args){
    
    var el = args.element;   
    $(el).find("a").attr("title",args.item.text);
    if(args.item.border_top){
    $(el).addClass("menu_border_top");
    }
    
    if(args.item.new_tab == 1) {
    var el = args.element;
    $(el).find("a").attr("target","_blank");
    }
    
    
    if(args.item.cssClass > '') {
    var el = args.element;
    $(el).addClass(args.item.cssClass);
    }
    
    
    if(args.item.data_target == 'javascript') {
    
    $(el).find("a").attr("data-target",args.item.data_target);
    $(el).find("a").attr("js-target",args.item.url);
    $(el).find("a").attr("id",args.item.url);
    $(el).find("a").attr("href","javascript:void(0)");
    
    }else if(args.item.data_target) {
    
    $(el).find("a").attr("data-target",args.item.data_target);
    }
    
    
    },
};

// Initialize ContextMenu control.
new ej.navigations.ContextMenu(menuOptions, '#detailcontext_builder{{ $grid_id }}');
@endif
	
/** LAYOUTS **/
 

/** LAYOUT EVENTS **/    
	$(document).off('click', '#layoutsbtn_manage{{ $grid_id }}').on('click', '#layoutsbtn_manage{{ $grid_id }}', function() {
	    viewDialog('gridv{{ $grid_id }}','/{{$layouts_url}}?module_id={{ $module_id }}','Grid Views - {{$title}}','90%','90%','coreDialog');
	});
	
	$(document).off('click', '#layoutsbtn_create{{ $grid_id }}').on('click', '#layoutsbtn_create{{ $grid_id }}', function() {
	    sidebarform('gridcv{{ $grid_id }}','/{{$layouts_url}}/edit?module_id={{ $module_id }}&grid_reference=grid_{{ $grid_id }}','Create Grid View','','90%');
	});
	
	$(document).off('click', '#layoutsbtn_save{{$grid_id}}').on('click', '#layoutsbtn_save{{$grid_id}}', function(e) {
	    detail_layout_save{{ $master_grid_id }}();
	});
	
	$(document).off('click', '#layoutsbtn_delete{{$grid_id}}').on('click', '#layoutsbtn_delete{{$grid_id}}', function() {
        var confirm_text = "Delete layout?"
        var confirmation = confirm(confirm_text);
        if (confirmation) {
	        detail_layout_delete{{ $grid_id }}();
        }
	});

	$(document).off('click', '#layoutsbtn_showall{{$grid_id}}').on('click', '#layoutsbtn_showall{{$grid_id}}', function() {
	    gridview_show_all();
	});
	
	$(document).off('click', '[id^="layoutsbtnload{{$grid_id}}_"]').on('click', '[id^="layoutsbtnload{{$grid_id}}_"]', function() {
	    var layout_id = $(this).attr('id').replace("layoutsbtnload{{$grid_id}}_", "");
	    detail_layout_load{{ $grid_id }}(layout_id);
	});
	
	$(document).off('click', '#layoutsbtn_edit{{ $grid_id }}').on('click', '#layoutsbtn_edit{{ $grid_id }}', function() {
	    sidebarform('gridcv{{ $grid_id }}','/{{$layouts_url}}/edit/'+window['layout_id{{ $grid_id }}'],'Edit Layout','','90%');
	});
	
/** LAYOUT FUNCTIONS **/ 
    function detail_layout_init{{ $grid_id }}(){
      
        window['layout_id{{ $grid_id }}'] = {{$grid_layout_id}};
       
        @if($grid_layout_type == 'default_new')
        if(default_detail_layout_save{{ $master_grid_id }} == 0){
            default_detail_layout_save{{ $master_grid_id }} = 1;
            detail_layout_save{{ $master_grid_id }}();
        }
     
        @else
        
        detail_layout_load{{ $grid_id }}(window['layout_id{{ $grid_id }}'],window['layout_id{{ $master_grid_id }}'], 1);
        @endif
    }
    
    function detail_layout_delete{{ $grid_id }}(){
        $.ajax({
		url: '/delete_grid_config/'+window['layout_id{{ $grid_id }}'],
		contentType: false,
		processData: false,
		success: function(data) { 
		   
            toastNotify('View deleted.','success', false);
            if(data.default_id){
                detail_layout_load{{ $grid_id }}(data.default_id)
            }else{
               setTimeout(function(){ location.reload(); }, 500);
            }
        }
        });
    }
    
    function detail_layout_create{{ $grid_id }}(layout_id){
       
        var layout = {};
        layout.colState = detail_grid_api.columnApi.getColumnState();
        layout.groupState = detail_grid_api.columnApi.getColumnGroupState();
      
        layout.filterState = detail_grid_api.api.getFilterModel();
        
        var pivot = {};
        var pivotMode = detail_grid_api.columnApi.isPivotMode();
        if(pivotMode){
            var pivot_mode = 1;
            pivot.colState = detail_grid_api.columnApi.getPivotColumns();
        }else{
            var pivot_mode = 0;
        }
          
        var data = {layout_id: layout_id, grid_reference: 'grid_{{ $grid_id }}', layout : layout, pivot: pivot, pivot_mode: pivot_mode,  query_string: {!! $query_string !!}};   
       
        
        $.ajax({
            type: 'post',
            url: '{{ url($menu_route."/aggrid_layout_save") }}',
            data: data,
    		success: function(data) { 
    		  
    		    window['layout_id{{ $grid_id }}'] = data.layout_id;
    		    
                @if(session('role_level') == 'Admin')
                
                window['detailgridlayouts_{{ $grid_id }}'].items = JSON.parse(data.menu);
                window['detailgridlayouts_{{ $grid_id }}'].dataBind();
              
    		    @endif
    		    toastNotify('Layout saved.','success');
    		}
    	});
    }
    
    function detail_layout_save{{ $master_grid_id }}(){
        
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
           
        var data = {layout : layout, master_layout_id: window['layout_id{{ $master_grid_id }}'], layout_id: window['layout_id{{ $grid_id }}'], pivot: pivot, pivot_mode: pivot_mode,  query_string: {!! $query_string !!}};
        //console.log(layout.filterState);
        $.ajax({
            type: 'post',
            url: '{{ url($menu_route."/aggrid_layout_save") }}',
            data: data,
    		success: function(data) { 
    		 
    		    window['layout_id{{ $grid_id }}'] = data.layout_id;
    		    toastNotify('Detail Layout saved.','success');
    		}
    	});
    }
    
    function detail_layout_load{{ $grid_id }}(layout_id, master_layout_id = 0, first_load = 0){
        //console.log('detail_layout_load{{ $grid_id }}');
        //console.log(first_load);
        if(first_load){
            
    		   //console.log(window['detailgridlayouts_{{ $grid_id }}']);
                var data = {!! json_encode($layout_init) !!};
              
                var state = JSON.parse(data.settings);
                //console.log(data);
                //console.log(state);
                
                window['grid_{{ $master_grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
               
                if(data.pivot_mode == 1){
                   detailGridApi.columnApi.setPivotMode(true);
                   
                }else{
                   detailGridApi.columnApi.setPivotMode(false);
                }
                
                    ////console.log('load2');
                    ////console.log(state);
                    ////console.log(data);
                if(state){
                if(state.colState){
                   detailGridApi.columnApi.applyColumnState({state:state.colState,applyOrder: true,});
                }
                if(state.groupState){
                   detailGridApi.columnApi.setColumnGroupState(state.groupState);
                }
             
                if(state.filterState){
                   detailGridApi.api.setFilterModel(state.filterState);
                }
                }
                
               detailGridApi.columnApi.autoSizeAllColumns();
             
                
                
                });
                window['layout_id{{ $grid_id }}'] = data.layout_id;
                
                window['detail_layout_id{{ $master_grid_id }}'] = data.layout_id;
                $('#layoutsbtn_delete{{$grid_id}}').removeAttr('disabled');
                $('#layoutsbtn_save{{$grid_id}}').removeAttr('disabled');
        }else{
    	var ajax_data = {aggrid: 1, master_layout_id: master_layout_id, layout_id: layout_id, grid_reference: 'grid_{{ $grid_id }}', query_string: {!! $query_string !!} };
      //console.log(ajax_data);
        $.ajax({
            type: 'post',
            url: '{{ url($menu_route."/aggrid_layout_data") }}',
            data: ajax_data,
            beforeSend: function(){
                $('#layoutsbtn_delete{{$grid_id}}').attr('disabled','disabled');
                $('#layoutsbtn_save{{$grid_id}}').attr('disabled','disabled');
            },
    		success: function(data) { 
    		    setTimeout(function(){
    		   //console.log('detail_layout_load{{ $grid_id }}');
    		   //console.log(data);
    		   //console.log(window['detailgridlayouts_{{ $grid_id }}']);
                @if(session('role_level') == 'Admin')
               
                window['detailgridlayouts_{{ $grid_id }}'].items = JSON.parse(data.menu);
                window['detailgridlayouts_{{ $grid_id }}'].dataBind();
                @endif
                var state = JSON.parse(data.settings);
                
                
                window['grid_{{ $master_grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
               
                if(data.pivot_mode == 1){
                   detailGridApi.columnApi.setPivotMode(true);
                   
                }else{
                   detailGridApi.columnApi.setPivotMode(false);
                }
                
                    ////console.log('load2');
                    ////console.log(state);
                    ////console.log(data);
                if(state){
                if(state.colState){
                   detailGridApi.columnApi.applyColumnState({state:state.colState,applyOrder: true,});
                }
                if(state.groupState){
                   detailGridApi.columnApi.setColumnGroupState(state.groupState);
                }
             
                if(state.filterState){
                   detailGridApi.api.setFilterModel(state.filterState);
                }
                }
                
               detailGridApi.columnApi.autoSizeAllColumns();
             
                
                
                });
                window['layout_id{{ $grid_id }}'] = data.layout_id;
                
                window['detail_layout_id{{ $master_grid_id }}'] = data.layout_id;
                $('#layoutsbtn_delete{{$grid_id}}').removeAttr('disabled');
                $('#layoutsbtn_save{{$grid_id}}').removeAttr('disabled');
                //$("#title{{ $grid_id }}").text(data.name);
    		    },200)
    		}
    	});
        }
    }
    
    function reload_grid_config_detail{{ $grid_id }}(){
        detail_layout_load{{ $grid_id }}(window['layout_id{{ $grid_id }}']);
    }
    
    function get_sidebar_data{{ $module_id }}(){
        ////console.log('get_sidebar_data{{ $module_id }}');
        if(window['layout_id{{ $grid_id }}']){
            var data = {grid_id: "{{ $grid_id }}",grid_layout_id: window['layout_id{{ $grid_id }}']};
        }else{
            var data = {grid_id: "{{ $grid_id }}"};
        }
        $.ajax({
            url:'{{ url($menu_route."/aggrid_sidebar_data") }}',
            data: data,
            type: 'post',
            success:function(data){
        ////console.log(data);
                if(data){
                    @if(session('role_level') == 'Admin')  
                    if(data.sidebar_layouts){
                 
                         
                        window['detailgridlayouts_{{ $grid_id }}'].items = data.sidebar_layouts;
                        window['detailgridlayouts_{{ $grid_id }}'].dataBind();
                     
            		  
                    }
                    if(data.sidebar_reports && window['gridreports_{{ $grid_id }}']){
                        window['gridreports_{{ $grid_id }}'].dataSource = data.sidebar_reports;
                        window['gridreports_{{ $grid_id }}'].dataBind();
                    }
        		    @endif
                }
            }
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
        @if(!$serverside_model)
        statusBar: {
        statusPanels: [
        {
        statusPanel: 'agTotalAndFilteredRowCountComponent',
        align: 'left',
        }
        ]
        },
        @endif
        tooltipShowDelay:1,
        enableBrowserTooltips: true,
        suppressPropertyNamesCheck: true,
        suppressCopyRowsToClipboard:true,
        suppressMoveWhenRowDragging: true,
        rowDragEntireRow: true,
        getMainMenuItems: getMainMenuItems{{$grid_id}},
        getContextMenuItems: getContextMenuItems{{$module_id}},
        rowHeight: 26,
        headerHeight: 30,
        @if(session('role_id') > 10)
        suppressContextMenu:true,
        @endif
        @if($access['is_edit'])
        onRowDoubleClicked: function(){
            var selected = window['selectedrow_{{ $grid_id }}'];
            
            @if($documents_module)
            transactionDialog('{{ $menu_route }}edit', '/{{ $menu_route }}/edit/'+ selected.rowId, 'Documents - Edit', '80%', '100%');
            @else
            
            sidebarform('{{ $menu_route }}edit' , '/{{ $menu_route }}/edit/'+ selected.rowId+'?layout_id='+window['detail_layout_id{{ $master_grid_id }}'], '{{$menu_name}} - Edit', '','60%');
            
            @endif
        },
        @endif
        columnDefs: {!! json_encode($detail_col_defs) !!},
        columnTypes: {
        defaultField: {
          
            filter: 'agTextColumnFilter',
            filterParams: {
                suppressAndOrCondition: true
            },
            maxWidth : 200,
            cellStyle : { 'text-overflow':'ellipsis','white-space':'nowrap', 'overflow': 'hidden', 'padding': 0 }
        },
        dateField: {
           
            filter: 'agDateColumnFilter',
            filterParams: {
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
                browserDatePicker: true,
                minValidYear: 2000,
            }
        },
        booleanField: {
            
            filter: 'agSetColumnFilter',
            filterParams: {
                suppressAndOrCondition: true,
                defaultToNothingSelected: true,
                suppressSelectAll: true,
                cellRenderer: function(params){
                    if(params.value === "1" || params.value === 1 || params.value === "true"){
                        return "Yes";
                    }
                    if(params.value === "0" || params.value === 0 || params.value === "false"){
                        return "No";
                    }
                    return params.value;
                },
            },
            cellRenderer: function(params){
                if(params.value === "1" || params.value === 1 || params.value === "true"){
                    return "Yes";
                }
                if(params.value === "0" || params.value === 0 || params.value === "false"){
                    return "No";
                }
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
                        maxWidth : 300,
                        cellStyle : { 'text-overflow':'ellipsis','white-space':'nowrap', 'overflow': 'hidden', 'padding': 0 },
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
                    maxWidth : 300,
                    cellStyle : { 'text-overflow':'ellipsis','white-space':'nowrap', 'overflow': 'hidden', 'padding': 0 },
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
        intField: {
            filter: 'agNumberColumnFilter',
            cellClass: 'ag-right-aligned-cell',
            headerClass: 'ag-right-aligned-header',
            //headerClass: 'ag-right-aligned-header',
            //cellClass: 'ag-cell-numeric-right',
           // valueFormatter: currencyValueFormatter,
        },
        currencyField: {
            filter: 'agNumberColumnFilter',
            valueFormatter: function(params){
                if(!params.node.footer){
                   
                    var currency_decimals = params.colDef.currency_decimals;
                    var currency_symbol = params.colDef.currency_symbol;
                    var row_data_currency = params.colDef.row_data_currency;
                   
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
                   
                }
            },
            headerClass: 'ag-right-aligned-header',
            cellClass: 'ag-right-aligned-cell',
            comparator: (valueA, valueB, nodeA, nodeB, isInverted) => valueA - valueB
        },
        sortField:{
            rowDrag: params => !params.node.group,
        },
        fileField: {
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
            }
        },
        
        
    },
        defaultColDef: {
            getQuickFilterText: function(params) {
                return (!params.column.visible) ? '' : params.value; 
            },
            minWidth: 80,
            // allow every column to be aggregated
            enableValue: false,
            // allow every column to be grouped
            enableRowGroup: false,
            // allow every column to be pivoted
            enablePivot: false,
            sortable: true,
            filter: true,
            filterParams: {
                suppressAndOrCondition: true,
                newRowsAction: 'keep',
                buttons: ['reset']
            },
            //menuTabs: ['filterMenuTab','generalMenuTab','columnMenuTab'],
            @if(session('role_level') == 'Admin')
            menuTabs: ['columnsMenuTab','filterMenuTab','generalMenuTab'],
            @else
            menuTabs: ['filterMenuTab'],
            @endif
        }, 
        @if(!empty($rowClassRules))
        rowClassRules: {!! json_encode($rowClassRules) !!},
        @endif
        icons: {
            layouts_icon: '<i class="far fa-bookmark"/>',
            reports_icon: '<i class="far fa-chart-bar"/>',
            communications_icon: '<i class="far fa-envelope"/>',
            builder_icon: '<i class="far fa-caret-square-right"/>',
        },
        
        @if(session('role_level') == 'Admin')  
        sideBar: {
        toolPanels: [
            /*
            {
                id: 'columns',
                labelDefault: '',
                labelKey: 'columns',
                iconKey: 'columns',
                toolPanel: 'agColumnsToolPanel',
            },
            {
                id: 'filters',
                labelDefault: '',
                labelKey: 'filters',
                iconKey: 'filter',
                toolPanel: 'agFiltersToolPanel',
            },
            */
           
            
            /*
            {
                id: 'filters',
                labelDefault: 'Filters',
                labelKey: 'filters',
                iconKey: 'filter',
                toolPanel: 'agFiltersToolPanel',
            },
            */
            
        ],
        
        defaultToolPanel: '',
        },
        @endif
        components: {
            
        },
        rowSelection: 'single',
        onGridReady: detailGridReady,
            
        onFilterChanged: function(){
     
        },
        onRowSelected: function(event){
       //  ////console.log('detail onRowSelected');
        // //console.log(event);
       //  ////console.log(event.node.isSelected());
       //  ////console.log(event.node.group);
           
           
           
            if(!event.node.isSelected()){
                var deselected = event.node.data;
                if(deselected.{{$db_key}} == window['selectedrow_{{ $grid_id }}'].rowId){
                  
                    window['selectedrow_{{ $grid_id }}'] = null;
                    onDetailRowDeselected();
                }
            }
            if(event.node.isSelected() && event.node.group == false){
            //    ////console.log('detail selected');
            //    ////console.log(window['grid_{{ $master_grid_id }}']);
                // set selected for button events
                window['selectedrow_{{ $grid_id }}'] = event.node.data;
                window['selectedrow_{{ $grid_id }}'].rowId = window['selectedrow_{{ $grid_id }}'].{{$db_key}};
                
                // deselect all rows
                window['grid_{{ $master_grid_id }}'].gridOptions.api.deselectAll();
                window['grid_{{ $master_grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
                 
                   
                        detail_grid_api = detailGridApi;
                    
                });
                
                // swap sidebar
                // swap toolbar
                
                if( $("#detailtoolbar{{$grid_id}}").is(":hidden")){
                    
                    $("#gridheadertoolbar{{$master_grid_id}}").hide();
                    $("#gridtoolbar{{$master_grid_id}}").hide();
                    $("#detailtoolbar{{$grid_id}}").show();
                    $("#detailheadertoolbar{{$grid_id}}").show();
                    $("#grid_{{$master_grid_id}}").addClass('detailgrid-focus').removeClass('mastergrid-focus');
                    window['grid_{{ $master_grid_id }}'].gridOptions.api.setSideBarVisible(false);
                }
                // $("#grid_{{$master_grid_id}}").addClass('detailgrid-focus').removeClass('mastergrid-focus');
                
                
                // set buttons
                onDetailRowSelected();
                
            }
        },
        onViewportChanged: function(){
           
                this.columnApi.autoSizeAllColumns();
                window['grid_{{ $master_grid_id }}'].gridOptions.columnApi.autoSizeAllColumns();
            
        },
        onRowDataChanged: function(){
            this.columnApi.autoSizeAllColumns();
        },
        
        @if(session('role_level') == 'Admin' && $has_sort)
            onRowDragEnd: onDetailRowDragEnd{{$grid_id}},
            onRowDragMove: onDetailRowDragMove{{$grid_id}},
        @endif
    
        getRowId: getDetailRowNodeId{{$grid_id}},
     
        multiSortKey: 'ctrl',
    };


   @if(session('role_level') == 'Admin')
        function onDetailRowDragEnd{{$grid_id}} (e) {
         
            if(e.node && e.node.group == false && e.node.data && e.node.data.id && overData){
                var start_id = e.node.data.id;
                var target_id = overData.id;
                var sort_data = JSON.stringify({"start_id" : start_id, "target_id" : target_id});
              
                $.ajax({ 
                    type: "POST",
                    url: "/{{$menu_route}}/sort", 
                    datatype: "json", 
                    contentType: "application/json; charset=utf-8", 
                    data: sort_data, 
                    beforeSend: function(){
                    },
                    success: function (result) { 
                       //console.log(result); 
                      
        	           window['grid_{{$master_grid_id}}'].gridOptions.refresh();
                     //  window['grid_{{$master_grid_id}}'].gridOptions.api.getDetailRowData();
                    }, 
                });
            }
        }

        function onDetailRowDragMove{{$grid_id}}(event) {
            var immutableStore = window['detail_row_data{{$master_grid_id}}'];
         
            var movingNode = event.node;
            var overNode = event.overNode;
            
            var rowNeedsToMove = movingNode !== overNode;
            
            
            if (rowNeedsToMove) {
                // the list of rows we have is data, not row nodes, so extract the data
                var movingData = movingNode.data;
                overData = overNode.data;
                
                var fromIndex = findWithAttr(immutableStore,'id',movingData.id);
                var toIndex = findWithAttr(immutableStore,'id',overData.id);
                
                
                var newStore = immutableStore.slice();
                moveInArray(newStore, fromIndex, toIndex);
                
                
                immutableStore = newStore;
                detail_grid_api.api.setRowData(newStore);
                
                detail_grid_api.api.clearFocusedCell();
            }
        
            function moveInArray(arr, fromIndex, toIndex) {
                ////console.log('moveInArray');
                ////console.log(arr);
                ////console.log(fromIndex);
                ////console.log(toIndex);
                ////console.log(arr[toIndex]);
                
                var to_sort = arr[toIndex].sort_order
                arr[toIndex].sort_order = arr[fromIndex].sort_order;
                arr[fromIndex].sort_order = to_sort;
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


/** TOOLBAR **/

    window['detailheadertoolbar{{ $grid_id }}'] = new ej.navigations.Toolbar({
        items: [
            { template: "#toolbar_template_layouts{{ $grid_id }}", align: 'left' },
            { template: "#toolbar_template_layouts_right{{ $grid_id }}", align: 'right' },
        ]
    });
    window['detailheadertoolbar{{ $grid_id }}'].appendTo('#detailheadertoolbar{{ $grid_id }}');

    window['detailtoolbar{{ $grid_id }}'] = new ej.navigations.Toolbar({
        items: [
            { template: "#toolbar_template_leftbuttons{{ $grid_id }}", align: 'left' },
            { template: "#toolbar_template_actionbuttons{{ $grid_id }}", align: 'left' },
            @if(session('role_level') == 'Admin')
            { template: "#toolbar_template_rightbuttons{{ $grid_id }}", align: 'right' },
            @endif
        ]
    });
    window['detailtoolbar{{ $grid_id }}'].appendTo('#detailtoolbar{{ $grid_id }}');

function onDetailRowSelected() {
    
@if(session('role_id')==1 || is_dev())
$('#{{ $grid_id }}module_log').removeAttr("disabled");
@endif
    ////console.log('onDetailRowSelected');
    ////console.log($('#{{ $grid_id }}Edit'));
@if($check_doctype)
    doctypes = {!! json_encode($doctypes) !!};
@endif
var selected = window['selectedrow_{{ $grid_id }}'];
selected_doctype_el = null;



@if(!empty($grid_menu_menu) && count($grid_menu_menu) > 0) 
    grid_menu{{ $grid_id }}.refresh();
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
$('#{{ $grid_id }}Edit').removeAttr("disabled");
}else{
$('#{{ $grid_id }}Edit').attr("disabled","disabled");
}
}else{
$('#{{ $grid_id }}Edit').removeAttr("disabled");
}

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
$('#{{ $grid_id }}Duplicate').removeAttr("disabled");
@endif

@if($access['is_delete'])
   @if($db_table == 'crm_accounts')
            if(selected.status  != 'Deleted'){
                if(selected.cancelled == "Yes"){
                    detail_toolbar_button_icon('{{ $grid_id }}Delete','restore', 'Undo Cancel');
                    $('#{{ $grid_id }}Delete').removeAttr("disabled");
                }else{
                    detail_toolbar_button_icon('{{ $grid_id }}Delete','cancel', 'Cancel Account');
                    $('#{{ $grid_id }}Delete').removeAttr("disabled");
                }
            }
        @if(check_access('1,34'))
            if(selected.status  == 'Deleted'){
            detail_toolbar_button_icon('{{ $grid_id }}Delete','restore', 'Restore Account');
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
            }
        @endif
    @elseif($db_table == 'sub_services')
        if(selected.status  != 'Deleted'){
        
            if(selected.to_cancel == "Yes"){
                detail_toolbar_button_icon('{{ $grid_id }}Delete','restore', 'Undo Cancel');
                $('#{{ $grid_id }}Delete').removeAttr("disabled");
            }else{
                detail_toolbar_button_icon('{{ $grid_id }}Delete','cancel', 'Cancel Subscription');
                $('#{{ $grid_id }}Delete').removeAttr("disabled");
            }
        }
    @else
    if(selected_doctype_el != null){
       
        if(selected_doctype_el.deletable == 1){
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
            detail_toolbar_button_icon('{{ $grid_id }}Delete','delete', 'Delete '+selected_doctype_el.doctype_label);
        }else if(selected_doctype_el.creditable == 1){
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
            detail_toolbar_button_icon('{{ $grid_id }}Delete','reverse', 'Credit '+selected_doctype_el.doctype_label);
        }else{
            $('#{{ $grid_id }}Delete').attr("disabled","disabled");
        }
    
    }else{
        @if($db_table == 'crm_suppliers')
            if(selected.status == "Deleted"){
                detail_toolbar_button_icon('{{ $grid_id }}Delete','restore', 'Restore');
                $('#{{ $grid_id }}Delete').removeAttr("disabled");
            }else{
                detail_toolbar_button_icon('{{ $grid_id }}Delete','delete', 'Delete');
                $('#{{ $grid_id }}Delete').removeAttr("disabled");
            }
        @else
       
        if(selected && selected.is_deleted  == 1){
            toolbar_button_icon('{{ $grid_id }}Delete','restore', 'Restore');
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
        }else if(selected && selected.is_deleted == 0){
            toolbar_button_icon('{{ $grid_id }}Delete','delete', 'Delete');
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
        }else if(selected && selected.status && selected.status != "Deleted"){
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
        }else if(selected && selected.status == undefined){
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
        }else if(selected && selected.status == ""){
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
        }
        @endif
    }
           
    @endif

@endif
@if(!empty($grid_menu_menu) && count($grid_menu_menu) > 0) 
{!! button_menu_selected($module_id, 'grid_menu', $grid_id, 'selected', false) !!}
@endif
}

function onDetailRowDeselected(){
        @if(!empty($grid_menu_menu) && count($grid_menu_menu) > 0) 
        {!! button_menu_selected($module_id, 'grid_menu', $grid_id, 'deselected', false) !!}
        @endif
        
        @if($access['is_add'])
		$('#{{ $grid_id }}Duplicate').attr("disabled","disabled");
        @endif
        
        @if($access['is_edit'])
		    $('#{{ $grid_id }}Edit').attr("disabled","disabled");
        @endif
        
        @if(session('role_id')==1 || is_dev())
        $('#{{ $grid_id }}module_log').attr("disabled","disabled");
        @endif
        
        @if($access['is_approve'])
    		@if($db_table == 'crm_documents' || $db_table == 'crm_supplier_documents')
    		$('#{{ $grid_id }}Approve').attr("disabled","disabled");
    		@endif
        @endif
        
      
        
        @if($access['is_delete'])
		$('#{{ $grid_id }}Delete').attr("disabled","disabled");
        @endif
        
}

function detail_toolbar_button_icon(id, icon, title = ''){
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
        $("#{{ $grid_id }}Import").click(function(){
         sidebarform('{{ $menu_route }}import' , '/{{ $menu_route }}/import');
        });
    @endif
    
    @if($access['is_add'])
        $("#{{ $grid_id }}Add").click(function(){
           
            @if($menu_route == 'pbx_menu')
                sidebarform('{{ $menu_route }}add' , 'pbx_menuedit', 'PBX menu - Add');
            @elseif(!empty(request()->account_id) && $documents_module)
                transactionDialog('{{ $menu_route }}add' , '/{{ $menu_route }}/edit?account_id={{request()->account_id}}', 'Documents - Add', '80%', 'auto');
            @elseif($documents_module)
            transactionDialog('{{ $menu_route }}add' , '/{{ $menu_route }}/edit/', 'Documents - Add', '80%', 'auto');
            @elseif(!empty($request_get))
                if(mastergrid_id){
                    sidebarform('{{ $menu_route }}add' , '/{{ $menu_route }}/edit?{{ $master_module_key }}='+mastergrid_id+'&layout_id='+window['detail_layout_id{{ $master_grid_id }}']  , '{!! $menu_name !!} - Add','{!! $form_description !!}', '60%');
                }else{
                    sidebarform('{{ $menu_route }}add' , '/{{ $menu_route }}/edit?{!! $request_get !!}'+'&layout_id='+window['detail_layout_id{{ $master_grid_id }}'] , '{!! $menu_name !!} - Add','{!! $form_description !!}', '60%');
                }
            @elseif(!$documents_module)
                if(mastergrid_id){
                    sidebarform('{{ $menu_route }}add' , '/{{ $menu_route }}/edit?{{ $master_module_key }}='+mastergrid_id+'&layout_id='+window['detail_layout_id{{ $master_grid_id }}'] , '{!! $menu_name !!} - Add','{!! $form_description !!}', '60%');
                }else{
                    sidebarform('{{ $menu_route }}add' , '/{{ $menu_route }}/edit'+'?layout_id='+window['detail_layout_id{{ $master_grid_id }}'] , '{!! $menu_name !!} - Add','{!! $form_description !!}', '60%');
                }
            @endif
            
        });
    @endif
    
    
    @if($access['is_edit'])
        $("#{{ $grid_id }}Edit").click(function(){
         
            var selected = window['selectedrow_{{ $grid_id }}'];
            @if($documents_module)
                transactionDialog('{{ $menu_route }}edit', '/{{ $menu_route }}/edit/'+ selected.rowId, 'Documents - Edit', '80%', '100%');
            @else
                sidebarform('{{ $menu_route }}edit' , '/{{ $menu_route }}/edit/'+ selected.rowId+'?layout_id='+window['detail_layout_id{{ $master_grid_id }}'], '{!! $menu_name !!} - Edit', '{!! $form_description !!}','60%');
            @endif
        });
    @endif
    

    @if($access['is_approve'])
    $("#{{ $grid_id }}Approve").click(function(){
        var selected = window['selectedrow_{{ $grid_id }}'];
        var check_access = {{ (check_access('1,2,7')) ? 1: 0 }};
       
        @if($db_table == 'crm_documents')
            if(selected.doctype == 'Credit Note Draft'){
                gridAjaxConfirm('/{{ $menu_route }}/approve', 'Approve Credit Note?', {"id" : selected.rowId}, 'post');
            }else if((selected.doctype == 'Order' || selected.doctype == 'Order') && check_access == 1){
                
                gridAjaxConfirm('/{{ $menu_route }}/approve', 'Approve Order?', {"id" : selected.rowId}, 'post');
            }else if(selected.doctype == 'Quotation'){
                gridAjaxConfirm('/{{ $menu_route }}/approve', 'Approve Quotation?', {"id" : selected.rowId}, 'post');
            }
        @elseif($db_table == 'crm_supplier_documents')
            if(selected.doctype == 'Supplier Order'){
                gridAjaxConfirm('/{{ $menu_route }}/approve', 'Approve Supplier Order?', {"id" : selected.rowId}, 'post');
            }
        @endif
    });
    @endif
    
	         

    @if(session('role_id')==1 || is_dev())
        $('#{{ $grid_id }}module_log').click(function(){
                
            var selected = window['selectedrow_{{ $grid_id }}'];
            if(selected && selected.rowId){
                viewDialog('{{ $menu_route }}module_log', '{{ url($module_log_url) }}?module_id={{$module_id}}&row_id='+ selected.rowId, '70%', '80%', '100%');
            }
           
        });
    @endif
    
     @if($access['is_view'] && (in_array($db_table,['crm_documents','crm_supplier_documents','crm_supplier_import_documents'])))
    
	  
        $("#{{ $grid_id }}View").click(function(){
            var selected = window['selectedrow_{{ $grid_id }}'];
          
            viewDialog('{{ $menu_route }}'+selected.rowId, '/{{ $menu_route }}/view/'+ selected.rowId,'','70%');
          
        });
    @endif
    
    @if($access['is_add'])
        $("#{{ $grid_id }}Duplicate").click(function(){
            var selected = window['selectedrow_{{ $grid_id }}'];
            gridAjaxConfirm('/{{ $menu_route }}/duplicate', 'Duplicate record?', {"id" : selected.rowId}, 'post');
        });
    @endif
    
   
    @if($access['is_delete'])
        
        $("#{{ $grid_id }}Delete").click(function(){
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
                        detail_toolbar_button_icon('{{ $grid_id }}Delete','restore', 'Restore Account');
                        gridAjaxConfirm('/restore_account/'+selected.rowId, 'Restore Account?', {"id" : selected.rowId}, 'post');
        		        $('#{{ $grid_id }}Delete').removeAttr("disabled");
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
                    }else if((selected.doctype == 'Order' || selected.doctype == 'Order')){
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
                        detail_toolbar_button_icon('{{ $grid_id }}Delete','delete', 'Delete');
                        gridAjaxConfirm('/{{ $menu_route }}/delete', 'Delete record?', {"id" : selected.rowId}, 'post');
                    }
                @elseif($db_table == 'sub_services')
                    gridAjax('/{{ $menu_route }}/delete',, {"id" : selected.rowId}, 'post');
                @else
                  
                    if(selected && selected.is_deleted == 1){
                        gridAjaxConfirm('/{{ $menu_route }}/restore', 'Restore record?', {"id" : selected.rowId}, 'post');
                    }else{
                        gridAjaxConfirm('/{{ $menu_route }}/delete', 'Delete record?', {"id" : selected.rowId}, 'post');
                    }
                @endif
            @endif
        });
    @endif


    
    @if($access['is_view'])
        $("#{{ $grid_id }}Export").click(function(){
            detail_grid_api.api.exportDataAsExcel({fileName: '{{$master_grid_title}}.xlsx'});
        });
    @endif      
    
    

    
    
</script>

@parent
@endsection
@section('css')
@parent
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
@endsection