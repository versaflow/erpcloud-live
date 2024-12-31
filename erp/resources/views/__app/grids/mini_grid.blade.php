@extends('__app.layouts.panel' )

@php



if(empty(request()->all()) ||count(request()->all()) == 0){
    $request_get = '';
}else{
    $request_get = http_build_query(request()->all());
}

@endphp
@include('__app.grids.partials.toolbar')
@include('__app.grids.partials.cards', ['module_cards' => $module_cards,'module_id'=>$module_id,'grid_id'=>$grid_id])

@include('__app.grids.grid_form')

@if($master_detail && !$drill_down)
@include('__app.grids.grid_details', $detail_grid)
@endif
@if(empty($is_primary_tab))
@section('content')
@else
@section('primary_tab')
@endif
@if($master_detail && !$drill_down)
@yield('detail_content')
@endif

<style id="conditional_styles{{$module_id}}">
@foreach($cell_styles as $cell_style)
.ag-row .ag-cell-style{{$cell_style->id}} {
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
.ag-row.ag-row-style{{$row_style->id}} {
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
@if($master_detail && !$drill_down)
@if($detail_grid['cell_styles'])
@foreach($detail_grid['cell_styles'] as $cell_style)
.ag-row .ag-cell-style{{$cell_style->id}} {
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
.ag-row.ag-row-style{{$row_style->id}} {
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
   
    color:#000;
    font-weight: 500;
}
.name-field{
 
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




.communications_btn .e-caret{
    height: 6px !important;
}
/*
.communications_btn .e-caret{
    display:none !important;
}
*/

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
.admin_buttons .e-btn{
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



 /*   
#gridheadertoolbar{{ $grid_id }}{
    border-bottom: 1px solid #babfc7 !important;
}
*/
/*
#gridheadertoolbar{{ $grid_id }}, #gridheadertoolbar{{ $grid_id }} .e-toolbar-items {
    background: rgb(25 69 126 / 60%);
}
*/
/*
#detailheadertoolbar{{ $grid_id }}{
    border-bottom: 1px solid #babfc7 !important;
}
*/
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

<div class="card gridcontainer" id="gridcontainer{{ $grid_id }}">
    
<div class="card-header p-0 b-radius-top d-none">
@yield('layouts_toolbar')
</div>

<div class="card-body p-0 @if(!empty($chart_container)) bg-red @endif">
@if(!empty($chart_container))
<div id="{{$chart_container}}" class="ag-theme-alpine d-none"  style="height:400px"></div>
<div id="aggrid-chart-loader{{ $grid_id }}" class="aggrid-chart-loader">
    <div class="ph-item">
        <div class="ph-col-12">
            <div class="ph-picture"></div>
                <div class="ph-row">
                    <div class="ph-col-12"></div>
                    <div class="ph-col-12"></div>
                    <div class="ph-col-12"></div>
                </div>
        </div>
    </div>
</div>
@endif
<div id="grid_{{ $grid_id }}" class="ag-theme-alpine layout_mode aggrid @if(!empty($chart_container)) d-none @endif @if(!empty($chart_container)) bg-red @endif" style="height: 100% !important;min-height:100px"></div>
</div>

</div>

<div class="grid_menubtn{{$module_id}}"></div>
<div class="status_buttonsbtn{{$module_id}}"></div>
<div class="related_items_menubtn{{$module_id}}"></div>
@endsection


@push('footer_assets')


@endpush

@push('page-scripts')

<script>
@if(!empty($detail_cell_renderer))
class DetailCellIFrameRenderer{{$grid_id}} {
    {!! $detail_cell_renderer !!}
}
@endif
</script>
<script>
 

to_expand_nodes{{$grid_id}} = 0;
datasource_to_expand_nodes{{$grid_id}} = 0;

</script>
<script>
@if($app_id == 12 || $module_id == 1929 || $module_id == 334)
//pbx menu access group account ids
{!! get_pbx_domain_groups_js(); !!}
@endif

first_row_select{{$grid_id}} = false;

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
                window['grid_{{ $grid_id }}'].gridOptions.api.showLoadingOverlay(); 
            },
            success: function (result) { 
                window['grid_{{ $grid_id }}'].gridOptions.api.hideOverlay(); 
                //////console.log('sort end');
	            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                window['grid_{{ $grid_id }}'].gridOptions.api.hideOverlay(); 
              
	            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            },
        });
    }
}

