@extends((!empty($is_primary_tab)) ? '__app.layouts.blank' : (( request()->ajax() ) ? '__app.layouts.ajax' : '__app.layouts.app') )

@php



if(empty(request()->all()) ||count(request()->all()) == 0){
    $request_get = '';
}else{
    $request_get = http_build_query(request()->all());
}

@endphp
@include('__app.grids.dev.partials.toolbar')
@include('__app.grids.partials.cards', ['module_cards' => $module_cards,'module_id'=>$module_id,'grid_id'=>$grid_id])
@include('__app.grids.grid_sidebar')

@if($master_detail)
@include('__app.grids.dev.grid_details', $detail_grid)
@endif
@if(empty($is_primary_tab))
@section('content')
@else
@section('primary_tab')
@endif
@if($master_detail)
@yield('detail_content')
@endif

<style id="conditional_styles{{$module_id}}">
@foreach($cell_styles as $cell_style)
.ag-row:not(.ag-row-selected) .ag-cell-style{{$cell_style->id}} {
    @if($cell_style->text_bold)
    font-weight: bold;
    @endif
    @if($cell_style->text_italics)
    font-style: italic;
    @endif
    @if($cell_style->background_color)
    background-color: {!! $cell_style->background_color !!}  !important;
    @endif
    @if($cell_style->text_color)
    color: {!! $cell_style->text_color !!}  !important;
    @endif
}
@endforeach
@foreach($row_styles as $row_style)
.ag-row:not(.ag-row-selected).ag-row-style{{$row_style->id}} {
    @if($row_style->text_bold)
    font-weight: bold;
    @endif
    @if($row_style->text_italics)
    font-style: italic;
    @endif
    @if($row_style->background_color)
    background-color: {!! $row_style->background_color !!}  !important;
    @endif
    @if($row_style->text_color)
    color: {!! $row_style->text_color !!} !important;
    @endif
}
@endforeach
@if($master_detail)
@if($detail_grid['cell_styles'])
@foreach($detail_grid['cell_styles'] as $cell_style)
.ag-row:not(.ag-row-selected) .ag-cell-style{{$cell_style->id}} {
    @if($cell_style->text_bold)
    font-weight: bold;
    @endif
    @if($cell_style->text_italics)
    font-style: italic;
    @endif
    @if($cell_style->background_color)
    background-color: {!! $cell_style->background_color !!}  !important;
    @endif
    @if($cell_style->text_color)
    color: {!! $cell_style->text_color !!}  !important;
    @endif
}
@endforeach
@endif
@if($detail_grid['row_styles'])
@foreach($detail_grid['row_styles'] as $row_style)
.ag-row:not(.ag-row-selected).ag-row-style{{$row_style->id}} {
    @if($row_style->text_bold)
    font-weight: bold;
    @endif
    @if($row_style->text_italics)
    font-style: italic;
    @endif
    @if($row_style->background_color)
    background-color: {!! $row_style->background_color !!}  !important;
    @endif
    @if($row_style->text_color)
    color: {!! $row_style->text_color !!} !important;
    @endif
}
@endforeach
@endif
@endif
</style>
<style>

/*
.ag-theme-alpine {
    --ag-foreground-color: rgb(126, 46, 132);
    --ag-background-color: rgb(249, 245, 227);
    --ag-header-foreground-color: rgb(204, 245, 172);
    --ag-header-background-color: rgb(209, 64, 129);
    --ag-odd-row-background-color: rgb(0, 0, 0, 0.03);
    --ag-header-column-resize-handle-color: rgb(126, 46, 132);

    --ag-font-size: 17px;
    --ag-font-family: monospace;
}
*/

.ag-theme-alpine {
    --ag-font-size: 11px;
}
.ag-header-cell-menu-button{
display: none !important;
}
.detail-expand-field{
    background-color:#eaeaea;
    color:#000;
    font-weight: 500;
}
.name-field{
    background-color:#eaeaea;
    color:#000;
    font-weight: 500;
}



.ag-cell-focus{
    border:none !important;
}
/*.ag-unselectable {*/
/*     transition: top 100s, left 100s;*/
/*}*/
.ag-header-cell:hover .edit_column_header{
    display: block;
}

.ag-header-cell.editing .edit_column_header{
    display: none;
}
.edit_column_header{
    display: none;
}
.space-right{
    margin-right:11px;
}
.space-right2{
    margin-right:14px;
}
.grid-title {
    font-family: "Titillium Web", Arial, Sans-serif;
}
.ag-popup-editor .form-group{
    margin-bottom:0px !important;
}
/*
.ag-theme-alpine .ag-row.ag-row-level-0 {
  background-color: #dddbdb;
}

.ag-theme-alpine .ag-row.ag-row-level-1 {
  background-color: #e6e5e5b3;
}
*/
@media print {
  body * {
    visibility: hidden;
  }
  #grid_{{ $grid_id }}, #grid_{{ $grid_id }} * {
    visibility: visible;
  }
  #grid_{{ $grid_id }} {
    position: absolute;
    left: 0;
    top: 0;
  }
}



.hover-over {
  background-color: #e5e5ff;
}
.ag-cell-inline-editing{
    color:#000 !important;
    font-weight:normal !important;
}


.communications_btn {
    height: 26px !important;
    padding: 1px 6px !important;
}

.communications_btn .e-caret{
    height: 6px !important;
}
/*
.communications_btn .e-caret{
    display:none !important;
}
*/
#numbersearch{
text-align: left;
    height: 26px;
    line-height: 26px;    
}
#grid_{{ $grid_id }}.edit_mode .ag-side-bar {
    position: absolute;
    right: 0;
    height: 100%;
}
#grid_{{ $grid_id }}.edit_mode .ag-root .ag-header{
    display: none !important;
}
#grid_{{ $grid_id }}.edit_mode .ag-root .ag-floating-top{
    display: none !important;
}
#grid_{{ $grid_id }}.edit_mode .ag-root .ag-body-viewport{
    display: none !important;
}
#grid_{{ $grid_id }}.edit_mode .ag-root .ag-floating-bottom{
    display: none !important;
}
#grid_{{ $grid_id }}.edit_mode .ag-root .ag-body-horizontal-scroll{
    display: none !important;
}
#grid_{{ $grid_id }}.edit_mode .ag-root .ag-overlay{
    display: none !important;
}
#grid_{{ $grid_id }}.edit_mode .ag-root .ag-header{
    display: none !important;
}
#gridcontainer{{ $grid_id }}{
display: flex;
flex-direction: column;
height: 100%;
}




.e-btn .e-btn-icon, .e-css.e-btn .e-btn-icon {
    display: inline-block;
    font-size: 12px;
    vertical-align: middle;
    width: 1em;
}
.admin_buttons .k-button{
    background-color:#ccc;    
}
.ag-cell-expandable .ag-icon{
    font-weight: bold;
    font-size: 18px;
    line-height: 18px;
}
/*
.ag-cell-expandable .ag-group-value{
    display:none;
}
*/

.field-levelaccess{
color:lightgray !important;    
}
#extrabtnsdropdown{{ $grid_id }}{
margin-top: 0 !important;
padding: 0px !important;
}
#extrabtnsdropdown{{ $grid_id }} button{
width:100%;   
justify-content: left;
border-radius:0px !important;
}
#extrabtnsdropdown{{ $grid_id }} a{
width:100%;   
justify-content: left;
border-radius:0px !important;
}
#extrabtnsdropdown{{ $grid_id }} button span{
margin-right: 5px;
}
#extrabtnsdropdown{{ $grid_id }} a span{
margin-right: 5px;
}



    
#gridheadertoolbar{{ $grid_id }}{
    border-bottom: 1px solid #babfc7 !important;
}
/*
#gridheadertoolbar{{ $grid_id }}, #gridheadertoolbar{{ $grid_id }} .e-toolbar-items {
    background: rgb(25 69 126 / 60%);
}
*/
#detailheadertoolbar{{ $grid_id }}{
    border-bottom: 1px solid #babfc7 !important;
}
/*
#detailheadertoolbar{{ $grid_id }}, #detailheadertoolbar{{ $grid_id }} .e-toolbar-items {
    background: rgb(25 69 126 / 60%) !important;
}
*/

</style>

@yield('module_sidebar')

@if(!empty($layout_name))
<span class="layout_name d-none">{{$layout_name}}</span>
@endif

<div class="col p-0 m-0 h-100 layout_mode gridcontainer" id="gridcontainer{{ $grid_id }}">
<div class="gridheader">
@yield('layouts_toolbar')

@yield('gridcards')
</div>





<div id="grid_{{ $grid_id }}" class="ag-theme-alpine layout_mode aggrid" style="height: 100%!important;"></div>





</div>

@endsection


@section('scripts')
@endsection

@section('page-scripts')
@parent

<script>
first_row_select = false;

function onlyUnique(value, index, array) {
  return self.indexOf(value) === index;
}

@if($tree_data)
//treedata sort
var potentialParent = null;

function onManagedRowDragEnter{{ $grid_id }}(args){
    var event = args.event;
   
    dragcolId = $(event.target).closest('.ag-cell').attr('col-id');
   
}

function onManagedRowDragEnd{{ $grid_id }}(event){
   
    if(dragcolId == 'sort_order'){
  
        return onRowDragEnd{{ $grid_id }}(event);
    }else{
  
        return onTreeRowDragEnd{{ $grid_id }}(event);
    }
}

function onManagedRowDragMove{{ $grid_id }}(event){
    if(dragcolId == 'sort_order'){
        return onRowDragMove{{ $grid_id }}(event);
    }else{
        return onTreeRowDragMove{{ $grid_id }}(event);
    }
   
}

function onManagedRowDragLeave{{ $grid_id }}(event){
    if(dragcolId == 'sort_order'){
       
    }else{
        return onTreeRowDragLeave{{ $grid_id }}(event);
    }
}

function onTreeRowDragMove{{ $grid_id }}(event) {
  setPotentialParentForNode(event.api, event.overNode);
}

function onTreeRowDragLeave{{ $grid_id }}(event) {
  // clear node to highlight
  setPotentialParentForNode(event.api, null);
}

function onTreeRowDragEnd{{ $grid_id }}(event) {

  if (!potentialParent) {
    return;
  }

  var movingData = event.node.data;

  // take new parent path from parent, if data is missing, means it's the root node,
  // which has no data.
  var newParentPath = potentialParent.data ? potentialParent.data.hierarchy : [];
  var needToChangeParent = !arePathsEqual(newParentPath, movingData.hierarchy);

  // check we are not moving a folder into a child folder
  var invalidMode = isSelectionParentOfTarget(event.node, potentialParent);
  if (invalidMode) {
   
  }

  if (needToChangeParent && !invalidMode) {
    var updatedRows = [];
    moveToPath(newParentPath, event.node, updatedRows);
 
    gridOptions.api.applyTransaction({
      update: updatedRows,
    });
    gridOptions.api.clearFocusedCell();
  }

 
  if(potentialParent.data.{{$db_key}}){
   var post_data =  {id: movingData.{{$db_key}}, value: potentialParent.data.{{$db_key}}, field: 'parent_id' };    

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
  
  // clear node to highlight
  setPotentialParentForNode(event.api, null);
}

function moveToPath(newParentPath, node, allUpdatedNodes) {
  // last part of the file path is the file name
  var oldPath = node.data.hierarchy;
  var fileName = oldPath[oldPath.length - 1];
  var newChildPath = newParentPath.slice();
  newChildPath.push(fileName);

  node.data.hierarchy = newChildPath;

  allUpdatedNodes.push(node.data);

  if (node.childrenAfterGroup) {
    node.childrenAfterGroup.forEach(function (childNode) {
      moveToPath(newChildPath, childNode, allUpdatedNodes);
    });
  }
}

function isSelectionParentOfTarget(selectedNode, targetNode) {
  var children = selectedNode.childrenAfterGroup;
  for (var i = 0; i < children.length; i++) {
    if (targetNode && children[i].key === targetNode.key) return true;
    isSelectionParentOfTarget(children[i], targetNode);
  }
  return false;
}

function arePathsEqual(path1, path2) {
  if (path1.length !== path2.length) {
    return false;
  }

  var equal = true;
  path1.forEach(function (item, index) {
    if (path2[index] !== item) {
      equal = false;
    }
  });

  return equal;
}

function setPotentialParentForNode(api, overNode) {
  
  var newPotentialParent;
  if (overNode) {
    newPotentialParent = overNode;
  } else {
    newPotentialParent = null;
  }

  var alreadySelected = potentialParent === newPotentialParent;
  if (alreadySelected) {
    return;
  }

  // we refresh the previous selection (if it exists) to clear
  // the highlighted and then the new selection.
  var rowsToRefresh = [];
  if (potentialParent) {
    rowsToRefresh.push(potentialParent);
  }
  if (newPotentialParent) {
    rowsToRefresh.push(newPotentialParent);
  }

  potentialParent = newPotentialParent;

  refreshRows(api, rowsToRefresh);
}

function refreshRows(api, rowsToRefresh) {
  var params = {
    // refresh these rows only.
    rowNodes: rowsToRefresh,
    // because the grid does change detection, the refresh
    // will not happen because the underlying value has not
    // changed. to get around this, we force the refresh,
    // which skips change detection.
    force: true,
  };
  api.refreshCells(params);
}




@endif

overData = false;

@if(session('role_level') == 'Admin')
function onRowDragEnd{{ $grid_id }} (e) {

     
        
       
    if(e.node && e.node.group == false && e.node.data && e.node.data.id && updateDragData){
        var start_id = e.node.data.id;
        var target_id = updateDragData.id;
        var sort_data = JSON.stringify({"start_id" : start_id, "target_id" : target_id});
        
        if($("#tabs_container").length > 0){
            var spinner_ref = "#"+$("#gridcontainer{{ $grid_id }}").closest(".gridtabid").attr('id');
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
               
	            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                hideSpinner(spinner_ref);
              
	            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            },
        });
    }
}

