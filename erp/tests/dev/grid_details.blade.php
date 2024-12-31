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


</div>
</div>



@endsection


@section('page-scripts')
<script>

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



    @if(!empty($moduleleft_menu) && count($moduleleft_menu) > 0)   
    var detail_moduleleftMenuItems = @php echo json_encode($moduleleft_menu); @endphp;
    // top_menu initialization
    var moduleleft{{ $grid_id }} = new ej.navigations.Menu({
       items: detail_moduleleftMenuItems,
       orientation: 'Horizontal',
       cssClass: 'top-menu k-widget k-button-group',
       
        created: function(args){
           
            @if(is_superadmin())
            
            $('body').append('<ul id="moduleleft_context{{$grid_id}}" class="m-0"></ul>');
            var context_items = [
                {
                    id: "context_gridtab_edit",
                    text: "Edit Menu",
                    iconCss: "fas fa-list",
                    url: 'sf_menu_manager/{{$module_id}}/moduleleft',
                    data_target: 'view_modal',
                },
            ];
            var menuOptions = {
                target: '#moduleleft_menu{{ $grid_id }}',
                items: context_items,
                beforeItemRender: dropdowntargetrender
            };
            
            // Initialize ContextMenu control
            new ej.navigations.ContextMenu(menuOptions, '#moduleleft_context{{$grid_id}}');
            
            @endif
    
        },
        beforeOpen: function(args){
         
            var popup_items = [];
            $(args.items).each(function(i, el){
                popup_items.push(el.text);
            });
        
            var selected = window['selectedrow_{{ $grid_id }}'];
            
           
            
            {!! button_menu_selected($module_id, 'moduleleft', $grid_id, 'selected', true) !!}
            
          
    	    var selected = window['mastergrid_row{{ $master_grid_id }}'];
           
            {!! button_headermenu_selected($master_module_id, 'moduleleft', $grid_id, 'selected', true) !!}
           
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
    },'#moduleleft_menu{{ $grid_id }}');
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
   
    @if($moduleleft_context)
    {!! $moduleleft_context !!}
    @endif
    
    
    /* ADD STATUS BUTTONS */
   
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
    
    var editmenu_btn = {
        name: 'Edit Menu',
        action: function(){
            viewDialog('editrowcontext{{$grid_id}}','sf_menu_manager/{{$module_id}}/moduleleft');
        }
    };
    result.push(editmenu_btn);
    
   
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
}

   