function onRowDragMove{{ $grid_id }}(event) {
    //var immutableStore = row_data{{ $grid_id }};
    var allRowData = [];

    window['grid_{{ $grid_id }}'].gridOptions.api.forEachNode(function(node) {
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
      
        
        var v_cols = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getValueColumns();
        let v_col_fields = v_cols.map(a => a.colId);
      
       
        var layout = {};
        layout.colState =window['grid_{{ $grid_id }}'].gridOptions.columnApi.getColumnState();
        //////console.log('layout_save');
        //////console.log(layout.colState);
     
        /*
        $(layout.colState).each(function(i, obj){
         
            if($.inArray(obj.colId, displayed_col_fields) !== -1 || $.inArray(obj.colId, v_col_fields) !== -1){
               
                layout.colState[i].hide = false;
            }else{
                layout.colState[i].hide = true;
            }
        });
       */
     
        
       
     
        layout.groupStorage =window['expanded_groups{{$grid_id}}'];
        layout.filterState =window['grid_{{ $grid_id }}'].gridOptions.api.getFilterModel();
        layout.searchtext = searchtext{{ $grid_id }}.value;
        layout.searchtext = '';
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
        @if($master_detail && !$drill_down)
            if(detail_grid_api){
                
                var detail_layout = {};
                detail_layout.colState = detail_grid_api.columnApi.getColumnState();
                detail_layout.groupState = detail_grid_api.columnApi.getColumnGroupState();
                detail_layout.filterState = detail_grid_api.api.getFilterModel();
                data.detail_layout = detail_layout;
            }
        @endif
       
        //////console.log('layoutsave');
        //////console.log(data);
        ////////console.log(window['expanded_groups{{$grid_id}}']);
      
        
        $.ajax({
            type: 'post',
            url: '{{ url($menu_route."/aggrid_layout_save") }}',
            data: data,
    		success: function(data) { 
    		    
        //////console.log('layoutsave result');
        //////console.log(data);
    		    if(data.status != 'success'){
    		        toastNotify(data.message,data.status);
    		    }else{
     
        		    @if($master_detail && !$drill_down)
        		    if(data.detail_col_defs){
        		    window['detail_col_defs{{$grid_id}}'] = data.detail_col_defs;  
        		    }
        		    if(data.detail_settings){
        		    window['detail_settings{{$grid_id}}'] = data.detail_settings;  
        		    }
        		    
            //detail_layout_load{{ $grid_id }}();
        		    @endif
        		    if(save_as_duplicate){
        		    @if(session('role_level') == 'Admin')
        		   
                    refresh_content_sidebar_layouts{{$grid_id}}();
                    @endif
        		    }
        		  
        		    window['layout_id{{ $grid_id }}'] = data.layout_id;
        		    toastNotify(data.message,data.status);
        		    
                    if(data.name){
                    $("#layout_header{{$grid_id}}").html(data.name); 
                    }else{
                    $('#layout_header{{$grid_id}}').html('{{$menu_name}}');
                    }
                    $("#layout_header{{$grid_id}}").attr('data-track_layout',data.track_layout);
                    $("#layout_header{{$grid_id}}").attr('data-layout_type',data.layout_type);
                    $("#layout_header{{$grid_id}}").attr('data-show_on_dashboard',data.show_on_dashboard);
    		    }
    		}
    	});
    }
 
    function layout_load{{$grid_id}}(layout_id, first_load = 0){
        
       
        
       
        if(first_load){
               
                var data = {!! json_encode($layout_init) !!};
               
                load_layout_into_grid{{$grid_id}}(data,layout_id);
               
                
                
        }else{
        
        filter_cleared{{ $grid_id }} = 0;
  
    	var ajax_data = {aggrid: 1, layout_id: layout_id, grid_reference: 'grid_{{ $grid_id }}' };
   
        $.ajax({
            type: 'post',
            url: '{{ url($menu_route."/aggrid_layout_data") }}',
            data: ajax_data,
            beforeSend: function(){
                window['grid_{{ $grid_id }}'].gridOptions.api.showLoadingOverlay(); 
                $('#layoutsbtn_delete{{ $grid_id }}').attr('disabled','disabled');
                $('#layoutsbtn_duplicate{{ $grid_id }}').attr('disabled','disabled');
                $('#layoutsbtn_save{{ $grid_id }}').attr('disabled','disabled');
                
           
            },
    		success: function(data) { 
    		    

               load_layout_into_grid{{$grid_id}}(data,layout_id);
               
		    },
            error: function(jqXHR, textStatus, errorThrown) {
                toastNotify('An error occured', 'error');
                window['grid_{{ $grid_id }}'].gridOptions.api.hideOverlay(); 
            },
    	});
        }
        
        
    }
    
    
    function load_layout_into_grid{{$grid_id}}(data,layout_id){
      //  if ( document.readyState === "complete" ) {
        groupIncludeFooter{{ $grid_id }} = data.group_include_footer;
        groupIncludeTotalFooter{{ $grid_id }} = data.group_include_total_footer;
        
        if(groupIncludeTotalFooter{{ $grid_id }}){
            $("#grid_{{ $grid_id }} .ag-floating-bottom").removeClass('d-none');
        }else{
            $("#grid_{{ $grid_id }} .ag-floating-bottom").addClass('d-none');
        }
        showOpenedGroup{{ $grid_id }} = data.show_opened_group;
        window['layout_type{{$grid_id}}'] = data.layout_type;
        window['layout_global_default{{$grid_id}}'] = data.global_default;
        //////console.log('load_layout_into_grid{{$grid_id}}');
        //////console.log(data);
        @if($master_detail && !$drill_down)
            window['detail_col_defs{{$grid_id}}'] = data.detail_col_defs;
            window['detail_settings{{$grid_id}}'] = data.detail_settings;
        @endif
	    
        var state = JSON.parse(data.settings);
        ////console.log('layout_load');
        ////console.log(data);
        ////////console.log(state);
      
     
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
        
        window['layout_tracking_{{ $grid_id }}'] = 0;
        if(data.track_layout == 1){
        window['layout_tracking_{{ $grid_id }}'] = 1;
        }
        /*
        if(data.pivot_mode == 1){
           window['grid_{{ $grid_id }}'].gridOptions.columnApi.setPivotMode(true);
           
        }else{
           window['grid_{{ $grid_id }}'].gridOptions.columnApi.setPivotMode(false);
        }
        */
       
        if(data.layout_type == 'Report'){
          
            report_toolpanel_enable{{$grid_id}}();
            
        @if(session('role_level') == 'Admin')
                if(data.chart_model){
                    
                    
                    window['chartModel{{$grid_id}}'] = JSON.parse(data.chart_model);
                    
                    
                }
        @endif
            window['grid_{{ $grid_id }}'].gridOptions.api.refreshHeader();
            
         
        }else{
            
            window['grid_{{ $grid_id }}'].gridOptions.columnApi.setPivotMode(false);
            window['grid_{{ $grid_id }}'].gridOptions.columnApi.resetColumnState();
            window['grid_{{ $grid_id }}'].gridOptions.columnApi.resetColumnGroupState();
           
           report_toolpanel_disable{{$grid_id}}();
        }
       
        if(data.auto_group_col_sort){
         
            window['grid_{{ $grid_id }}'].gridOptions.autoGroupColumnDef.sort = data.auto_group_col_sort;    
        }
        if(data.columnDefs){
           window['grid_{{ $grid_id }}'].gridOptions.api.setColumnDefs(data.columnDefs);
            
        }
        
       
      
        if(state){
        
        if(state.colState){
            //////console.log(state.colState);
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
        
        if(state.filterState){
            //set layout grid filter dropdowns
            
            setTimeout(function(){
          
            $.each(state.filterState,function(field_name,obj){
               
                field_name = field_name.replace('join_','');
               
                if(window['layout_filter_'+field_name+'_{{ $grid_id }}']){
                    if(obj.filterType == 'date'){
                        const searchString = obj.type;
                        const matchingObject = window['layout_filter_'+field_name+'_{{ $grid_id }}'].dataSource.find(item => item.value === searchString);
                    
                        if (matchingObject) {
                        const correspondingText = matchingObject.text;
                       
                           window['layout_filter_'+field_name+'_{{ $grid_id }}'].value = matchingObject.text;
                        }
                       
                    }
                    if(obj.filterType == 'set' && obj.values.length == 1){
                        ////console.log('set layout grid filter dropdowns');
                       window['layout_filter_'+field_name+'_{{ $grid_id }}'].value = obj.values[0];
                    }
                }
                
            });
            },500);
            
            //window['layout_filter_{{$layout_field_filter->field}}_{{ $grid_id }}']
        }
        // if(state.searchtext){
       //         searchtext{{ $grid_id }}.value = state.searchtext;
        //    }
            
             
        if(state && state.groupStorage){
            @if($serverside_model)
            to_expand_nodes{{$grid_id}} = 1;
            window['expand_node_state_{{ $grid_id }}'] = state.groupStorage;
            @else
            expandNodes{{ $grid_id }}(state.groupStorage);
            @endif
        }
        }
        window['grid_{{ $grid_id }}'].gridOptions.columnApi.autoSizeAllColumns(true);
       
        /*
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
        */
      
        setTimeout(function(){
           
            var value_column_order =window['grid_{{ $grid_id }}'].gridOptions.columnApi.getValueColumns();
          
            if(state){
            
                if(state.colState){
                    colstate_arr = state.colState;
                    if(!Array.isArray(colstate_arr)){
                    colstate_arr = Object.values(state.colState)
                    }
                    value_column_order.sort(function(a, b) {
                    var aIndex = colstate_arr.findIndex(obj => obj.colId === a.colId);
                    var bIndex = colstate_arr.findIndex(obj => obj.colId === b.colId);
                    
                    return aIndex - bIndex;
                    });
                    var value_column_order_arr = [];
                    $.each(value_column_order,function(i,obj){
                    value_column_order_arr.push(obj.colId);    
                    })
                    
                    window['grid_{{ $grid_id }}'].gridOptions.columnApi.setValueColumns(value_column_order_arr);
                }
            }
     
         },1000);
        
        
        setTimeout(function(){
            sortSidebarColumns{{ $grid_id }}(); 
            sortFilterColumns{{ $grid_id }}();
        },1500);
        if(data.layout_type == 'Report'){
                 setTimeout(function(){
            window['grid_{{ $grid_id }}'].gridOptions.refresh();
        },2000);
        }
      
    
        window['layout_id{{ $grid_id }}'] = layout_id;
        @if(session('role_level') == 'Admin')
        $('#layoutsbtn_delete{{ $grid_id }}').removeAttr('disabled');
        $('#layoutsbtn_duplicate{{ $grid_id }}').removeAttr('disabled');
        $('#layoutsbtn_save{{ $grid_id }}').removeAttr('disabled');
        
        @endif
      
        if(data.name){
        $("#layout_header{{$grid_id}}").html(data.name); 
        }else{
        $('#layout_header{{$grid_id}}').html('{{$menu_name}}');
        }
        
        $("#layout_header{{$grid_id}}").attr('data-track_layout',data.track_layout);
        $("#layout_header{{$grid_id}}").attr('data-layout_type',data.layout_type);
        $("#layout_header{{$grid_id}}").attr('data-show_on_dashboard',data.show_on_dashboard);
      
      
        
       
        window['grid_{{ $grid_id }}'].gridOptions.api.hideOverlay(); 
       
       
        @if($master_detail && !$drill_down)
        window['detail_col_defs{{$grid_id}}'] = data.detail_col_defs;
        window['detail_settings{{$grid_id}}'] = data.detail_settings;
        
        detail_layout_load{{ $grid_id }}();
        @endif
        
        
        if(data.kanban_default){
            
            $("#{{$grid_id}}ToggleKanban").removeClass('d-none');
           
        }else{
            $("#{{$grid_id}}ToggleKanban").addClass('d-none'); 
            $("#kanban_{{$grid_id}}").addClass('d-none');
           
        }
        
        if(data.layout_type  == 'Report' || {{$module_id}} == 519){
        
               setTimeout(function(){
               
         
              
                window['grid_{{ $grid_id }}'].gridOptions.refresh();
             },1000);
            
            
           
        }
        //}
        setTimeout(function(){
        window['grid_{{ $grid_id }}'].gridOptions.columnApi.autoSizeAllColumns(true);
        },1000);
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
    @if(!empty($grid_menu_menu) && count($grid_menu_menu) > 0)   
    var grid_menuMenuItems = @php echo json_encode($grid_menu_menu); @endphp;
    // top_menu initialization
    var grid_menu{{ $grid_id }} = new ej.navigations.Menu({
        items: grid_menuMenuItems,
        orientation: 'Horizontal',
        cssClass: 'grid_menu',
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
                        grid_menu_context{{$module_id}} .enableItems(['Edit Function'], true);        
                    }else{
                        grid_menu_context{{$module_id}} .enableItems(['Edit Function'], false); 
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
            grid_menu_context{{$module_id}} = new ej.navigations.ContextMenu(menuOptions, '#grid_menu_context{{$grid_id}}');
            
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
        },
        beforeItemRender: function(args){
            
            var selected = window['selectedrow_{{ $grid_id }}'];
       
          
          
            var el = args.element;   
            
            $(el).attr("data-menu-id",args.item.menu_id);
            $(el).attr("data-button-function",args.item.button_function);
            
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
    },'#grid_menu_menu{{ $grid_id }}');
    @endif
    
    
    @if(!empty($status_buttons_menu) && count($status_buttons_menu) > 0)   
    var status_buttonsMenuItems = @php echo json_encode($status_buttons_menu); @endphp;
    // top_menu initialization
    var status_buttons{{ $grid_id }} = new ej.navigations.Menu({
        items: status_buttonsMenuItems,
        orientation: 'Horizontal',
        cssClass: 'status_buttons top-menu k-widget e-btn-group',
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
                        status_buttons_context{{$module_id}} .enableItems(['Edit Function'], true);        
                    }else{
                        status_buttons_context{{$module_id}} .enableItems(['Edit Function'], false); 
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
            status_buttons_context{{$module_id}} = new ej.navigations.ContextMenu(menuOptions, '#status_buttons_context{{$grid_id}}');
            
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
        },
        beforeItemRender: function(args){
            
            var selected = window['selectedrow_{{ $grid_id }}'];
       
          
          
            var el = args.element;   
            
            $(el).attr("data-menu-id",args.item.menu_id);
            $(el).attr("data-button-function",args.item.button_function);
            
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
    },'#status_buttons_menu{{ $grid_id }}');
    @endif
    
    

    
    
    @if(!empty($adminbtns_menu) && count($adminbtns_menu) > 0)   
    var adminbtnsMenuItems = @php echo json_encode($adminbtns_menu); @endphp;
    // top_menu initialization
    var adminbtns{{ $grid_id }} = new ej.navigations.Menu({
        items: adminbtnsMenuItems,
        orientation: 'Horizontal',
        cssClass: '',
        created: function(args){
           
            @if(is_superadmin())
            
            $('body').append('<ul id="adminbtns_context{{$grid_id}}" class="m-0"></ul>');
            var context_items = [
                {
                    id: "context_gridtab_edit",
                    text: "Edit Menu",
                    iconCss: "fas fa-list",
                    url: 'sf_menu_manager/{{$module_id}}/module_actions',
                    data_target: 'view_modal',
                },
            ];
            var menuOptions = {
                target: '#adminbtns_menu{{ $grid_id }}',
                items: context_items,
                beforeItemRender: dropdowntargetrender
            };
          
            // Initialize ContextMenu control
            new ej.navigations.ContextMenu(menuOptions, '#adminbtns_context{{$grid_id}}');
            
            @endif
    
        },
        beforeOpen: function(args){
          
            var popup_items = [];
            $(args.items).each(function(i, el){
                popup_items.push(el.text);
            });
        
            var selected = window['selectedrow_{{ $grid_id }}'];
           
            {!! button_menu_selected($module_id, 'adminbtns', $grid_id, 'selected', true) !!}
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
    },'#adminbtns_menu{{ $grid_id }}');
    @endif
    
    
  
    


    
    function get_sidebar_data{{$module_id}}(){
      
        
       refresh_content_sidebar_layouts{{$grid_id}}();
       
        
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
    
    searching_detail = false;
    searching_detail_ids = [];
    @if($master_detail)
    //https://www.ag-grid.com/javascript-data-grid/filter-external/
  
    
    function search_detail() {
      
        searching_detail = true;
        var post_data = {search: searchtext{{ $grid_id }}.value, search_key: '{{$detail_module_key}}' };  
        $.ajax({ 
            url: "/{{ $detail_menu_route }}/aggrid_detail_search", 
            type: 'post',
            data: post_data,
            success: function (result) {
              
                searching_detail_ids = result
                //////console.log(searching_detail_ids);
                //window['grid_{{ $grid_id }}'].gridOptions.api.setQuickFilter(null);
                //window['grid_{{ $grid_id }}'].gridOptions.api.setFilterModel(null);
                window['grid_{{ $grid_id }}'].gridOptions.api.onFilterChanged();
                
                
                //filter_cleared{{ $grid_id }} = 1;
                
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
          //////console.log('doesExternalFilterPass1');
        if (node.data) {
            if(searching_detail_ids.length > 0){
                //////console.log(node.data);
                var node_key = node.data.{{ ($master_module_key) ? $master_module_key : 'id' }};
                //var node_key = node_key.toString();
                var pass = searching_detail_ids.includes(node_key);
                //////console.log('doesExternalFilterPass');
                //////console.log(pass);
                return pass;
            }
        }
        return true;
    }
    
    @endif
  


   
// wrap grid functions to avoid modal conflict
function reload_conditional_styles(module_id){
    
    ////////console.log('reload_conditional_styles');
    //////////console.log(module_id);
    
   $.ajax({
        type: 'get',
        url: '{{ url("getgridstyles") }}/'+module_id,
		success: function(data) { 
    //////////console.log(data);
            $("#conditional_styles"+module_id).html(data);
            setTimeout(function(){
                $("#conditional_styles"+module_id).trigger('contentchanged');
		    },500)
		}
   });
}

$('#conditional_styles{{$module_id}}').bind('contentchanged', function() {
    ////////console.log('conditional_styles contentchanged');
  // do something after the div content has changed
  reload_grid_config{{$module_id}}();
});

function reload_grid_config{{$module_id}}(){
    //////console.log('reload_grid_config');
    
    layout_reload{{$module_id}}(window['layout_id{{ $grid_id }}']);
    
   
     
}


     function layout_reload{{$module_id}}(layout_id){
        //////console.log('layout_reload');
    	var ajax_data = {aggrid: 1, layout_id: layout_id, grid_reference: 'grid_{{ $grid_id }}', query_string: {!! $query_string !!} };
      
        $.ajax({
            type: 'post',
            url: '{{ url($menu_route."/aggrid_layout_data") }}',
            data: ajax_data,
            beforeSend: function(){
                window['grid_{{ $grid_id }}'].gridOptions.api.showLoadingOverlay(); 
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
                //////console.log('beforesend');
            },
    		success: function(data) { 
                //////console.log('success');
                //////console.log(data);
                window['grid_{{ $grid_id }}'].gridOptions.api.hideOverlay(); 
    		    @if($master_detail && !$drill_down)
                window['detail_col_defs{{$grid_id}}'] = data.detail_col_defs;
                window['detail_settings{{$grid_id}}'] = data.detail_settings;
                
    		    
      
                detail_layout_load{{$grid_id}}();
   
                @endif
    		    @if(session('role_level') == 'Admin')
    		    refresh_content_sidebar_layouts{{$grid_id}}();
                @endif
                if(data.columnDefs){
                   window['grid_{{ $grid_id }}'].gridOptions.api.setColumnDefs(data.columnDefs);
               
                }
                var state = JSON.parse(data.settings);
               
               if(data.auto_group_col_sort){
             
                    window['grid_{{ $grid_id }}'].gridOptions.autoGroupColumnDef.sort = data.auto_group_col_sort;    
                }
                 
                
                window['grid_{{ $grid_id }}'].gridOptions.columnApi.autoSizeAllColumns(true);
                    
                // restore temp state
              
               
                 if(window['gridstate_{{ $grid_id }}'].colState){ 
                    window['grid_{{ $grid_id }}'].gridOptions.columnApi.applyColumnState({state:window['gridstate_{{ $grid_id }}'].colState,applyOrder: true});
                }else if(state && state.colState){
                    
                    colstate_arr = state.colState;
                    if(!Array.isArray(colstate_arr)){
                    colstate_arr = Object.values(state.colState)
                    }
                   
                    window['grid_{{ $grid_id }}'].gridOptions.columnApi.applyColumnState({state:colstate_arr,applyOrder: true});
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
                $("#layout_header{{$grid_id}}").html(data.name); 
                }else{
                $('#layout_header{{$grid_id}}').html('{{$menu_name}}');
                }
                $("#layout_header{{$grid_id}}").attr('data-track_layout',data.track_layout);
                $("#layout_header{{$grid_id}}").attr('data-layout_type',data.layout_type);
                $("#layout_header{{$grid_id}}").attr('data-show_on_dashboard',data.show_on_dashboard);
            
                
    		}, 
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                //////console.log('error');
                window['grid_{{ $grid_id }}'].gridOptions.api.hideOverlay(); 
                //////console.log('XHR ERROR ' + XMLHttpRequest.status);
                //////console.log(JSON.parse(XMLHttpRequest.responseText));
            },
    	});
    }
    
    


    $("#gridcontainer{{ $grid_id }}").off("keydown").on("keydown", function(e){
        var modifier = ( e.ctrlKey || e.metaKey );
     
     
      
        @if(is_superadmin() || is_dev())
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
   
   //////console.log(params);
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
                viewDialog("","{{ url($module_fields_url.'?module_id='.$module_id) }}");
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
     menuItems.push({
                name: 'Refresh',
                action: function () {
                    window['grid_{{ $grid_id }}'].gridOptions.refresh();
                },
            });
            
    @if($module_fields_access['is_edit'])
        
        menuItems.push({
            name: 'Edit Field',
            action: function () {
                 var url = "{{ url($module_fields_url.'/edit') }}"+'/'+params.column.colDef.dbid;
                ////////console.log(url);
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
        
        var condition_styles_submenu = [];
        condition_styles_submenu.push({
            name: 'List',
            action: function () {
             
                var field_name = params.column.colDef.db_field;
                field_name.replace('join_');
         
                viewDialog('condition_styles',"{{ url($condition_styles_url.'?module_id='.$module_id) }}"+'&field='+field_name);
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
        });
    @endif
    
    
    menuItems.push('separator');
    menuItems.push('pinSubMenu');
    

  
    return menuItems;
}
  




    



</script>

<script>









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

   
    
   
	
  
	

/** AGGRID COMPONENTS **/
// https://www.ag-grid.com/javascript-data-grid/component-tool-panel/

/** AGGRID PINNED ROW**/
function setStyle(element, propertyObject) {
  for (var property in propertyObject) {
    element.style[property] = propertyObject[property];
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
 
    
    var functions_context = [];
    // grid extra buttons
    /*
   
    var clearfilter_btn = {
        name: 'Clear Filters',
        action: function(){
            
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
        }
    };
    functions_context.push(clearfilter_btn);
    */
    
    var save_layout_btn = {
        name: 'Save Layout',
        action: function(){
            layout_save{{ $grid_id }}();
            
        }
    };
    functions_context.push(save_layout_btn);
    
    if(show_deleted{{ $grid_id }} == 0){
        var show_deleted_btn = {
            name: 'Show Deleted',
            action: function(){
                $.get( "filter_soft_delete/{{$module_id}}/1", function( data ) {
                window['grid_{{ $grid_id }}'].gridOptions.refresh();
                });
                show_deleted{{ $grid_id }} = 1;
                @if($module_id == 1944)
                clear_filters{{$grid_id}}();
                @endif
                
            }
        };
        functions_context.push(show_deleted_btn);
    }else{
        var hide_deleted_btn = {
            name: 'Hide Deleted',
            action: function(){
                $.get( "filter_soft_delete/{{$module_id}}/0", function( data ) {
                window['grid_{{ $grid_id }}'].gridOptions.refresh();
                });
                show_deleted{{ $grid_id }} = 0;
                
                @if($module_id == 1944)
                clear_filters{{$grid_id}}();
                @endif
            }
        };
        functions_context.push(hide_deleted_btn);
    }
   
    var copy_row_btn = {
        name: 'Copy Row',
        action: function(){
            window['grid_{{ $grid_id }}'].gridOptions.api.copySelectedRowsToClipboard();
        }
    };
   // functions_context.push(copy_row_btn);
    @if($access['is_view'])
    
    var print_btn = {
        name: 'Print',
        action: function(){
            onBtnPrint();
        }
    };
    functions_context.push(print_btn);
    @if(is_superadmin())
    var export_btn = {
        name: 'Export',
        action: function(){
            
            if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
                detail{{ $grid_id }}Export();
            }else{
                window['grid_{{ $grid_id }}'].gridOptions.api.exportDataAsExcel({skipRowGroups: true,fileName: '{{$menu_name}}.xlsx'});
            }
        }
    };
    functions_context.push(export_btn);
    
    @endif
    
    @endif
    
  
    @if($access['is_import'])
        
        var import_btn = {
            name: 'Import',
            action: function(){
                if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
                    detail{{ $grid_id }}Import();
                }else{
                    sidebarform('{{ $menu_route }}import' , '/{{ $menu_route }}/import', '','', '50%');
                }
            }
        };
        functions_context.push(import_btn);
    @endif
          
       
  
        
    @if(is_superadmin() && ($db_table == 'crm_accounts' || $db_table == 'sub_services'))
        
        var mdelete_btn = {
            name: 'Manager Delete',
            action: function(){
                if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
                    detail{{ $grid_id }}ManagerDelete();
                }else{
                    var selected = window['selectedrow_{{ $grid_id }}'];
                    //////console.log(selected);
                    if(selected && selected.status  != 'Deleted'){
                        gridAjaxConfirm('/{{ $menu_route }}/manager_delete', 'Delete Account?', {"id" : selected.rowId}, 'post');
                    }
                }
            }
        };
        functions_context.push(mdelete_btn);    
    
    @endif
    
  
 
    var copy_cell_btn = {
        name: 'Copy Cell',
        action: function(e){
            copyToClipboard(params.value);
        }
    };
    result.push(copy_cell_btn);

    //result.push('copy');
    result.push(copy_row_btn);
    result.push('separator');
      @if(is_dev())
    result.push('chartRange');
    result.push('separator');
    @endif
    
    var editmenu_btn = {
        name: 'Edit Grid Menu',
        action: function(){
            viewDialog('editrowcontext{{$grid_id}}','sf_menu_manager/{{$module_id}}/grid_menu');
        }
    };
    functions_context.push(editmenu_btn);
   
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
          //  //////console.log(result);
            $('#'+args.column.colId+"_container").html(result);
            $('#'+args.column.colId+"_container").addClass("grid-editable-cell");
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
  
   var field_db_type = this.colType;
    var field_id = this.colId;
    var field_id = field_id.replace('join_','');
    
   //////console.log(window[field_id+"{{$module_id}}"]);
   if(field_db_type == "dateField"){
    
     var field_val =  window[field_id+"{{$module_id}}"].val();
   }else if(field_db_type == "booleanField"){
     var field_val =  window[field_id+"{{$module_id}}"].checked;
   }else{
    var field_val = window[field_id+"{{$module_id}}"].value;
 
    if(this.colType == "booleanField"){
    var field_val =  $('#'+field_id+"{{$module_id}}").val();
    }
   }
    
    var post_data =  {id: this.rowId, value: field_val, field: field_id }; 
    syncfusion_data[field_id] = field_val;
      
    $.ajax({ 
        url: "/{{$menu_route}}/save_cell", 
        type: 'post',
        data: post_data,
        beforeSend: function(){
     
        },
        success: function (result) { 
            if(result.status != 'success' && result.status != 'skip'){
            toastNotify(result.message,result.status);
            window['grid_{{ $grid_id }}'].gridOptions.api.undoCellEditing();
            }
            if(result.status != 'skip'){
            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            }
        }, 
    });
      
    return $('#'+field_id+"{{$module_id}}").val();
   },200);
   return '<div id="grid-editable-cell"> Saving...</div>';
  }

  // Gets called once after initialised.
  // If you return true, the editor will appear in a popup
  isPopup() {
    return false;
  }
}

  
@endif

window['selectedrow_{{ $grid_id }}'] = null;
groupIncludeFooter{{ $grid_id }} = false;
groupIncludeTotalFooter{{ $grid_id }} = false;
showOpenedGroup{{ $grid_id }} = false;

var gridOptions = {
    suppressAggFuncInHeader: true,
    /*
    @if(is_dev())
    
      editType: 'fullRow',
      onCellValueChanged: onCellValueChanged,
      onRowValueChanged: onRowValueChanged,
    @endif
    */
   
    accentedSort: true,
    @if(!$serverside_model)
    statusBar: {
        statusPanels: [
            {
                statusPanel: LastRefreshStatusBarComponent{{ $master_grid_id }},
                align: 'left',
            },
            
         
            {
                statusPanel: 'agTotalAndFilteredRowCountComponent',
                align: 'right',
            },
            
        ]
    },
    @endif
    enableCellChangeFlash: true,
    maintainColumnOrder: true,
   

    @if($master_detail && !$drill_down)
    isExternalFilterPresent: isExternalFilterPresent,
    doesExternalFilterPass: doesExternalFilterPass,
    @endif
 
    loadingOverlayComponent: CustomLoadingOverlay,
    loadingOverlayComponentParams: {
    loadingMessage: 'One moment please...',
    },
   
    suppressPropertyNamesCheck: true,
    suppressScrollOnNewData: true,
    debounceVerticalScrollbar: true,
    suppressDragLeaveHidesColumns: false,
    getRowId: getRowNodeId{{ $grid_id }}, 
    enableSorting: true,
  
    autoApproveParentItemWhenTreeColumnIsValid: false,
    @if($tree_data)
        excludeChildrenWhenTreeDataFiltering: true,
        //enableGroupEdit: true,
        treeData: true, // enable Tree Data mode
        getDataPath: data => {
            return data.hierachy;
        },
    @else
    
    //suppressDragLeaveHidesColumns: true,
    //suppressGroupRowsSticky:true,
    groupDisplayType: 'groupRows',
    @if(!is_dev() && !is_superadmin())
    pivotPanelShow: 'never',
    @endif
    
    //pivotRowTotals: 'before',
    
    //pivotPanelShow: 'onlyWhenPivoting',
    //groupMaintainOrder: true,
    //rowGroupPanelShow: 'never',
  
    //showOpenedGroup:false,
    groupIncludeFooter: false,
    groupIncludeTotalFooter: false,
    
    pivotMode: true,
    @endif
    getMainMenuItems: getMainMenuItems{{ $grid_id }},
    @if(is_superadmin() || is_manager())
    getContextMenuItems: getContextMenuItems{{$grid_id}},
    @endif
    defaultExcelExportParams: {skipRowGroups: false,fileName: '{{$menu_name}}.xlsx'},
    defaultCsvExportParams: {skipRowGroups: false,fileName: '{{$menu_name}}.csv'},
   
    debug: false,
    onToolPanelVisibleChanged: function(args){
        //////console.log('onToolPanelVisibleChanged');
        //////console.log(args);
    },
 
    onRowDoubleClicked: function(args){
      
    ////console.log('mastergrid onRowDoubleClicked');
    ////console.log(args);
    ////console.log(args.event.target);
       
            
     
        @if($drill_down)
        const node = args.node;
        
        function findGroupIds(groupNode, idArray) {
            ////console.log(groupNode.childrenAfterFilter);
            if (groupNode.childrenAfterFilter) {
                for (const childNode of groupNode.childrenAfterFilter) {
                    if (childNode.data) {
                        // Child node has a data property, it's the lowest level group
                        idArray.push(childNode.data.id);
                    } else if (childNode.childrenAfterFilter) {
                        // Recursively traverse child groups
                        findGroupIds(childNode, idArray);
                    }
                }
            }
        }
        
        if (node.group) {
            ////console.log(node);
            const groupIds = [];
            findGroupIds(node, groupIds);
            const groupIdsString = groupIds.join(','); // Comma-delimited string
            ////console.log('Group IDs:', groupIdsString);
            var master_ids = groupIdsString;
        } else {
            // It's a data row, handle accordingly
           ////console.log('Data Row ID:', node.data.id);
            var master_ids = node.data.id;
        }
       
        ////console.log(master_ids);
        ////console.log('/{{ $detail_menu_route }}?{{ $detail_module_key }}='+ master_ids);
        viewDialog('{{ $detail_menu_route }}drilldown', '/{{ $detail_menu_route }}?drilldown_field={{ $detail_module_key }}&{{ $detail_module_key }}='+ master_ids,'','70%');
        @endif
        
        
        
    ////console.log($(args.event.target).hasClass('detail-expand-field'));
    ////console.log($(args.event.target).closest('.ag-cell'));
    ////console.log($(args.event.target).closest('.ag-cell').hasClass('detail-expand-field'));
    ////console.log($(args.event.target));
        
        
        if ($(args.event.target).hasClass('detail-expand-field') === true || $(args.event.target).closest('.ag-cell').hasClass('detail-expand-field') === true){
            args.node.setExpanded(true);
        }else{
             
            @if($has_cell_editing)
                var open_form = false;
                
              
                if($(args.event.target).hasClass('ag-cell-value')){
                    
                    
                    
                    var open_form = false;
                }else if($(args.event.target).hasClass('ag-cell') && $(args.event.target).hasClass('grid-editable-cell') === false){
                  
                    
                    var open_form = true;
                }else if(!$(args.event.target).hasClass('ag-cell') && $(args.event.target).closest('.ag-cell').hasClass('grid-editable-cell') === false){
                 
                    var open_form = true;
                }
                if( $(args.event.target).parent().length == 0){
                    var open_form = false;
                    
                }
                /*
                //console.log( $(args.event.target));
                //console.log( $(args.event.target).hasClass('ag-cell'));
                //console.log( $(args.event.target).hasClass('grid-editable-cell'));
                //console.log( $(args.event.target).closest('.ag-cell').hasClass('grid-editable-cell'));
                //console.log( 'parent', $(args.event.target).parent().length);
                //console.log( open_form);
                */
      
                
                if(open_form){
                    @if($access['is_edit'])
                        var selected = window['selectedrow_{{ $grid_id }}'];
                        //////console.log(selected);
                        @if($documents_module)
                            sidebarform('{{ $menu_route }}edit', '/{{ $menu_route }}/edit/'+ selected.rowId, 'Documents - Edit', '80%', '100%');
                        @else
                        
                            sidebarform('{{ $menu_route }}edit' , '/{{ $menu_route }}/edit/'+ selected.rowId+'?layout_id='+window['layout_id{{ $grid_id }}'], '{!! $menu_name !!} - Edit', '', '50%');
                        
                        @endif
                    @endif
                }
            @else
                @if($access['is_edit'])
                    var selected = window['selectedrow_{{ $grid_id }}'];
                    //////console.log(selected);
                    @if($documents_module)
                        sidebarform('{{ $menu_route }}edit', '/{{ $menu_route }}/edit/'+ selected.rowId, 'Documents - Edit', '80%', '100%');
                    @else
                    
                        sidebarform('{{ $menu_route }}edit' , '/{{ $menu_route }}/edit/'+ selected.rowId+'?layout_id='+window['layout_id{{ $grid_id }}'], '{!! $menu_name !!} - Edit', '', '50%');
                    
                    @endif
                @endif
            @endif
        }
       
      
    },
    
    @if($master_detail && !$drill_down)
    //detailRowAutoHeight: true,
    isDetailGridMaximized: false,
    detailRowHeight: 280,
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
    enableCharts: true,
    enableRangeSelection: true,
    createChartContainer: createChartContainer{{$grid_id}},
        /* chart loader */
    @if(!empty($chart_container))
    onChartCreated: function(){
   
        // Code to run when the chart is rendered
        //console.log('Chart rendered:', event);
        // You can access the chart instance using event.chart
        // For example: var chart = event.chart;
        $("#aggrid-chart-loader{{ $grid_id }}").addClass('d-none');
        $("#{{$chart_container}}").removeClass('d-none');
        
    
    },
    @endif
    @endif
    @if(session('role_level') != 'Admin')
    suppressContextMenu:true,
    @endif
    suppressCopyRowsToClipboard:true,
    suppressColumnVirtualisation:true,
  
    enableCellTextSelection:false,
    animateRows: false,
    rowSelection: 'single',
   
    multiSortKey: 'ctrl',
    getRowStyle: function (params) {
        if (params.node.rowPinned) {
            return { 'font-weight': 'bold' };
        }
    },
    //suppressMovableColumns: (isMobile()) ? true : false,
    @if($serverside_model)
        pagination: true, 
        
        @if($connection == 'pbx_cdr')
        //paginationPageSize:25,
        //cacheBlockSize: 25,
        paginationAutoPageSize:true,
        @else
        paginationAutoPageSize:true,
        @endif 
        paginationPageSize:400,
        cacheBlockSize: 400,
        serverSideInfiniteScroll: true,
        rowModelType: 'serverSide',
        @if($pinned_totals)
        pinnedBottomRowData: [{}],
        @endif
    @else
        @if($allow_sorting && $has_sort)
        suppressRowDrag: (isMobile()) ? true : false,
        rowDragEntireRow: (isMobile()) ? false : true,
        rowDragManaged: false,
        
        rowDragText: function(params){
            //console.log(params);
            return params.defaultTextValue;
        },
       
        @endif
        @if($pinned_totals)
        pinnedBottomRowData: [{}],
        @endif
    @endif
    pinnedRowCellRenderer: function (render)
    {
    for (var obj_id in render.data)
    {
        if (obj_id == render.column.colId)
        {
            return '<div>' + render.data[obj_id] + '</div>';
        }
    }
    return '<div></div>';
    },
    enableBrowserTooltips: false,
    columnDefs: {!! json_encode($columnDefs) !!},
    columnTypes: {
        
        defaultField: {
            //inWidth:150,
            filter: 'agTextColumnFilter',
            filterParams: {
                suppressAndOrCondition: true,
                /*
                filterOptions: [
                        
                        'equals',
                        'notEqual',
                        'contains',
                        'notContains',
                        'startsWith',
                        'endsWith',
                        'blank',
                        'notBlank',
                ],
                */
            }, 
           
            //cellStyle : { 'text-overflow':'ellipsis','white-space':'nowrap', 'overflow': 'hidden', 'padding': 0 }
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
                pivotComparator: function(a, b) {
                    var dateA = new Date(a);
                    var dateB = new Date(b);
                    return dateB.getTime() - dateA.getTime();
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
                          
                            if( celldate <= date_today){
                              
                                return true;    
                            }else{
                                
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
                         
                            if(celldate >= date_today){
                              
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
                                ////console.log('currentmonth compare error');
                                ////console.log(e);
                                ////console.log(cellValue);
                                
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
                                ////console.log('currentmonth compare error');
                                ////console.log(e);
                                ////console.log(cellValue);
                                
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
                                ////console.log('currentmonth compare error');
                                ////console.log(e);
                                ////console.log(cellValue);
                                
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
                                
                            ////console.log(celldate);
                            ////console.log(nextmonthlastday);
                                
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
                                ////console.log('currentmonth compare error');
                                ////console.log(e);
                                ////console.log(cellValue);
                                
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
                          
                        var key1 = field_values.indexOf(valueB);
                        var key2 = field_values.indexOf(valueA);
                        
                        
                  

    
                        if (key1 === null && key2 === null) {
                            return 0;
                        }
                        if (key1 === null) {
                            return -1;
                        }
                        if (key2 === null) {
                            return 1;
                        }
                        return key2 - key1;
                
                    },
                    
                   
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
            filter: 'agNumberColumnFilter',
         
           
          
            valueFormatter: function(params){
               // if(!params.node.footer){
                   
                    var currency_decimals = params.colDef.currency_decimals;
                    var currency_symbol = params.colDef.currency_symbol;
                    var row_data_currency = params.colDef.row_data_currency;
                  
                    //if(!row_data_currency && params.data && params.data['document_currency']){
                    //    row_data_currency = 'document_currency';
                    //}
                  
                    if(params && params.data && row_data_currency && params.data[row_data_currency]){
                     
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
            /*
            cellRenderer: function(params){
                if(params.data && params.data.id && params.value > ''){
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
            */
            @endif
        },
        emailField: {
            filter: 'agTextColumnFilter',
            @if(session('role_level') == 'Admin')
            /*
            cellRenderer: function(params){
                if(params.data && params.data.id && params.value > ''){
                     var cell_value = params.value;
                     @if($module_id == 343)
                       cell_value = '<a href="/email_form/default/'+params.data.id+'/'+params.value+'" target="_blank" data-target="form_modal">'+params.value+'</a>';
                     @else
                        if(params.data.account_id){
                            cell_value = '<a href="/email_form/default/'+params.data.account_id+'/'+params.value+'" target="_blank" data-target="form_modal">'+params.value+'</a>';
                        }else{
                            cell_value = '<a href="/email_form/default/1/'+params.value+'" target="_blank" data-target="form_modal">'+params.value+'</a>';
                     
                        }
                     @endif
                    return cell_value;
                }else{
                    return params.value;
                }
            },
            */
            @endif
        },
    },
    @if($tree_data)
    autoGroupColumnDef: {
        rowDrag: true,
       
        headerName: 'Title',
        flex: 1,
        maxWidth:500,
        // enabled sorting on Row Group Columns only 
        sortable: true,     
        sort: null,
        @if(!empty($row_tooltips))
        tooltipComponent: 'rowtooltip{{$module_id}}',
        cellRenderer: 'agGroupCellRenderer', // Use the group cell renderer for auto-group columns
        tooltipValueGetter: params => {
        // Your logic to determine the tooltip content
        return `Tooltip for ${params.value}`;
        },
        @endif
    },
    @else
    autoGroupColumnDef: {
        headerValueGetter: getGroupHeaderName,
        // enabled sorting on Row Group Columns only 
        sortable: true,     
        sort: null,
    },
    @endif
    defaultColDef: {
        lockVisible: false,
        getQuickFilterText: function(params) {
            return (!params.column.visible) ? '' : params.value; 
        },
        pivotComparator: function(a, b) {
            const regex = /^\d{4}-\d{2}$/;
            if (regex.test(a) && regex.test(b)) {
                var dateA = new Date(a);
                var dateB = new Date(b);
                return dateB.getTime() - dateA.getTime();
            } else {
                return a.localeCompare(b); // for normal strings, compare alphabetically
            }
        },
        suppressSizeToFit:false,
        flex:1,
        minWidth: 100,
        width: 'auto',
       
        maxWidth:300,
       
     
        enableValue: true,
        enableRowGroup: true,
        // allow every column to be pivoted
        enablePivot: true,
        sortable: true,
        filter: true,
        //floatingFilter: true,
        filterParams: {
            suppressAndOrCondition: true,
            newRowsAction: 'keep',
            buttons: ['reset'],
        },
        
       
        allowedAggFuncs: ['value', 'percentage', 'calc', 'sum', 'min', 'max', 'count', 'avg'],
        //menuTabs: ['filterMenuTab','generalMenuTab','columnsMenuTab'],
        @if(session('role_level') == 'Admin')
        menuTabs: ['filterMenuTab','columnsMenuTab','generalMenuTab'],
        @else
        menuTabs: [],
        @endif
        @if($tree_data)
        cellClassRules:treeRowClassRules,
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
            //if(params.rowNode.level === 0){
            //    return '';
            //}
           
           // if(params && params.rowNode && params.rowNode.group === true){
               // return '';
            //}
            let val = '';
            //if(params.values.length === 1){
            params.values.forEach(value => val = value);
            //}
            return val;
        },
        'percentage': params => {
           
            let val = '';
            params.values.forEach(value => val = value);
            return val;
        }
    },
    autoSizeStrategy: {
        type: 'fitCellContents',
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
        ],
        defaultToolPanel: '',
    },
    processToolPanel(params) {
      //console.log('processToolPanel');  
      //console.log(params); 
    },
    
    @endif
    components: {
        @if(!empty($module_footer_cards))
        FooterCardsStatusBarComponent{{ $master_grid_id }}: FooterCardsStatusBarComponent{{ $master_grid_id }},
        @endif
      
        LastRefreshStatusBarComponent{{ $master_grid_id }}: LastRefreshStatusBarComponent{{ $master_grid_id }},
    
        @if($has_cell_editing || $inline_editing)
        SyncFusionCellEditor{{ $grid_id }}:SyncFusionCellEditor{{ $grid_id }},
        @endif
      
        CustomLoadingOverlay: CustomLoadingOverlay,
        booleanCellRenderer: booleanCellRenderer,
        progressCellRenderer: progressCellRenderer,
       
        @if(!empty($row_tooltips))
        rowtooltip{{$module_id}}: rowtooltip{{$module_id}},
        @endif
    },
   
    @if(!empty($is_row_master))
    isRowMaster: function (rowNode) {
       {!! $is_row_master !!}
    },
    @endif
    @if(!empty($detail_cell_renderer))
    masterDetail: true,
    detailCellRenderer: DetailCellIFrameRenderer{{$grid_id}},
    @endif
    
    @if($master_detail && !$drill_down)
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
           ////console.log("/{{ $detail_menu_route }}/aggrid_detail_data");
           ////console.log(post_data);
            window['mastergrid_row{{ $grid_id }}'] =params.data;
          
            request_detail_value = master_key;
            request_detail_field = '{{ $detail_module_key }}';
            $.ajax({ 
                url: "/{{ $detail_menu_route }}/aggrid_detail_data", 
                type: 'post',
                data: post_data,
                success: function (result) {
                   ////console.log(result);
                    window['detail_row_data{{ $grid_id }}'] = result;
                    params.successCallback(result);
                   
                }, 
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    ////console.log('XHR ERROR ' + XMLHttpRequest.status);
                    ////console.log(JSON.parse(XMLHttpRequest.responseText));
                },
            });
        },
    },
    @endif
    onFilterChanged: function(params){
      // //////console.log('onFilterChanged');
        if(!first_row_select{{$grid_id}}){
        window['grid_{{ $grid_id }}'].gridOptions.api.deselectAll();
        }
        var row_count = window['grid_{{ $grid_id }}'].gridOptions.api.getDisplayedRowCount();
        $("#rowcount{{ $grid_id }}").text(row_count);
        @if($pinned_totals && !$serverside_model)
        if(groupIncludeTotalFooter{{ $grid_id }}){
        let pinnedBottomData = generatePinnedBottomData{{ $grid_id }}();
       
        window['grid_{{ $grid_id }}'].gridOptions.api.setPinnedBottomRowData([pinnedBottomData]);
        }
        @endif
      
        @if($serverside_model)
        window['grid_{{ $grid_id }}'].gridOptions.refresh();
        @else
          window['grid_{{ $grid_id }}'].gridOptions.api.refreshClientSideRowModel('aggregate');
        //window['grid_{{ $grid_id }}'].gridOptions.api.refreshCells({force:true});
        @endif
      
    },

    onRowClicked: function(event){
      
     
      ////console.log('mastergrid onRowClicked');
      ////console.log(event);
      /////console.log(event.node.selected);
      ////console.log(event.detail);
      /*
     if(event.node.selected === true && event.detail == 1){
            
     // //console.log('mastergrid onRowClicked 2');
            var deselected = event.node.data;
            if(window['selectedrow_{{ $grid_id }}'] && deselected.{{$db_key}} == window['selectedrow_{{ $grid_id }}'].rowId){
            
      ////console.log('mastergrid onRowClicked 3');
                window['selectedrow_{{ $grid_id }}'] = null;
                window['selectedrow_node_{{ $grid_id }}'] = null;
                //rowDeselected{{$grid_id}}();
                window['grid_{{ $grid_id }}'].gridOptions.api.deselectAll();
            }
        }
        */  
        
          
       
    },
 
 
    onRowSelected: function(event){
        //window['grid_{{ $grid_id }}'].gridOptions.api.closeToolPanel();
   
        //if(active_requests == 0){ 
            
            if(first_row_select{{$grid_id}}){
               
                setTimeout(function(){first_row_select{{$grid_id}} = false; },500)
                //////console.log(event.node);
                
                window['selectedrow_{{ $grid_id }}'] = event.node.data;
                window['selectedrow_{{ $grid_id }}'].rowId = window['selectedrow_{{ $grid_id }}'].{{$db_key}};
                
                window['selectedrow_node_{{ $grid_id }}'] = event.node;
                
                @if($master_detail && !$drill_down)
                    window['grid_{{ $grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
                            detailGridApi.api.deselectAll();
                    });
                @endif
                $("#grid_menu_menu{{ $grid_id }}").removeClass('d-none');
                $("#adminbtns_menu{{ $grid_id }}").removeClass('d-none');
                $("#grid_menu_menudetail{{ $grid_id }}").addClass('d-none');
                
                $("#status_buttons_menu{{ $grid_id }}").removeClass('d-none');
                $("#status_buttons_menudetail{{ $grid_id }}").addClass('d-none');
                $(".status_dropdown{{ $grid_id }}").removeClass('d-none');
                $(".status_dropdowndetail{{ $grid_id }}").addClass('d-none');
                $("#grid_{{ $grid_id }}").removeClass('detailgrid-focus').addClass('mastergrid-focus');
                rowSelected{{ $grid_id }}();
            }else{
                
               
              if(!event.node.isSelected()){
                
               
                var deselected = event.node.data;
                if(window['selectedrow_{{ $grid_id }}'] && deselected.{{$db_key}} == window['selectedrow_{{ $grid_id }}'].rowId){
                  
                    window['selectedrow_{{ $grid_id }}'] = null;
                    window['selectedrow_node_{{ $grid_id }}'] = null;
                    rowDeselected{{$grid_id}}();
                }
            }
            if(event.node.isSelected() && event.node.group == false){
              
               
                window['selectedrow_{{ $grid_id }}'] = event.node.data;
                window['selectedrow_{{ $grid_id }}'].rowId = window['selectedrow_{{ $grid_id }}'].{{$db_key}};
                
                window['selectedrow_node_{{ $grid_id }}'] = event.node;
               
                @if($master_detail && !$drill_down)
                    window['grid_{{ $grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
                            detailGridApi.api.deselectAll();
                    });
                   
                    
                   
                      
                   
                    
                @endif
                    $("#grid_menu_menu{{ $grid_id }}").removeClass('d-none');
                    $("#grid_menu_menudetail{{ $grid_id }}").addClass('d-none');
                    $("#status_buttons_menu{{ $grid_id }}").removeClass('d-none');
                    $("#status_buttons_menudetail{{ $grid_id }}").addClass('d-none');
                    $(".status_dropdown{{ $grid_id }}").removeClass('d-none');
                    $(".status_dropdowndetail{{ $grid_id }}").addClass('d-none');
                    $("#grid_{{ $grid_id }}").removeClass('detailgrid-focus').addClass('mastergrid-focus');
                rowSelected{{ $grid_id }}();
            }  
            }
        //}
       
       
    }, 
 
   
 
    onColumnVisible: function(params){
        //https://www.ag-grid.com/javascript-data-grid/pivoting/#pivot-mode--visible-columns
        ////console.log('onColumnVisible',params);
        var pivotMode =window['grid_{{ $grid_id }}'].gridOptions.columnApi.isPivotMode();
        if(pivotMode){
            if(params.column && params.source == 'api' && params.type == 'columnVisible' && params.visible == false){
                hide_value_column(params.column.colId)
            }
        }

    },
    @if($master_detail && !$drill_down)
    onRowGroupOpened: function(params){
        
         ////console.log('onRowGroupOpened',params);
        if(params.expanded){
         ////console.log('onRowGroupOpened',11);
            window['grid_{{ $master_grid_id }}'].gridOptions.isDetailGridMaximized = false;
            window['grid_{{ $master_grid_id }}'].gridOptions.api.forEachNode(function (node) {
                 ////console.log(node)
          
                if(node.expanded && node.id != params.node.id && node.groupData == null){
                 ////console.log('close node');
                   
                    node.setExpanded(false);
                }
            });
        }
        
         
          if (restoringExpandedNodes{{$grid_id}}) {
            return;
          }
         
        
        var expandedNodes = [];
        
        window['grid_{{ $grid_id }}'].gridOptions.api.forEachNode(function (node) {
        if (node.expanded) {
        expandedNodes.push(node.id);
        }
        });
       
        window['expanded_groups{{$grid_id}}'] = expandedNodes;
        if(params.expanded){
            $("#grid_menu_menu{{ $master_grid_id }}").addClass('d-none');
            $("#adminbtns_menudetail{{ $grid_id }}").removeClass('d-none');
            $("#grid_menu_menudetail{{ $grid_id }}").removeClass('d-none');
            
            $("#status_buttons_menu{{ $master_grid_id }}").addClass('d-none');
            $("#status_buttons_menudetail{{ $grid_id }}").removeClass('d-none');
            
            $(".status_dropdown{{ $master_grid_id }}").addClass('d-none');
            $(".status_dropdowndetail{{ $grid_id }}").removeClass('d-none');
            
            $("#grid_{{$master_grid_id}}").addClass('detailgrid-focus').removeClass('mastergrid-focus');
            
        }else{
            
        }
    },
    @elseif($tree_data)
    onRowGroupOpened: function (params) {
        
        if (restoringExpandedNodes{{$grid_id}}) {
            return;
        }
        
        let expandedNodeDetails = [];
        params.api.forEachNode(node => {
        
            if (node.expanded) {
                let expandedDetails = getExpandedDetails{{$grid_id}}(node);
                expandedNodeDetails.push(expandedDetails);
            }
        });
       
        window['expanded_groups{{$grid_id}}'] = JSON.stringify(expandedNodeDetails); 
        
        var expandedNodes = [];
        
        window['grid_{{ $grid_id }}'].gridOptions.api.forEachNode(function (node) {
            if (node.expanded) {
                expandedNodes.push(node.id);
            }
        });
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
          
        var expandedNodes = [];
        
        window['grid_{{ $grid_id }}'].gridOptions.api.forEachNode(function (node) {
        if (node.expanded) {
        expandedNodes.push(node.id);
        }
        });
        //////console.log('save expandedNodes');
        //////console.log(expandedNodes);
        
        //////console.log(window['expanded_groups{{$grid_id}}']);
    },
    @endif
    onModelUpdated: function(args){
    
      @if($serverside_model)
            if(args.keepRenderedRows && datasource_to_expand_nodes{{$grid_id}} && window['expand_node_state_{{ $grid_id }}']){
              
                //////console.log('onModelUpdated datasource expand');
                expandNodes{{ $grid_id }}(window['expand_node_state_{{ $grid_id }}']);
                datasource_to_expand_nodes{{$grid_id}} = 0;
            }
            @endif 
        
    },
    onViewportChanged: function(args){
     
        this.columnApi.autoSizeAllColumns(true);
        
        
    },
    onRowDataUpdated:  function(args){
        ////console.log('rowDataUpdated',args);
        
         @if(session('role_level') == 'Admin')
                if(window['chartModel{{$grid_id}}']){
                    setTimeout(function(){
                    
                  
                    restoreChart{{$grid_id}}();
                    
                    },1000)
                }else{
                    clearChart{{$grid_id}}();
                }
        @endif
        
        window['grid_{{ $grid_id }}'].gridOptions.columnApi.autoSizeAllColumns(true);
    },
    onFirstDataRendered:  function(args){
    ////console.log('onFirstDataRendered',args);
       
    //setTimeout(window['grid_{{ $grid_id }}'].gridOptions.columnApi.autoSizeAllColumns(true),1500)
    
    @if(!empty($workspace_filter_placeholder))
    
        try{
            setTimeout(function(){
            var filterInstance = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterInstance('join_user_id'); 
           
           //   //console.log(filterInstance);
            ////console.log('{{$workspace_filter_placeholder}}');
            // Set the filter model
            filterInstance.setModel({
            filterType: 'set',
            values: ['{{$workspace_filter_placeholder}}'],
            });
            
            window['grid_{{ $grid_id }}'].gridOptions.api.onFilterChanged();
            },2000)
        }catch(e){
            //console.log(e);
        }
    @endif
    },
   
    onStoreRefreshed:  function(args){
    
    },
    onGridReady: onGridReady{{ $grid_id }},
    @if($serverside_model) 
    refresh: function(){
     
        @if(!empty($module_cards))
        refresh_module_cards{{$module_id}}();
        @endif
        @if(!empty($module_footer_cards))
        refresh_module_footer_cards{{$grid_id}}();
        @endif
        
        var timestamp = getRefreshTime();
        $("#last_refresh_time{{$grid_id}}").text(timestamp);
      
      
        var selected_row_before_refresh = window['selectedrow_{{ $grid_id }}'];
    
        window['grid_{{ $grid_id }}'].gridOptions.api.deselectAll();
        window['grid_{{ $grid_id }}'].gridOptions.api.refreshServerSide();
        setTimeout(function(){
            if( selected_row_before_refresh){
                window['grid_{{ $grid_id }}'].gridOptions.api.forEachNode((node) => {
                     ////////console.log(node.data);
                     ////////console.log(row_id);
                  if (node.data.{{$db_key}} == selected_row_before_refresh.rowId) {
                    node.setSelected(true, true);
                    
                  }
                });
            }
        },500)
        
    },
    @else
    refresh: function(data = false){
     
        @if(!empty($module_cards))
        refresh_module_cards{{$module_id}}();
        @endif
        
        @if(!empty($module_footer_cards))
        refresh_module_footer_cards{{$grid_id}}();
        @endif
      
        
        var timestamp = getRefreshTime();
        $("#last_refresh_time{{$grid_id}}").text(timestamp);
      
        if(!data){
            @if($master_detail && !$drill_down)
                if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
                    refresh_detail_grid{{'detail'.$grid_id}}();
                }else{
                    ////console.log('refresh 1');
                    refreshGridData{{ $grid_id }}();
                }
            @else
                    ////console.log('refresh 2');
                refreshGridData{{ $grid_id }}();
            @endif
        }else{
            ////////console.log(data);
         @if($tree_data)
            refreshGridData{{ $grid_id }}();
         @elseif($master_detail && !$drill_down)
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
        }
        
        @if($pinned_totals && !$serverside_model)
         setTimeout(()=>{
             if(groupIncludeTotalFooter{{ $grid_id }}){
        let pinnedBottomData = generatePinnedBottomData{{ $grid_id }}();
       
        window['grid_{{ $grid_id }}'].gridOptions.api.setPinnedBottomRowData([pinnedBottomData]);
             }
       // window['grid_{{ $grid_id }}'].gridOptions.api.redrawRows();
         },1000)
        @endif
    },
    @endif
    @if($allow_sorting && $has_sort)
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


function findAttributes(node, attributeArray) {
  if (node && node.field && node.key) {
    attributeArray.push({ field: node.field, key: node.key });
  }

  if (node.parent) {
    findAttributes(node.parent, attributeArray);
  }
}



currentChartRef{{$grid_id}} = false;

// chart functions 
function createChartContainer{{$grid_id}}(chartRef) {
  if (currentChartRef{{$grid_id}}) {
    currentChartRef{{$grid_id}}.destroyChart();
  }

  const eChart = chartRef.chartElement;
  @if(!empty($chart_container))
  const eParent = document.querySelector("#{{$chart_container}}");
  @else
  const eParent = document.querySelector("#minigridChart");
  @endif
  eParent.appendChild(eChart);
  currentChartRef{{$grid_id}} = chartRef;
}
function saveChart{{$grid_id}}() {
  @if(is_superadmin())
  const chartModels = window['grid_{{ $grid_id }}'].gridOptions.api.getChartModels() || [];
 
 
  if (chartModels.length > 0) {
      window['chartModel{{$grid_id}}'] = chartModels[0];
  }else{
      window['chartModel{{$grid_id}}'] =  [];
  }
  @endif
}

function clearChart{{$grid_id}}() {
  if (currentChartRef{{$grid_id}}) {
    currentChartRef{{$grid_id}}.destroyChart();
    currentChartRef{{$grid_id}} = undefined;
  }
}

function restoreChart{{$grid_id}}() {
    
 
      
  if (!window['chartModel{{$grid_id}}']) return;

  $("#aggrid-chart-loader"+window['layout_id{{ $grid_id }}']).addClass('d-none');
  $("#aggrid-chart"+window['layout_id{{ $grid_id }}']).removeClass('d-none');
  currentChartRef{{$grid_id}} = window['grid_{{ $grid_id }}'].gridOptions.api.restoreChart(window['chartModel{{$grid_id}}']);
}


// dynamically update the tool panel params
function report_toolpanel_enable{{$grid_id}}() {
  ////////console.log('report_toolpanel_enable');
 
    window['grid_{{ $grid_id }}'].gridOptions.enableCharts = false;
    window['grid_{{ $grid_id }}'].gridOptions.enableRangeSelection = false;
    window['grid_{{ $grid_id }}'].gridOptions.api.redrawRows();
    
  
 // if($("#toolbar_template_filters{{ $grid_id }}").length  > 0) {
 //   $("#toolbar_template_filters{{ $grid_id }}").removeClass('d-none');
 //   }
  $('.aggrid').addClass('ag-report');
        
        window['grid_{{ $grid_id }}'].gridOptions.columnApi.setPivotMode(true);
      
        //////console.log('showOpenedGroup{{ $grid_id }}',showOpenedGroup{{ $grid_id }});
        //////console.log('groupIncludeFooter{{ $grid_id }}',groupIncludeFooter{{ $grid_id }});
        //////console.log('groupIncludeTotalFooter{{ $grid_id }}',groupIncludeTotalFooter{{ $grid_id }});
        
        if(showOpenedGroup{{ $grid_id }} && !groupIncludeFooter{{ $grid_id }} && !groupIncludeTotalFooter{{ $grid_id }}){
            // adds inline totals
            window['grid_{{ $grid_id }}'].gridOptions.showOpenedGroup = true;
        }
        
        
        if(groupIncludeFooter{{ $grid_id }} ){
            // adds subtotals
            window['grid_{{ $grid_id }}'].gridOptions.groupIncludeFooter = true;
        //////console.log(111);
        }
        
        
        if(groupIncludeTotalFooter{{ $grid_id }}){
            // includes grand total
            window['grid_{{ $grid_id }}'].gridOptions.groupIncludeTotalFooter = true;
        //////console.log(222);
        }
        
        
    
        var params = {
            suppressPivotMode: false,
            suppressRowGroups: false,
            suppressValues: false,
            suppressPivots: false,
            suppressSyncLayoutWithGrid: true,
        }; 
        
       @if(empty($hide_toolbar_items))
        window['grid_{{ $grid_id }}'].gridOptions.api.setSideBar(['columns','filters']);
        @else
        
    window['grid_{{ $grid_id }}'].gridOptions.api.setSideBarVisible(false);
        
        @endif
        window['grid_{{ $grid_id }}'].gridOptions.api.closeToolPanel();
  
 
}

function report_toolpanel_disable{{$grid_id}}() {
   // if($("#toolbar_template_filters{{ $grid_id }}").length  > 0) {
  //  $("#toolbar_template_filters{{ $grid_id }}").addClass('d-none');
  //  }
  
    window['grid_{{ $grid_id }}'].gridOptions.enableCharts = false;
    window['grid_{{ $grid_id }}'].gridOptions.enableRangeSelection = false;
    window['grid_{{ $grid_id }}'].gridOptions.api.redrawRows();
  
  $('.aggrid').removeClass('ag-report');
   
    // adds inline totals
   
    window['grid_{{ $grid_id }}'].gridOptions.showOpenedGroup = false;
    // adds subtotals
    window['grid_{{ $grid_id }}'].gridOptions.groupIncludeFooter = false;
   
    // includes grand total
    window['grid_{{ $grid_id }}'].gridOptions.groupIncludeTotalFooter = false;
   
    
    
    
    
    
    
    window['grid_{{ $grid_id }}'].gridOptions.columnApi.setPivotMode(false);
    window['grid_{{ $grid_id }}'].gridOptions.api.setSideBarVisible(false);
    
   
 
}


function getExpandedDetails{{ $grid_id }}(node, expandedDetails = '') {
    if (node && node.group && node.uiLevel >= 0) {
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
    ////////console.log('expandNodes{{ $grid_id }}');
    ////////console.log(groupStorage);
    ////////console.log(processedGroups);
    ////////console.log(num_expands);
    if(groupStorage){
        setTimeout(function(){
        
        
       
        if(num_expands == 0 && groupStorage){
            try{
                groupStorage = JSON.parse(groupStorage);
            }catch(e){
                groupStorage = false;
            }
        }
        if(groupStorage){
        window['expanded_groups{{$grid_id}}']= groupStorage;
        }else{
        window['expanded_groups{{$grid_id}}']= null;
        }
        
            ////////console.log('groupStorage0');
            ////////console.log(groupStorage);
        if (groupStorage) {
        
            ////////console.log('groupStorage1');
        restoringExpandedNodes{{$grid_id}} = true;
            window['grid_{{ $grid_id }}'].gridOptions.api.forEachNode(node => {
            ////////console.log('node',node);
            @if($master_detail && !$drill_down)
                   
                let ind = node.id;
        
                if($.isArray(groupStorage) && $.inArray(ind,groupStorage) !== -1){
                   
                    if (!processedGroups.includes(ind) && ind !== -1) {
                        processedGroups.push(ind);
                  
                        node.setExpanded(true);
                    }
                }
                if(!$.isArray(groupStorage) && groupStorage == ind){
                  
                    if (!processedGroups.includes(ind) && ind !== -1) {
                        processedGroups.push(ind);
                 
                        node.setExpanded(true);
                    }
                }
            @else
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
            @endif
        });
        setTimeout(() => restoringExpandedNodes{{$grid_id}} = false, 0);
        }
        },200);
        }
}


@if($serverside_model)
grid_filters = null;
var datasource = {
    getRows(params) {
        window['grid_{{ $grid_id }}'].gridOptions.api.deselectAll();
        
        var load_data = true;
      
        @if(session('role_level') == 'Admin' && $load_empty_data)
            
              
            if(!window['layout_global_default{{$grid_id}}'] ){
                var filterModel = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterModel();
              
                if(Object.keys(filterModel).length === 0){
                    var load_data = false;
                   
                    params.successCallback([], 0);
                }
            }
        @endif
        
      
        if (load_data){
                       
            if(searchtext{{ $grid_id }}.value != null && searchtext{{ $grid_id }}.value != ''  && searchtext{{ $grid_id }}.value!= ' '){
                 var search_val = searchtext{{ $grid_id }}.value;
                                if(search_val > ''){
                                    search_val = search_val.trim();
                                }
                params.request.search = search_val;
            }else{
                params.request.search = null;
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
                headers: {"Content-Type": "application/json; charset=utf-8","X-Requested-With": "XMLHttpRequest"}
            })
            .then(httpResponse => httpResponse.json())
            .then(response => {
            // //////console.log('response',response);
             //////console.log('rowtotals',response.rowTotals);
                params.successCallback(response.rows, response.lastRow);
                @if($pinned_totals)
                
                if(response.rowTotals){
                window['grid_{{ $grid_id }}'].gridOptions.api.setPinnedBottomRowData(response.rowTotals);
                }
                @endif
                
                window['grid_{{ $grid_id }}'].gridOptions.api.hideOverlay();
                
                @if($serverside_model)
                if(window['chartModel{{$grid_id}}']){
                setTimeout(function(){
                
                
                restoreChart{{$grid_id}}();
                
                },2000)
                }else{
                clearChart{{$grid_id}}();
                }
                @endif
                ////////console.log('datasource hideOverlay');
               
                //window['grid_{{ $grid_id }}'].gridOptions.columnApi.autoSizeAllColumns(true);
              
            }).then(response => {
                
                @if($serverside_model)
                if(to_expand_nodes{{$grid_id}} && window['expand_node_state_{{ $grid_id }}']){
                    datasource_to_expand_nodes{{$grid_id}} = 1;
                    ////////console.log('datasource expand');
                   // expandNodes{{ $grid_id }}(window['expand_node_state_{{ $grid_id }}']);
                  //  to_expand_nodes{{$grid_id}} = 0;
                }
                @endif  
            }).catch(error => {
                
                window['grid_{{ $grid_id }}'].gridOptions.api.hideOverlay();  
                params.failCallback();
                
            })
        }
    }
};

gridOptions.api.setServerSideDatasource(datasource);
@endif



/** AGGRID FUNCTIONS **/
function refreshRowData{{ $grid_id }}(row_id){
   //////////console.log('refreshRowData');
   var row_id = parseInt(row_id);
            //////////console.log(row_id);
   $.ajax({ 
        url: "/{{$menu_route}}/aggrid_refresh_row?row_id="+row_id, 
        beforeSend: function(){
          
        },
        success: function (result) { 
           
            //rowNode.setData(data) or rowNode.setDataValue(col,value)
            let rowNode;
             window['grid_{{ $grid_id }}'].gridOptions.api.forEachNode((node) => {
                 //////////console.log(node.data);
                 //////////console.log(row_id);
              if (node.data.id == row_id) {
                  //////////console.log('match');
                rowNode = node;
              }
            });
            //////////console.log(rowNode);
            //////////console.log(result);
            
            if(rowNode){
                //////////console.log('set');
                rowNode.setData(result);
                window['grid_{{ $grid_id }}'].gridOptions.api.refreshClientSideRowModel()
            
                var row_count = window['grid_{{ $grid_id }}'].gridOptions.api.getDisplayedRowCount();
                $("#rowcount{{ $grid_id }}").text(row_count);;
            }
            
            
          
        }, 
    });
}


function refreshGridData{{ $grid_id }}(row_id = false){
    //////////console.log('refreshGridData');
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
            
            //////console.log('refresh_minutely');
            //$("#rowcount{{ $grid_id }}").text(row_count);
        },
        error: function(){
            window['grid_{{ $grid_id }}'].gridOptions.api.hideOverlay();    
        }
    });
}

@if(!$serverside_model && $refresh_minutely && !is_dev())



setInterval(function(){
    //////////console.log('refreshGridData');
    $.ajax({ 
        url: "/{{$menu_route}}/aggrid_refresh_data", 
        beforeSend: function(){
            window['grid_{{ $grid_id }}'].gridOptions.api.showLoadingOverlay(); 
            //window['grid_{{ $grid_id }}'].gridOptions.api.setRowData(null);
        },
        success: function (result) {  
            row_data{{ $grid_id }} = result;
           
            const itemsToUpdate = [];
            window['grid_{{ $grid_id }}'].gridOptions.api.forEachNodeAfterFilterAndSort(function (rowNode, index) {
               
                $(result).each(function(i,el){
                    
                    if(el.rowId == rowNode.data.rowId){
                      
                       rowNode.setData(el);
                    }
                });
            });
            window['grid_{{ $grid_id }}'].gridOptions.api.hideOverlay();
            @if($master_detail && !$drill_down)
            refresh_detail_grid{{'detail'.$grid_id}}();
            @endif
            
            
        },
        error: function(){
            window['grid_{{ $grid_id }}'].gridOptions.api.hideOverlay();    
        }
    });
    
},1000*60*5)

@endif



function sortSidebarColumns{{ $grid_id }}(source = false){

  
@if(session('role_level') == 'Admin')

    
    var columnState = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getColumnState();
    var columnDefs = window['grid_{{ $grid_id }}'].gridOptions.columnDefs;
    var valueColumns = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getValueColumns();
   
    
    
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
    
      // compare grouped status and row group index
        if (aIsGrouped && !bIsGrouped) {
          return -1;
        } else if (!aIsGrouped && bIsGrouped) {
          return 1;
        } else if (aIsGrouped && bIsGrouped) {
          var aRowGroupIndex = columnState.find(function(colState) {
            return colState.colId === a.field && colState.rowGroup;
          }).rowGroupIndex;
          var bRowGroupIndex = columnState.find(function(colState) {
            return colState.colId === b.field && colState.rowGroup;
          }).rowGroupIndex;
          return aRowGroupIndex - bRowGroupIndex;
        }
        
        
        // compare value column index
        if (aIsValueCol && !bIsValueCol) {
            return -1;
        } else if (!aIsValueCol && bIsValueCol) {
            return 1;
        } else if (aIsValueCol && bIsValueCol) {
          
            var aValIndex = valueColumns.findIndex(obj => obj.colId === a.field);
           
            var bValIndex = valueColumns.findIndex(obj => obj.colId === b.field);
            
            return aValIndex - bValIndex;
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

    if(source == 'toolPanelUi'){
    // change column order for value columns
    var newColumnOrder = [];
    sortedColumns.forEach(function(column) {
        newColumnOrder.push({colId:column.field});
    });

    // set the new column order using the column API

    //window['grid_{{ $grid_id }}'].gridOptions.columnApi.applyColumnState({state:newColumnOrder,applyOrder: true,});
    }
   
 
@endif

}

function sortFilterColumns{{ $grid_id }}(source = false){
  
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
    if(filtersToolPanel){
    filtersToolPanel.setFilterLayout(sortedColumnDefs);
    }
@endif
  
}

window['grid_{{ $grid_id }}'].gridOptions.api.addEventListener('columnVisible', function(event) {
    //////console.log('columnVisible',event);
  
  // sortSidebarColumns{{ $grid_id }}(event.source);
});

window['grid_{{ $grid_id }}'].gridOptions.api.addEventListener('columnRowGroupChanged', function(event) {
    //////console.log('columnRowGroupChanged',event);
  
  
 //  sortSidebarColumns{{ $grid_id }}(event.source);
});

window['grid_{{ $grid_id }}'].gridOptions.api.addEventListener('columnValueChanged', function(event) {
    //////console.log('columnValueChanged',event);

  
 //  sortSidebarColumns{{ $grid_id }}(event.source);
});
/*
window['grid_{{ $grid_id }}'].gridOptions.api.addEventListener('columnPivotChanged', function(event) {
    //////console.log('columnPivotChanged',event);
});
window['grid_{{ $grid_id }}'].gridOptions.api.addEventListener('gridColumnsChanged', function(event) {
    //////console.log('gridColumnsChanged',event);
});
window['grid_{{ $grid_id }}'].gridOptions.api.addEventListener('displayedColumnsChanged', function(event) {
    //////console.log('displayedColumnsChanged',event);
});
window['grid_{{ $grid_id }}'].gridOptions.api.addEventListener('virtualColumnsChanged', function(event) {
    //////console.log('virtualColumnsChanged',event);
});
window['grid_{{ $grid_id }}'].gridOptions.api.addEventListener('columnEverythingChanged', function(event) {
    //////console.log('columnEverythingChanged',event);
});
*/
window['grid_{{ $grid_id }}'].gridOptions.api.addEventListener('filterChanged', function(event) {
  
  //  var filter_model = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterModel();
              
  //  var filter_count =Object.keys(filter_model).length;
//  $("#filterscount{{$grid_id}}").text('('+filter_count+')');
 // sortFilterColumns{{ $grid_id }}(event.source);
});

function resizegridcols(){
    window['grid_{{ $grid_id }}'].gridOptions.columnApi.autoSizeAllColumns(true)
}
function getGroupHeaderName(params){
   
       
    var label = 'Group';
    var groupCols = window['grid_{{$grid_id}}'].gridOptions.columnApi.getRowGroupColumns();

    if(groupCols.length > 0 ){
       
        var label = groupCols[0].colDef.headerName+ ' (Group)';
    }
       
    return label;
}
function onGridReady{{ $grid_id }}(params){
  //////console.log('onGridReady');
     @if($master_detail && !$drill_down)
     detail_row_selected{{$grid_id}} = false;
     @endif
   
    @if($init_filters)
        var init_filters = {!! json_encode($init_filters) !!}
        window['grid_{{ $grid_id }}'].gridOptions.api.setFilterModel(init_filters);
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
    $('#{{ $grid_id }}Email').attr("disabled","disabled");
    $('#{{ $grid_id }}Download').attr("disabled","disabled");
    
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
    
    @if(!empty($grid_menu_menu) && count($grid_menu_menu) > 0) 
   
    {!! button_menu_selected($module_id, 'grid_menu', $grid_id, 'deselected', false) !!}
    @endif
    
    @if(!empty($status_buttons_menu) && count($status_buttons_menu) > 0) 
   
    {!! button_menu_selected($module_id, 'status_buttons', $grid_id, 'deselected', false) !!}
    @endif
    
   
    
    @if($pinned_totals && !$serverside_model)
   
    let pinnedBottomData = generatePinnedBottomData{{ $grid_id }}();
   
    window['grid_{{ $grid_id }}'].gridOptions.api.setPinnedBottomRowData([pinnedBottomData]);
    
    @endif
    first_row_select{{$grid_id}} = true;
 
    var firstNode = params.api.getDisplayedRowAtIndex(0);
    if(firstNode){
    setTimeout(function(){firstNode.setSelected(true)},1000)
    /*setTimeout(function(){window['grid_{{ $grid_id }}'].gridOptions.api.deselectAll();},500)*/
    }
    
    @if(session('role_level') == 'Admin' && !empty($grid_id)  && in_array(2,session('app_ids')))
    @if($module_id!=1923)
    try{
    minimize_app_sidebar();
    }catch(e){}
    @endif
    @endif
    
}


function refresh_grid() {
	
	@if($master_detail && !$drill_down)
        if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
            refresh_detail_grid{{'detail'.$grid_id}}();
        }else{
            window['grid_{{ $grid_id }}'].gridOptions.refresh();
        }
	@else
	    window['grid_{{ $grid_id }}'].gridOptions.refresh();
	@endif
}

$("#{{ $grid_id }}ToggleKanban").click(function() {
    if($("#kanban_{{$grid_id}}").hasClass('d-none')){
        $("#kanban_{{$grid_id}}").removeClass('d-none');
        $("#grid_{{$grid_id}}").addClass('d-none');
    }else{
        $("#kanban_{{$grid_id}}").addClass('d-none');
        $("#grid_{{$grid_id}}").removeClass('d-none');
    }
});


$(".{{ $grid_id }}Refresh").click(function() {
    if($("#grid_{{$grid_id}}").hasClass('d-none')){
        kanbanObj.refresh();
    }else{
    	@if($master_detail && !$drill_down)
            if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
                refresh_detail_grid{{'detail'.$grid_id}}();
            }else{
                window['grid_{{ $grid_id }}'].gridOptions.refresh();
            }
    	@else
    	    window['grid_{{ $grid_id }}'].gridOptions.refresh();
    	@endif
    }
});



    function getRowNodeId{{ $grid_id }}(params) {
        //////console.log('getRowNodeId',params);
        @if($serverside_model)
        
            // if leaf level, we have ID
            if (params.level===0 && params.data.id!=null) {
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
            //////console.log(parts);
            return parts.join('-');
        @else
       
        return params.data.rowId;
        @endif
    }

window['accounts_datasource{{$grid_id}}'] = {!! json_encode($accounts_datasource) !!};

function rowSelected{{ $grid_id }}() {
 
   @if(session('role_level') == 'Admin' && in_array(2,session('app_ids')))
    if(window['layout_tracking_{{ $grid_id }}']){
    //set_layout_row_tracking_details();
    }

	@endif
	@if(!empty($status_dropdown) && !empty($status_dropdown['status_key']))
   
    window['status_dropdown{{ $grid_id }}'].value = window['selectedrow_{{ $grid_id }}'].{{$status_dropdown['status_key']}};
  
    @endif
@if($check_doctype)
    doctypes = {!! json_encode($doctypes) !!};
   
@endif
var selected = window['selectedrow_{{ $grid_id }}'];
@if($master_detail && !$drill_down)
detail_row_selected{{$grid_id}} = false;
@endif




// LINKED RECORDS DROPDOWN
/*
var show_linked_records = false;
var dropdown_html = '';
@if($master_detail && session('role_level') == 'Admin')
dropdown_html += '<li><button title="Detail Grid" href="{{ url($detail_menu_route) }}" data-target="view_modal" class="e-btn" ><span  class="e-btn-icon fa fa-list"></span> Detail Grid</button></li>';
@endif
$.each(selected, function(k,v){
    if(k.startsWith("join_") && v > ''){
        var btn_text = k.replace("join_", "");
        var btn_text = btn_text.replace("_", " ");
        var btn_text = v+' - '+btn_text;
        show_linked_records = true;
        dropdown_html += '<li><button title="'+v+'" data-target="view_modal" class="e-btn" href="linkedrecords/{{$module_id}}/'+selected.rowId+'/'+k+'">'+btn_text+'</button></li>';
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


@if(!empty($adminbtns_menu) && count($adminbtns_menu) > 0) 

    adminbtns{{ $grid_id }}.refresh();
@endif

    
@if(!empty($related_items_menu_menu) && count($related_items_menu_menu) > 0) 

    related_items_menu{{ $grid_id }}.refresh();

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
$('#{{ $grid_id }}Email').removeAttr("disabled");
$('#{{ $grid_id }}Download').removeAttr("disabled");
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
        if(selected && selected.status == "Deleted"){
         
            toolbar_button_icon('{{ $grid_id }}Delete','restore', 'Restore');
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
        }else if(selected && selected.is_deleted  == 1){
         
            toolbar_button_icon('{{ $grid_id }}Delete','restore', 'Restore');
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
        }else if(selected && selected.is_deleted == 0){
          
            toolbar_button_icon('{{ $grid_id }}Delete','delete', 'Delete');
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
        }else if(selected && selected.status && selected.status != "Deleted"){
            toolbar_button_icon('{{ $grid_id }}Delete','delete', 'Delete');
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
        }else if(selected && selected.status == undefined){
            toolbar_button_icon('{{ $grid_id }}Delete','delete', 'Delete');
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
        }else if(selected && selected.status == ""){
            toolbar_button_icon('{{ $grid_id }}Delete','delete', 'Delete');
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
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




@if(!empty($related_items_menu_menu) && count($related_items_menu_menu) > 0) 
{!! button_menu_selected($module_id, 'related_items_menu', $grid_id, 'selected', false) !!}
@endif

}

function rowDeselected(){
        
    @if(!empty($grid_menu_menu) && count($grid_menu_menu) > 0) 
    {!! button_menu_selected($module_id, 'grid_menu', $grid_id, 'deselected', false) !!}
    @endif
    
    @if(!empty($status_buttons_menu) && count($status_buttons_menu) > 0) 
    {!! button_menu_selected($module_id, 'status_buttons', $grid_id, 'deselected', false) !!}
    @endif
    
  
    

    
    @if(!empty($related_items_menu_menu) && count($related_items_menu_menu) > 0) 
    {!! button_menu_selected($module_id, 'related_items_menu', $grid_id, 'deselected', false) !!}
    @endif
     
       
    @if(!empty($status_dropdown))
    window['status_dropdown{{ $grid_id }}'].value = null;
    @endif
        
}

    var dialogclass = '';
/** BUTTON EVENTS **/
    @if($access['is_import'])
        $("#{{ $grid_id }}Import").click(function(){
             if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
               detail{{ $grid_id }}Import();
            }else{
         sidebarform('{{ $menu_route }}import' , '/{{ $menu_route }}/import', '','', '50%');
            }
        });
    @endif
    
    @if($access['is_add'])
        $("#{{ $grid_id }}Add").click(function(){
            
            if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
               detail{{ $grid_id }}Add();
            }else{
            @if($menu_route == 'pbx_menu')
                sidebarform('{{ $menu_route }}add' , 'pbx_menuedit', 'PBx Menu Add', '','60%');
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
                
                sidebarform('{{ $menu_route }}add' , url, '{!! $menu_name !!} - Add', '{!! $form_description !!}','60%');
            @elseif(!$documents_module)
                var url = '/{{ $menu_route }}/edit'+'?layout_id='+window['layout_id{{ $grid_id }}'];
                
                var filter_model = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterModel();
              
                if(Object.keys(filter_model).length > 0){
                    url += '&filter_model='+JSON.stringify(filter_model);
                }
               
                sidebarform('{{ $menu_route }}add' , url, '{!! $menu_name !!} - Add','{!! $form_description !!}', '50%');
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
                //////console.log("{{ $grid_id }}Edit",selected);
                @if($documents_module)
                    sidebarform('{{ $menu_route }}edit', '/{{ $menu_route }}/edit/'+ selected.rowId, 'Documents Edit', '80%', '100%');
                @else
                    sidebarform('{{ $menu_route }}edit' , '/{{ $menu_route }}/edit/'+ selected.rowId+'?layout_id='+window['layout_id{{ $grid_id }}'], '{!! $menu_name !!} - Edit', '{!! $form_description !!}','60%');
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
        
        $("#{{ $grid_id }}Email").click(function(){
            
        if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
          
        }else{
            var selected = window['selectedrow_{{ $grid_id }}'];
          
            sidebarform('{{ $menu_route }}'+selected.rowId, '/email_form/documents/'+ selected.rowId,'','70%');
        }
          
        });     
        
        $("#{{ $grid_id }}Download").click(function(){
            
        if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
       
        }else{
            var selected = window['selectedrow_{{ $grid_id }}'];
          
            window.open('download_document/'+ selected.rowId,'_blank');
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
                        gridAjaxConfirm('/{{ $menu_route }}/manager_delete', 'Delete Record?', {"id" : selected.rowId}, 'post');
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
                    if(selected && selected.status == "Deleted"){
                        gridAjaxConfirm('/{{ $menu_route }}/restore', 'Restore record?', {"id" : selected.rowId}, 'post');
                    }else if(selected && selected.is_deleted == 1){
                        gridAjaxConfirm('/{{ $menu_route }}/restore', 'Restore record?', {"id" : selected.rowId}, 'post');
                    }else{
                        
                       
                        deleteConfirm{{$grid_id}}('/{{ $menu_route }}/delete', 'Delete record?', {"id" : selected.rowId}, 'post');
                       
                      //  gridAjaxConfirm('/{{ $menu_route }}/delete', 'Delete record?', {"id" : selected.rowId}, 'post');
                      
                    }
                @endif
            @endif
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
        // "pivot_aging_group-account_status_0-30-Cancelled_aging"
        //////console.log('calculatePinnedBottomData');
        //list of columns for aggregation
        let columnsWithAggregation = {!! json_encode($pinned_total_cols) !!}
        //////console.log(columnsWithAggregation);
       
        columnsWithAggregation.forEach(element => {
           
            window['grid_{{ $grid_id }}'].gridOptions.api.forEachNodeAfterFilter((rowNode) => {
                
                if (rowNode && rowNode.data && rowNode.data[element])
                    target[element] += Number(parseFloat(rowNode.data[element]).toFixed(2));
            });
           
            if (target[element])
                target[element] = `${target[element].toFixed(2)}`;
        })
        /*
        var pivotResultColumns = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getPivotResultColumns();
        
        if(pivotResultColumns){
            pivotResultColumns.forEach(element => {
               
                window['grid_{{ $grid_id }}'].gridOptions.api.forEachNodeAfterFilter((rowNode) => {
                    
                    if (rowNode && rowNode.data && rowNode.data[element])
                        target[element] += Number(parseFloat(rowNode.data[element]).toFixed(2));
                });
               
                if (target[element])
                    target[element] = `${target[element].toFixed(2)}`;
            })
        }
        
        //////console.log(target);
        */
        return target;
    }
    @endif

    
 function getpivotresults(){
     var r = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getPivotResultColumn();
     var rr = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getPivotResultColumns();
     //////console.log(r);
     //////console.log(rr);
 }
    
 

   
    


    
$(document).off('click', '#copyrow{{ $grid_id }}').on('click', '#copyrow{{ $grid_id }}', function() {  
    
    window['grid_{{ $grid_id }}'].gridOptions.api.copySelectedRowsToClipboard();
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



function deleteConfirm{{$grid_id}}(url, confirm_text, data = null, type = 'get') {
    
    
    var confirmation = confirm(confirm_text);
    if (confirmation) {
        delete_row = window['grid_{{$grid_id}}'].gridOptions.api.getSelectedRows();
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
                
                window['grid_{{$grid_id}}'].gridOptions.api.applyTransaction({remove: delete_row});
                
                window['grid_{{ $grid_id }}'].gridOptions.api.showLoadingOverlay(); 
            },
            success: function(data) {
                
              
                window['grid_{{ $grid_id }}'].gridOptions.api.hideOverlay(); 
                
              
                toastNotify(data.message, data.status);
                if(data.status!='success'){
                    
                    window['grid_{{$grid_id}}'].gridOptions.api.applyTransaction({add: delete_row});    
                }
                
            },
            error: function(jqXHR, textStatus, errorThrown) {
                window['grid_{{ $grid_id }}'].gridOptions.api.hideOverlay(); 
                window['grid_{{$grid_id}}'].gridOptions.api.applyTransaction({add: delete_row}); 
                toastNotify('Error deleting record', 'error');
            },
        });
    }
}
</script>


@endpush

@push('page-styles')

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
    padding: 0px !important;
}
.grid-toolbar .space-right{
    margin-right: 7px;    
}

.e-dropdown-popup ul .e-item.accountinfo_heading .e-menu-icon {
   margin-right: 5px;
}
.e-btn-group .e-btn.tracking{
border-radius:0px !important;    
}
.ag-toolpanel-buttons .e-btn{
border-radius:0px !important;    
}
.e-btn-group.ag-toolpanel-buttons .e-btn:first-child, .e-btn-group .k-group-start{
border-radius:0px !important;    
}

.e-btn-group.ag-toolpanel-buttons .e-btn:last-child, .e-btn-group .k-group-end{
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




#toolbar_template_layouts{{ $grid_id }} li{
 
    align-items: center;
    text-align:center;
}




#toolbar_template_layouts{{ $grid_id }} .e-menu-item.e-btn.layout_active{
	background-color:#e9e9e9 !important;
	font-weight: 600;
}



.panel-w-100{width:100% !important;}

.ag-column-select {
   
    flex: 1 1 0px;
}
.e-menu-item.fw-bold {
    font-weight: bold;
}
.e-menu-wrapper ul .e-menu-item.layout-header.e-disabled {
    pointer-events: auto;
}
.ag-details-row .ag-root-wrapper{
    border: none !important;
}
.grid_menu .e-menu-item.e-disabled{
display:none !Important;   
}
.ag-layouts-content{overflow:auto}
 
    /*
    hide column labels on sidebar
    */
    @if(!is_dev() && !is_superadmin())
.ag-column-drop-vertical.ag-last-column-drop{
    display: none !important;
}
@endif
.ag-column-drop-vertical.ag-last-column-drop{
    display: none !important;
}
#contacts_results{{$grid_id}} .card-body{
user-select: text !important;
}

#toolbar_template_title{{ $grid_id }} ,#toolbar_template_title{{ $grid_id }}:hover,#toolbar_template_title{{ $grid_id }} h1,#toolbar_template_title{{ $grid_id }} h1:hover{

    user-select: text;
    cursor: text;
}
.grid-editable-cell{
    background-color:#efefef;
}
.layout-current .e-caret{display:none !important;}
.layout-current{font-weight:bold}

.ag-paging-panel{
    height: 36px;
}

.ag-theme-alpine .ag-group-child-count{
    display: none !important;
}
.ag-header-group-cell-with-group{
    border-left: 1px solid #ccc;
    
}

@if(!$serverside_model)
#grid_{{$grid_id}}.ag-report .ag-floating-bottom{
    display: none !important;
}
@endif

.ag-row-footer {
    background-color: #ccc;
    font-weight: bold !important;
}
</style>
@endpush