function onRowDragMove{{ $grid_id }}(event) {
    var immutableStore = row_data{{ $grid_id }};
    var movingNode = event.node;
    var overNode = event.overNode;
    
    var rowNeedsToMove = movingNode !== overNode;
    
    
    if (rowNeedsToMove) {
        // the list of rows we have is data, not row nodes, so extract the data
        var movingData = movingNode.data;
       
        overData = overNode.data;
       
        if(movingData.rowId != overData.rowId){
            updateDragData = overData;
            var fromIndex = findWithAttr(immutableStore,'id',movingData.id);
            var toIndex = findWithAttr(immutableStore,'id',overData.id);
            
            
            var newStore = immutableStore.slice();
            moveInArray{{ $grid_id }}(newStore, fromIndex, toIndex);
            
            
            immutableStore = newStore;
            window['grid_{{ $grid_id }}'].gridOptions.api.setRowData(newStore);
            
            window['grid_{{ $grid_id }}'].gridOptions.api.clearFocusedCell();
        }
    }

    function moveInArray{{ $grid_id }}(arr, fromIndex, toIndex) {
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
    

/** CONTEXT MENUS **/

/* button right click context menu*/
ej.base.enableRipple(true);

// layouts contextmenu
function dropdowntargetrender(args){
    
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
}




    
 /** LAYOUT FUNCTIONS **/ 
    function layout_save{{ $grid_id }}(save_as_duplicate = false, type = 'default'){
        
        var displayed_cols = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getAllDisplayedColumns();
        let displayed_col_fields = displayed_cols.map(a => a.colId);
       
        var layout = {};
        layout.colState =window['grid_{{ $grid_id }}'].gridOptions.columnApi.getColumnState();
        $(layout.colState).each(function(i, obj){
         
            if($.inArray(obj.colId, displayed_col_fields) !== -1){
               
                layout.colState[i].hide = false;
            }else{
                layout.colState[i].hide = true;
            }
        });
       
        
       
     
        layout.groupStorage =window['expanded_groups{{$grid_id}}'];
        layout.filterState =window['grid_{{ $grid_id }}'].gridOptions.api.getFilterModel();
        layout.searchtext = searchtext{{ $grid_id }}.value;
       
        var pivot = {};
        var pivotMode =window['grid_{{ $grid_id }}'].gridOptions.columnApi.isPivotMode();
        if(pivotMode){
            var pivot_mode = 1;
            // pivot.colState =window['grid_{{ $grid_id }}'].gridOptions.columnApi.getPivotColumns();
            pivot.colState =layout.colState;
          
        }else{
            var pivot_mode = 0;
        }
      
           
        var data = {type: type, layout : layout, grid_reference: 'grid_{{ $grid_id }}', layout_id: window['layout_id{{ $grid_id }}'],pivot: pivot, pivot_mode: pivot_mode,  query_string: {!! $query_string !!},save_as_duplicate: save_as_duplicate};
        @if($master_detail)
            if(detail_grid_api){
                
                var detail_layout = {};
                detail_layout.colState = detail_grid_api.columnApi.getColumnState();
                detail_layout.groupState = detail_grid_api.columnApi.getColumnGroupState();
                detail_layout.filterState = detail_grid_api.api.getFilterModel();
                data.detail_layout = detail_layout;
            }
        @endif
       
       
        $.ajax({
            type: 'post',
            url: '{{ url($menu_route."/aggrid_layout_save") }}',
            data: data,
    		success: function(data) { 
    		    if(save_as_duplicate){
    		      @if(session('role_level') == 'Admin')
    		   
                
                window['gridlayouts_{{ $grid_id }}'].items = JSON.parse(data.menu);
                window['gridlayouts_{{ $grid_id }}'].refresh();
                        refresh_layout_context_menus{{$grid_id}}();
                @endif
    		    }
    		  
    		    window['layout_id{{ $grid_id }}'] = data.layout_id;
    		    toastNotify(data.message,data.status);
    		}
    	});
    }
 
    function layout_load{{$grid_id}}(layout_id, first_load = 0){
        
       
        
       
        if(first_load){
               
                var data = {!! json_encode($layout_init) !!};
               
               
    		    if(data.name){
    		       $("#layoutname{{$grid_id}}").html(': '+data.name);  
    		       }
                var state = JSON.parse(data.settings);
    		  
    		   
                
                @if(is_superadmin() && !str_contains($db_table,'crm_task'))
                $("#layout_tracking_disable{{ $grid_id }}").attr('data-layout-id',layout_id);
                $("#layout_tracking_enable{{ $grid_id }}").attr('data-layout-id',layout_id);
                if(data.layout_tracking){
                    $("#layout_tracking_disable{{ $grid_id }}").show();
                    $("#layout_tracking_enable{{ $grid_id }}").hide();
                }else{
                    $("#layout_tracking_disable{{ $grid_id }}").hide();
                    $("#layout_tracking_enable{{ $grid_id }}").show();
                }
                @endif
               
               
                if(data.auto_group_col_sort){
                  
                    window['grid_{{ $grid_id }}'].gridOptions.autoGroupColumnDef.sort = data.auto_group_col_sort;    
                }
                if(data.columnDefs){
                   window['grid_{{ $grid_id }}'].gridOptions.api.setColumnDefs(data.columnDefs);
                    sortSidebarColumns{{ $grid_id }}();
                }
                
                if(data.pivot_mode == 1){
                   window['grid_{{ $grid_id }}'].gridOptions.columnApi.setPivotMode(true);
                   
                }else{
                   window['grid_{{ $grid_id }}'].gridOptions.columnApi.setPivotMode(false);
                }
              
                if(data.is_report){
                   report_toolpanel_disable{{$grid_id}}(false);
                }else{
                   report_toolpanel_disable{{$grid_id}}(true);
                }
            
               
                if(state){
                    if(state.colState){
                        colstate_arr = state.colState;
                        if(!Array.isArray(colstate_arr)){
                        colstate_arr = Object.values(state.colState)
                        }
                        
                        window['grid_{{ $grid_id }}'].gridOptions.columnApi.applyColumnState({state:colstate_arr,applyOrder: true});
                    }
                    
                    
                  
                   
               
                    if(state.filterState){
                       window['grid_filterState_{{ $grid_id }}'] = state.filterState;
                       window['grid_{{ $grid_id }}'].gridOptions.api.setFilterModel(state.filterState);
                    }
                    if(state.searchtext){
                        searchtext{{ $grid_id }}.value = state.searchtext;
                    }
                }
               // window['grid_{{ $grid_id }}'].gridOptions.columnApi.autoSizeAllColumns();
               
                setTimeout(function(){
                  
                    if(searchtext{{ $grid_id }}.value == '' || searchtext{{ $grid_id }}.value == null){
                    searchtext{{ $grid_id }}.value = ' ';
                    window['grid_{{ $grid_id }}'].gridOptions.api.setQuickFilter(' ');
                    @if($serverside_model)
                    window['grid_{{ $grid_id }}'].gridOptions.refresh();
                    @endif
                    }else{
                    var search_val = searchtext{{ $grid_id }}.value;
                    if(search_val == 'Yes'){
                        search_val = '1';    
                    }
                    if(search_val == 'No'){
                        search_val = '0';    
                    }
                    window['grid_{{ $grid_id }}'].gridOptions.api.setQuickFilter(search_val);
                    @if($serverside_model)
                    window['grid_{{ $grid_id }}'].gridOptions.refresh();
                    @endif
                    }
                    
                    window['grid_{{ $grid_id }}'].gridOptions.columnApi.autoSizeAllColumns();
                },200);
                
                if(state && state.groupStorage){
                    expandNodes{{ $grid_id }}(state.groupStorage);
                }
                
                @if(session('role_level') == 'Admin')
                $('#layoutsbtn_delete{{ $grid_id }}').removeAttr('disabled');
                $('#layoutsbtn_duplicate{{ $grid_id }}').removeAttr('disabled');
                $('#layoutsbtn_save{{ $grid_id }}').removeAttr('disabled');
                
                @endif
                
              
                
                @if($master_detail)
                window['detail_col_defs{{$grid_id}}'] = data.detail_col_defs;
                window['detail_settings{{$grid_id}}'] = data.detail_settings;
                
                detail_layout_load{{ $grid_id }}();
                @endif
                
        }else{
        
        filter_cleared{{ $grid_id }} = 0;
  
    	var ajax_data = {aggrid: 1, layout_id: layout_id, grid_reference: 'grid_{{ $grid_id }}' };
   
        $.ajax({
            type: 'post',
            url: '{{ url($menu_route."/aggrid_layout_data") }}',
            data: ajax_data,
            beforeSend: function(){
                showSpinner("#grid_{{$grid_id}}");
                $('#layoutsbtn_delete{{ $grid_id }}').attr('disabled','disabled');
                $('#layoutsbtn_duplicate{{ $grid_id }}').attr('disabled','disabled');
                $('#layoutsbtn_save{{ $grid_id }}').attr('disabled','disabled');
            },
    		success: function(data) { 
              
                @if($master_detail)
                window['detail_col_defs{{$grid_id}}'] = data.detail_col_defs;
                window['detail_settings{{$grid_id}}'] = data.detail_settings;
                
    		  
                @endif
    		    @if(session('role_level') == 'Admin')
                window['gridlayouts_{{ $grid_id }}'].items = JSON.parse(data.menu);
                window['gridlayouts_{{ $grid_id }}'].refresh();
                        refresh_layout_context_menus{{$grid_id}}();
                @endif
                var state = JSON.parse(data.settings);
              
                if(data.is_report){
                   report_toolpanel_disable{{$grid_id}}(false);
                }else{
                   report_toolpanel_disable{{$grid_id}}(true);
                }
                @if(is_superadmin() && !str_contains($db_table,'crm_task'))
                $("#layout_tracking_disable{{ $grid_id }}").attr('data-layout-id',layout_id);
                $("#layout_tracking_enable{{ $grid_id }}").attr('data-layout-id',layout_id);
                if(data.layout_tracking){
                    $("#layout_tracking_disable{{ $grid_id }}").show();
                    $("#layout_tracking_enable{{ $grid_id }}").hide();
                }else{
                    $("#layout_tracking_disable{{ $grid_id }}").hide();
                    $("#layout_tracking_enable{{ $grid_id }}").show();
                }
                @endif
                   if(data.pivot_mode == 1){
                   window['grid_{{ $grid_id }}'].gridOptions.columnApi.setPivotMode(true);
                   
                }else{
                   window['grid_{{ $grid_id }}'].gridOptions.columnApi.setPivotMode(false);
                }
               
                if(data.auto_group_col_sort){
                 
                    window['grid_{{ $grid_id }}'].gridOptions.autoGroupColumnDef.sort = data.auto_group_col_sort;    
                }
                if(data.columnDefs){
                   window['grid_{{ $grid_id }}'].gridOptions.api.setColumnDefs(data.columnDefs);
                    sortSidebarColumns{{ $grid_id }}();
                }
                
               
              
                if(state){
                
                if(state.colState){
                    colstate_arr = state.colState;
                    if(!Array.isArray(colstate_arr)){
                    colstate_arr = Object.values(state.colState)
                    }
                 
                    window['grid_{{ $grid_id }}'].gridOptions.columnApi.applyColumnState({state:colstate_arr});
                }
                
               
               
                if(state.filterState){
                   window['grid_filterState_{{ $grid_id }}'] = state.filterState;
                   window['grid_{{ $grid_id }}'].gridOptions.api.setFilterModel(state.filterState);
                }
                 if(state.searchtext){
                        searchtext{{ $grid_id }}.value = state.searchtext;
                    }
                    
                     
                if(state && state.groupStorage){
                expandNodes{{ $grid_id }}(state.groupStorage);
                }
                }
                window['grid_{{ $grid_id }}'].gridOptions.columnApi.autoSizeAllColumns();
               
                
                setTimeout(function(){
                    
                    if(searchtext{{ $grid_id }}.value == '' || searchtext{{ $grid_id }}.value == null){
                    searchtext{{ $grid_id }}.value = ' ';
                    window['grid_{{ $grid_id }}'].gridOptions.api.setQuickFilter(' ');
                    @if($serverside_model)
                    window['grid_{{ $grid_id }}'].gridOptions.refresh();
                    @endif
                    }else{
                    var search_val = searchtext{{ $grid_id }}.value;
                    if(search_val == 'Yes'){
                        search_val = '1';    
                    }
                    if(search_val == 'No'){
                        search_val = '0';    
                    }
                    window['grid_{{ $grid_id }}'].gridOptions.api.setQuickFilter(search_val);
                    @if($serverside_model)
                    window['grid_{{ $grid_id }}'].gridOptions.refresh();
                    @endif
                    }
                },200);
                
               
                window['layout_id{{ $grid_id }}'] = layout_id;
                @if(session('role_level') == 'Admin')
                $('#layoutsbtn_delete{{ $grid_id }}').removeAttr('disabled');
                $('#layoutsbtn_duplicate{{ $grid_id }}').removeAttr('disabled');
                $('#layoutsbtn_save{{ $grid_id }}').removeAttr('disabled');
                
                @endif
              
                if(data.name){
                $("#layoutname{{$grid_id}}").html(': '+data.name); 
                }else{
                $('#layoutname{{$grid_id}}').html('');
                }
              
              
                
               
               hideSpinner("#grid_{{$grid_id}}");
               
               
                @if($master_detail)
                window['detail_col_defs{{$grid_id}}'] = data.detail_col_defs;
                window['detail_settings{{$grid_id}}'] = data.detail_settings;
                
                detail_layout_load{{ $grid_id }}();
                @endif
		    },
            error: function(jqXHR, textStatus, errorThrown) {
                toastNotify('An error occured', 'error');
                hideSpinner("#grid_{{$grid_id}}");
            },
    	});
        }
        
        
    }


    function layout_init{{$grid_id}}(){
       
        window['layout_id{{ $grid_id }}'] = {{$grid_layout_id}};
      
        @if($grid_layout_type == 'default_new')
        if(default_layout_saved == 0){
            default_layout_saved = 1;
            layout_save{{ $grid_id }}();
        }
     
        @else
        
        layout_load{{$grid_id}}(window['layout_id{{ $grid_id }}'],1);
        @endif
    }
    
    function layout_delete(layout_id = false){
        if(layout_id){
            $.ajax({
    		url: '/delete_grid_config/'+layout_id,
    		contentType: false,
    		processData: false,
    		success: function(data) { 
    		   
                toastNotify('View deleted.','success', false);
                if(data.default_id){
                    layout_load{{$grid_id}}(data.default_id)
                }
            }
            });
        }
    }   
            
    loaded_from_args = 0;
    @if(!empty($moduleleft_menu) && count($moduleleft_menu) > 0)   
    var moduleleftMenuItems = @php echo json_encode($moduleleft_menu); @endphp;
    // top_menu initialization
    var moduleleft{{ $grid_id }} = new ej.navigations.Menu({
        items: moduleleftMenuItems,
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
        },
        beforeItemRender: function(args){
            
            var selected = window['selectedrow_{{ $grid_id }}'];
       
          
          
            var el = args.element;   
       
            
            @if($module_id == 1863)
          
            if(args.item.menu_id == 2865 && selected && selected.join_layout_id){
                args.item.title = selected.join_module_id;
                args.item.menu_title = selected.join_module_id;
            }
            @endif
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
           
            if(args.item.require_grid_id){
                if(window['selectedrow_{{ $grid_id }}'] && window['selectedrow_{{ $grid_id }}'].id){
                   
                    var grid_url = args.item.original_url + window['selectedrow_{{ $grid_id }}'].id; 
                    if(args.item.in_iframe == 1){
                        var grid_url = grid_url+'/1';    
                    }
                    if(args.item.data_target == 'transaction' || args.item.data_target == 'transaction_modal') {
                        $(el).find("a").attr("modal_url",grid_url);
                        $(el).find("a").attr("href","javascript:void(0)");
                    }else{
                        $(el).find("a").attr("href",grid_url);
                    }
                }
            }
            
        },
    },'#moduleleft_menu{{ $grid_id }}');
    @endif
    
    
  
    


    
    function get_sidebar_data{{$module_id}}(){
      
        
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
                
                if(data){
                
                    if(data.sidebar_layouts){
                        window['gridlayouts_{{ $grid_id }}'].items = data.sidebar_layouts;
                        window['gridlayouts_{{ $grid_id }}'].dataBind();
                        refresh_layout_context_menus{{$grid_id}}();
                       
                    }
                    
                    
                }
            }
        });
        @if($master_detail)
       
        get_sidebar_data{{ $detail_module_id }}();
        @endif
        
    }
   

    // PRINT
    
    function onBtnPrint() {
        const api = window['grid_{{ $grid_id }}'].gridOptions.api;
        
        setPrinterFriendly(api);
        
        setTimeout(function () {
            print();
            setNormal(api);
        }, 2000);
    }
    
    function setPrinterFriendly(api) {
        api.setDomLayout('print');
    }
    
    function setNormal(api) {
        api.setDomLayout(null);
    }
    @if($master_detail)
    //https://www.ag-grid.com/javascript-data-grid/filter-external/
    searching_detail = false;
    searching_detail_ids = [];
    
    function search_detail() {
        searching_detail = true;
        var post_data = {search: searchtext{{ $grid_id }}.value, search_key: '{{$detail_module_key}}' };  
        $.ajax({ 
            url: "/{{ $detail_menu_route }}/aggrid_detail_search", 
            type: 'post',
            data: post_data,
            success: function (result) {
              
                searching_detail_ids = result
                
                window['grid_{{ $grid_id }}'].gridOptions.api.setQuickFilter(null);
                window['grid_{{ $grid_id }}'].gridOptions.api.setFilterModel(null);
                window['grid_{{ $grid_id }}'].gridOptions.api.onFilterChanged();
                filter_cleared{{ $grid_id }} = 1;
               //return result;
            },
            error: function(jqXHR, textStatus, errorThrown) {
               
            },
        });
       
    }
    // apply detail filter with external filter
    function isExternalFilterPresent() {
       
        // if ageType is not everyone, then we are filtering
        return searching_detail;
    }
    
    function doesExternalFilterPass(node) {
       
        if (node.data) {
            if(searching_detail && searching_detail_ids.length > 0){
                var node_key = node.data.{{ ($master_module_key) ? $master_module_key : 'id' }};
                var node_key = node_key.toString();
                var pass = searching_detail_ids.includes(node_key);
                
                return pass;
            }
        }
        return true;
    }
    
    @endif
  


   