function detailGridReady(params){
  

    @if(session('role_level') == 'Admin')

    
filterclear{{ $grid_id }} = () => {  

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
        detail_grid_api.api.onFilterChanged();
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

$(document).off('click', '#copyrow{{ $grid_id }}').on('click', '#copyrow{{ $grid_id }}', function() {  
    detail_grid_api.api.copySelectedRowsToClipboard();
});



showdeleted{{ $grid_id }} = () => {
  
    if(show_deleted{{ $grid_id }} == 0){
        $.get( "filter_soft_delete/{{$module_id}}/1", function( data ) {
           refresh_detail_grid{{ $grid_id }}();
        });
        show_deleted{{ $grid_id }} = 1;
    }else{
    
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
    onDetailRowDeselected();
    detail_layout_load{{ $master_grid_id }}();
    
    // swap toolbar
    
        
        $("#moduleleft_menu{{ $master_grid_id }}").addClass('d-none');
        $("#moduleleft_menu{{ $grid_id }}").removeClass('d-none');
        $("#grid_{{$master_grid_id}}").addClass('detailgrid-focus').removeClass('mastergrid-focus');
        window['grid_{{ $master_grid_id }}'].gridOptions.api.setSideBarVisible(false);
    
    
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
        
       
        if(window['detail_col_defs{{$master_grid_id}}']){
            // set column defs for colmenu buttons
        
            detailGridOptions.columnDefs = window['detail_col_defs{{$master_grid_id}}'];
        }
       
              
        var state = JSON.parse(window['detail_settings{{$master_grid_id}}']);
    
        
        window['grid_{{ $master_grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
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


// note toolpanel functions
$(document).off('click', '.addnotetbtn{{$grid_id}}').on('click', '.addnotetbtn{{$grid_id}}', function() {
    var selected = window['selectedrow_{{ $grid_id }}'];
    
    if(!selected && !selected.rowId){
        
        toastNotify('Select a record','warning');
    }else{
      
        var note = $("#record_note{{$grid_id}}").val();
        if(selected && selected.rowId && note > ''){
            
     
               $.ajax({
                url: '/{{$menu_route}}/addnote',
                type:'post',
                data: {note:note,module_id: {{$module_id}}, row_id:selected.rowId},
                success: function(data) { 
                   
                    $("#record_note{{$grid_id}}").val('');
                    getNotes{{$grid_id}}();
                }
              });  
        }else{
            toastNotify('Note field cannot be blank','warning');
        }
    }   
});

$(document).off('click', '.deletenotetbtn{{$grid_id}}').on('click', '.deletenotetbtn{{$grid_id}}', function() {
  
    var note_id = $(this).attr('data-note-id');
    if(note_id > ''){
           $.ajax({
            url: '/{{$menu_route}}/deletenote',
            type:'post',
            data: {note_id: note_id},
            success: function(data) { 
                getNotes{{$grid_id}}();
            }
          });  
    }
});

function getNotes{{$grid_id}}(params = false) {
   
        if(params){
            var rows = params.api.getSelectedRows();
           
            if(rows.length > 0){
                var selected = rows[0];
            }
        }else{
            var selected = window['selectedrow_{{ $grid_id }}'];
        }
      
        if(selected && selected.rowId){
            $.ajax({
            url: '/{{$menu_route}}/getnotes/'+selected.rowId,
            contentType: false,
            processData: false,
            success: function(data) { 
               
                var notes_html = '';
                $("#notescount{{$grid_id}}").text(' ('+data.length+')');
                $(data).each(function(i,el){
                  
                    notes_html += '<div class="card">';
                    notes_html += '<div class="card-body p-2">';
                    notes_html += '<div style="white-space: pre-line">'+el.note+'</div>';
                    notes_html += '<div class="text-muted text-right">'+el.username+'<br>'+el.created_at+'</div>';
                    notes_html += '</div>';
                    @if(session('role_level')=='Admin')
                    notes_html += '<div class="card-footer p-2 text-right">';
                    notes_html += '<button data-note-id="'+el.id+'" type="button" class="deletenotetbtn{{$grid_id}} btn btn-danger"><i class="fa fa-trash"></i></button>';
                    notes_html += '</div>';
                    @endif
                    notes_html += '</div>';
                });
                
                
                $("#notes_results{{ $grid_id }}").html(notes_html);
               
             
            }
            });
            $("#notes_notice{{ $grid_id }}").hide();
            $("#notes_form{{ $grid_id }}").show();
        }else{
            var notes_html = '';
            $("#notes_notice{{ $grid_id }}").show();
            $("#notes_form{{ $grid_id }}").hide();
            $("#notes_results{{ $grid_id }}").html(notes_html);
        }
  }
  
class notesToolPanel{{ $grid_id }} {
  init(params) {
    this.eGui = document.createElement('div');

    this.eGui.classList.add("ag-column-panel");
    
    var notes_html = '';      
    
    notes_html += '<div class="ag-layouts-content" id="notes_panel{{ $grid_id }}">';
    
    notes_html += '<h4 class="w-100 p-2 m-0">Notes</h4>'; 
    notes_html += '<div id="notes_notice{{ $grid_id }}" class="w-100 p-2 m-0"><h6>Please select a record</h6></div>';  
    notes_html += '<div id="notes_form{{ $grid_id }}" style="display:none">';
    notes_html += '<div class="card">';
    notes_html += '<div class="card-body p-2">';
    notes_html += '<textarea class="form-control" id="record_note{{$grid_id}}"></textarea>';
    notes_html += '</div>';
    notes_html += '<div class="card-footer p-2 text-right">';
    notes_html += '<button type="button" class="btn btn-primary addnotetbtn{{$grid_id}}"><i class="fa fa-plus"></i></button>';
    notes_html += '</div>';
    notes_html += '</div>';
    notes_html += '</div>';
    notes_html += '<div id="notes_results{{ $grid_id }}"></div>';
    notes_html += '</div>';
    
    this.eGui.innerHTML = notes_html
    // calculate stats when new rows loaded, i.e. onModelUpdated
    const updateNotes{{ $grid_id }} = () => {
        this.getNotes(params);
     // this.eGui.innerHTML = this.getNotes(params);
    };
    params.api.addEventListener('rowSelected', updateNotes{{ $grid_id }});
  }

  getGui() {
    return this.eGui;
  }

  refresh() {}

  getNotes(params) {
    getNotes{{$grid_id}}(params);
  }
}

var detailGridOptions = {
    
       
        accentedSort: true,
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
        @if($serverside_model)
            pagination: true,
            paginationAutoPageSize:true,
            rowModelType: 'serverSide',
            serverSideInfiniteScroll: true,
            @if($pinned_totals)
            pinnedBottomRowData: [{}],
            @endif
        @else
            @if($has_sort)
            rowDragEntireRow: true,
            rowDragManaged: false,
            suppressMoveWhenRowDragging: true,
            @endif
            @if($pinned_totals)
            pinnedBottomRowData: [{}],
            @endif
        @endif
        getMainMenuItems: getMainMenuItems{{$grid_id}},
        getContextMenuItems: getContextMenuItems{{$grid_id}},
        rowHeight: 26,
        headerHeight: 30,
        @if(session('role_level') != 'Admin')
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
            //cellRenderer: pinnedTotalRenderer{{$grid_id}},
            filter: 'agNumberColumnFilter',
            cellClass: 'ag-right-aligned-cell',
            headerClass: 'ag-right-aligned-header',
            //headerClass: 'ag-right-aligned-header',
            //cellClass: 'ag-cell-numeric-right',
           // valueFormatter: currencyValueFormatter,
        },
        
        decimalField: {
            filter: 'agNumberColumnFilter',
            enableValue: true,
            enableRowGroup: false,
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
            enableValue: true,
            enableRowGroup: true,
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
            menuTabs: ['filterMenuTab','columnsMenuTab','generalMenuTab'],
            @else
            menuTabs: ['filterMenuTab'],
            @endif
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
        
        @if(session('role_level') == 'Admin')  
        sideBar: {
        toolPanels: [
            
            {
                id: 'notes',
                labelDefault: 'Notes <span id="notescount{{ $grid_id }}"></span>',
                labelKey: 'notes',
                iconKey: 'forms_icon',
                toolPanel: 'notesToolPanel{{ $grid_id }}',
            },
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
       
        rowSelection: 'single',
        onGridReady: detailGridReady,
        
        components: {
            notesToolPanel{{ $grid_id }}: notesToolPanel{{ $grid_id }},
        },    
        onFilterChanged: function(){
     
       
      
        },
        onModelUpdated:  function(){
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
           
            
            if(!event.node.isSelected()){
                var deselected = event.node.data;
                if(deselected.{{$db_key}} == window['selectedrow_{{ $grid_id }}'].rowId){
                  
                    window['selectedrow_{{ $grid_id }}'] = null;
                    
                    window['selectedrow_node_{{ $grid_id }}'] = null;
                   
                    onDetailRowDeselected();
                }
            }
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
                
               
                $("#moduleleft_menu{{ $master_grid_id }}").addClass('d-none');
                $("#moduleleft_menu{{ $grid_id }}").removeClass('d-none');
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
                    target[element] += Number(parseInt(rowNode.data[element]).toFixed(2));
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
                        showSpinner(spinner_ref);
                    },
                    success: function (result) { 
                        
                        hideSpinner(spinner_ref);  
                    
                      refresh_detail_grid{{ $grid_id }}();
        	          // window['grid_{{$master_grid_id}}'].gridOptions.refresh();
                     //  window['grid_{{$master_grid_id}}'].gridOptions.api.getDetailRowData();
                    }, 
                    error: function(){
                        hideSpinner(spinner_ref);    
                    }
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
                
                if(movingData.rowId != overData.rowId){
                    
                    var immutableStore = window['detail_row_data{{$master_grid_id}}'];
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
    

@if($check_doctype)
    doctypes = {!! json_encode($doctypes) !!};
@endif
var selected = window['selectedrow_{{ $grid_id }}'];
selected_doctype_el = null;


detail_row_selected{{$master_grid_id}} = true;


@if(!empty($moduleleft_menu) && count($moduleleft_menu) > 0) 
    moduleleft{{ $grid_id }}.refresh();
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
@if(!empty($moduleleft_menu) && count($moduleleft_menu) > 0) 
{!! button_menu_selected($module_id, 'moduleleft', $grid_id, 'selected', false) !!}
@endif
}

function onDetailRowDeselected(){
        @if(!empty($moduleleft_menu) && count($moduleleft_menu) > 0) 
        {!! button_menu_selected($module_id, 'moduleleft', $grid_id, 'deselected', false) !!}
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
        {{ $grid_id }}Import = () => {
         sidebarform('{{ $menu_route }}import' , '/{{ $menu_route }}/import');
        }
    @endif
    
    @if($access['is_add'])
        {{ $grid_id }}Add = () => {
           
            @if($menu_route == 'pbx_menu')
                sidebarform('{{ $menu_route }}add' , 'pbxmenuedit', 'PBX menu - Add');
            @elseif(!empty(request()->account_id) && $documents_module)
                transactionDialog('{{ $menu_route }}add' , '/{{ $menu_route }}/edit?account_id={{request()->account_id}}', 'Documents - Add', '80%', 'auto');
            @elseif($documents_module)
            transactionDialog('{{ $menu_route }}add' , '/{{ $menu_route }}/edit/', 'Documents - Add', '80%', 'auto');
            @elseif(!empty($request_get))
                if(master_rowid{{$master_grid_id}}){
                    sidebarform('{{ $menu_route }}add' , '/{{ $menu_route }}/edit?{{ $master_module_key }}='+master_rowid{{$master_grid_id}}+'&layout_id='+window['detail_layout_id{{ $master_grid_id }}']  , '{!! $menu_name !!} - Add','', '60%');
                }else{
                    sidebarform('{{ $menu_route }}add' , '/{{ $menu_route }}/edit?{!! $request_get !!}'+'&layout_id='+window['detail_layout_id{{ $master_grid_id }}'] , '{!! $menu_name !!} - Add','', '60%');
                }
            @elseif(!$documents_module)
                if(master_rowid{{$master_grid_id}}){
                    sidebarform('{{ $menu_route }}add' , '/{{ $menu_route }}/edit?{{ $master_module_key }}='+master_rowid{{$master_grid_id}}+'&layout_id='+window['detail_layout_id{{ $master_grid_id }}'] , '{!! $menu_name !!} - Add','', '60%');
                }else{
                    sidebarform('{{ $menu_route }}add' , '/{{ $menu_route }}/edit'+'?layout_id='+window['detail_layout_id{{ $master_grid_id }}'] , '{!! $menu_name !!} - Add','', '60%');
                }
            @endif
            
        }
    @endif
    
    
    @if($access['is_edit'])
        {{ $grid_id }}Edit = () => {
         
            var selected = window['selectedrow_{{ $grid_id }}'];
            @if($documents_module)
                transactionDialog('{{ $menu_route }}edit', '/{{ $menu_route }}/edit/'+ selected.rowId, 'Documents - Edit', '80%', '100%');
            @else
                sidebarform('{{ $menu_route }}edit' , '/{{ $menu_route }}/edit/'+ selected.rowId+'?layout_id='+window['detail_layout_id{{ $master_grid_id }}'], '{!! $menu_name !!} - Edit', '{!! $form_description !!}','60%');
            @endif
        }
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
          
        });
    @endif
    
    @if($access['is_add'])
        {{ $grid_id }}Duplicate = () => {
            var selected = window['selectedrow_{{ $grid_id }}'];
            gridAjaxConfirm('/{{ $menu_route }}/duplicate', 'Duplicate record?', {"id" : selected.rowId}, 'post');
        }
    @endif
    
   
    @if($access['is_delete'])
        
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
        }
    @endif


    
    @if($access['is_view'])
        {{ $grid_id }}Export = () => {
            detail_grid_api.api.exportDataAsExcel({fileName: '{{$master_grid_title}}.xlsx'});
        }
    @endif      
    
    

    
    
</script>

@parent
@endsection
@section('page-styles')
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
@endsection