// wrap grid functions to avoid modal conflict
function reload_conditional_styles(module_id){
    
   // console.log('reload_conditional_styles');
    //console.log(module_id);
    
   $.ajax({
        type: 'get',
        url: '{{ url("getgridstyles") }}/'+module_id,
		success: function(data) { 
    //console.log(data);
            $("#conditional_styles"+module_id).html(data);
            $("#conditional_styles"+module_id).trigger('contentchanged');
		}
   });
}

$('#conditional_styles{{$module_id}}').bind('contentchanged', function() {
   // console.log('conditional_styles contentchanged');
  // do something after the div content has changed
  reload_grid_config{{$module_id}}();
});

function reload_grid_config{{$module_id}}(){
    // console.log('reload_grid_config');
    setTimeout(function() {
    layout_reload{{$module_id}}(window['layout_id{{ $grid_id }}']);
    },500);
   
     
}


     function layout_reload{{$module_id}}(layout_id){
  
    	var ajax_data = {aggrid: 1, layout_id: layout_id, grid_reference: 'grid_{{ $grid_id }}', query_string: {!! $query_string !!} };
      
        $.ajax({
            type: 'post',
            url: '{{ url($menu_route."/aggrid_layout_data") }}',
            data: ajax_data,
            beforeSend: function(){
                $('#layoutsbtn_delete{{ $grid_id }}').attr('disabled','disabled');
                $('#layoutsbtn_duplicate{{ $grid_id }}').attr('disabled','disabled');
                $('#layoutsbtn_save{{ $grid_id }}').attr('disabled','disabled');
                // save temp state
                var temp_state = {};
                temp_state.colState = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getColumnState();
                temp_state.groupState = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getColumnGroupState();
                
                temp_state.filterState = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterModel();
                temp_state.search = searchtext{{ $grid_id }}.value;
                //searchtext{{ $grid_id }}.value = '';
                window['gridstate_{{ $grid_id }}'] = temp_state;
            },
    		success: function(data) { 
    		  
    		    @if($master_detail)
                window['detail_col_defs{{$grid_id}}'] = data.detail_col_defs;
                window['detail_settings{{$grid_id}}'] = data.detail_settings;
                
    		    
      
                detail_layout_load{{$grid_id}}();
   
                @endif
    		    @if(session('role_level') == 'Admin')
    		    
                window['gridlayouts_{{ $grid_id }}'].items = JSON.parse(data.menu);
                window['gridlayouts_{{ $grid_id }}'].refresh();
                        refresh_layout_context_menus{{$grid_id}}();
                @endif
                if(data.columnDefs){
                   window['grid_{{ $grid_id }}'].gridOptions.api.setColumnDefs(data.columnDefs);
               
                }
                var state = JSON.parse(data.settings);
               
               if(data.auto_group_col_sort){
             
                    window['grid_{{ $grid_id }}'].gridOptions.autoGroupColumnDef.sort = data.auto_group_col_sort;    
                }
                 
                
                window['grid_{{ $grid_id }}'].gridOptions.columnApi.autoSizeAllColumns();
                    
                // restore temp state
              
               
                 if(window['gridstate_{{ $grid_id }}'].colState){ 
                    window['grid_{{ $grid_id }}'].gridOptions.columnApi.applyColumnState({state:window['gridstate_{{ $grid_id }}'].colState});
                }else if(state && state.colState){
                    
                    colstate_arr = state.colState;
                    if(!Array.isArray(colstate_arr)){
                    colstate_arr = Object.values(state.colState)
                    }
                   
                    window['grid_{{ $grid_id }}'].gridOptions.columnApi.applyColumnState({state:colstate_arr});
                }
               
                if(window['gridstate_{{ $grid_id }}'].groupState){
                    window['grid_{{ $grid_id }}'].gridOptions.columnApi.setColumnGroupState(window['gridstate_{{ $grid_id }}'].groupState);
                }else if(state && state.groupState){
                    window['grid_{{ $grid_id }}'].gridOptions.columnApi.setColumnGroupState(state.groupState);
                }
                
                if(window['gridstate_{{ $grid_id }}'].filterState){
                     window['grid_filterState_{{ $grid_id }}'] = state.filterState;
                    window['grid_{{ $grid_id }}'].gridOptions.api.setFilterModel(window['gridstate_{{ $grid_id }}'].filterState);
                }else if(state && state.filterState){
                    window['grid_{{ $grid_id }}'].gridOptions.api.setFilterModel(state.filterState);
                }
                if(window['gridstate_{{ $grid_id }}'].search){
                    searchtext{{ $grid_id }}.value = window['gridstate_{{ $grid_id }}'].search;
                }
                
                
                
                
               
                window['layout_id{{ $grid_id }}'] = layout_id;
                @if(session('role_level') == 'Admin')
                $('#layoutsbtn_delete{{ $grid_id }}').removeAttr('disabled');
                $('#layoutsbtn_duplicate{{ $grid_id }}').removeAttr('disabled');
                $('#layoutsbtn_save{{ $grid_id }}').removeAttr('disabled');
                
                @endif
                
                if(data.name){
                $("#layoutname{{$grid_id}}").html(': '+data.name); 
                }else{
                $('#layoutname{{$grid_id}}').html('');
                }
            
                
    		}
    	});
    }
    
    


    $("#gridcontainer{{ $grid_id }}").off("keydown").on("keydown", function(e){
        var modifier = ( e.ctrlKey || e.metaKey );
     
     
      
        @if(check_access('1,31,34') || is_dev())
        if(modifier && e.which == 83){
            e.preventDefault();
        
            layout_save{{ $grid_id }}();   
                
           
        }
        @endif
    });


   @foreach($columnDefs as $i => $colDef)
        @if(!empty($colDef['children']))
            @foreach($colDef['children'] as $i => $col)
                @if(!empty($col['filter_options']))
                window["field_values{{ $col['field'] }}{{ $module_id}}"] = {!! json_encode($col['filter_options']) !!};
                @endif
            @endforeach
        @else
            @if(!empty($colDef['filter_options']))
                window["field_values{{ $colDef['field'] }}{{ $module_id}}"] = {!! json_encode($colDef['filter_options']) !!};
            @endif
        @endif
    @endforeach

   
    
    function isGroupingActive() {
      
        const rowGroupColumns = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getRowGroupColumns();
        return rowGroupColumns && rowGroupColumns.length > 0;
    }
  
 



var treeRowClassRules = {
  'hover-over': function (params) {
    
    return params.node === potentialParent;
  },
};
// COLUMN MENU CUSTOM

function getMainMenuItems{{ $grid_id }}(params) {
   
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
                var fields_url = "{{ url($module_fields_url.'/edit?module_id='.$module_id) }}";
                fields_url+='&layout_ids='+window['layout_id{{ $grid_id }}'];
            
                sidebarform('coladd',fields_url,"{{$fields_module_title}}","{{$fields_module_description}}");
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
                sidebarform('coladd',"{{ url($module_fields_url.'/edit') }}"+'/'+params.column.colDef.dbid,"{{$fields_module_title}}","{{$fields_module_description}}");
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
             
                var field_name = params.column.colDef.field;
                field_name.replace('join_');
         
                viewDialog('condition_styles',"{{ url($condition_styles_url.'?module_id='.$module_id) }}"+'&field='+field_name);
            },
        });
    @endif
    
    @if($module_fields_access['is_add'])
    if(params.column.colDef && params.column.colDef.pinned_row_total == 0){
    
        menuItems.push({
            name: 'Show Total',
            action: function () {
                gridAjax('/module_field_toggle_totals/1/'+ params.column.colDef.dbid);
               
            },
        });
        
    }
    if(params.column.colDef && params.column.colDef.pinned_row_total == 1){
    
        menuItems.push({
            name: 'Hide Total',
            action: function () {
                gridAjax('/module_field_toggle_totals/0/'+ params.column.colDef.dbid);
               
            },
        });
        
    }
    @endif
  
    return menuItems;
}
  




    





@if(session('role_level') == 'Admin')

class changelogToolPanel{{ $grid_id }} {
  init(params) {
    this.eGui = document.createElement('div');

    this.eGui.classList.add("ag-column-panel");
    
    var changelog_html = '<h4 class="w-100 p-2 m-0">Log</h4>';      
    
    changelog_html += '<div class="ag-layouts-content">';
    changelog_html += '<div id="changelog_results{{ $grid_id }}"></div>';
    changelog_html += '</div>';
    
    this.eGui.innerHTML = changelog_html
    // calculate stats when new rows loaded, i.e. onModelUpdated
    const updateChangelog{{ $grid_id }} = () => {
        this.getChangelog(params);
     
    };
    params.api.addEventListener('rowSelected', updateChangelog{{ $grid_id }});
  }

  getGui() {
    return this.eGui;
  }

  refresh() {}

  getChangelog(params) {
    
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
        url: '/{{$menu_route}}/getchangelog/'+selected.rowId,
        contentType: false,
        processData: false,
        success: function(data) { 
           
            var changelog_html = '';
           
            
            $("#logcount{{$grid_id}}").text(' ('+data.length+')');
            $(data).each(function(i,el){
               
                changelog_html += '<div class="card">';
                changelog_html += '<div class="card-header p-2">';
                changelog_html += el.action;
                changelog_html += '</div>';
                if(el.note > ''){
                changelog_html += '<div class="card-body p-2">';
                changelog_html += '<p>'+el.note+'</p>';
                changelog_html += '</div>';
                }
                changelog_html += '<div class="card-footer p-2 text-right">';
                changelog_html += '<div class="text-muted text-right">'+el.username+'<br>'+el.log_time+'</div>';
                changelog_html += '</div>';
                changelog_html += '</div>';
            });
            
            
            $("#changelog_results{{ $grid_id }}").html(changelog_html);
        }
        });
        
    }else{
        var changelog_html = '';
        
        $("#changelog_results{{ $grid_id }}").html(changelog_html);
    }
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
@if($communications_type > '')
@if($communications_type == 'account' || $communications_type == 'lead' && $module_id!=1912)
class emailLogToolPanel{{ $grid_id }} {
  init(params) {
    this.eGui = document.createElement('div');

    this.eGui.classList.add("ag-column-panel");
    
    var email_log_html = '<h4 class="w-100 p-2 m-0">Email History</h4>';      
    
    email_log_html += '<div class="ag-layouts-content" id="email_log_panel{{ $grid_id }}">';

    email_log_html += '</div>';
    
    this.eGui.innerHTML = email_log_html;
   
    // calculate stats when new rows loaded, i.e. onModelUpdated
    const updateEmailLog{{ $grid_id }} = () => {
        this.getEmailLog(params);
     // this.eGui.innerHTML = this.getEmaillog(params);
    };
    params.api.addEventListener('rowSelected', updateEmailLog{{ $grid_id }});
  }

  getGui() {
    return this.eGui;
  }

  refresh() {}

  getEmailLog(params) {
    
  
     if(params){
            var rows = params.api.getSelectedRows();
           
            if(rows.length > 0){
                var selected = rows[0];
            }
        }else{
            var selected = window['selectedrow_{{ $grid_id }}'];
        }
      
        if(selected && selected.rowId){
            var url = false;
            @if($communications_type == 'lead')
            var url = '/{{$communications_url}}?iframe=1&lead_id='+selected.id;
            @elseif($communications_type == 'account' && $db_table == 'crm_accounts')
            var url = '/{{$communications_url}}?iframe=1&account_id='+selected.id;
            @elseif($communications_type == 'account')
            if(selected.account_id){
            var url = '/{{$communications_url}}?iframe=1&account_id='+selected.account_id;
            }
            @endif
            if(url){
                $.ajax({
                url: url,
                contentType: false,
                processData: false,
                beforeSend: function(){
                  showSpinner("#email_log_panel{{ $grid_id }}");
                },
                success: function(data) { 
                   
                   // var email_log_html = data.html;
                
                   // $("#email_log_count{{ $grid_id }}").text(' ('+data.count+')');
                   // $("#email_log_panel{{ $grid_id }}").html(email_log_html);
                   $("#email_log_panel{{ $grid_id }}").html(data);
                //    $("#email_log_panel{{ $grid_id }}").closest('.ag-tool-panel-wrapper').addClass("panel-w-100");
                  hideSpinner("#email_log_panel{{ $grid_id }}");
                },
                error: function(){
                    hideSpinner("#email_log_panel{{ $grid_id }}");
                }
                });
            }else{
                
                var email_log_html = '';
                
                $("#email_log_panel{{ $grid_id }}").html(email_log_html);    
            }
        }else{
            var email_log_html = '';
            
            $("#email_log_panel{{ $grid_id }}").html(email_log_html);
        }    
    
  }
}
class callsLogToolPanel{{ $grid_id }} {
  init(params) {
    this.eGui = document.createElement('div');

    this.eGui.classList.add("ag-column-panel");
    
    var calls_log_html = '<h4 class="w-100 p-2 m-0">Call History</h4>';      
    
    calls_log_html += '<div class="ag-layouts-content" id="calls_log_panel{{ $grid_id }}">';

    calls_log_html += '</div>';
    
    this.eGui.innerHTML = calls_log_html;
   
    // calculate stats when new rows loaded, i.e. onModelUpdated
    const updateCallsLog{{ $grid_id }} = () => {
        this.getCallsLog(params);
     // this.eGui.innerHTML = this.getCallslog(params);
    };
    params.api.addEventListener('rowSelected', updateCallsLog{{ $grid_id }});
  }

  getGui() {
    return this.eGui;
  }

  refresh() {}

  getCallsLog(params) {
    
  
     if(params){
            var rows = params.api.getSelectedRows();
            
            if(rows.length > 0){
                var selected = rows[0];
            }
        }else{
            var selected = window['selectedrow_{{ $grid_id }}'];
        }
      
        if(selected && selected.rowId){
            var url = false;
            @if($communications_type == 'lead')
            var url = '/{{$call_history_url}}?iframe=1&lead_id='+selected.id;
            @elseif($communications_type == 'account' && $db_table == 'crm_accounts')
            var url = '/{{$call_history_url}}?iframe=1&account_id='+selected.id;
            @elseif($communications_type == 'account')
            if(selected.account_id){
            var url = '/{{$call_history_url}}?iframe=1&account_id='+selected.account_id;
            }
            @endif
            if(url){
                $.ajax({
                url: url,
                contentType: false,
                processData: false,
                beforeSend: function(){
                  showSpinner("#calls_log_panel{{ $grid_id }}");
                },
                success: function(data) { 
                   
                   // var calls_log_html = data.html;
                
                   // $("#calls_log_count{{ $grid_id }}").text(' ('+data.count+')');
                   // $("#calls_log_panel{{ $grid_id }}").html(calls_log_html);
                   $("#calls_log_panel{{ $grid_id }}").html(data);
                //    $("#calls_log_panel{{ $grid_id }}").closest('.ag-tool-panel-wrapper').addClass("panel-w-100");
                  hideSpinner("#calls_log_panel{{ $grid_id }}");
                },
                error: function(){
                    hideSpinner("#calls_log_panel{{ $grid_id }}");
                }
                });
            }else{
                
                var calls_log_html = '';
                
                $("#calls_log_panel{{ $grid_id }}").html(calls_log_html);    
            }
        }else{
            var calls_log_html = '';
            
            $("#calls_log_panel{{ $grid_id }}").html(calls_log_html);
        }    
    
  }
}
@endif
class contactsToolPanel{{ $grid_id }} {
  init(params) {
    this.eGui = document.createElement('div');

    this.eGui.classList.add("ag-column-panel");
    
    var contacts_html = '<h4 class="w-100 p-2 m-0">Contacts</h4>';      
    
    contacts_html += '<div class="ag-layouts-content" id="contacts_panel{{ $grid_id }}">';

    contacts_html += '</div>';
    
    this.eGui.innerHTML = contacts_html
    // calculate stats when new rows loaded, i.e. onModelUpdated
    const updateContacts{{ $grid_id }} = () => {
        this.getContacts(params);
     // this.eGui.innerHTML = this.getContacts(params);
    };
    params.api.addEventListener('rowSelected', updateContacts{{ $grid_id }});
  }

  getGui() {
    return this.eGui;
  }

  refresh() {}

  getContacts(params) {
    getContacts{{$grid_id}}(params);
  }
}

// contact toolpanel functions
$(document).off('click', '.addcontacttbtn{{$grid_id}}').on('click', '.addcontacttbtn{{$grid_id}}', function() {
    var selected = window['selectedrow_{{ $grid_id }}'];
    if(!selected && !selected.rowId){
        toastNotify('Select a record','warning');
    }else{
        var name = $("#contacts_form_name{{ $grid_id }}").val();
        var type = $("#contacts_form_type{{ $grid_id }}").val();
        var phone = $("#contacts_form_phone{{ $grid_id }}").val();
        var email = $("#contacts_form_email{{ $grid_id }}").val();
       
        if(selected && selected.rowId && name > '' && type > '' && (phone > '' || email > '')){
              
               $.ajax({
                url: '/{{$menu_route}}/addcontact',
                type:'post',
                data: {name:name, type:type, phone:phone, email:email, row_id:selected.rowId,account_type:'{{$communications_type}}'},
                success: function(data) { 
                    if(data.status!='success'){
                        toastNotify(data.message,data.status);    
                    }else{
                        getContacts{{$grid_id}}();
                    }
                }
              });  
        }else{
            toastNotify('Contact fields cannot be blank','warning');
        }
    }   
});

$(document).off('click', '.deletecontacttbtn{{$grid_id}}').on('click', '.deletecontacttbtn{{$grid_id}}', function() {
  
    var contact_id = $(this).attr('data-contact-id');
    if(contact_id > ''){
      var confirm_text = "Permanently delete this contact?"
      var confirmation = confirm(confirm_text);
      if (confirmation) {
           $.ajax({
            url: '/{{$menu_route}}/deletecontact',
            type:'post',
            data: {contact_id: contact_id,account_type:'{{$communications_type}}'},
            success: function(data) { 
                getContacts{{$grid_id}}();
            }
          });  
      }
    }
});

function getContacts{{$grid_id}}(params = false) {
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
            url: '/{{$menu_route}}/getcontacts/{{$communications_type}}/'+selected.rowId,
            contentType: false,
            processData: false,
            success: function(data) { 
               
                var contacts_html = '';
                var contacts_results = '';
                
                $("#contactscount{{$grid_id}}").text(' ('+data.length+')');
                $(data).each(function(i,el){
                
                    contacts_results += '<div class="card contact-card">';
                    contacts_results += '<div class="card-body p-2">';
                    contacts_results += '<div style="white-space: pre-line"><b>Name: </b>'+el.name+'</div>';
                  
                    @if($communications_type == 'supplier')
                    contacts_results += '<div><b>Email: </b><a data-target="sidebarform" href="/email_form/suppliers/'+el.supplier_id+'/'+el.email+'">'+el.email+'</a></div>';
                    @else
                    contacts_results += '<div><b>Email: </b><a data-target="sidebarform" href="/email_form/default/'+el.account_id+'/'+el.email+'">'+el.email+'</a></div>';
                    @endif
                    @if(session('instance')->id != 11)
                    contacts_results += '<div style="white-space: pre-line"><b>Phone: </b><a data-target="ajax" href="/pbx_call/'+el.phone+'/'+el.id+'">'+el.phone+'</a></div>';
                    @endif
                    contacts_results += '<div class="text-muted text-right">Type: '+el.type+'</div>';
                    
                    
                    contacts_results += '</div>';
                    contacts_results += '<div class="card-footer p-2 text-right">';
                    
                    @if($communications_type == 'supplier')
                    contacts_results += '<a title="Edit Contact" data-target="sidebarform" href="/{{$supplier_contacts_url}}/edit/'+el.id+'" class="btn btn-sm btn-success"><i class="fa fa-edit"></i></a>';
                    @else
                    contacts_results += '<a title="Edit Contact" data-target="sidebarform" href="/{{$account_contacts_url}}/edit/'+el.id+'" class="btn btn-sm btn-success"><i class="fa fa-edit"></i></a>';
                    @endif
                    @if(is_superadmin())
                    contacts_results += '<button data-contact-id="'+el.id+'" type="button" class="deletecontacttbtn{{$grid_id}} btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>';
                    @endif
                    contacts_results += '</div>';
                    contacts_results += '</div>';
                });
                
                contacts_html += '<div id="contacts_form{{ $grid_id }}">';
                contacts_html += '<div class="card">';
                contacts_html += '<div class="card-body p-2">';
                contacts_html += '<div class="form-group"><input class="form-control" type="text" id="contacts_form_name{{ $grid_id }}" placeholder="Name"/></div>';
                contacts_html += '<div class="form-group"><select class="form-control" id="contacts_form_type{{ $grid_id }}" placeholder="Type">';
                contacts_html += '<option value="" selected>Contact Type</option>';
                contacts_html += '<option value="Accounting">Accounting</option>';
                contacts_html += '<option value="Support">Support</option>';
                contacts_html += '<option value="Sales">Sales</option>';
                contacts_html += '</select></div>';
                contacts_html += '<div class="form-group"><input class="form-control" type="email" id="contacts_form_email{{ $grid_id }}" placeholder="Email"/></div>';
                @if(session('instance')->id != 11)
                contacts_html += '<div class="form-group"><input class="form-control" type="phone" id="contacts_form_phone{{ $grid_id }}" placeholder="Phone"/></div>';
                @endif
                contacts_html += '</div>';
                contacts_html += '<div class="card-footer p-2 text-right">';
                contacts_html += '<button type="button" class="btn btn-primary addcontacttbtn{{$grid_id}}"><i class="fa fa-plus"></i></button>';
                contacts_html += '</div>';
                contacts_html += '</div>';
                contacts_html += '</div>';
                contacts_html += '<div id="contacts_results{{ $grid_id }}">'+contacts_results+'</div>';
                $("#contacts_panel{{ $grid_id }}").html(contacts_html);
            }
            });
            
        }else{
            var contacts_html = '';
            
            $("#contacts_results{{ $grid_id }}").html(contacts_html);
        }
  }  
@endif  
  

class filesToolPanel{{ $grid_id }} {
  init(params) {
    this.eGui = document.createElement('div');

    this.eGui.classList.add("ag-column-panel");
    
    var files_html = '<h4 class="w-100 p-2 m-0">Files</h4>';      
    
    files_html += '<div class="ag-layouts-content">';
    files_html += '<div id="files_form{{ $grid_id }}">';
    files_html += '<div id="droparea{{ $grid_id }}" class="py-4 text-center" style="height: auto; overflow: auto">';
    files_html += '<span id="drop{{ $grid_id }}"> Drop files here or <a href="" id="filebrowse{{ $grid_id }}"><u>Browse</u></a> </span>';
    //files_html += '<span id="drop{{ $grid_id }}"> Drop files here </span>';
    files_html += '<input type="file" id="fileupload{{ $grid_id }}">';
    files_html += '</div>';
    files_html += '</div>';

    files_html += '<div id="files_results{{ $grid_id }}"></div>';
    files_html += '</div>';
    
    this.eGui.innerHTML = files_html
    // calculate stats when new rows loaded, i.e. onModelUpdated
    const updateFiles{{ $grid_id }} = () => {
        this.getFiles(params);
     // this.eGui.innerHTML = this.getFiles(params);
    };
    
        
    function renderFilesUploader(params){
       
        window['filesuploader_{{ $grid_id }}'] =  new ej.inputs.Uploader({
        asyncSettings: {
        saveUrl: '{{$menu_route}}/addfile',
        },
        htmlAttributes: {name: 'file_name[]'},
        showFileList: true,
        dropArea: document.getElementById('droparea{{ $grid_id }}'),
        uploading: function(args){
            var selected = window['selectedrow_{{ $grid_id }}'];
           
            if(!selected || !selected.rowId){
                toastNotify('Select a record','warning');
                args.cancel=true;
            }else{
                args.customFormData = [{row_id:selected.rowId},{module_id: {{$module_id}}}];
            } 
        },
        success: function(args){
            getFiles{{$grid_id}}();
        },
        failure: function(args){
            toastNotify('File upload failed','warning');
        },
        },'#fileupload{{ $grid_id }}');
        // render initialized Uploader
        
        document.getElementById('filebrowse{{ $grid_id }}').onclick = function () {
            var selected = window['selectedrow_{{ $grid_id }}'];
            
            if(!selected || !selected.rowId){
                alert('Please select a row');
            }else{
                document.getElementsByClassName('e-file-select-wrap')[0].querySelector('button').click();
            }
            return false;
        };
        
    
    }
    
    params.api.addEventListener('gridReady', renderFilesUploader);
    params.api.addEventListener('rowSelected', updateFiles{{ $grid_id }});
  }

  getGui() {
    return this.eGui;
  }

  refresh() {}

  getFiles(params) {
    getFiles{{$grid_id}}(params);
  }
}


// file toolpanel functions

$(document).off('click', '.deletefiletbtn{{$grid_id}}').on('click', '.deletefiletbtn{{$grid_id}}', function() {
  
    var file_id = $(this).attr('data-file-id');
    if(file_id > ''){
           $.ajax({
            url: '/{{$menu_route}}/deletefile',
            type:'post',
            data: {file_id: file_id},
            success: function(data) { 
                getFiles{{$grid_id}}();
            }
          });  
    }
});

function getFiles{{$grid_id}}(params = false) {
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
            url: '/{{$menu_route}}/getfiles/'+selected.rowId,
            contentType: false,
            processData: false,
            success: function(data) { 
            
                var files_html = '';
            
                $("#filescount{{$grid_id}}").text(' ('+data.length+')');
                
                $(data).each(function(i,el){
                    files_html += '<div class="row p-1">';
                
                    files_html += '<div class="col"><a href="'+el.url+'" target="_blank">'+el.file_name+'</a><br><br>';
                    files_html += '<p class="text-muted">'+el.username+' - '+el.created_at+'</p>';;
                    files_html += '</div>';
                  
                    @if(is_superadmin())
                    files_html += '<div class="col col-auto text-right">';
                    files_html += '<button data-file-id="'+el.id+'" type="button" class="deletefiletbtn{{$grid_id}} btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>';
                    files_html += '</div>';
                    @endif
                    files_html += '</div>';
                });
                
                
                $("#files_results{{ $grid_id }}").html(files_html);
                window['filesuploader_{{ $grid_id }}'].clearAll();
            }
            });
            
        }else{
            var files_html = '';
            
            $("#files_results{{ $grid_id }}").html(files_html);
        }
  }



@endif
/** CUSTOM TOOLPANELS - END **/









grid_mode = 'layout';
@if(!request()->ajax())
window['original_title'] = document.title;
@endif
init_load = true;
pivot_mode = 0;
filter_cleared{{ $grid_id }} = 0;
show_deleted{{ $grid_id }} = 0;


restoringExpandedNodes{{$grid_id}} = false;
window['expanded_groups{{$grid_id}}'] = [];




/** TOOLBAR **/

    default_layout_saved = 0;
    
   

	
/** LAYOUTS **/


	

    
/** FORMS EVENTS **/
	$(document).off('click', '.form_builder_btn').on('click', '.form_builder_btn', function() {
	  
	    window['builder_id{{ $grid_id }}'] = $(this).attr('builder_id');
	    window['builder_role_id{{ $grid_id }}'] = $(this).attr('role_id');
	    //enable form buttons
	    if(window['builder_id{{ $grid_id }}']){
            $('#formsbtn_delete{{ $grid_id }}').removeAttr('disabled');
            $('#formsbtn_duplicate{{ $grid_id }}').removeAttr('disabled');
            $('#formsbtn_edit{{ $grid_id }}').removeAttr('disabled');
            $('#formsbtn_view{{ $grid_id }}').removeAttr('disabled'); 
        }else{
            $('#formsbtn_delete{{ $grid_id }}').attr('disabled','disabled');
            $('#formsbtn_duplicate{{ $grid_id }}').attr('disabled','disabled');
            $('#formsbtn_edit{{ $grid_id }}').attr('disabled','disabled'); 
            $('#formsbtn_view{{ $grid_id }}').attr('disabled','disabled');     
	    }
	});
	
	$(document).off('click', '#formsbtn_view{{ $grid_id }}').on('click', '#formsbtn_view{{ $grid_id }}', function() {
	    sidebarform('gridf{{ $grid_id }}','/{{$menu_route}}/edit?form_role_id='+window['builder_role_id{{ $grid_id }}']);
	});
	
	$(document).off('click', '#formsbtn_create{{ $grid_id }}').on('click', '#formsbtn_create{{ $grid_id }}', function() {
	    sidebarformlarge('gridf{{ $grid_id }}','/{{$forms_url}}/edit?module_id={{$module_id}}');
	});
	
	$(document).off('click', '#formsbtn_edit{{ $grid_id }}').on('click', '#formsbtn_edit{{ $grid_id }}', function() {  
	   
	    sidebarformlarge('gridf{{ $grid_id }}','/{{$forms_url}}/edit/'+window['builder_id{{ $grid_id }}']);
	});
	$(document).off('click', '#formsbtn_duplicate{{ $grid_id }}').on('click', '#formsbtn_duplicate{{ $grid_id }}', function() {
		if(window['builder_id{{ $grid_id }}']){
	    	gridAjaxConfirm('/{{ $forms_url }}/duplicate', 'Duplicate form?', {"id" : window['builder_id{{ $grid_id }}']}, 'post');
		}	
	});
	
	$(document).off('click', '#formsbtn_delete{{ $grid_id }}').on('click', '#formsbtn_delete{{ $grid_id }}', function() {
	    gridAjaxConfirm('/{{ $forms_url }}/delete', 'Delete form?', {"id" : window['builder_id{{ $grid_id }}']}, 'post');
	});
/** FORMS EVENTS END **/

   
    
   
	
    function get_sidebar_data_reset(){
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
                
                if(data){
                    if(data.sidebar_layouts){
                        window['gridlayouts_{{ $grid_id }}'].items = data.sidebar_layouts;
                        window['gridlayouts_{{ $grid_id }}'].dataBind();
                        refresh_layout_context_menus{{$grid_id}}();
                       
                    }
                   
                   
                }
            }
        });
    }
	

/** AGGRID COMPONENTS **/
// https://www.ag-grid.com/javascript-data-grid/component-tool-panel/

/** AGGRID PINNED ROW**/
function setStyle(element, propertyObject) {
  for (var property in propertyObject) {
    element.style[property] = propertyObject[property];
  }
}


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

/** AGGRID **/

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

function booleanCellRenderer(params){
    if(params.value === "1" || params.value === 1 || params.value === "true"){
    return "Yes";
    }
    if(params.value === "0" || params.value === 0 || params.value === "false"){
    return "No";
    }
    return params.value;
}



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
   
   
    var field_id = this.colId;
    var field_id = field_id.replace('join_','');
    var field_val = window[field_id+"{{$module_id}}"].value;
    if(this.colType == 'dateField'){
    var field_val =  $('#'+field_id+"{{$module_id}}").val();
    }
    var post_data =  {id: this.rowId, value: field_val, field: field_id }; 
    syncfusion_data[field_id] = field_val;
    /*
    $.ajax({ 
        url: "/{{$menu_route}}/save_cell", 
        type: 'post',
        data: post_data,
        beforeSend: function(){
     
        },
        success: function (result) { 
     
           
                createTopRow{{ $grid_id }}();
        
        
          
            if(result.status != 'success' && result.status != 'skip'){
            toastNotify(result.message,result.status);
            window['grid_{{ $grid_id }}'].gridOptions.api.undoCellEditing();
            }
            if(result.status != 'skip'){
            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            }
        }, 
    });
      */
    return $('#'+field_id+"{{$module_id}}").val();
  }

  // Gets called once after initialised.
  // If you return true, the editor will appear in a popup
  isPopup() {
    return false;
  }
}




var gridOptions = {
    //enableRangeSelection: true,
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
    enableCellChangeFlash: true,
    maintainColumnOrder: true,
   

    @if($master_detail)
    isExternalFilterPresent: isExternalFilterPresent,
    doesExternalFilterPass: doesExternalFilterPass,
    @endif
 
    loadingOverlayComponent: CustomLoadingOverlay,
    loadingOverlayComponentParams: {
    loadingMessage: 'One moment please...',
    },
    suppressMoveWhenRowDragging: true,
    suppressPropertyNamesCheck: true,
  //  @if(!$master_detail)
    getRowId: getRowNodeId{{ $grid_id }},
  //  @endif
   
    groupDisplayType: 'multipleColumns',
    groupMaintainOrder: true,
    rowGroupPanelShow: 'never',
    showOpenedGroup:true,
     // adds subtotals
  
     // adds subtotals
    @if($show_group_totals)
    groupIncludeFooter: true,
    @endif
    // includes grand total
    @if($show_group_subtotals)
    groupIncludeTotalFooter: true,
    @endif

    
   
    
    enableSorting: true,
    excludeChildrenWhenTreeDataFiltering: false,
    autoApproveParentItemWhenTreeColumnIsValid: false,
    @if($tree_data)
        excludeChildrenWhenTreeDataFiltering: false,
        //enableGroupEdit: true,
        treeData: true, // enable Tree Data mode
        getDataPath: data => {
         
            return data.hierarchy;
        },
        autoGroupColumnDef: {
            headerName: "{{$tree_data_header}}",
            cellRendererParams: {
                suppressCount: true
            },
            // rowDrag: true,
            //editable: true,
        },
    @endif
    getMainMenuItems: getMainMenuItems{{ $grid_id }},
    getContextMenuItems: getContextMenuItems{{$grid_id}},
    defaultExcelExportParams: {fileName: '{{$menu_name}}.xlsx'},
    defaultCsvExportParams: {fileName: '{{$menu_name}}.csv'},
    pivotMode: false,
    debug: false,
    onRowDoubleClicked: function(args){
      
        args.event.preventDefault();
        @if($access['is_edit'])
        var selected = window['selectedrow_{{ $grid_id }}'];
        
        @if($documents_module)
        sidebarform('{{ $menu_route }}edit', '/{{ $menu_route }}/edit/'+ selected.rowId, 'Documents - Edit', '80%', '100%');
        @else
        
        sidebarform{{$grid_id}}('{{ $menu_route }}edit' , '/{{ $menu_route }}/edit/'+ selected.rowId+'?layout_id='+window['layout_id{{ $grid_id }}'], '{!! $menu_name !!} - Edit', '', '60%');
        
        @endif
        @endif
    },
   
    @if($master_detail)
    //detailRowAutoHeight: true,
    detailRowHeight: 400,
    @endif
    icons: {
        layouts_icon: '<i class="far fa-bookmark"/>',
        forms_icon: '<i class="far fa-bookmark"/>',
        communications_icon: '<i class="far fa-envelope"/>',
        pivotmode_icon: '<i class="fa fa-toggle-on "/>',
        builder_icon: '<i class="far fa-caret-square-right"/>',
    },
   
    @if(!empty($rowClassRules))
    rowClassRules: {!! json_encode($rowClassRules) !!},
    @endif
    
    @if(session('role_level') == 'Admin')
    //enableCharts: true,
    //enableRangeSelection: true,
    @endif
    @if(session('role_level') != 'Admin')
    suppressContextMenu:true,
    @endif
    suppressCopyRowsToClipboard:true,
    animateRows: false,
    rowSelection: 'single',
    multiSortKey: 'ctrl',
    getRowStyle: function (params) {
        if (params.node.rowPinned) {
            return { 'font-weight': 'bold' };
        }
    },
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
        @if($pinned_totals && !$show_group_totals)
        pinnedBottomRowData: [{}],
        @endif
    @endif
    tooltipShowDelay:1,
    enableBrowserTooltips: true,
    columnDefs: {!! json_encode($columnDefs) !!},
    columnTypes: {
        
        defaultField: {
            enableValue: false,
            enableRowGroup: true,
            filter: 'agTextColumnFilter',
            filterParams: {
                suppressAndOrCondition: true
            },
            maxWidth : 200,
            cellStyle : { 'text-overflow':'ellipsis','white-space':'nowrap', 'overflow': 'hidden', 'padding': 0 }
        },
        dateField: {
            enableValue: true,
            enableRowGroup: true,
            filter: 'agDateColumnFilter',
            filterParams: {
              
                allowTextInput: true,
                
                includeBlanksInLessThan: true,
                comparator: function(filterLocalDateAtMidnight, cellValue) {
                    
                return 1;
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
                    {
                        displayKey: 'currentDay',
                        displayName: 'Current Day',
                        predicate: ([filterValue], cellValue) => {
                            
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
                        displayKey: 'currentWeek',
                        displayName: 'Current Week',
                        predicate: ([filterValue], cellValue) => {
                            
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
                            
                            var dateParts = cellValue.split(/[- :]/);
                           
                            if(dateParts[1] == cur_month && dateParts[0] == cur_year){
                                return true;    
                            }else{
                                return false;    
                            }
                     
                        },
                        numberOfInputs: 0,
                    },
                    'lessThan',
                    'greaterThan',
                    'inRange',
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
                    {
                        displayKey: 'notCurrentMonth',
                        displayName: 'Not Current Month',
                        predicate: ([filterValue], cellValue) => {
                            
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
                        displayKey: 'lastMonth',
                        displayName: 'Last Month',
                        predicate: ([filterValue], cellValue) => {
                            
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
                        displayKey: 'lessEqualToday',
                        displayName: 'Today and before',
                        predicate: ([filterValue], cellValue) => {
                            
                           
                           var celldate = new Date(cellValue);
                         
                            if(celldate <= date_today){
                              
                                return true;    
                            }else{
                                
                                return false;    
                            }
                      
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'notlastThreeDays',
                        displayName: 'Not Last Three Days',
                        predicate: ([filterValue], cellValue) => {
                            
                           
                           var celldate = new Date(cellValue);
                            if(celldate < date_3days){
                                return true;    
                            }else{
                                return false;    
                            }
                      
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'notlastSevenDays',
                        displayName: 'Not Last Seven Days',
                        predicate: ([filterValue], cellValue) => {
                            var celldate = new Date(cellValue);
                            if(celldate < date_7days){
                                return true;    
                            }else{
                                return false;    
                            }
                      
                        },
                        numberOfInputs: 0,
                    },
                    {
                        displayKey: 'notlastThirtyDays',
                        displayName: 'Not Last Thirty Days',
                        predicate: ([filterValue], cellValue) => {
                            
                           
                           var celldate = new Date(cellValue);
                            if(celldate < date_30days){
                                return true;    
                            }else{
                                return false;    
                            }
                      
                        },
                        numberOfInputs: 0,
                    },
                ],
            }
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
        @if($serverside_model)
        checkboxField: {
            filter: 'agSetColumnFilter',
            filterParams: {
                values: params =>  {
                    params.success(params.colDef.filter_options);
                },
                //suppressSelectAll: true,
                defaultToNothingSelected: true,
            },
            buttons: ['reset']
        },
        @else
        @foreach($columnDefs as $i => $colDef)
            @if($colDef['children'])
                @foreach($colDef['children'] as $i => $col)
                    @if(!empty($col['filter_options']))
                    {{ $col['field'].$module_id }}Field: {
                        filter: 'agSetColumnFilter',
                        filterParams: {
                            values: params =>  {
                                params.success(params.colDef.filter_options);
                            },
                            refreshValuesOnOpen: true,
                            defaultToNothingSelected: true,
                            //suppressSelectAll: true,
                            buttons: ['reset']
                        },
                        @if($col['select_multiple'])
                        valueGetter: multiValueGetter,
                        @endif
                        comparator: function (valueA, valueB, nodeA, nodeB, isInverted) {
                       
                        var field_values = window["field_values{{ $col['field'] }}{{ $module_id}}"];
                        var key1 = field_values.indexOf(valueA);
                        var key2 = field_values.indexOf(valueB);
                        
                        
                      
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
                        //cellStyle : { 'text-overflow':'ellipsis','white-space':'nowrap', 'overflow': 'hidden', 'padding': 0 }
                    },
                    @endif
                @endforeach
            @else
                @if(!empty($colDef['filter_options']))
                {{ $colDef['field'].$module_id }}Field: {
                    filter: 'agSetColumnFilter',
                    filterParams: {
                        values: params =>  {
                            params.success(params.colDef.filter_options);
                        },
                        refreshValuesOnOpen: true,
                        defaultToNothingSelected: true,
                        //suppressSelectAll: true,
                        buttons: ['reset']
                    },
                    @if($colDef['select_multiple'])
                    valueGetter: multiValueGetter,
                    @endif
                    comparator: function (valueA, valueB, nodeA, nodeB, isInverted) {
                   
                    var field_values = window["field_values{{ $colDef['field'] }}{{ $module_id}}"];
                    var key1 = field_values.indexOf(valueA);
                    var key2 = field_values.indexOf(valueB);
                    
                    
                  
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
                    //cellStyle : { 'text-overflow':'ellipsis','white-space':'nowrap', 'overflow': 'hidden', 'padding': 0 }
                },
                @endif
            @endif
        @endforeach
        checkboxField: {
            filter: 'agSetColumnFilter',
        },
        @endif
        intField: {
            filter: 'agNumberColumnFilter',
            enableValue: true,
            enableRowGroup: false,
           
           // cellRenderer: pinnedTotalRenderer{{$grid_id}},
          
            //headerClass: 'ag-right-aligned-header',
            //cellClass: 'ag-right-aligned-cell',
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
            }
            
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
            filter: 'agNumberColumnFilter',
            enableValue: true,
            enableRowGroup: false,
           
           // cellRenderer: pinnedTotalRenderer{{$grid_id}},
          
            valueFormatter: function(params){
               // if(!params.node.footer){
                   
                    var currency_decimals = params.colDef.currency_decimals;
                    var currency_symbol = params.colDef.currency_symbol;
                    var row_data_currency = params.colDef.row_data_currency;
                    if(row_data_currency && params.data[row_data_currency]){
                     
                        if(params.data[row_data_currency].toLowerCase() == 'zar'){
                            var currency_decimals = 2;
                            var currency_symbol = 'R';
                        }
                        
                        if(params.data[row_data_currency].toLowerCase() == 'usd'){
                            var currency_decimals = 3;
                            var currency_symbol = '$';
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
            }
        },
        sortField:{
            rowDrag: params => !params.node.group,
            headerClass: 'ag-right-aligned-header',
            cellClass: 'ag-right-aligned-cell sort-field-cell',
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
            },
        },
        imageField: {
            cellRenderer: function(params){
                if(params.value > ''){
                    var files = params.value.split(",");
                    var cell_value = '';
                    var url = "{{ uploads_url($module_id) }}";
                    if(files.length > 0 && files[0] > ''){
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
            },
            
        },
        phoneField: {
            filter: 'agTextColumnFilter',
            @if(session('role_level') == 'Admin')
            cellRenderer: function(params){
                if(params.value > ''){
                     var cell_value = params.value;
                     @if($module_id == 343)
                       cell_value = '<a href="javascript:void(0);" onclick="gridAjax(\'/pbx_call/'+params.value+'/'+params.data.id+'\')">'+params.value+'</a>';
                     @else
                        if(params.data.account_id){
                            cell_value = '<a href="javascript:void(0);" onclick="gridAjax(\'/pbx_call/'+params.value+'/'+params.data.account_id+'\')">'+params.value+'</a>';
                        }else{
                            cell_value = '<a href="javascript:void(0);" onclick="gridAjax(\'/pbx_call/'+params.value+'\')">'+params.value+'</a>';
                        }
                     @endif
                    return cell_value;
                }else{
                    return params.value;
                }
            },
            @endif
        },
        emailField: {
            filter: 'agTextColumnFilter',
            @if(session('role_level') == 'Admin')
            cellRenderer: function(params){
                if(params.value > ''){
                     var cell_value = params.value;
                     @if($module_id == 343)
                       cell_value = '<a href="/email_form/default/'+params.data.id+'/'+params.value+'" target="_blank" data-target="form_modal">'+params.value+'</a>';
                     @else
                        if(params.data.account_id){
                            cell_value = '<a href="/email_form/default/'+params.data.account_id+'/'+params.value+'" target="_blank" data-target="form_modal">'+params.value+'</a>';
                        }
                     @endif
                    return cell_value;
                }else{
                    return params.value;
                }
            },
            @endif
        },
    },
    autoGroupColumnDef: {
        // enabled sorting on Row Group Columns only 
        sortable: true,     
        sort: null,
    },
    defaultColDef: {
      
        getQuickFilterText: function(params) {
            return (!params.column.visible) ? '' : params.value; 
        },
        minWidth: 150,
        // allow every column to be aggregated
        enableValue: false,
        // allow every column to be grouped
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
  
        allowedAggFuncs: ['value', 'percentage', 'calc', 'sum', 'min', 'max', 'count', 'avg'],
        //menuTabs: ['filterMenuTab','generalMenuTab','columnMenuTab'],
        @if(session('role_level') == 'Admin')
        menuTabs: ['filterMenuTab','columnsMenuTab','generalMenuTab'],
        @else
        menuTabs: ['filterMenuTab'],
        @endif
        @if($tree_data)
        cellClassRules:treeRowClassRules,
        @endif
         @if(check_access('1,31,34'))
        headerComponentParams: {
            template:
                '<div class="ag-cell-label-container" role="presentation">' +
                '  <span ref="eMenu" class="ag-header-icon ag-header-cell-menu-button"></span>' +
                '  <div ref="eLabel" class="ag-header-cell-label" role="presentation">' +
                '    <span ref="eSortOrder" class="ag-header-icon ag-sort-order"></span>' +
                '    <span ref="eSortAsc" class="ag-header-icon ag-sort-ascending-icon"></span>' +
                '    <span ref="eSortDesc" class="ag-header-icon ag-sort-descending-icon"></span>' +
                '    <span ref="eSortNone" class="ag-header-icon ag-sort-none-icon"></span>' +
                '    <span ref="eText" class="ag-header-cell-text" role="columnheader"></span>' +
                '    <span ref="eFilter" class="ag-header-icon ag-filter-icon"></span>' +
                '  </div>' +
                '</div>'
        },
        @endif
        
    },
    aggFuncs: {
        // this overrides the grids built-in sum function
        
        'calc': params => {
             
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
        'value': params => {
            
            let val = '';
            params.values.forEach(value => val = value);
            return val;
        },
        'percentage': params => {
           
            let val = '';
            params.values.forEach(value => val = value);
            return val;
        }
    },
    @if(session('role_level') == 'Admin')
    
  
    sideBar: {
    toolPanels: [
         {
                id: 'columns',
                labelDefault: 'Columns',
                labelKey: 'columns',
                iconKey: 'columns',
                toolPanel: 'agColumnsToolPanel',
            },
            {
                id: 'filters',
                labelDefault: 'Filters <span id="filterscount{{ $grid_id }}">(0)</span>',
                labelKey: 'filters',
                iconKey: 'filter',
                toolPanel: 'agFiltersToolPanel',
            },
            /*
            {
                id: 'columns',
                labelDefault: 'Columns',
                labelKey: 'columns',
                iconKey: 'columns',
                toolPanel: 'agColumnsToolPanel',
            },
            {
                id: 'filters',
                labelDefault: 'Filters',
                labelKey: 'filters',
                iconKey: 'filter',
                toolPanel: 'agFiltersToolPanel',
            },
            */
            @if(session('role_level') == 'Admin')
            
            @if($communications_type > '')
            {
                id: 'contacts',
                labelDefault: 'Contacts <span id="contactscount{{ $grid_id }}"></span>',
                labelKey: 'contacts',
                iconKey: 'forms_icon',
                toolPanel: 'contactsToolPanel{{ $grid_id }}',
            },
            @endif
            {
                id: 'notes',
                labelDefault: 'Notes <span id="notescount{{ $grid_id }}"></span>',
                labelKey: 'notes',
                iconKey: 'forms_icon',
                toolPanel: 'notesToolPanel{{ $grid_id }}',
            },
          
            @if($communications_type == 'account' || $communications_type == 'lead' && $module_id!=1912)
            {
                id: 'email_log',
                labelDefault: 'Email History <span id="email_log_count{{ $grid_id }}"></span>',
                labelKey: 'email_log',
                iconKey: 'forms_icon',
                toolPanel: 'emailLogToolPanel{{ $grid_id }}',
                width: 1000,
                minWidth: 800,
                maxWidth: 1200
            },
            {
                id: 'calls_log',
                labelDefault: 'Call History <span id="email_log_count{{ $grid_id }}"></span>',
                labelKey: 'calls_log',
                iconKey: 'forms_icon',
                toolPanel: 'callsLogToolPanel{{ $grid_id }}',
                width: 1000,
                minWidth: 800,
                maxWidth: 1200
            },
            @endif
            {
                id: 'files',
                labelDefault: 'Files <span id="filescount{{ $grid_id }}"></span>',
                labelKey: 'files',
                iconKey: 'forms_icon',
                toolPanel: 'filesToolPanel{{ $grid_id }}',
            },
            {
                id: 'changelog',
                labelDefault: 'Change Log <span id="logcount{{ $grid_id }}"></span>',
                labelKey: 'changelog',
                iconKey: 'forms_icon',
                toolPanel: 'changelogToolPanel{{ $grid_id }}',
            },
            @endif
           
             
           
        ],
        defaultToolPanel: '',
    },
    @endif
    components: {
        CustomLoadingOverlay: CustomLoadingOverlay,
        SyncFusionCellEditor{{ $grid_id }}:SyncFusionCellEditor{{ $grid_id }},
        booleanCellRenderer: booleanCellRenderer,
      
        @if(session('role_level') == 'Admin')
        @if($communications_type > '')
        contactsToolPanel{{ $grid_id }}: contactsToolPanel{{ $grid_id }},
        @endif
        notesToolPanel{{ $grid_id }}: notesToolPanel{{ $grid_id }},
        @if($communications_type == 'account' || $communications_type == 'lead' && $module_id!=1912)
        emailLogToolPanel{{ $grid_id }}: emailLogToolPanel{{ $grid_id }},
        callsLogToolPanel{{ $grid_id }}: callsLogToolPanel{{ $grid_id }},
        @endif
        filesToolPanel{{ $grid_id }}: filesToolPanel{{ $grid_id }},
        changelogToolPanel{{ $grid_id }}: changelogToolPanel{{ $grid_id }},
        @endif
        
    },
   
    @if($master_detail)
    keepDetailRows: false,
    masterDetail: true,
    detailCellRendererParams: 
    {
        refreshStrategy: 'rows',
        // provide the Grid Options to use on the Detail Grid
        detailGridOptions: detailGridOptions,
        // get the rows for each Detail Grid
        getDetailRowData: function (params) {
         
            @if($master_module_key)
            var master_key = params.data.{{$master_module_key}};
            master_rowid{{$grid_id}} = params.data.{{$master_module_key}};
            @else
            var master_key = params.data.{{$db_key}};
            master_rowid{{$grid_id}} = params.data.{{$db_key}};
            @endif
           
            var post_data = { detail_value:master_key, detail_field: '{{ $detail_module_key }}' };
           
            window['mastergrid_row{{ $grid_id }}'] =params.data;
          
            request_detail_value = master_key;
            request_detail_field = '{{ $detail_module_key }}';
            $.ajax({ 
                url: "/{{ $detail_menu_route }}/aggrid_detail_data", 
                type: 'post',
                data: post_data,
                success: function (result) {
                   
                    window['detail_row_data{{ $grid_id }}'] = result;
                    params.successCallback(result);
                   
                }, 
            });
        },
    },
    @endif
    onFilterChanged: function(params){
      // console.log('onFilterChanged');
        if(!first_row_select){
        window['grid_{{ $grid_id }}'].gridOptions.api.deselectAll();
        }
        var row_count = window['grid_{{ $grid_id }}'].gridOptions.api.getDisplayedRowCount();
        $("#rowcount{{ $grid_id }}").text(row_count);
        @if($pinned_totals && !$serverside_model && !$show_group_totals)
       
        let pinnedBottomData = generatePinnedBottomData{{ $grid_id }}();
       
        window['grid_{{ $grid_id }}'].gridOptions.api.setPinnedBottomRowData([pinnedBottomData]);
      
        @endif
      
        @if($serverside_model)
        window['grid_{{ $grid_id }}'].gridOptions.refresh();
        @endif
      
    },
    onRowSelected: function(event){
        //console.log('mastergrid onRowSelected',event);
        //if(active_requests == 0){ 
            
            if(first_row_select){
               
                setTimeout(function(){first_row_select = false; },500)
                
                
                window['selectedrow_{{ $grid_id }}'] = event.node.data;
                window['selectedrow_{{ $grid_id }}'].rowId = window['selectedrow_{{ $grid_id }}'].{{$db_key}};
                
                window['selectedrow_node_{{ $grid_id }}'] = event.node;
                @if($master_detail)
                    window['grid_{{ $grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
                            detailGridApi.api.deselectAll();
                    });
                   
                    
                   
                      
                    $("#moduleleft_menu{{ $grid_id }}").removeClass('d-none');
                    $("#moduleleft_menudetail{{ $grid_id }}").addClass('d-none');
                    $("#grid_{{ $grid_id }}").removeClass('detailgrid-focus').addClass('mastergrid-focus');
                    window['grid_{{ $grid_id }}'].gridOptions.api.setSideBarVisible(true);
                    
                @endif
                rowSelected{{ $grid_id }}();
            }else{
                
               
              if(!event.node.isSelected()){
                
               
                var deselected = event.node.data;
                if(window['selectedrow_{{ $grid_id }}'] && deselected.{{$db_key}} == window['selectedrow_{{ $grid_id }}'].rowId){
                  
                    window['selectedrow_{{ $grid_id }}'] = null;
                    window['selectedrow_node_{{ $grid_id }}'] = null;
                    rowDeselected();
                }
            }
            if(event.node.isSelected() && event.node.group == false){
               
               
                window['selectedrow_{{ $grid_id }}'] = event.node.data;
                window['selectedrow_{{ $grid_id }}'].rowId = window['selectedrow_{{ $grid_id }}'].{{$db_key}};
                
                window['selectedrow_node_{{ $grid_id }}'] = event.node;
                @if($master_detail)
                    window['grid_{{ $grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
                            detailGridApi.api.deselectAll();
                    });
                   
                    
                   
                      
                    $("#moduleleft_menu{{ $grid_id }}").removeClass('d-none');
                    $("#moduleleft_menudetail{{ $grid_id }}").addClass('d-none');
                    $("#grid_{{ $grid_id }}").removeClass('detailgrid-focus').addClass('mastergrid-focus');
                    window['grid_{{ $grid_id }}'].gridOptions.api.setSideBarVisible(true);
                    
                @endif
                rowSelected{{ $grid_id }}();
            }  
            }
        //}
    }, 
 
    onColumnRowGroupChanged: function(params){
         
    },
    @if($master_detail)
    onRowGroupOpened: function(params){
        if(params.expanded){
        
            gridOptions.api.forEachNode(function (node) {
                
          
                if(node.expanded && node.id != params.node.id && node.groupData == null){
                   
                    node.setExpanded(false);
                }
            });
        }
        
      
    },
    @else
    onRowGroupOpened: function (params) {
         
          if (restoringExpandedNodes{{$grid_id}}) {
            return;
          }
          let expandedNodeDetails = [];
          params.api.forEachNode(node => {
            if (node.group && node.expanded) {
              let expandedDetails = getExpandedDetails{{$grid_id}}(node);
              expandedNodeDetails.push(expandedDetails);
            }
          });
         
          window['expanded_groups{{$grid_id}}'] = JSON.stringify(expandedNodeDetails);    
    },
    @endif
    onModelUpdated: function(args){
        
    },
    onViewportChanged: function(args){
     
        this.columnApi.autoSizeAllColumns();
        
        
    },
    onFirstDataRendered:  function(){
    // console.log('onFirstDataRendered');
    },
    onGridReady: onGridReady{{ $grid_id }},
    @if($serverside_model) 
    refresh: function(){
        window['grid_{{ $grid_id }}'].gridOptions.api.deselectAll();
        window['grid_{{ $grid_id }}'].gridOptions.api.refreshServerSide();
    },
    @else
    refresh: function(data){
            //console.log(data);
         @if($master_detail)
            if(data && data.refresh_master_grid){
                
                refreshGridData{{ $grid_id }}();
            }else if(data && data.master_module_id){
             
                refresh_detail_grid{{'detail'.$grid_id}}();
            }else{
            
                if(data && data.grid_refresh){
                 
                    refreshGridData{{ $grid_id }}();
                } else if(data && data.row_id){
       
                    refreshRowData{{ $grid_id }}(data.row_id); 
                }else{
                
                    refreshGridData{{ $grid_id }}();    
                }
            }
        @else
        
            if(data && data.grid_refresh){
            
            refreshGridData{{ $grid_id }}();
            } else if(data && data.row_id){
           
           
            refreshRowData{{ $grid_id }}(data.row_id); 
            }else{
          
            refreshGridData{{ $grid_id }}();    
            }
        @endif
       
    },
    @endif
    @if(session('role_level') == 'Admin' && $has_sort)
        @if($tree_data)
        onRowDragEnter: onManagedRowDragEnter{{ $grid_id }},
        onRowDragEnd: onManagedRowDragEnd{{ $grid_id }},
        onRowDragMove: onManagedRowDragMove{{ $grid_id }},
        onRowDragLeave: onManagedRowDragLeave{{ $grid_id }},
        @else
        onRowDragEnd: onRowDragEnd{{ $grid_id }},
        onRowDragMove: onRowDragMove{{ $grid_id }},
        @endif
    @endif
    rowHeight: 26,
    headerHeight: 36,
    
};
window['layoutgridOptions_{{ $grid_id }}'] = gridOptions;

window['grid_{{ $grid_id }}'] = new agGrid.Grid(document.querySelector('#grid_{{ $grid_id }}'), gridOptions);


// dynamically update the tool panel params
function report_toolpanel_disable{{$grid_id}}(suppress = false) {
  
    var params = {
        suppressPivotMode: suppress,
        suppressRowGroups: suppress,
        suppressValues: suppress,
        suppressPivots: suppress,
        suppressSyncLayoutWithGrid: true,
    }; 
  
  var sidebar = window['grid_{{ $grid_id }}'].gridOptions.sideBar;
  var toolPanels = sidebar.toolPanels.map(function (panel) {
    if (panel.id === "columns") {
      return {
        ...panel,
        toolPanelParams: params,
      };
    }
    return panel;
  });
  sidebar.toolPanels = toolPanels;
  window['grid_{{ $grid_id }}'].gridOptions.sideBar = sidebar;
 
  window['grid_{{ $grid_id }}'].gridOptions.api.setSideBar(sidebar);
  
 
}


function getExpandedDetails{{ $grid_id }}(node, expandedDetails = '') {
    if (node.group && node.uiLevel >= 0) {
      if (expandedDetails === '') {
        expandedDetails = node.field + ':' + node.key
      } else {
        expandedDetails += '&' + node.field + ':' + node.key
      }
      return getExpandedDetails{{ $grid_id }}(node.parent, expandedDetails);
    }
  return expandedDetails;
}

function expandNodes{{ $grid_id }}(groupStorage, processedGroups = [], num_expands = 0) {
    if(groupStorage){
        setTimeout(function(){
        
        
       
        if(num_expands == 0){
        groupStorage = JSON.parse(groupStorage);
        }
        if(groupStorage){
        window['expanded_groups{{$grid_id}}']= groupStorage;
        }else{
        window['expanded_groups{{$grid_id}}']= null;
        }
        if (groupStorage) {
        
        restoringExpandedNodes{{$grid_id}} = true;
        window['grid_{{ $grid_id }}'].gridOptions.api.forEachNode(node => {
        if (node.group) {
        let expandedDetails = getExpandedDetails{{$grid_id}}(node);
        let ind = groupStorage.findIndex(
        storageItem => storageItem === expandedDetails
        );
        if (!processedGroups.includes(groupStorage[ind]) && ind !== -1) {
            processedGroups.push(groupStorage[ind]);
            node.setExpanded(true);
            num_expands++;
            if(processedGroups.length != groupStorage.length && num_expands < groupStorage.length){
                expandNodes{{ $grid_id }}(groupStorage, processedGroups, num_expands);       
            }
        }
        }
        });
        setTimeout(() => restoringExpandedNodes{{$grid_id}} = false, 0);
        }
        },100);
        }
    }


@if($serverside_model)
grid_filters = null;
var datasource = {
    getRows(params) {
       
        window['grid_{{ $grid_id }}'].gridOptions.api.deselectAll();
      
                       
        if(searchtext{{ $grid_id }}.value != null){
             var search_val = searchtext{{ $grid_id }}.value;
                            if(search_val > ''){
                                search_val = search_val.trim();
                            }
            params.request.search = search_val;
        }
        @if(!empty($detail_field))
            params.request.detail_field = '{!! $detail_field !!}';
        @endif
        @if(!empty($detail_value))
            params.request.detail_value = '{!! $detail_value !!}';
        @endif
        
      
       
      
        grid_filters = JSON.stringify(params.request);
      
        window['grid_{{ $grid_id }}'].gridOptions.api.showLoadingOverlay(); 
        fetch('/{{$menu_route}}/aggrid_data', {
            method: 'post',
            body: JSON.stringify(params.request),
            headers: {"Content-Type": "application/json; charset=utf-8"}
        })
        .then(httpResponse => httpResponse.json())
        .then(response => {
           
            params.successCallback(response.rows, response.lastRow);
            @if($pinned_totals)
            if(response.rowTotals){
            window['grid_{{ $grid_id }}'].gridOptions.api.setPinnedBottomRowData(response.rowTotals);
            }
            @endif
            
            window['grid_{{ $grid_id }}'].gridOptions.api.hideOverlay();
            
           
            //window['grid_{{ $grid_id }}'].gridOptions.columnApi.autoSizeAllColumns();
          
        })
        .catch(error => {
            
            window['grid_{{ $grid_id }}'].gridOptions.api.hideOverlay();  
            params.failCallback();
            
        })
    }
};

gridOptions.api.setServerSideDatasource(datasource);
@endif



/** AGGRID FUNCTIONS **/
function refreshRowData{{ $grid_id }}(row_id){
   //console.log('refreshRowData');
   var row_id = parseInt(row_id);
            //console.log(row_id);
   $.ajax({ 
        url: "/{{$menu_route}}/aggrid_refresh_row?row_id="+row_id, 
        beforeSend: function(){
          
        },
        success: function (result) { 
           
            //rowNode.setData(data) or rowNode.setDataValue(col,value)
            let rowNode;
             window['grid_{{ $grid_id }}'].gridOptions.api.forEachNode((node) => {
                 //console.log(node.data);
                 //console.log(row_id);
              if (node.data.id == row_id) {
                  //console.log('match');
                rowNode = node;
              }
            });
            //console.log(rowNode);
            //console.log(result);
            
            if(rowNode){
                //console.log('set');
                rowNode.setData(result);
                window['grid_{{ $grid_id }}'].gridOptions.api.refreshClientSideRowModel()
            
                var row_count = window['grid_{{ $grid_id }}'].gridOptions.api.getDisplayedRowCount();
                $("#rowcount{{ $grid_id }}").text(row_count);;
            }
            
            
          
        }, 
    });
}


function refreshGridData{{ $grid_id }}(row_id = false){
    //console.log('refreshGridData');
    $.ajax({ 
        url: "/{{$menu_route}}/aggrid_refresh_data", 
        beforeSend: function(){
            window['grid_{{ $grid_id }}'].gridOptions.api.showLoadingOverlay(); 
            //window['grid_{{ $grid_id }}'].gridOptions.api.setRowData(null);
        },
        success: function (result) { 
            
            row_data{{ $grid_id }} = result;
            window['grid_{{ $grid_id }}'].gridOptions.api.setRowData(result);
            window['grid_{{ $grid_id }}'].gridOptions.api.hideOverlay();
            
            
            //$("#rowcount{{ $grid_id }}").text(row_count);
        },
        error: function(){
            window['grid_{{ $grid_id }}'].gridOptions.api.hideOverlay();    
        }
    });
}



function sortSidebarColumns{{ $grid_id }}(){
@if(session('role_level') == 'Admin')

    
    var columnState = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getColumnState();
    var columnDefs = window['grid_{{ $grid_id }}'].gridOptions.columnDefs;
   
    
    var sortedColumns = columnDefs.sort(function(a, b) {
      // check if columns are grouped or visible
      var aIsGrouped = columnState.find(function(colState) {
        return colState.colId === a.field && colState.rowGroup;
      });
      var bIsGrouped = columnState.find(function(colState) {
        return colState.colId === b.field && colState.rowGroup;
      });
      var aIsVisible = columnState.find(function(colState) {
        return colState.colId === a.field && !colState.hide;
      });
      var bIsVisible = columnState.find(function(colState) {
        return colState.colId === b.field && !colState.hide;
      });
      
      var aIsValueCol = columnState.find(function(colState) {
        return colState.colId === a.field && colState.aggFunc;
      });
      var bIsValueCol = columnState.find(function(colState) {
        return colState.colId === b.field && colState.aggFunc;
      });
    
      // compare grouped status
      if (aIsGrouped && !bIsGrouped) {
        return -1;
      } else if (!aIsGrouped && bIsGrouped) {
        return 1;
      }
    
      // compare value column status
      if (aIsValueCol && !bIsValueCol) {
        return -1;
      } else if (!aIsValueCol && bIsValueCol) {
        return 1;
      }
    
      // compare visibility status
      if (aIsVisible && !bIsVisible) {
        return -1;
      } else if (!aIsVisible && bIsVisible) {
        return 1;
      }
        if(!a.headerName){
        return -1;    
        }
      // compare header name (A to Z)
      return a.headerName.localeCompare(b.headerName);
    });

    // set custom Columns Tool Panel layout
    var columnsToolPanel = window['grid_{{ $grid_id }}'].gridOptions.api.getToolPanelInstance("columns");
    columnsToolPanel.setColumnLayout(sortedColumns);
@endif
}

function sortFilterColumns{{ $grid_id }}(){
    /*
@if(session('role_level') == 'Admin')

   
    // Get the current column definitions
    var columnDefs = window['grid_{{ $grid_id }}'].gridOptions.columnDefs;
    
    
    var columnState = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getColumnState();
    
    // Get the current filter model
    var filterModel = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterModel();
    // Get the current filter model
    var filterModel =  window['grid_{{ $grid_id }}'].gridOptions.api.getFilterModel();
    
    // Get an array of column IDs that have filters set
    var filteredColumns = Object.keys(filterModel);


    // Sort the column definitions based on whether filters are set and then A to Z
    const sortedColumnDefs = columnDefs
      .sort((a, b) => {
        // Check if a has a filter set and b does not
        if (filteredColumns.includes(a.field) && !filteredColumns.includes(b.field)) {
          return -1;
        }
    
        // Check if b has a filter set and a does not
        if (filteredColumns.includes(b.field) && !filteredColumns.includes(a.field)) {
          return 1;
        }
        
      // check if columns are grouped or visible
      var aIsGrouped = columnState.find(function(colState) {
        return colState.colId === a.field && colState.rowGroup;
      });
      var bIsGrouped = columnState.find(function(colState) {
        return colState.colId === b.field && colState.rowGroup;
      });
      var aIsVisible = columnState.find(function(colState) {
        return colState.colId === a.field && !colState.hide;
      });
      var bIsVisible = columnState.find(function(colState) {
        return colState.colId === b.field && !colState.hide;
      });
      
      var aIsValueCol = columnState.find(function(colState) {
        return colState.colId === a.field && colState.aggFunc;
      });
      var bIsValueCol = columnState.find(function(colState) {
        return colState.colId === b.field && colState.aggFunc;
      });
    
      // compare grouped status
      if (aIsGrouped && !bIsGrouped) {
        return -1;
      } else if (!aIsGrouped && bIsGrouped) {
        return 1;
      }
    
      // compare value column status
      if (aIsValueCol && !bIsValueCol) {
        return -1;
      } else if (!aIsValueCol && bIsValueCol) {
        return 1;
      }
    
      // compare visibility status
      if (aIsVisible && !bIsVisible) {
        return -1;
      } else if (!aIsVisible && bIsVisible) {
        return 1;
      }
    
    
        // Sort by headerName if both have filters set or if neither have filters set
        return a.headerName.localeCompare(b.headerName);
      });



       
    
    
    var filtersToolPanel = window['grid_{{ $grid_id }}'].gridOptions.api.getToolPanelInstance("filters");
    
    filtersToolPanel.setFilterLayout(sortedColumnDefs);
@endif
  */
}

window['grid_{{ $grid_id }}'].gridOptions.api.addEventListener('columnVisible', function(event) {
 
  
   sortSidebarColumns{{ $grid_id }}();
});

window['grid_{{ $grid_id }}'].gridOptions.api.addEventListener('columnRowGroupChanged', function(event) {
  
  
   sortSidebarColumns{{ $grid_id }}();
});

window['grid_{{ $grid_id }}'].gridOptions.api.addEventListener('columnValueChanged', function(event) {
 
  
   sortSidebarColumns{{ $grid_id }}();
});

window['grid_{{ $grid_id }}'].gridOptions.api.addEventListener('filterChanged', function(event) {
    var filter_model = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterModel();
              
    var filter_count =Object.keys(filter_model).length;
  $("#filterscount{{$grid_id}}").text('('+filter_count+')');
  sortFilterColumns{{ $grid_id }}();
});

   
function onGridReady{{ $grid_id }}(params){
    
     @if($master_detail)
     detail_row_selected{{$grid_id}} = false;
     @endif
   
    @if($init_filters)
        var init_filters = {!! json_encode($init_filters) !!}
        window['grid_{{ $grid_id }}'].gridOptions.api.setFilterModel(init_filters);
    @endif
    @if($module_id == 1898)
	setInterval(function(){window['grid_{{ $grid_id }}'].gridOptions.api.refresh();}, 1000*60*2);
	@endif
   
    
   
  
    
    row_data{{ $grid_id }} = {!! json_encode($row_data) !!};
    var row_count = window['grid_{{ $grid_id }}'].gridOptions.api.getDisplayedRowCount();
    //$("#rowcount{{ $grid_id }}").text(row_count);
    @if(!$serverside_model)
    window['grid_{{ $grid_id }}'].gridOptions.api.setRowData(row_data{{ $grid_id }});
    @endif
  
    layout_init{{$grid_id}}();
    @if($access['is_add'] && !in_array($db_table,['call_records_inbound','call_records_outbound','crm_documents','crm_supplier_documents']))
    $('#{{ $grid_id }}Duplicate').attr("disabled","disabled");
    @endif
    
 
    
    @if($access['is_edit'])
    $('#{{ $grid_id }}Edit').attr("disabled","disabled");
    @if($db_table == 'crm_documents' || $db_table == 'crm_supplier_documents')
    $('#{{ $grid_id }}Approve').attr("disabled","disabled");
    @endif
    @endif
    
    @if($access['is_view'] && (in_array($db_table,['crm_documents','crm_supplier_documents','crm_supplier_import_documents'])))
    $('#{{ $grid_id }}View').attr("disabled","disabled");
    
    @endif
    
    @if($access['is_delete'])
    $('#{{ $grid_id }}Delete').attr("disabled","disabled");
    @endif
    
    
    if(init_load){
   
    }
    
    // right click grid header
    //showColumnMenuAfterMouseClick
 
    $(document).on("contextmenu", "#grid_{{ $grid_id }} .ag-header .ag-header-cell", function (e) {
        
        e.preventDefault();
        if ($(this).parents('.ag-details-row').length) {
            return false;
        }
        var col_id = $(this).attr('col-id');
        window['grid_{{ $grid_id }}'].gridOptions.api.showColumnMenuAfterMouseClick(col_id,e);
        return false;
    });
    init_load = false;
    
    @if(!empty($moduleleft_menu) && count($moduleleft_menu) > 0) 
   
    {!! button_menu_selected($module_id, 'moduleleft', $grid_id, 'deselected', false) !!}
    @endif
   
    
    @if($pinned_totals && !$serverside_model && !$show_group_totals)
   
    let pinnedBottomData = generatePinnedBottomData{{ $grid_id }}();
   
    window['grid_{{ $grid_id }}'].gridOptions.api.setPinnedBottomRowData([pinnedBottomData]);
    
    @endif
    first_row_select = true;
    var firstNode = params.api.getDisplayedRowAtIndex(0);
    firstNode.setSelected(true);
    
    window['grid_{{ $grid_id }}'].gridOptions.columnApi.autoSizeAllColumns();
}


function refresh_grid() {
	window['grid_{{ $grid_id }}'].gridOptions.refresh();
}

$(".{{ $grid_id }}Refresh").click(function() {
	window['grid_{{ $grid_id }}'].gridOptions.refresh();
	@if($master_detail)
	window['grid_{{ $grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
	    var master_id = detailGridApi.id.replace('detail_','');
	  
	    
        var post_data = { detail_value: master_id, detail_field: '{{ $detail_module_key }}' };
	  
        $.ajax({ 
            url: "/{{ $detail_menu_route }}/aggrid_detail_data", 
            type: 'post',
            data: post_data,
            beforeSend: function(){
            },
            success: function (result) { 
                detailGridApi.api.setRowData(result);
                
                window['detail_row_data{{$grid_id}}'] = result;
               //return result;
            }, 
        });
	});
	@endif
});



    function getRowNodeId{{ $grid_id }}(params) {
        @if($serverside_model)
        
            // if leaf level, we have ID
            if (params.data.id!=null) {
                return params.data.id;
            }
            
            // this array will contain items that will compose the unique key
            var parts = [];
        
            // if parent groups, add the value for the parent group
            if (params.parentKeys){
                parts.push(...params.parentKeys);
            }
            
            // it we are a group, add the value for this level's group
            var rowGroupCols = params.columnApi.getRowGroupColumns();
            var thisGroupCol = rowGroupCols[params.level];
            if (thisGroupCol) {
                parts.push(params.data[thisGroupCol.getColDef().field]);
            }
            
            return parts.join('-');
        @else
       
        return params.data.rowId;
        @endif
    }



function rowSelected{{ $grid_id }}() {
   
@if($check_doctype)
    doctypes = {!! json_encode($doctypes) !!};
   
@endif
var selected = window['selectedrow_{{ $grid_id }}'];
@if($master_detail)
detail_row_selected{{$grid_id}} = false;
@endif


// LINKED RECORDS DROPDOWN
/*
var show_linked_records = false;
var dropdown_html = '';
@if($master_detail && session('role_level') == 'Admin')
dropdown_html += '<li><button title="Detail Grid" href="{{ url($detail_menu_route) }}" data-target="view_modal" class="k-button" ><span  class="e-btn-icon fa fa-list"></span> Detail Grid</button></li>';
@endif
$.each(selected, function(k,v){
    if(k.startsWith("join_") && v > ''){
        var btn_text = k.replace("join_", "");
        var btn_text = btn_text.replace("_", " ");
        var btn_text = v+' - '+btn_text;
        show_linked_records = true;
        dropdown_html += '<li><button title="'+v+'" data-target="view_modal" class="k-button" href="linkedrecords/{{$module_id}}/'+selected.rowId+'/'+k+'">'+btn_text+'</button></li>';
    }
});
linkedrecordsdropdown{{ $grid_id }}
if(show_linked_records){
    $("#linkedrecords{{ $grid_id }}").show();
    $("#linkedrecordsdropdown{{ $grid_id }}").html(dropdown_html);
}else{
    $("#linkedrecords{{ $grid_id }}").hide();
    $("#linkedrecordsdropdown{{ $grid_id }}").html('');
}
*/


@if(!empty($moduleleft_menu) && count($moduleleft_menu) > 0) 

    moduleleft{{ $grid_id }}.refresh();
@endif


    
@if(!empty($moduleright_menu) && count($moduleright_menu) > 0) 

    moduleright{{ $grid_id }}.refresh();
@endif

selected_doctype = null;


@if($check_doctype)
    
    $(doctypes).each(function(i,el){

        if((selected.doctype == el.doctype) || (el.doctype_label > '' && selected.doctype == el.doctype_label )){
            selected_doctype = el;
            

            selected_doctype.doctype_label = selected_doctype.doctype;
            selected_doctype.allow_approve = false;
           
            if(el.approve_permission > '' && (el.approve || el.approve_manager)){
                selected_doctype.approve_permission = el.approve_permission.split(',');
                
                allow_approve = $.inArray(session_role_id,selected_doctype.approve_permission);
                selected_doctype.allow_approve = (allow_approve > -1) ? true : false;
              
            }
            
        }
    });

@endif

@if($access['is_edit'])

if(selected_doctype != null){
if(selected_doctype.editable == 1){
$('#{{ $grid_id }}Edit').removeAttr("disabled");
}else{
$('#{{ $grid_id }}Edit').attr("disabled","disabled");
}
}else{
$('#{{ $grid_id }}Edit').removeAttr("disabled");
}

@endif



@if($access['is_add'] && !in_array($db_table,['call_records_inbound','call_records_outbound','crm_documents','crm_supplier_documents']))
$('#{{ $grid_id }}Duplicate').removeAttr("disabled");
@endif

@if($access['is_approve'])

if(selected_doctype != null){

if(selected_doctype.allow_approve == 1){
$('#{{ $grid_id }}Approve').removeAttr("disabled");
toolbar_button_icon('{{ $grid_id }}Approve','approve', 'Approve '+selected_doctype.doctype_label);
}else{
$('#{{ $grid_id }}Approve').attr("disabled","disabled");
}
}else{
$('#{{ $grid_id }}Approve').attr("disabled","disabled");
}

@endif

@if($access['is_view'] && (in_array($db_table,['crm_documents','crm_supplier_documents','crm_supplier_import_documents'])))
$('#{{ $grid_id }}View').removeAttr("disabled");
@endif

@if($access['is_delete'])
   @if($db_table == 'crm_accounts')
            if(selected.status  != 'Deleted'){
                if(selected.cancelled == "Yes"){
                    toolbar_button_icon('{{ $grid_id }}Delete','restore', 'Undo Cancel');
                    $('#{{ $grid_id }}Delete').removeAttr("disabled");
                }else{
                    toolbar_button_icon('{{ $grid_id }}Delete','cancel', 'Cancel Account');
                    $('#{{ $grid_id }}Delete').removeAttr("disabled");
                }
            }
        @if(check_access('1,34'))
            if(selected.status  == 'Deleted'){
            toolbar_button_icon('{{ $grid_id }}Delete','restore', 'Restore Account');
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
            } 
            
        @endif
    @elseif($db_table == 'sub_services')
        if(selected.status  != 'Deleted'){
        
            if(selected.to_cancel == "Yes"){
                toolbar_button_icon('{{ $grid_id }}Delete','restore', 'Undo Cancel');
                $('#{{ $grid_id }}Delete').removeAttr("disabled");
            }else{
                toolbar_button_icon('{{ $grid_id }}Delete','cancel', 'Cancel Subscription');
                $('#{{ $grid_id }}Delete').removeAttr("disabled");
            }
        }
    @else
    if(selected_doctype != null){
       
        if(selected_doctype.deletable == 1){
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
            toolbar_button_icon('{{ $grid_id }}Delete','delete', 'Delete '+selected_doctype.doctype_label);
        }else if(selected_doctype.creditable == 1){
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
            toolbar_button_icon('{{ $grid_id }}Delete','reverse', 'Credit '+selected_doctype.doctype_label);
        }else{
            $('#{{ $grid_id }}Delete').attr("disabled","disabled");
        }
    
    }else{
      
        @if($db_table == 'crm_suppliers')
            if(selected.status == "Deleted"){
                toolbar_button_icon('{{ $grid_id }}Delete','restore', 'Restore');
                $('#{{ $grid_id }}Delete').removeAttr("disabled");
            }else{
                toolbar_button_icon('{{ $grid_id }}Delete','delete', 'Delete');
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



@if(!empty($moduleright_menu) && count($moduleright_menu) > 0) 
{!! button_menu_selected($module_id, 'moduleright', $grid_id, 'selected', false) !!}
@endif

}

function rowDeselected(){
        
        @if(!empty($moduleleft_menu) && count($moduleleft_menu) > 0) 
        {!! button_menu_selected($module_id, 'moduleleft', $grid_id, 'deselected', false) !!}
        @endif
        
       

        
        @if(!empty($moduleright_menu) && count($moduleright_menu) > 0) 
        {!! button_menu_selected($module_id, 'moduleright', $grid_id, 'deselected', false) !!}
        @endif
     
       
        
}

    var dialogclass = '';
/** BUTTON EVENTS **/
    @if($access['is_import'])
        $("#{{ $grid_id }}Import").click(function(){
             if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
               detail{{ $grid_id }}Import();
            }else{
         sidebarform('{{ $menu_route }}import' , '/{{ $menu_route }}/import', '','', '60%');
            }
        });
    @endif
    
    @if($access['is_add'])
        $("#{{ $grid_id }}Add").click(function(){
            
            if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
               detail{{ $grid_id }}Add();
            }else{
            @if($menu_route == 'pbx_menu')
                sidebarform{{$grid_id}}('{{ $menu_route }}add' , 'pbxmenuedit', 'PBx Menu Add', '','60%');
            @elseif(!empty(request()->account_id) && $documents_module)
                sidebarform('{{ $menu_route }}add' , '/{{ $menu_route }}/edit?account_id={{request()->account_id}}', 'Document - Add', '80%', 'auto');
            @elseif($documents_module)
            sidebarform('{{ $menu_route }}add' , '/{{ $menu_route }}/edit/', 'Document - Add', '80%', 'auto');
            @elseif(!empty($request_get) && !$documents_module)
                var url = '/{{ $menu_route }}/edit?{!! $request_get !!}'+'&layout_id='+window['layout_id{{ $grid_id }}'];
                
                var filter_model = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterModel();
              
                if(Object.keys(filter_model).length > 0){
                    url += '&filter_model='+JSON.stringify(filter_model);
                }
                
                sidebarform{{$grid_id}}('{{ $menu_route }}add' , url, '{!! $menu_name !!} - Add', '{!! $form_description !!}','60%');
            @elseif(!$documents_module)
                var url = '/{{ $menu_route }}/edit'+'?layout_id='+window['layout_id{{ $grid_id }}'];
                
                var filter_model = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterModel();
              
                if(Object.keys(filter_model).length > 0){
                    url += '&filter_model='+JSON.stringify(filter_model);
                }
               
                sidebarform{{$grid_id}}('{{ $menu_route }}add' , url, '{!! $menu_name !!} - Add','{!! $form_description !!}', '60%');
            @endif
            }
        });
    @endif
    
    $(".toolbar_grid_buttons").click(function(e){
        if($(e.target).is('[disabled=disabled]') || $(e.target.parentElement).is('[disabled=disabled]')){
            alert('Select a row');
        }
    });
    
    
    @if($access['is_edit'])
        $("#{{ $grid_id }}Edit").click(function(){
            if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
                detail{{ $grid_id }}Edit();
            }else{
                var selected = window['selectedrow_{{ $grid_id }}'];
                
                @if($documents_module)
                    sidebarform('{{ $menu_route }}edit', '/{{ $menu_route }}/edit/'+ selected.rowId, 'Documents Edit', '80%', '100%');
                @else
                    sidebarform{{$grid_id}}('{{ $menu_route }}edit' , '/{{ $menu_route }}/edit/'+ selected.rowId+'?layout_id='+window['layout_id{{ $grid_id }}'], '{!! $menu_name !!} - Edit', '{!! $form_description !!}','60%');
                @endif
            }
        });
    @endif
    

    @if($access['is_approve'])
      
    $("#{{ $grid_id }}Approve").click(function(){
        if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
            detail{{ $grid_id }}Approve();
        }else{
            var selected = window['selectedrow_{{ $grid_id }}'];
            var check_access = 1;
          
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
    });
    @endif
    
	         
    @if($access['is_view'] && (in_array($db_table,['crm_documents','crm_supplier_documents','crm_supplier_import_documents'])))
    
	  
        $("#{{ $grid_id }}View").click(function(){
            
        if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
            detail{{ $grid_id }}View();
        }else{
            var selected = window['selectedrow_{{ $grid_id }}'];
          
            viewDialog('{{ $menu_route }}'+selected.rowId, '/{{ $menu_route }}/view/'+ selected.rowId,'','70%');
        }
          
        });
    @endif

    
    @if($access['is_add'] && !in_array($db_table,['call_records_inbound','call_records_outbound','crm_documents','crm_supplier_documents']))
        $("#{{ $grid_id }}Duplicate").click(function(){
            
            if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
                detail{{ $grid_id }}Duplicate();
            }else{
                var selected = window['selectedrow_{{ $grid_id }}'];
                gridAjaxConfirm('/{{ $menu_route }}/duplicate', 'Duplicate record?', {"id" : selected.rowId}, 'post');
            }
        });
    @endif
    
    @if($access['is_delete'])
        @if(is_superadmin() && ($db_table == 'crm_accounts' || $db_table == 'sub_services'))    
            $("#{{ $grid_id }}ManagerDelete").click(function(){
                if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
                    detail{{ $grid_id }}ManagerDelete();
                }else{
                    var selected = window['selectedrow_{{ $grid_id }}'];
                    if(selected && selected.status  != 'Deleted'){
                        gridAjaxConfirm('/{{ $menu_route }}/manager_delete', 'Delete Account?', {"id" : selected.rowId}, 'post');
                    }
                }
            });
		@endif
        
        $("#{{ $grid_id }}Delete").click(function(){
            if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
                detail{{ $grid_id }}Delete();
            }else{
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
                        toolbar_button_icon('{{ $grid_id }}Delete','restore', 'Restore Account');
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
                        toolbar_button_icon('{{ $grid_id }}Delete','delete', 'Delete');
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
        });
    @endif
    
    @if($access['is_view'])
        $("#{{ $grid_id }}Export").click(function(){
            if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
                detail{{ $grid_id }}Export();
            }else{
                window['grid_{{ $grid_id }}'].gridOptions.api.exportDataAsExcel({fileName: '{{$menu_name}}.xlsx'});
            }
        });
    @endif      
    
    
  
    
    $(document).off('click','.gridimage').on('click','.gridimage', function(){
       imgDialog($(this).attr('src')); 
    });
    
    
    
    
   
    
    
    @if($pinned_totals)
    function generatePinnedBottomData{{ $grid_id }}(){
        // generate a row-data with null values
        let result = {};
    
        window['grid_{{ $grid_id }}'].gridOptions.columnApi.getAllGridColumns().forEach(item => {
            result[item.colId] = null;
        });
        return calculatePinnedBottomData{{ $grid_id }}(result);
    }
    
    function calculatePinnedBottomData{{ $grid_id }}(target){
    
        //list of columns for aggregation
        let columnsWithAggregation = {!! json_encode($pinned_total_cols) !!}
       
        columnsWithAggregation.forEach(element => {
           
            window['grid_{{ $grid_id }}'].gridOptions.api.forEachNodeAfterFilter((rowNode) => {
               
                if (rowNode && rowNode.data && rowNode.data[element])
                    target[element] += Number(parseInt(rowNode.data[element]).toFixed(2));
            });
           
            if (target[element])
                target[element] = `${target[element].toFixed(2)}`;
        })
        
        return target;
    }
    @endif

    
    
 

   
    


    
$(document).off('click', '#copyrow{{ $grid_id }}').on('click', '#copyrow{{ $grid_id }}', function() {  
    window['grid_{{ $grid_id }}'].gridOptions.api.copySelectedRowsToClipboard();
});


$(document).off('click', '#showdeleted{{ $grid_id }}').on('click', '#showdeleted{{ $grid_id }}', function() {  
    if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
        showdeleteddetail{{ $grid_id }}();
    }else{
        if(show_deleted{{ $grid_id }} == 0){
            $.get( "filter_soft_delete/{{$module_id}}/1", function( data ) {
            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            });
            show_deleted{{ $grid_id }} = 1;
        }else{
        
            $.get( "filter_soft_delete/{{$module_id}}/0", function( data ) {
            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            });
            show_deleted{{ $grid_id }} = 0;
        }
    }
});


$(document).off('click', '#filterclear{{ $grid_id }}').on('click', '#filterclear{{ $grid_id }}', function() {   
   if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
        filtercleardetail{{ $grid_id }}();
    }else{
    if(filter_cleared{{ $grid_id }} == 0){
        //searchtext{{ $grid_id }}.value = ' ';
        // save temp state
        var temp_state = {};
        temp_state.colState = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getColumnState();
        
       
        temp_state.filterState = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterModel();
      
        window['gridstate_{{ $grid_id }}'] = temp_state;
        
        window['grid_{{ $grid_id }}'].gridOptions.api.setFilterModel(null);
        window['grid_{{ $grid_id }}'].gridOptions.api.onFilterChanged();
        filter_cleared{{ $grid_id }} = 1;
        searching_detail = false;
        searching_detail_ids = [];
      
        
       
    }else{
    
        // restore temp state
        if( window['gridstate_{{ $grid_id }}']){
       
            if(window['gridstate_{{ $grid_id }}'].colState){ 
            window['grid_{{ $grid_id }}'].gridOptions.columnApi.applyColumnState({state:window['gridstate_{{ $grid_id }}'].colState,applyOrder: true,});
            }
           
           
            
            if(window['gridstate_{{ $grid_id }}'].filterState){
            window['grid_{{ $grid_id }}'].gridOptions.api.setFilterModel(window['gridstate_{{ $grid_id }}'].filterState);
            }
            
            window['grid_{{ $grid_id }}'].gridOptions.api.onFilterChanged();
            filter_cleared{{ $grid_id }} = 0;
            searching_detail = false;
            searching_detail_ids = [];
            
        }
       
        
    }
    
    if(searchtext{{ $grid_id }}.value == '' || searchtext{{ $grid_id }}.value == null){
        window['grid_{{ $grid_id }}'].gridOptions.api.setQuickFilter(null);
        @if($serverside_model)
        window['grid_{{ $grid_id }}'].gridOptions.refresh();
        @endif
    }else{
        
        var search_val = searchtext{{ $grid_id }}.value;
        if(search_val == 'Yes'){
            search_val = '1';    
        }
        if(search_val == 'No'){
            search_val = '0';    
        }
        window['grid_{{ $grid_id }}'].gridOptions.api.setQuickFilter(search_val);
        @if($serverside_model)
        window['grid_{{ $grid_id }}'].gridOptions.refresh();
        @endif
    }
    
    @if($serverside_model)
    window['grid_{{ $grid_id }}'].gridOptions.refresh();
    @endif
    }
});

function toolbar_button_icon(id, icon, title = ''){
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
    
  
    
    if(icon == 'restore'){
       $("#"+id).html('<span  class="e-btn-icon fa fa-trash-restore"></span>'); 
       if(title == ''){
       title = 'Restore';
       }
    }
    
    if(icon == 'reverse'){
       $("#"+id).html('<span  class="e-btn-icon fa fa-undo"></span>'); 
       if(title == ''){
       title = 'Reverse';
       }
    }
    
    $("#"+id).attr('title',title); 
}
</script>


@endsection

@section('page-styles')
@parent
<style >
div[col-id="sort_order"].ag-cell{
    padding-right: 0px;
}
div[col-id="sort_order"] .ag-cell-wrapper{
    display: flex;
    flex-direction: row-reverse;
}

div[col-id="sort_order"] .ag-cell-value{
    text-align: right;
    padding-right: 5px;
}

#toolbar_template_search{{ $grid_id }} .e-input-group{
border-top-left-radius: 4px;
border-bottom-left-radius: 4px;
}
.subtitle{
    font-family: "Segoe UI", Arial, Sans-serif !important ;
}

.ag-theme-alpine .ag-cell-inline-editing {
    height: 30px;
}
.grid-title{ 
    font-size: 14px;
    display: table-cell;
    vertical-align: middle;
}
.grid_subtitle{
    display: table-cell;
    vertical-align: middle;
    font-size: 12px;
    line-height: 18px;    
}
#toolbar_template_layouts_right{{ $grid_id }}{
height:100%;
display:table;
margin: 0 auto;
min-width:145px;
}
.ag-cell-wrapper.ag-row-group {
    align-items: inherit !important;
}

.accountinfo_item:not(.e-separator){
    height: 30px !important;
    line-height: 30px !important;
}
.accountinfo_item:not(.e-separator) a{
    padding-left:5px !important;
    font-size:13px !important;
}

.accountinfo_heading{
    font-weight:bold;    
}

.accountinfo_bold{
    font-weight:bold;    
}
.ag-theme-alpine .ag-details-row {
    padding: 10px !important;
}
.grid-toolbar .space-right{
    margin-right: 7px;    
}

.e-dropdown-popup ul .e-item.accountinfo_heading .e-menu-icon {
   margin-right: 5px;
}
.k-button-group .k-button.tracking{
border-radius:0px !important;    
}
.ag-toolpanel-buttons .k-button{
border-radius:0px !important;    
}
.k-button-group.ag-toolpanel-buttons .k-button:first-child, .k-button-group .k-group-start{
border-radius:0px !important;    
}

.k-button-group.ag-toolpanel-buttons .k-button:last-child, .k-button-group .k-group-end{
border-radius:0px !important;    
}
.searchinputgroup .e-clear-icon-hide{
display:flex !important;    
}

#files_form{{ $grid_id }} .e-file-select-wrap {
    display: none;
}
#files_form{{ $grid_id }} #droparea{{ $grid_id }} .e-upload {
    border: 0;
    margin-top: 15px;
}

#files_form{{ $grid_id }} #droparea{{ $grid_id }} {
    min-height: 18px;
    border: 1px dashed #c3c3cc;
}
.contact-card .card-body{font-size:1rem}
#notes_results{{ $grid_id }}{
user-select: text !important;
overflow:scroll !important;
height:calc(100% - 140px);
}
#files_results{{ $grid_id }}{
overflow:scroll !important;
height:calc(100% - 140px);
}
.ag-layouts-content{
height:100%;   
}
#gridheadertoolbar{{ $grid_id }}, #gridheadertoolbar{{ $grid_id }} .e-toolbar-items{
    background-color: #d4d4d4;
}
#toolbar_template_layouts{{ $grid_id }} li{
 
    align-items: center;
    text-align:center;
}




#toolbar_template_layouts{{ $grid_id }} .e-menu-item.k-button.layout_active{
	background-color:#e9e9e9 !important;
	font-weight: 600;
}
@if($iframe)
#gridcontainer{{ $grid_id }} .ag-side-buttons, #gridcontainer{{ $grid_id }} .gridheader{
  display: none !important;  
}
@endif
.panel-w-100{width:100% !important;}
.e-menu-wrapper .e-ul .e-menu-item .e-menu-icon, .e-menu-container .e-ul .e-menu-item .e-menu-icon {
  
    line-height: 26px;
}

.ag-column-select {
   
    flex: 1 1 0px;
}
.e-menu-item.fw-bold {
    font-weight: bold;
}
.e-menu-wrapper ul .e-menu-item.layout-header.e-disabled {
    pointer-events: auto;
}
</style>
@endsection