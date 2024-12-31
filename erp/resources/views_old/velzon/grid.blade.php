@php
if(empty(request()->all()) ||count(request()->all()) == 0){
    $request_get = '';
}else{
    $request_get = http_build_query(request()->all());
}
@endphp
@extends('velzon.layouts.master')
@section('title') {{ $menu_name }} @endsection
@section('content')
    @component('velzon.components.page_header')
        @slot('title') {{ $menu_name }} @endslot
        @slot('related_items_menu_menu')
        @if(!empty($related_items_menu_menu) && count($related_items_menu_menu) > 0)   
        <ul id="related_items_menu_menu{{ $grid_id }}"></ul>
        @endif
        @endslot
    @endcomponent
    @component('velzon.components.layouts_header')
        @slot('grid_id'){{ $grid_id }}@endslot
        @slot('gridlayouts')
        <ul id="gridlayouts_{{ $grid_id }}"></ul>
        @endslot
    @endcomponent

@if($master_detail)
@include('velzon.grid_details', $detail_grid)
@endif
@if($master_detail)
@yield('detail_content')
@endif

<div class="p-0 m-0" id="gridcontainer{{ $grid_id }}" style="height:600px">
<div class="gridheader">

<div id="gridtoolbar{{ $grid_id }}" class="grid-toolbar"></div>
</div>


<div id="toolbar_template_gridbuttons{{ $grid_id }}" class="pl-3">
   
  
   
     
    <div class="toolbar_grid_buttons align-items-center d-flex" id="gridactions{{ $grid_id }}">  
        @if($form_description > '')
        <span id="description_tooltip{{$grid_id}}" class="grid-tooltip ml-1 mr-2 far fa-question-circle"></span>
        @endif
        <div class="search-box me-2">
        <input type="text" class="form-control search" placeholder="Search..." id="searchtext{{ $grid_id }}">
        <i class="ri-search-line search-icon"></i>
        </div>
       
     
    <div class="btn-group" role="group" >
   

       <button type="button" title="Refresh Data" class="btn btn-light btn-icon waves-effect {{ $grid_id }}Refresh"><i class="ri-refresh-line"></i></button>  
      
        @if($access['is_add'])
            <button type="button" title="Create Record" id="{{ $grid_id }}Add" class="btn btn-light btn-icon waves-effect" ><i class="ri-add-fill"></i></button>
        @endif
        
    
        @if($access['is_edit'])
            <button type="button" title="Edit Record" id="{{ $grid_id }}Edit" class="btn btn-light btn-icon waves-effect" ><i class="ri-pencil-fill"></i></button>
        @endif
        
        @if($access['is_delete'])
            <button type="button" title="Delete Record" id="{{ $grid_id }}Delete" class="btn btn-light btn-icon waves-effect" ><i class="ri-delete-bin-2-line"></i></button>
        @endif
        
        @if($access['is_add'] && !in_array($db_table,['call_records_inbound','call_records_outbound','crm_documents','crm_supplier_documents']))
            <button type="button" title="Duplicate Record" id="{{ $grid_id }}Duplicate" class="btn btn-light btn-icon waves-effect" ><i class="ri-file-copy-line"></i></button>
        @endif
        
        
        @if($access['is_view'] && (in_array($db_table,['crm_documents','crm_supplier_documents','crm_supplier_import_documents'])))
            <button type="button" title="View Record" id="{{ $grid_id }}View" class="btn btn-light btn-icon waves-effect" ><i class="ri-file-pdf-line"></i></button>
        @endif
        <!-- Dropdown Variant -->
<div class="btn-group">
    <button type="button" class="btn btn-icon btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="ri-more-2-fill"></i></button>
    <div class="dropdown-menu">
    
        <a title="Clear All Filters" id="filterclear{{ $grid_id }}" class="dropdown-item ">Clear Filters</a>

        @if($master_detail && session('role_level') == 'Admin')
        <a title="Detail Grid" href="{{ url($detail_menu_route) }}" data-target="view_modal" class="dropdown-item " >Detail Grid</a>
        @endif
        
        @if($db_table == 'crm_documents' || $db_table == 'crm_supplier_documents')
            <a title="Approve" id="{{ $grid_id }}Approve" class="dropdown-item" >Approve</a>
        @endif
        @if($access['is_view'])
            <a  title="Print"  onclick="onBtnPrint()"  class="dropdown-item" >Print</a>
            <a title="Export Data" id="{{ $grid_id }}Export" class="dropdown-item" >Export</a>
        @endif
        @if($access['is_import'])
            <a title="Import Data" id="{{ $grid_id }}Import" class="dropdown-item" >Import</a>
        @endif
    </div>
</div><!-- /btn-group -->
        
    
    </div>
    </div>
</div>


<div id="toolbar_template_rowbuttons{{ $grid_id }}" class="toolbar_actionbuttons d-flex align-items-center">  
    @if(!empty($grid_menu_menu) && count($grid_menu_menu) > 0)  
    <ul id="grid_menu_menu{{ $grid_id }}"></ul>
    @endif
</div>





<div id="toolbar_template_accountbuttons{{ $grid_id }}">
 
   
   
     <div class="k-widget k-button-group ">

        <button title="Account Links" id="accountinfo{{ $grid_id }}" class="k-button communications_btn" ><i class="fa fa-user"></i></button>
        <button title="Resseller Links" id="resellerinfo{{ $grid_id }}" class="k-button communications_btn" ><i class="fa fa-user-tag"></i></button>
        <button title="Communications Links" id="communicationsinfo{{ $grid_id }}" class="k-button communications_btn" ><i class="fa fa-phone"></i></button>
        <button title="Services Links" id="servicesinfo{{ $grid_id }}" class="k-button communications_btn" ><i class="fas fa-server"></i></button>
        
        <button title="PBX Links" id="pbxinfo{{ $grid_id }}" class="k-button communications_btn" ><i class="fa fa-phone-square"></i></button>
    </div>
</div>





<div id="grid_{{ $grid_id }}" class="ag-theme-balham aggrid" style="height: 100%!important;"></div>


</div>

@if(!empty($module_context_builder_menu) && count($module_context_builder_menu) > 0)
<ul id="context_builder{{ $grid_id }}" class="m-0"></ul>
@endif

@if(!empty($module_contextbuttons_menu) && count($module_contextbuttons_menu) > 0)
<ul id="contextbuttons{{ $grid_id }}" class="m-0"></ul>
@endif

@if(is_superadmin())
<ul id="contextlayouts{{ $grid_id }}" class="m-0"></ul>
@endif


@endsection
@section('script')
    <script src="{{ '/assets/velzon/libs/prismjs/prismjs.min.js' }}"></script>
    <script src="{{ '//assets/velzon/js/app.min.js' }}"></script>
    
@endsection
@section('script-bottom')

<script>
@if($tree_data)
//treedata sort
var potentialParent = null;

function onManagedRowDragEnter{{ $grid_id }}(args){
    var event = args.event;
   
    dragcolId = $(event.target).closest('.ag-cell').attr('col-id');
   
}

function onManagedRowDragEnd{{ $grid_id }}(event){
    //console.log('onManagedRowDragEnd{{ $grid_id }}');
    //console.log(event);
    //console.log(dragcolId);
    if(dragcolId == 'sort_order'){
    //console.log(11);
        return onRowDragEnd{{ $grid_id }}(event);
    }else{
    //console.log(22);
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
        //////console.log(result);
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

        //console.log('onRowDragEnd');
        //console.log(e);
    if(e.node && e.node.group == false && e.node.data && e.node.data.id && overData){
        var start_id = e.node.data.id;
        var target_id = overData.id;
        var sort_data = JSON.stringify({"start_id" : start_id, "target_id" : target_id});
        //console.log(sort_data);
        $.ajax({ 
            type: "POST",
            url: "/{{$menu_route}}/sort", 
            datatype: "json", 
            contentType: "application/json; charset=utf-8", 
            data: sort_data, 
            beforeSend: function(){
            },
            success: function (result) { 
                //console.log('onRowDragEnd{{ $grid_id }} ajax');
                //console.log(result);
	            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                //console.log(jqXHR);
                //console.log(textStatus);
                //console.log(errorThrown);
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
        
        var fromIndex = findWithAttr(immutableStore,'id',movingData.id);
        var toIndex = findWithAttr(immutableStore,'id',overData.id);
        
        
        var newStore = immutableStore.slice();
        moveInArray(newStore, fromIndex, toIndex);
        
        
        immutableStore = newStore;
        window['grid_{{ $grid_id }}'].gridOptions.api.setRowData(newStore);
        
        window['grid_{{ $grid_id }}'].gridOptions.api.clearFocusedCell();
    }

    function moveInArray(arr, fromIndex, toIndex) {
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

@if( $form_description > '')
    new ej.popups.Tooltip({
    enableHtmlParse: true,
    cssClass: 'description-tooltip',
    content: '{!! str_replace("'","",$form_description) !!}',
    position:'RightBottom',
    
    },'#description_tooltip{{$grid_id}}');
@endif

/** AUTOSCROLL GRID **/

$(document).ready(function() {


    scroll_section = $('#grid_{{ $grid_id }}');
    $foo = $('#grid_{{ $grid_id }} .ag-body-horizontal-scroll-viewport');


});   
    

$('#grid_{{ $grid_id }}').mousemove(function(e) {
  
    let bounds = this.getBoundingClientRect();

    let x = e.clientX - bounds.left;
    var sectionWidth = scroll_section.width();
    var diffWidth = sectionWidth - x;
    ////console.log(sectionWidth);
    ////console.log(e);
    ////console.log(x);
    ////console.log(diffWidth);
    if(x < 50) { 
        $foo.animate({
            scrollLeft: -500 }
            , 1500, 'linear'); 

    }else if (diffWidth < 50) {
        $foo.animate({
            scrollLeft: 1000 }
            , 1500, 'linear');
    } else {
        $foo.stop()
    }
    
    
});

/** CONTEXT MENUS **/

/* button right click context menu*/
ej.base.enableRipple(true);

// layouts contextmenu
function contextmenurender(args){
    
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




// grid contextmenu
@if(!empty($module_context_builder_menu) && count($module_context_builder_menu) > 0)
var menuOptions = {
    target: '#gridactions{{ $grid_id }}',
    items: {!! json_encode($module_context_builder_menu) !!},
    beforeItemRender: contextmenurender
};

// Initialize ContextMenu control.
new ej.navigations.ContextMenu(menuOptions, '#context_builder{{ $grid_id }}');
@endif


@if(!empty($module_contextbuttons_menu) && count($module_contextbuttons_menu) > 0)
var menuOptions = {
    target: '#toolbar_template_rowbuttons{{ $grid_id }}',
    items: {!! json_encode($module_contextbuttons_menu) !!},
    beforeItemRender: contextmenurender
};

// Initialize ContextMenu control.
new ej.navigations.ContextMenu(menuOptions, '#contextbuttons{{ $grid_id }}');
@endif

window['gridlayouts_{{ $grid_id }}'] = new ej.navigations.Menu({
    items: {!! json_encode($sidebar_layouts) !!},
    enableScrolling: true,
    showItemOnClick: true,
    orientation: 'Horizontal',
    cssClass: 'top-menu k-button-group',
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
}, '#gridlayouts_{{ $grid_id }}');
            
    loaded_from_args = 0;
    @if(!empty($grid_menu_menu) && count($grid_menu_menu) > 0)   
    var grid_menuMenuItems = @php echo json_encode($grid_menu_menu); @endphp;
    // top_menu initialization
    var grid_menu{{ $grid_id }} = new ej.navigations.Menu({
        items: grid_menuMenuItems,
        orientation: 'Horizontal',
        cssClass: 'top-menu k-button-group',
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
            ];
            var menuOptions = {
                target: '#grid_menu_menu{{ $grid_id }}',
                items: context_items,
                beforeItemRender: contextmenurender
            };
            
            // Initialize ContextMenu control
            new ej.navigations.ContextMenu(menuOptions, '#grid_menu_context{{$grid_id}}');
            
            @endif
    
        },
        beforeOpen: function(args){
          
            var popup_items = [];
            $(args.items).each(function(i, el){
                popup_items.push(el.text);
            });
        
            var selected = window['selectedrow_{{ $grid_id }}'];
           
            {!! button_menu_selected($module_id, 'grid_menu', $grid_id, 'selected', true) !!}
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
            
            if(args.item.require_grid_id){
                if(window['selectedrow_{{ $grid_id }}'] && window['selectedrow_{{ $grid_id }}'].id){
                   
                    var grid_url = args.item.original_url + window['selectedrow_{{ $grid_id }}'].id; 
                   
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
    
    @if(!empty($related_items_menu_menu) && count($related_items_menu_menu) > 0)   
    var related_items_menuMenuItems = @php echo json_encode($related_items_menu_menu); @endphp;
    // top_menu initialization
    var related_items_menu{{ $grid_id }} = new ej.navigations.Menu({
        items: related_items_menuMenuItems,
        orientation: 'Horizontal',
        cssClass: 'top-menu k-button-group',
        created: function(args){
     
            @if(is_superadmin())
            
            $('body').append('<ul id="related_items_menu_context{{$grid_id}}" class="m-0"></ul>');
            var context_items = [
                {
                    id: "context_gridtab_edit",
                    text: "Edit Menu",
                    iconCss: "fas fa-list",
                    url: 'sf_menu_manager/{{$module_id}}/related_items_menu',
                    data_target: 'view_modal',
                },
            ];
            var menuOptions = {
                target: '#related_items_menu_menu{{ $grid_id }}',
                items: context_items,
                beforeItemRender: contextmenurender
            };
            
            // Initialize ContextMenu control
            new ej.navigations.ContextMenu(menuOptions, '#related_items_menu_context{{$grid_id}}');
            
            @endif
    
        },
        beforeOpen: function(args){
          
            var popup_items = [];
            $(args.items).each(function(i, el){
                popup_items.push(el.text);
            });
        
            var selected = window['selectedrow_{{ $grid_id }}'];
            //console.log('related_items_menu_menu open');
            {!! button_menu_selected($module_id, 'related_items_menu', $grid_id, 'selected', true) !!}
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
            
            if(args.item.require_grid_id){
                if(window['selectedrow_{{ $grid_id }}'] && window['selectedrow_{{ $grid_id }}'].id){
                   
                    var grid_url = args.item.original_url + window['selectedrow_{{ $grid_id }}'].id; 
                   
                    if(args.item.data_target == 'transaction' || args.item.data_target == 'transaction_modal') {
                        $(el).find("a").attr("modal_url",grid_url);
                        $(el).find("a").attr("href","javascript:void(0)");
                    }else{
                        $(el).find("a").attr("href",grid_url);
                    }
                }
            }
            
        },
    },'#related_items_menu_menu{{ $grid_id }}');
    @endif

    
    function get_sidebar_data(){
        //////console.log(get_sidebar_data);
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
                //////console.log(data);
                if(data){
                
                    if(data.sidebar_layouts){
                        window['gridlayouts_{{ $grid_id }}'].items = data.sidebar_layouts;
                        window['gridlayouts_{{ $grid_id }}'].dataBind();
                    }
                 
                    
                }
            }
        });
        @if($master_detail)
       
        get_sidebar_data{{ $detail_module_id }}();
        @endif
    }
   

    // PRINT REPORTS
    
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
        var post_data = {search: $("#searchtext{{ $grid_id }}").val(), search_key: '{{$detail_module_key}}' };  
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
                return searching_detail_ids.includes(node.data.{{ ($master_module_key) ? $master_module_key : 'id' }})
            }
        }
        return true;
    }
    @endif
  

   
// wrap grid functions to avoid modal conflict
function reload_conditional_styles(module_id){
    //////console.log('reload_conditional_styles');
    //////console.log(module_id);
    
   $.ajax({
        type: 'get',
        url: '{{ url("getgridstyles") }}/'+module_id,
		success: function(data) { 
            $("#conditional_styles"+module_id).html(data);
            $("#conditional_styles"+module_id).trigger('contentchanged');
		}
   });
}

$('#conditional_styles{{$module_id}}').bind('contentchanged', function() {
  // do something after the div content has changed
  reload_grid_config{{$module_id}}();
});

function reload_grid_config{{$module_id}}(){
  
      //console.log('reload_grid_config2');
    setTimeout(function() {
    layout_reload{{$module_id}}(window['layout_id{{ $grid_id }}']);
    },500);
    @if($master_detail)
   
    detail_layout_load(window['detail_layout_id{{ $grid_id }}']);
    @endif
}


     function layout_reload{{$module_id}}(layout_id){
  
      //console.log('reload_grid_config3');
    	var ajax_data = {aggrid: 1, layout_id: layout_id, grid_reference: 'grid_{{ $grid_id }}', query_string: {!! $query_string !!} };
      
        $.ajax({
            type: 'post',
            url: '{{ url($menu_route."/aggrid_layout_data") }}',
            data: ajax_data,
            beforeSend: function(){
                showSpinner();
                $('#layoutsbtn_delete{{ $grid_id }}').attr('disabled','disabled');
                $('#layoutsbtn_duplicate{{ $grid_id }}').attr('disabled','disabled');
                $('#layoutsbtn_save{{ $grid_id }}').attr('disabled','disabled');
                // save temp state
                var temp_state = {};
                temp_state.colState = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getColumnState();
                temp_state.groupState = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getColumnGroupState();
                
                temp_state.filterState = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterModel();
                temp_state.search = $("#searchtext{{ $grid_id }}").val();
               
                window['gridstate_{{ $grid_id }}'] = temp_state;
            },
    		success: function(data) { 
      //console.log('reload_grid_config4');
                hideSpinner();
    		  
    		    @if(session('role_level') == 'Admin')
                window['gridlayouts_{{ $grid_id }}'].items = JSON.parse(data.menu);
                window['gridlayouts_{{ $grid_id }}'].refresh();
                @endif
                if(data.columnDefs){
                   window['grid_{{ $grid_id }}'].gridOptions.api.setColumnDefs(data.columnDefs);
                }
                var state = JSON.parse(data.settings);
               
               if(data.auto_group_col_sort){
                    //////console.log(window['grid_{{ $grid_id }}'].gridOptions);
                    window['grid_{{ $grid_id }}'].gridOptions.autoGroupColumnDef.sort = data.auto_group_col_sort;    
                }
                 
                
                window['grid_{{ $grid_id }}'].gridOptions.columnApi.autoSizeAllColumns();
                    
                // restore temp state
              
                
                if(window['gridstate_{{ $grid_id }}'].colState){ 
                    window['grid_{{ $grid_id }}'].gridOptions.columnApi.applyColumnState({state:window['gridstate_{{ $grid_id }}'].colState,applyOrder: true,});
                }else if(state && state.colState){
                     window['grid_{{ $grid_id }}'].gridOptions.columnApi.applyColumnState({state:state.colState,applyOrder: true,});
                }
                if(window['gridstate_{{ $grid_id }}'].groupState){
                    window['grid_{{ $grid_id }}'].gridOptions.columnApi.setColumnGroupState(window['gridstate_{{ $grid_id }}'].groupState);
                }else if(state && state.groupState){
                    window['grid_{{ $grid_id }}'].gridOptions.columnApi.setColumnGroupState(state.groupState);
                }
                
                if(window['gridstate_{{ $grid_id }}'].filterState){
                    window['grid_{{ $grid_id }}'].gridOptions.api.setFilterModel(window['gridstate_{{ $grid_id }}'].filterState);
                }else if(state && state.filterState){
                    window['grid_{{ $grid_id }}'].gridOptions.api.setFilterModel(state.filterState);
                }
                if(window['gridstate_{{ $grid_id }}'].search){
                    $("#searchtext{{ $grid_id }}").val(window['gridstate_{{ $grid_id }}'].search);
                }
                
                
                
                
               
                window['layout_id{{ $grid_id }}'] = layout_id;
                @if(session('role_level') == 'Admin')
                $('#layoutsbtn_delete{{ $grid_id }}').removeAttr('disabled');
                $('#layoutsbtn_duplicate{{ $grid_id }}').removeAttr('disabled');
                $('#layoutsbtn_save{{ $grid_id }}').removeAttr('disabled');
                
                @endif
                /*
                if(data.name){
                    $('#layout_title{{ $grid_id }}').text(data.name);
                }else{
                    $('#layout_title{{ $grid_id }}').text('');
                }
                */
            
                
    		},
            error: function(jqXHR, textStatus, errorThrown) {
                hideSpinner();
                processAjaxError(jqXHR, textStatus, errorThrown);
            },
    	});
    }
    
    
(function() {

    $("#gridcontainer{{ $grid_id }}").off("keydown").on("keydown", function(e){
        var modifier = ( e.ctrlKey || e.metaKey );
     
     
       
        @if(check_access('1,31,34') || is_dev())
        if(modifier && e.which == 83){
            e.preventDefault();
        
            
            
            if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
                
                detail_layout_save{{ $grid_id }}();
            }else{
                layout_save{{ $grid_id }}();   
            }
            
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
   
    //Initialize action items.
    var items = [];
    
    // initialize DropDownButton control
    accountinfo{{ $grid_id }} = new ej.splitbuttons.DropDownButton({
        items: items,
        disabled: true,
        open: function(args){
            //position popup to align to button
            args.element.parentElement.style.left = accountinfo{{ $grid_id }}.element.getBoundingClientRect().left - args.element.parentElement.offsetWidth + 'px';  
        },
        beforeItemRender: function (args){
             
            ////console.log(args);
            var el = args.element;   
          
          
            if(args.item.text) {
                $(el).find("a").attr("title",args.item.text);
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
            }else if(args.item.data_target == 'view_modal') {
                $(el).find("a").attr("target","_blank");
            }else if(args.item.data_target) {
                $(el).find("a").attr("data-target",args.item.data_target);
            }
            
            if(args.item.heading){
                $(el).addClass('accountinfo_heading');
            }else{
                $(el).addClass('accountinfo_item');    
            }
            if(args.item.bold){
                $(el).addClass('accountinfo_bold');
            }
        },
    });
    
    // Render initialized DropDownButton.
    accountinfo{{ $grid_id }}.appendTo('#accountinfo{{ $grid_id }}');
    
    
    var items = [];
    
    // initialize DropDownButton control
    pbxinfo{{ $grid_id }} = new ej.splitbuttons.DropDownButton({
        items: items,
        disabled: true,
        open: function(args){
            //position popup to align to button
            args.element.parentElement.style.left = pbxinfo{{ $grid_id }}.element.getBoundingClientRect().left - args.element.parentElement.offsetWidth + 'px';  
        },
        beforeItemRender: function (args){
            var el = args.element;   
            
          
            if(args.item.text) {
                $(el).find("a").attr("title",args.item.text);
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
            }else if(args.item.data_target == 'view_modal') {
                $(el).find("a").attr("target","_blank");
            }else if(args.item.data_target) {
                $(el).find("a").attr("data-target",args.item.data_target);
            }
            
            if(args.item.heading){
                $(el).addClass('accountinfo_heading');
            }else{
                $(el).addClass('accountinfo_item');    
            }
            if(args.item.bold){
                $(el).addClass('accountinfo_bold');
            }
        },
    });
    
    // Render initialized DropDownButton.
    pbxinfo{{ $grid_id }}.appendTo('#pbxinfo{{ $grid_id }}');
    
    
    //Initialize action items.
    var items = [];
    
    // initialize DropDownButton control
    resellerinfo{{ $grid_id }} = new ej.splitbuttons.DropDownButton({
        items: items,
        disabled: true,
        open: function(args){
            //position popup to align to button
            args.element.parentElement.style.left = resellerinfo{{ $grid_id }}.element.getBoundingClientRect().left - args.element.parentElement.offsetWidth + 'px';  
        },
        beforeItemRender: function (args){
            var el = args.element;  
            
          
            if(args.item.text) {
                $(el).find("a").attr("title",args.item.text);
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
            }else if(args.item.data_target == 'view_modal') {
                $(el).find("a").attr("target","_blank");
            }else if(args.item.data_target) {
                $(el).find("a").attr("data-target",args.item.data_target);
            }
            
            if(args.item.heading){
                $(el).addClass('accountinfo_heading');
            }else{
                $(el).addClass('accountinfo_item');    
            }
            if(args.item.bold){
                $(el).addClass('accountinfo_bold');
            }
        },
    });
    
    // Render initialized DropDownButton.
    resellerinfo{{ $grid_id }}.appendTo('#resellerinfo{{ $grid_id }}');

    
    communicationsinfo{{ $grid_id }} = new ej.splitbuttons.DropDownButton({
        items: items,
        disabled: true,
        open: function(args){
            //position popup to align to button
            args.element.parentElement.style.left = communicationsinfo{{ $grid_id }}.element.getBoundingClientRect().left - args.element.parentElement.offsetWidth + 'px';  
        },
        beforeItemRender: function (args){
            var el = args.element;   
          
            if(args.item.text) {
                $(el).find("a").attr("title",args.item.text);
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
            }else if(args.item.data_target == 'view_modal') {
                $(el).find("a").attr("target","_blank");
            }else if(args.item.data_target) {
                $(el).find("a").attr("data-target",args.item.data_target);
            }
            
            if(args.item.heading){
                $(el).addClass('accountinfo_heading');
            }else{
                $(el).addClass('accountinfo_item');    
            }
            if(args.item.bold){
                $(el).addClass('accountinfo_bold');
            }
        },
    });
    
    // Render initialized DropDownButton.
    communicationsinfo{{ $grid_id }}.appendTo('#communicationsinfo{{ $grid_id }}');
    
    
    //Initialize action items.
    var services_items = [];
    
    // initialize DropDownButton control
    servicesinfo{{ $grid_id }} = new ej.splitbuttons.DropDownButton({
        items: services_items,
        disabled: true,
        open: function(args){
            //position popup to align to button
            args.element.parentElement.style.left = servicesinfo{{ $grid_id }}.element.getBoundingClientRect().left - args.element.parentElement.offsetWidth + 'px';  
        },
        beforeItemRender: function (args){
            var el = args.element;   
          
            if(args.item.data_target == 'javascript') {
                $(el).find("a").attr("data-target",args.item.data_target);
                $(el).find("a").attr("js-target",args.item.url);
                $(el).find("a").attr("id",args.item.url);
                $(el).find("a").attr("href","javascript:void(0)");
            }else if(args.item.data_target == 'transaction' || args.item.data_target == 'transaction_modal') {
                $(el).find("a").attr("data-target",args.item.data_target);
                $(el).find("a").attr("href","javascript:void(0)");
                $(el).find("a").attr("modal_url",args.item.url);
            }else if(args.item.data_target == 'view_modal') {
                $(el).find("a").attr("target","_blank");
            }else if(args.item.data_target) {
                $(el).find("a").attr("data-target",args.item.data_target);
            }
            
            if(args.item.heading){
                $(el).addClass('accountinfo_heading');
            }else{
                $(el).addClass('accountinfo_item');    
            }
            if(args.item.bold){
                $(el).addClass('accountinfo_bold');
            }
        },
    });
    
    // Render initialized DropDownButton.
    servicesinfo{{ $grid_id }}.appendTo('#servicesinfo{{ $grid_id }}');
    
  
    
    function renderRightInfoButtons(params){
    
        var rows = params.api.getSelectedRows();
        if(rows.length > 0){
            $.ajax({
                url:'{{ url($menu_route."/aggrid_communications_panel") }}',
                type:'post',
                data: {selected_rows: rows, communications_type: '{{$communications_type}}'},
                success:function(data){
                    //////console.log(data);
                    var items = [];
                    var com_items = [];
                    var reseller_items = [];
                    
                    if(data.account){
                        
                        @if($communications_type == 'account')
                           
                            
                            items.push({ text:'Balance: R'+parseFloat(data.account.balance).toFixed(2), url: '#', bold: true,iconCss: 'fas fa-file-invoice-dollar', heading: true });
                        
                            items.push({ text:'Transactions',iconCss: 'far fa-file-alt', heading: true});
                            items.push({ text:'Place Order', url:'/{{$documents_url}}/edit?account_id='+data.id, data_target:"transaction",  });
                          
                            if(data.account.type == 'reseller_user'){
                                items.push({ text:'Documents', url:'/{{$documents_url}}?reseller_user='+data.id, data_target:"view_modal",  });
                            }else{
                                items.push({ text:'Documents', url:'/{{$documents_url}}?account_id='+data.id, data_target:"view_modal",  });
                            }
                            items.push({ text:'Statement', url:'/statement_pdf/'+data.id, data_target:"view_modal"  });
                            items.push({ text:'Statement Email', url:'/email_form/statement_email/'+data.id, data_target:"sidebarform"  });
                            @if(is_superadmin())
                            items.push({ text:'Payments', url:'/{{$cashregister_url}}?account_id='+data.account.id, data_target:"view_modal",  });
                            @endif
                            @if(session('role_id') == 1)
                            items.push({ text:'Create Deal', url:'/{{$deals_url}}/edit?account_id='+data.id, data_target:"sidebarform",  });
                            @endif
                            @if(is_superadmin() || session('role_id') == 37)
                            items.push({ text:'Cash Payment', url:'/{{$cashregister_url}}/edit?cashbook_id=8&account_id='+data.account.id, data_target:"sidebarform",  });
                            @endif
                            
                            items.push({separator:true});
                            items.push({ text:'Account', iconCss: 'far fa-user', heading: true});
                           
                            items.push({ text:'Status: '+data.account.status, url: '#', bold: true });
                            
                            items.push({ text:'List Account', url:'/{{$accounts_url}}?id='+data.id, data_target:"view_modal", });
                            items.push({ text:'Edit Account', url:'/{{$accounts_url}}/edit/'+data.id, data_target:"sidebarform", });
                            if(data.account.type == 'reseller'){
                                items.push({ text:'Partner Accounts', url:'/{{$accounts_url}}?partner_id='+data.id, data_target:"view_modal",  });
                            }
                            
                            items.push({ text:'Pricelist', url:'/{{$pricelist_items_url}}?pricelist_id='+data.pricelist_id, data_target:"view_modal",  });
                            items.push({ text:'User', url:'/{{$users_url}}?account_id='+data.id, data_target:"view_modal",  });
                            if(data.disable_access){
                                if(data.account.status == 'Disabled' || data.account.status == 'Disabled by Reseller'){
                                    items.push({ text:'Enable', url:'/switch_account/'+data.id, data_target:"ajax",  });
                                }
                                if(data.account.status == 'Enabled'){
                                    items.push({ text:'Disable', url:'/switch_account/'+data.id, data_target:"ajax",  });
                                }
                            }
                            
                            
                            com_items.push({ text:'Communications',iconCss: 'fas fa-phone', heading: true });
                            com_items.push({ text:'Inbound Call: '+ data.account.last_inbound_call,url:"#"  });
                            com_items.push({ text:'Outbound Call: '+ data.account.last_outbound_call,url:"#" });
                            
                            com_items.push({ text:'Contact List', url:'/{{$account_contacts_url}}?account_id='+data.account.id, data_target:"view_modal"});
                            com_items.push({ text:'Create Contact', url:'/{{$account_contacts_url}}/edit/?account_id='+data.account.id, data_target:"sidebarform"});
                            
                            if(data.account.phone){
                                com_items.push({ text: 'Manager '+data.account.contact+' '+data.account.phone, url:'/pbx_call/'+data.account.phone+'/'+data.id, data_target:"ajax" });
                            }
                            
                            if(data.contacts.length > 0){
                                $.each(data.contacts, function(i,obj){
                                    if(obj.phone){
                                        com_items.push({ text: obj.type+' '+obj.name+' '+obj.phone, url:'/pbx_call/'+obj.phone+'/'+data.id, data_target:"ajax" });
                                    }
                                });    
                            }
                            
                            items.push({separator:true});
                            
                            if(data.account.email){
                                com_items.push({ text: 'Manager '+data.account.contact+' '+data.account.email, url:'/email_form/'+data.type+'/'+data.id+'/'+data.account.email, data_target:"form_modal" });
                            }
                           
                            if(data.contacts.length > 0){
                                $.each(data.contacts, function(i,obj){
                           
                                    if(obj.email){
                           
                                        com_items.push({ text: obj.type+' '+obj.name+' '+obj.email, url:'/email_form/'+data.type+'/'+data.id+'/'+obj.email, data_target:"form_modal" });
                                    }
                                });    
                            }
                            
                        
                            @if(session('role_level') == 'Admin')
                            com_items.push({ text:'History', url:'/{{$communications_url}}?account_id='+data.account.id, data_target:"view_modal"});
                            @endif
                           
                           
                        @endif
                        
                        @if($communications_type == 'supplier')
                       
                            
                            items.push({ text:'Statement', url:'/supplier_statement_pdf/'+data.account.id, data_target:"view_modal",  });
                            items.push({ text:'Documents', url:'/{{$supplier_documents_url}}?supplier_id='+data.account.id, data_target:"view_modal",  });
                         
                            @if(is_superadmin())
                            items.push({ text:'Payments', url:'/{{$cashregister_url}}?supplier_id='+data.account.id, data_target:"view_modal",  });
                            @endif
                            items.push({ text:'Cash Payment', url:'/{{$cashregister_url}}/edit?cashbook_id=8&supplier_id='+data.account.id, data_target:"sidebarform",  });
                            
                          
                            com_items.push({ text:'Contact List', url:'/{{$supplier_contacts_url}}?supplier_id='+data.account.id, data_target:"view_modal"});
                            com_items.push({ text:'Create Contact', url:'/{{$supplier_contacts_url}}/edit/?supplier_id='+data.account.id, data_target:"sidebarform"});
                      
                            if(data.account.phone){
                                com_items.push({ text: 'Manager '+data.account.contact+' '+data.account.phone, url:'/pbx_call/'+data.account.phone+'/'+data.account.id, data_target:"ajax" });
                            }
                            
                            
                            if(data.contacts.length > 0){
                                $.each(data.contacts, function(i,obj){
                                    if(obj.phone){
                                        com_items.push({ text: obj.type+' '+obj.name+' '+obj.phone, url:'/pbx_call/'+obj.phone+'/'+data.id, data_target:"ajax" });
                                    }
                                });    
                            }
                            
                            items.push({separator:true});
                            //////console.log(data);
                             if(data.account.email){
                                com_items.push({ text: 'Manager '+data.account.contact+' '+data.account.email, url:'/email_form/'+data.type+'/'+data.account.id+'/'+data.account.email, data_target:"form_modal" });
                            }
                            
                            if(data.contacts.length > 0){
                                $.each(data.contacts, function(i,obj){
                                    if(obj.email){
                                        com_items.push({ text: obj.type+' '+obj.name+' '+obj.email, url:'/email_form/'+data.type+'/'+data.id+'/'+obj.email, data_target:"form_modal" });
                                    }
                                });    
                            }
                       
                        
                        @endif
                        
                        
                        accountinfo{{ $grid_id }}.items = items;
                        accountinfo{{ $grid_id }}.disabled = false;
                        communicationsinfo{{ $grid_id }}.items = com_items;
                        communicationsinfo{{ $grid_id }}.disabled = false;
                        
                        if(reseller_items.length > 0){
                            resellerinfo{{ $grid_id }}.items = reseller_items;
                            resellerinfo{{ $grid_id }}.disabled = false;
                        }
                    }else{
                        accountinfo{{ $grid_id }}.disabled = true;
                        resellerinfo{{ $grid_id }}.disabled = true;
                        communicationsinfo{{ $grid_id }}.disabled = true;
                    }
                    
                    
                    if(data.reseller){
                         reseller_items.push({ text:data.reseller.company, url: '#', bold: true,iconCss: 'fas fa-file-invoice-dollar', heading: true });
                        reseller_items.push({ text:'Balance: R'+parseFloat(data.reseller.balance).toFixed(2), url: '#', bold: true,iconCss: 'fas fa-file-invoice-dollar', heading: true });
                    
                        reseller_items.push({ text:'Transactions',iconCss: 'far fa-file-alt', heading: true});
                        reseller_items.push({ text:'Place Order', url:'/{{$documents_url}}/edit?account_id='+data.reseller.id, data_target:"transaction",  });
                      
                       
                        reseller_items.push({ text:'Documents', url:'/{{$documents_url}}?account_id='+data.reseller.id, data_target:"view_modal",  });
                        
                        reseller_items.push({ text:'Statement', url:'/statement_pdf/'+data.reseller.id, data_target:"view_modal"  });
                        @if(session('role_id') == 1)
                        reseller_items.push({ text:'Create Deal', url:'/{{$deals_url}}/edit?account_id='+data.reseller.id, data_target:"sidebarform",  });
                        @endif
                        
                        reseller_items.push({separator:true});
                        reseller_items.push({ text:'Account', iconCss: 'far fa-user', heading: true});
                       
                        reseller_items.push({ text:'Status: '+data.reseller.status, url: '#', bold: true });
                        
                        reseller_items.push({ text:'List Account', url:'/{{$accounts_url}}?id='+data.reseller.id, data_target:"sidebarform", });
                        reseller_items.push({ text:'Edit Account', url:'/{{$accounts_url}}/edit/'+data.reseller.id, data_target:"sidebarform", });
                       
                            reseller_items.push({ text:'Partner Accounts', url:'/{{$accounts_url}}?partner_id='+data.reseller.id, data_target:"view_modal",  });
                        
                        
                        reseller_items.push({ text:'Pricelist', url:'/{{$pricelist_items_url}}?pricelist_id='+data.pricelist_id, data_target:"view_modal",  });
                        reseller_items.push({ text:'User', url:'/{{$users_url}}?account_id='+data.reseller.id, data_target:"view_modal",  });
                        if(data.disable_access){
                            if(data.reseller.status == 'Disabled' || data.reseller.status == 'Disabled by Reseller'){
                                reseller_items.push({ text:'Enable', url:'/switch_account/'+data.reseller.id, data_target:"ajax",  });
                            }
                            if(data.reseller.status == 'Enabled'){
                                reseller_items.push({ text:'Disable', url:'/switch_account/'+data.reseller.id, data_target:"ajax",  });
                            }
                        }
                        
                        
                        
                        if(reseller_items.length > 0){
                            resellerinfo{{ $grid_id }}.items = reseller_items;
                            resellerinfo{{ $grid_id }}.disabled = false;
                        }
                    }else{
                        resellerinfo{{ $grid_id }}.disabled = true;
                    }
                    
                    @if($communications_type == 'account')
                    var services_items = [];
                    var pbx_items = [];
                    if(data.account){
                            
                            services_items.push({ text:'Services',iconCss: 'far fa-star', heading: true });
                            services_items.push({ text:'Subscription Total: R'+parseFloat(data.account.subs_total).toFixed(2), url: '#', bold: true });
                            services_items.push({ text:'Subscription Count: '+data.account.subs_count, url: '#', bold: true });
                            if(data.account.call_profits && data.account.call_profits != 'none'){
                                services_items.push({ text:'Call Profits: R'+parseFloat(data.account.call_profits).toFixed(2), url: '#', bold: true });
                            }
                            
                            if(data.account.type != 'reseller'){
                                if(data.account.sms_balance != 'none'){
                                    services_items.push({ text:'SMS Balance: R'+parseFloat(data.account.sms_balance).toFixed(2), url: '#', bold: true});
                                }
                            }
                            
                            if(data.account.type == 'reseller'){
                                services_items.push({ text:'Subscriptions', url:'/{{$subscriptions_url}}?partner_id='+data.id, data_target:"view_modal",  });
                            }else{
                                services_items.push({ text:'Subscriptions', url:'/{{$subscriptions_url}}?account_id='+data.id, data_target:"view_modal",  });
                            }
                            
                            services_items.push({ text:'SMS System', url:'/{{$sms_panel_url}}?account_id='+data.id, data_target:"view_modal",  });
                            services_items.push({ text:'Hosting System', url:'/{{$hosting_panel_url}}?account_id='+data.id, data_target:"view_modal",  });
                            
                            if(data.pbx_domains_url || data.pbx_domain_url){
                                pbx_items.push({ text:'PBX',iconCss: 'far fa-star', heading: true });
                                
                             
                                if(data.account.pbx_balance != 'none'){
                                    pbx_items.push({ text:'PBX Balance: R'+parseFloat(data.account.pbx_balance).toFixed(2), url: '#', bold: true });
                                }
                            
                                if(data.pbx_domains_url){
                                    pbx_items.push({ text:'PBX Domains', url:data.pbx_domains_url, data_target:"view_modal",  });
                                }
                                
                                if(data.pbx_domain_url){
                                    pbx_items.push({ text:'PBX Domain', url:data.pbx_domain_url, data_target:"view_modal",  });
                                }
                                
                                if(data.pbx_numbers_url){
                                    pbx_items.push({ text:'PBX Numbers', url:data.pbx_numbers_url, data_target:"view_modal",  });
                                }
                                
                                if(data.cdr_outbound_url){
                                    pbx_items.push({ text:'CDR Outbound', url:data.cdr_outbound_url, data_target:"view_modal",  });
                                }
                                
                                if(data.cdr_inbound_url){
                                    pbx_items.push({ text:'CDR Inbound', url:data.cdr_inbound_url, data_target:"view_modal",  });
                                }
                                
                                pbxinfo{{ $grid_id }}.items = pbx_items;
                                pbxinfo{{ $grid_id }}.disabled = false;
                            }else{
                                pbxinfo{{ $grid_id }}.items = [];
                                pbxinfo{{ $grid_id }}.disabled = true;
                            }
                           
                        
                        servicesinfo{{ $grid_id }}.items = services_items;
                        servicesinfo{{ $grid_id }}.disabled = false;
                    }else{
                        servicesinfo{{ $grid_id }}.disabled = true;
                        servicesinfo{{ $grid_id }}.items = [];
                    }
                    @endif
                    
                }
            });
            
            
            
        }else{
            accountinfo{{ $grid_id }}.disabled = true;
            accountinfo{{ $grid_id }}.items = [];
            
            communicationsinfo{{ $grid_id }}.disabled = true;
            communicationsinfo{{ $grid_id }}.items = [];
        }
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
                //////console.log(fields_url);
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
  
    return menuItems;
}
    



    

class CustomDateComponent{{ $grid_id }} {
  init(params) {
    const template = `
            <input type="text" data-input style="width: 100%;" />
            <a class="input-button" title="clear" data-clear>
                <i class="fa fa-times"></i>
            </a>`;

    this.params = params;

    this.eGui = document.createElement('div');
    this.eGui.setAttribute('role', 'presentation');
    this.eGui.classList.add('ag-input-wrapper');
    this.eGui.classList.add('custom-date-filter');
    this.eGui.innerHTML = template;

    this.eInput = this.eGui.querySelector('input');

    this.picker = flatpickr(this.eGui, {
      onChange: this.onDateChanged.bind(this),
      dateFormat: 'd/m/Y',
      wrap: true,
    });

    this.picker.calendarContainer.classList.add('ag-custom-component-popup');

    this.date = null;
  }

  getGui() {
    return this.eGui;
  }

  onDateChanged(selectedDates) {
    this.date = selectedDates[0] || null;
    this.params.onDateChanged();
  }

  getDate() {
    return this.date;
  }

  setDate(date) {
    this.picker.setDate(date);
    this.date = date;
  }

  setInputPlaceholder(placeholder) {
    this.eInput.setAttribute('placeholder', placeholder);
  }
}


/** CUSTOM TOOLPANELS - START **/
/*
class formsToolPanel{{ $grid_id }} {
    init(params) {
      
        this.eGui = document.createElement('div');
        this.eGui.classList.add("ag-column-panel");
        
        var forms_html = '<h4 class="w-100 p-2 m-0">Access</h4>';      
             
        @if(check_access('1,31,34'))
            forms_html += '<div class="gridsidebar_buttons ag-toolpanel-buttons e-btn-group d-flex k-button-group" role="group">';
            forms_html += '<button title="Create" id="formsbtn_create{{ $grid_id }}" class="k-button w-100" ><span  class="e-btn-icon fa fa-plus"></span></button>';
            forms_html += '<button title="View" id="formsbtn_view{{ $grid_id }}" class="k-button w-100" disabled="Disabled"><span  class="e-btn-icon fa fa-search"></span></button>';
            forms_html += '<button title="Edit" id="formsbtn_edit{{ $grid_id }}" class="k-button w-100"  disabled="Disabled"><span  class="e-btn-icon fas fa-pen"></span></button>';
            forms_html += '<button title="Duplicate" id="formsbtn_duplicate{{ $grid_id }}" class="k-button w-100" disabled="Disabled"><span  class="e-btn-icon fa fa-copy"></span></button>';
            forms_html += '<button title="Delete" id="formsbtn_delete{{ $grid_id }}" class="k-button w-100" disabled="Disabled"><span  class="e-btn-icon fa fa-trash"></span></button>';       
            forms_html += '</div>';
        @endif
        forms_html += '<div class="ag-layouts-content">';
        forms_html += '<ul id="gridforms_{{ $grid_id }}"></ul>';
        forms_html += '</div>';
       
        this.eGui.innerHTML = forms_html;
        
        function renderformsMenu(params){
           
            window['gridforms_{{ $grid_id }}'] = new ej.navigations.Menu({
            items: {!! json_encode($sidebar_forms) !!},
            orientation: 'Vertical',  
            cssClass: 'e-scrollable-menu formspanel',
            showItemOnClick: true,
            beforeItemRender: function(args){
            
                var el = args.element;   
                if(args.item.cssClass > '') {
                    var el = args.element;
                    $(el).find("a").addClass(args.item.cssClass);
                }
                
                if(args.item.builder_id) {
                    $(el).find("a").attr("builder_id",args.item.builder_id);
                }
                
                if(args.item.role_id) {
                    $(el).find("a").attr("role_id",args.item.role_id);
                }
                
            
            },
            }, '#gridforms_{{ $grid_id }}');
        
        }
        
        params.api.addEventListener('gridReady', renderformsMenu);
    }
    getGui() {
        return this.eGui;
    }
}
*/



@if(session('role_level') == 'Admin')

class changelogToolPanel{{ $grid_id }} {
  init(params) {
    this.eGui = document.createElement('div');

    this.eGui.classList.add("ag-column-panel");
    
    var changelog_html = '<h4 class="w-100 p-2 m-0">Change Log</h4>';      
    
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
        ////console.log(rows);
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
            ////console.log("getChangelog{{$grid_id}}");
            ////console.log(data);
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
    notes_html += '<div id="notes_form{{ $grid_id }}">';
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
            ////console.log(rows);
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
                ////console.log("getNotes{{$grid_id}}");
                ////console.log(data);
                var notes_html = '';
                $("#notescount{{$grid_id}}").text(' ('+data.length+')');
                $(data).each(function(i,el){
                    //console.log(el);
                    //console.log(el.note);
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
            
        }else{
            var notes_html = '';
            
            $("#notes_results{{ $grid_id }}").html(notes_html);
        }
  }
@if($communications_type > '')
class contactsToolPanel{{ $grid_id }} {
  init(params) {
    this.eGui = document.createElement('div');

    this.eGui.classList.add("ag-column-panel");
    
    var contacts_html = '<h4 class="w-100 p-2 m-0">Contacts</h4>';      
    
    contacts_html += '<div class="ag-layouts-content" id="contacts_panel{{ $grid_id }}" style="overflow:scroll">';

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
       
        if(selected && selected.rowId && name > '' && type > '' && phone > '' && email > ''){
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
            ////console.log(rows);
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
                //console.log("getContacts{{$grid_id}}");
                //console.log(data);
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
            ////console.log('uploading');
            ////console.log(args);
            ////console.log(selected);
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
            ////console.log(rows);
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
                ////console.log("getFiles{{$grid_id}}");
                ////console.log(data);
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



@if(!request()->ajax())
window['original_title'] = document.title;
@endif
init_load = true;
pivot_mode = 0;
filter_cleared{{ $grid_id }} = 0;

/** SYNCFUSION COMPONENTS **/
$(document).on('change','#searchtext{{ $grid_id }}',function(e){
       
        if($("#searchtext{{ $grid_id }}").val() == '' || $("#searchtext{{ $grid_id }}").val() == null){
            window['grid_{{ $grid_id }}'].gridOptions.api.setQuickFilter(' ');
            @if($serverside_model)
            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            @endif
        }else{
            window['grid_{{ $grid_id }}'].gridOptions.api.setQuickFilter($("#searchtext{{ $grid_id }}").val());
            @if($serverside_model)
            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            @endif
        }
	
});








/** TOOLBAR **/

    default_layout_saved = 0;
    
   
    
    window['toolbar{{ $grid_id }}'] = new ej.navigations.Toolbar({
        items: [
            
            { template: "#toolbar_template_gridbuttons{{ $grid_id }}", align: 'left' },
            { template: "#toolbar_template_accountbuttons{{ $grid_id }}", align: 'left' },
            { template: "#toolbar_template_rowbuttons{{ $grid_id }}", align: 'right' },
        ]
    });
    window['toolbar{{ $grid_id }}'].appendTo('#gridtoolbar{{ $grid_id }}');


	
/** LAYOUTS **/

/** LAYOUT EVENTS **/    
	$(document).off('click', '#layoutsbtn_manage{{ $grid_id }}').on('click', '#layoutsbtn_manage{{ $grid_id }}', function() {
	    viewDialog('gridv{{ $grid_id }}','/{{$layouts_url}}?module_id={{ $module_id }}','Grid Views - {{$title}}','90%','90%','coreDialog');
	});
	
	$(document).off('click', '#layoutsbtn_create{{ $grid_id }}').on('click', '#layoutsbtn_create{{ $grid_id }}', function() {
	    sidebarform('gridcv{{ $grid_id }}','/{{$layouts_url}}/edit?module_id={{ $module_id }}&grid_reference=grid_{{ $grid_id }}','Create Grid View','','90%');
	});
	
	$(document).off('click', '#layoutsbtn_save{{ $grid_id }}').on('click', '#layoutsbtn_save{{ $grid_id }}', function(e) {
	    //console.log(1);
	    layout_save{{ $grid_id }}();
	});
	
	
	@if($layout_access['is_add'])
	$(document).off('click', '#layoutsbtn_duplicate{{ $grid_id }}').on('click', '#layoutsbtn_duplicate{{ $grid_id }}', function() {
		if(window['layout_id{{ $grid_id }}']){
	    	gridAjaxConfirm('/{{ $layouts_url }}/duplicate', 'Duplicate layout?', {"id" : window['layout_id{{ $grid_id }}']}, 'post');
		}	
	});
	@endif
	
	$(document).off('click', '#layoutsbtn_delete{{ $grid_id }}').on('click', '#layoutsbtn_delete{{ $grid_id }}', function() {
        var confirm_text = "Delete layout?"
        var confirmation = confirm(confirm_text);
        if (confirmation) {
	        layout_delete();
        }
	});

	$(document).off('click', '#layoutsbtn_showall{{ $grid_id }}').on('click', '#layoutsbtn_showall{{ $grid_id }}', function() {
	    gridview_show_all();
	});
	
	$(document).off('click', '[id^="layoutsbtnload{{ $grid_id }}_"]').on('click', '[id^="layoutsbtnload{{ $grid_id }}_"]', function() {
	  
	    var layout_id = $(this).attr('id').replace("layoutsbtnload{{ $grid_id }}_", "");
	    layout_load(layout_id);
	});
	
	$(document).off('click', '#layoutsbtn_edit{{ $grid_id }}').on('click', '#layoutsbtn_edit{{ $grid_id }}', function() {
	    sidebarform('gridcv{{ $grid_id }}','/{{$layouts_url}}/edit/'+window['layout_id{{ $grid_id }}'],'Edit Layout','','90%');
	});
	

    
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
function CustomPinnedRowRenderer() {}

CustomPinnedRowRenderer.prototype.init = function (params) {
  this.eGui = document.createElement('div');
  setStyle(this.eGui, params.style);
  this.eGui.innerHTML = params.value;
};

CustomPinnedRowRenderer.prototype.getGui = function () {
  return this.eGui;
};

/** AGGRID **/

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
    @if(is_dev() || session('role_id') == 1)
    var range_cells = window['grid_{{ $grid_id }}'].gridOptions.api.getCellRanges();
    var rowModel = window['grid_{{ $grid_id }}'].gridOptions.api.getModel();
    //////console.log('range_cells',range_cells);
    if(range_cells[0].startRow.rowIndex != range_cells[0].endRow.rowIndex){
        var range = range_cells[0];
        // get starting and ending row, remember rowEnd could be before rowStart
        var startRow = Math.min(range.startRow.rowIndex, range.endRow.rowIndex);
        var endRow = Math.max(range.startRow.rowIndex, range.endRow.rowIndex);
        
        var row_ids = [];
        for (var rowIndex = startRow; rowIndex <= endRow; rowIndex++) {
            var rowNode = rowModel.getRow(rowIndex);
           
            row_ids.push(rowNode.{{$db_key}});
        }
        
        result.unshift(
        {
        name: "Delete "+row_ids.length+" Rows",
        action: function () {
            var confirmation = confirm("Delete "+row_ids.length+" Rows? delete cannot be undone.");
                if (confirmation) {
                var post_data =  {row_ids: row_ids };    
                $.ajax({ 
                url: "/{{$menu_route}}/delete_multiple", 
                type: 'post',
                data: post_data,
                beforeSend: function(){
                    showSpinner();
                },
                success: function(data) {
                    hideSpinner();
                    processAjaxSuccess(data, false);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    hideSpinner();
                    processAjaxError(jqXHR, textStatus, errorThrown);
                },
                });
            }
        }  
        }
        );
    }
    @endif
    var standard_buttons = [
        'copy',
        'copyWithHeaders',
        {
        name: 'Copy Selected Row',
        action: function () {
           window['grid_{{ $grid_id }}'].gridOptions.api.copySelectedRowsToClipboard();
        },
        icon:'<span class="ag-icon ag-icon-copy"></span>',
        },
        @if(session('user_id') == 1)
        'separator',
        'export',
        @endif
    ];
    result.push(...standard_buttons);
    
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


class CustomLoadingOverlay {
  init(params) {
    this.eGui = document.createElement('div');
    this.eGui.innerHTML = `
            <div class="ag-custom-loading-cell" style="background-color: #fff; padding-left: 10px; line-height: 25px; font-size: 15px;">  
                <i class="fas fa-spinner fa-pulse"></i> 
                <span><b>${params.loadingMessage} </b></span>
            </div>
        `;
  }

  getGui() {
    return this.eGui;
  }
}

var gridOptions = {
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
    getRowNodeId: getRowNodeId{{ $grid_id }},
    @if(!$serverside_model)
    immutableData: true,
    @endif
    @if((session('role_id')==1 || is_dev()) && $module_id==519)
     
        rowGroupPanelShow: 'always',
    @else
        @if($grouprows)
    
            groupDisplayType: 'singleColumn',
            groupMaintainOrder: true,
            rowGroupPanelShow: 'onlyWhenGrouping',
             // adds subtotals
            @if($show_group_totals)
            groupIncludeFooter: true,
            @endif
            // includes grand total
            @if($show_group_subtotals)
            groupIncludeTotalFooter: true,
            @endif
        @endif
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
    getContextMenuItems: getContextMenuItems{{$module_id}},
    defaultExcelExportParams: {fileName: '{{$menu_name}}.xlsx'},
    defaultCsvExportParams: {fileName: '{{$menu_name}}.csv'},
    pivotMode: false,
    debug: false,
    onRowDoubleClicked: function(event){
        @if($access['is_edit'])
        var selected = window['selectedrow_{{ $grid_id }}'];
        
        @if($documents_module)
        transactionDialog('{{ $menu_route }}edit', '/{{ $menu_route }}/edit/'+ selected.rowId, 'Documents - Edit', '80%', '100%');
        @else
        
        sidebarform('{{ $menu_route }}edit' , '/{{ $menu_route }}/edit/'+ selected.rowId+'?layout_id='+window['layout_id{{ $grid_id }}'], '{!! $menu_name !!} - Edit', '', '60%');
        
        @endif
        @endif
    },
    @if($master_detail)
    //detailRowAutoHeight: true,
    detailRowHeight: 400,
    @endif
    icons: {
        contacts_icon: '<i class="ri-contacts-book-2-line"></i>',
        notes_icon: '<i class="ri-sticky-note-line"></i>',
        log_icon: '<i class="ri-file-history-line"></i>',
        files_icon: '<i class="ri-folders-line"></i>',
    },
   
    @if(!empty($rowClassRules))
    rowClassRules: {!! json_encode($rowClassRules) !!},
    @endif
    //enableCharts: true,
    //enableRangeSelection: true,
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
        serverSideStoreType: 'partial',
        @if($pinned_totals)
        pinnedBottomRowData: [{}],
        @endif
    @else
        @if($has_sort)
        rowDragEntireRow: true,
        //rowDragManaged: true,
        //suppressMoveWhenRowDragging: true,
        @endif
    @endif
    tooltipShowDelay:1,
    enableBrowserTooltips: true,
    columnDefs: {!! json_encode($columnDefs) !!},
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
                browserDatePicker: true,
                minValidYear: 2000,
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
            headerClass: 'ag-right-aligned-header',
            cellClass: 'ag-right-aligned-cell',
            comparator: (valueA, valueB, nodeA, nodeB, isInverted) => valueA - valueB
        },
        currencyField: {
            filter: 'agNumberColumnFilter',
            valueFormatter: function(params){
              //  if(!params.node.footer){
                   
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
            comparator: (valueA, valueB, nodeA, nodeB, isInverted) => valueA - valueB
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
        enableValue: true,
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
        menuTabs: ['columnsMenuTab','filterMenuTab','generalMenuTab'],
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
              //  '  <i onClick="editColumnHeader(event)" class="edit_column_header far fa-edit"> </i>' +
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
            ////////console.log(params);
            let val = '';
            params.values.forEach(value => val = value);
            return val;
        }
    },
    @if(session('role_level') == 'Admin')
    sideBar: {
    toolPanels: [
       
            @if(session('role_level') == 'Admin')
            
            @if($communications_type > '')
            {
                id: 'contacts',
                labelDefault: 'Contacts <span id="contactscount{{ $grid_id }}"></span>',
                labelKey: 'contacts',
                iconKey: 'contacts_icon',
                toolPanel: 'contactsToolPanel{{ $grid_id }}',
            },
            @endif
            {
                id: 'notes',
                labelDefault: 'Notes <span id="notescount{{ $grid_id }}"></span>',
                labelKey: 'notes',
                iconKey: 'notes_icon',
                toolPanel: 'notesToolPanel{{ $grid_id }}',
            },
            {
                id: 'files',
                labelDefault: 'Files <span id="filescount{{ $grid_id }}"></span>',
                labelKey: 'files',
                iconKey: 'files_icon',
                toolPanel: 'filesToolPanel{{ $grid_id }}',
            },
            {
                id: 'changelog',
                labelDefault: 'Log <span id="logcount{{ $grid_id }}"></span>',
                labelKey: 'changelog',
                iconKey: 'log_icon',
                toolPanel: 'changelogToolPanel{{ $grid_id }}',
            },
            @endif
           
        ],
        defaultToolPanel: '',
    },
    @endif
    components: {
        CustomLoadingOverlay: CustomLoadingOverlay,
        //agDateInput: CustomDateComponent{{ $grid_id }},
        SyncFusionCellEditor{{ $grid_id }}:SyncFusionCellEditor{{ $grid_id }},
        booleanCellRenderer: booleanCellRenderer,
        customPinnedRowRenderer: CustomPinnedRowRenderer,
        /* formsToolPanel{{ $grid_id }}: formsToolPanel{{ $grid_id }}, */
    
        @if(session('role_level') == 'Admin')
        @if($communications_type > '')
        contactsToolPanel{{ $grid_id }}: contactsToolPanel{{ $grid_id }},
        @endif
        notesToolPanel{{ $grid_id }}: notesToolPanel{{ $grid_id }},
        filesToolPanel{{ $grid_id }}: filesToolPanel{{ $grid_id }},
        changelogToolPanel{{ $grid_id }}: changelogToolPanel{{ $grid_id }},
        @endif
        
    },
   
    @if($master_detail)
    
    masterDetail: true,
    detailCellRendererParams: 
    {
        refreshStrategy: 'rows',
        // provide the Grid Options to use on the Detail Grid
        detailGridOptions: detailGridOptions,
        // get the rows for each Detail Grid
        getDetailRowData: function (params) {
            ////////console.log(params);
            @if($master_module_key)
            var master_key = params.data.{{$master_module_key}};
            mastergrid_id = params.data.{{$master_module_key}};
            @else
            var master_key = params.data.{{$db_key}};
            mastergrid_id = params.data.{{$db_key}};
            @endif
            var post_data = { detail_value:master_key, detail_field: '{{ $detail_module_key }}' };
           
            window['mastergrid_row{{ $grid_id }}'] =params.data;
            ////////console.log(post_data);
            request_detail_value = master_key;
            request_detail_field = '{{ $detail_module_key }}';
            $.ajax({ 
                url: "/{{ $detail_menu_route }}/aggrid_detail_data", 
                type: 'post',
                data: post_data,
                success: function (result) {
                   
                    window['detail_row_data{{ $grid_id }}'] = result;
                    params.successCallback(result);
                   //return result;
                }, 
            });
        },
    },
    @endif
    onFilterChanged: function(){
     
        window['grid_{{ $grid_id }}'].gridOptions.api.deselectAll();
        var row_count = window['grid_{{ $grid_id }}'].gridOptions.api.getDisplayedRowCount();
        $("#rowcount{{ $grid_id }}").text(row_count);
    },
    onRowSelected: function(event){
      
        if(!event.node.isSelected()){
            var deselected = event.node.data;
            if(deselected.{{$db_key}} == window['selectedrow_{{ $grid_id }}'].rowId){
              
                window['selectedrow_{{ $grid_id }}'] = null;
                rowDeselected();
            }
        }
        if(event.node.isSelected() && event.node.group == false){
            
            window['selectedrow_{{ $grid_id }}'] = event.node.data;
            window['selectedrow_{{ $grid_id }}'].rowId = window['selectedrow_{{ $grid_id }}'].{{$db_key}};
            @if($master_detail)
                window['grid_{{ $grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
                        detailGridApi.api.deselectAll();
                });
                //$("#grid_{{ $grid_id }}").removeClass('detailgrid-focus').addClass('mastergrid-focus');
                
                if( $("#gridtoolbar{{ $grid_id }}").is(":hidden")){
                    $("#gridtoolbar{{ $grid_id }}").show();
                 
                    $("#detailtoolbardetail{{ $grid_id }}").hide();
                    $("#detailheadertoolbardetail{{ $grid_id }}").hide();
                    $("#grid_{{ $grid_id }}").removeClass('detailgrid-focus').addClass('mastergrid-focus');
                    window['grid_{{ $grid_id }}'].gridOptions.api.setSideBarVisible(true);
                }
            @endif
            rowSelected{{ $grid_id }}();
        }
    },
    @if($master_detail)
    onRowGroupOpened: function(event){
        if(event.expanded){   
            gridOptions.api.forEachNode(function (node) {
                if(node.expanded && node.id != event.node.id && node.groupData == null){
                    
                    node.setExpanded(false);
                }
            });
        }
    },
    @endif
    onModelUpdated: function(){
        ////console.log('onModelUpdated');
    },
    onViewportChanged: function(){
        //console.log('onViewportChanged');
            
        this.columnApi.autoSizeAllColumns();
    },
    onFirstDataRendered:  function(){
        ////console.log('onFirstDataRendered');
        //window['grid_{{ $grid_id }}'].gridOptions.columnApi.autoSizeAllColumns();
    },
    onGridReady: onGridReady{{ $grid_id }},
    @if($serverside_model) 
    refresh: function(){
        window['grid_{{ $grid_id }}'].gridOptions.api.deselectAll();
        window['grid_{{ $grid_id }}'].gridOptions.api.refreshServerSideStore();
    },
    refreshRow: function(){
        window['grid_{{ $grid_id }}'].gridOptions.api.deselectAll();
        window['grid_{{ $grid_id }}'].gridOptions.api.refreshServerSideStore();
    },
    @else
    refresh: function(){
        //console.log('grid refresh');
        refreshGridData{{ $grid_id }}();
        this.columnApi.autoSizeAllColumns();
    },
    refreshRow: function(row_id, new_record = 0){
       
        refreshGridData{{ $grid_id }}();
      
       
        this.columnApi.autoSizeAllColumns();
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
    rowHeight: 30,
    headerHeight: 36,
    
};
window['layoutgridOptions_{{ $grid_id }}'] = gridOptions;

window['grid_{{ $grid_id }}'] = new agGrid.Grid(document.querySelector('#grid_{{ $grid_id }}'), gridOptions);







@if($serverside_model)
grid_filters = null;
var datasource = {
    getRows(params) {
       
        window['grid_{{ $grid_id }}'].gridOptions.api.deselectAll();
      
                       
        if($("#searchtext{{ $grid_id }}").val() != null){
           
            params.request.search = $("#searchtext{{ $grid_id }}").val();
        }
        @if(!empty($detail_field))
            params.request.detail_field = '{!! $detail_field !!}';
        @endif
        @if(!empty($detail_value))
            params.request.detail_value = '{!! $detail_value !!}';
        @endif
        
        @if($pinned_totals)
        params.request.rowTotals = 1;
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
            gridOptions.columnApi.autoSizeAllColumns();
            @if($pinned_totals)
            gridOptions.api.setPinnedBottomRowData( response.rowTotals );
            @endif
            
            window['grid_{{ $grid_id }}'].gridOptions.api.hideOverlay(); 
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
function refreshRowData{{ $grid_id }}(row_id = false, new_record = 0){
   
 
    if(row_id){
       $.ajax({ 
            url: "/{{$menu_route}}/aggrid_refresh_row?row_id="+row_id, 
            beforeSend: function(){
              
            },
            success: function (result) { 
                if(new_record){
                    var newItems = [result];
                    var res = gridOptions.api.applyTransaction({
                    add: newItems,
                    });
                    ////////console.log(res);
                }else{
                    //rowNode.setData(data) or rowNode.setDataValue(col,value)
                    //////console.log(result);
                    //////console.log(row_id);
                    var rowNode = window['grid_{{ $grid_id }}'].gridOptions.api.getRowNode(row_id);
                    //////console.log(rowNode);
                    rowNode.setData(result);
                    window['grid_{{ $grid_id }}'].gridOptions.api.refreshClientSideRowModel();
                }
                var row_count = window['grid_{{ $grid_id }}'].gridOptions.api.getDisplayedRowCount();
                //$("#rowcount{{ $grid_id }}").text(row_count);
            }, 
        });
    }
}

function refreshGridData{{ $grid_id }}(row_id = false){
  
    //////console.log('refreshGridData');
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





function onGridReady{{ $grid_id }}(params){
    ////console.log('onGridReady');
    @if($init_filters)
        var init_filters = {!! json_encode($init_filters) !!}
        window['grid_{{ $grid_id }}'].gridOptions.api.setFilterModel(init_filters);
    @endif
    @if($module_id == 760)
	setInterval(function(){window['grid_{{ $grid_id }}'].gridOptions.refresh();}, 60 * 2000);
	@endif
    @if($communications_panel)
    window['grid_{{ $grid_id }}'].gridOptions.api.addEventListener('rowSelected', renderRightInfoButtons);
    @endif
    
   
  
    
    row_data{{ $grid_id }} = {!! json_encode($row_data) !!};
    var row_count = window['grid_{{ $grid_id }}'].gridOptions.api.getDisplayedRowCount();
    //$("#rowcount{{ $grid_id }}").text(row_count);
    @if(!$serverside_model)
    window['grid_{{ $grid_id }}'].gridOptions.api.setRowData(row_data{{ $grid_id }});
    @endif
   
    layout_init();
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
 
    $(document).on("contextmenu", ".ag-header .ag-header-cell", function (e) {
        
        e.preventDefault();
        if ($(this).parents('.ag-details-row').length) {
            return false;
        }
        var col_id = $(this).attr('col-id');
        window['grid_{{ $grid_id }}'].gridOptions.api.showColumnMenuAfterMouseClick(col_id,e);
        return false;
    });
    init_load = false;
    
}


function refresh_grid() {
	window['grid_{{ $grid_id }}'].gridOptions.refresh();
}

$(".{{ $grid_id }}Refresh").click(function() {
	window['grid_{{ $grid_id }}'].gridOptions.refresh();
	@if($master_detail)
	window['grid_{{ $grid_id }}'].gridOptions.api.forEachDetailGridInfo(function(detailGridApi) {
	    var master_id = detailGridApi.id.replace('detail_','');
	  
	    
        var post_data = { detail_value: master_id, detail_field: '{{ $detail_field }}' };
	  
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
	@endif
});


@if($module_id == 704)
    function getRowNodeId{{ $grid_id }}(data) {
      return data.grid_id;
    }
@else
    function getRowNodeId{{ $grid_id }}(data) {
      return data.{{$db_key}};
    }
@endif


function rowSelected{{ $grid_id }}() {
    
@if($check_doctype)
    doctypes = {!! json_encode($doctypes) !!};
@endif
var selected = window['selectedrow_{{ $grid_id }}'];


@if(!empty($grid_menu_menu) && count($grid_menu_menu) > 0) 
////////console.log('selected');
   ////////console.log(grid_menu{{ $grid_id }});
    ////////console.log(selected);
    grid_menu{{ $grid_id }}.refresh();
@endif
@if(!empty($related_items_menu_menu) && count($related_items_menu_menu) > 0) 
////////console.log('selected');
   ////////console.log(grid_menu{{ $grid_id }});
    ////////console.log(selected);
    related_items_menu{{ $grid_id }}.refresh();
@endif

selected_doctype_el = null;

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



@if($access['is_add'] && !in_array($db_table,['call_records_inbound','call_records_outbound','crm_documents','crm_supplier_documents']))
$('#{{ $grid_id }}Duplicate').removeAttr("disabled");
@endif

@if($access['is_approve'])

if(selected_doctype_el != null){

if(selected_doctype_el.approve_manager == 1){
$('#{{ $grid_id }}Approve').removeAttr("disabled");
toolbar_button_icon('{{ $grid_id }}Approve','approve_manager', 'Approve '+selected_doctype_el.doctype_label);
}else if(selected_doctype_el.approve == 1){
$('#{{ $grid_id }}Approve').removeAttr("disabled");
toolbar_button_icon('{{ $grid_id }}Approve','approve', 'Approve '+selected_doctype_el.doctype_label);
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
    if(selected_doctype_el != null){
       
        if(selected_doctype_el.deletable == 1){
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
            toolbar_button_icon('{{ $grid_id }}Delete','delete', 'Delete '+selected_doctype_el.doctype_label);
        }else if(selected_doctype_el.creditable == 1){
            $('#{{ $grid_id }}Delete').removeAttr("disabled");
            toolbar_button_icon('{{ $grid_id }}Delete','reverse', 'Credit '+selected_doctype_el.doctype_label);
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


@if(!empty($grid_menu_menu) && count($grid_menu_menu) > 0) 
{!! button_menu_selected($module_id, 'grid_menu', $grid_id, 'selected', false) !!}
@endif

@if(!empty($related_items_menu_menu) && count($related_items_menu_menu) > 0) 
{!! button_menu_selected($module_id, 'related_items_menu', $grid_id, 'selected', false) !!}
@endif

}

function rowDeselected(){
        
        @if(!empty($grid_menu_menu) && count($grid_menu_menu) > 0) 
        {!! button_menu_selected($module_id, 'grid_menu', $grid_id, 'deselected', false) !!}
        @endif
        
        @if(!empty($related_items_menu_menu) && count($related_items_menu_menu) > 0) 
        {!! button_menu_selected($module_id, 'related_items_menu', $grid_id, 'deselected', false) !!}
        @endif
     
        
        @if($access['is_add'] && !in_array($db_table,['call_records_inbound','call_records_outbound','crm_documents','crm_supplier_documents']))
		$('#{{ $grid_id }}Duplicate').attr("disabled","disabled");
        @endif
        
       
        
        @if($access['is_edit'])
		    $('#{{ $grid_id }}Edit').attr("disabled","disabled");
        @endif
        
        @if($access['is_approve'])
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
        
}

    var dialogclass = '';
/** BUTTON EVENTS **/
    @if($access['is_import'])
        $("#{{ $grid_id }}Import").click(function(){
         sidebarform('{{ $menu_route }}import' , '/{{ $menu_route }}/import', '','', '60%');
        });
    @endif
    
    @if($access['is_add'])
        $("#{{ $grid_id }}Add").click(function(){
            @if($menu_route == 'pbx_menu')
                sidebarform('{{ $menu_route }}add' , 'pbx_menuedit', 'PBx Menu Add', '','60%');
            @elseif(!empty(request()->account_id) && $documents_module)
                transactionDialog('{{ $menu_route }}add' , '/{{ $menu_route }}/edit?account_id={{request()->account_id}}', 'Document - Add', '80%', 'auto');
            @elseif($documents_module)
            transactionDialog('{{ $menu_route }}add' , '/{{ $menu_route }}/edit/', 'Document - Add', '80%', 'auto');
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
               
                sidebarform('{{ $menu_route }}add' , url, '{!! $menu_name !!} - Add','{!! $form_description !!}', '60%');
            @endif
        });
    @endif
    
    $(".toolbar_grid_buttons").click(function(e){
        if($(e.target).is('[disabled=disabled]') || $(e.target.parentElement).is('[disabled=disabled]')){
            alert('Select a row');
        }
    });
    
    
    @if($access['is_edit'])
        $("#{{ $grid_id }}Edit").click(function(){
            
            var selected = window['selectedrow_{{ $grid_id }}'];
            
            @if($documents_module)
                transactionDialog('{{ $menu_route }}edit', '/{{ $menu_route }}/edit/'+ selected.rowId, 'Documents Edit', '80%', '100%');
            @else
               
               
                sidebarform('{{ $menu_route }}edit' , '/{{ $menu_route }}/edit/'+ selected.rowId+'?layout_id='+window['layout_id{{ $grid_id }}'], '{!! $menu_name !!} - Edit', '{!! $form_description !!}','60%');
                
            @endif
        });
    @endif
    

    @if($access['is_approve'])
      
    $("#{{ $grid_id }}Approve").click(function(){
     
        var selected = window['selectedrow_{{ $grid_id }}'];
        var check_access = {{ (check_access('1,2,7,34')) ? 1: 0 }};
      
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
    
	         
    @if($access['is_view'] && (in_array($db_table,['crm_documents','crm_supplier_documents','crm_supplier_import_documents'])))
    
	  
        $("#{{ $grid_id }}View").click(function(){
            var selected = window['selectedrow_{{ $grid_id }}'];
          
            viewDialog('{{ $menu_route }}'+selected.rowId, '/{{ $menu_route }}/view/'+ selected.rowId,'','70%');
          
        });
    @endif

    
    @if($access['is_add'] && !in_array($db_table,['call_records_inbound','call_records_outbound','crm_documents','crm_supplier_documents']))
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
        });
    @endif
    
    @if($access['is_view'])
        $("#{{ $grid_id }}Export").click(function(){
            //////console.log("{{ $grid_id }}Export");
            window['grid_{{ $grid_id }}'].gridOptions.api.exportDataAsExcel({fileName: '{{$menu_name}}.xlsx'});
        });
    @endif      
    
    
  
    
    $(document).off('click','.gridimage').on('click','.gridimage', function(){
       imgDialog($(this).attr('src')); 
    });
    
  
    
    
   

 
    
    @if(is_dev())
    function sum(values, col) {
    var sum = 0;
    values.forEach( function(value) {sum += value.data[col]} );
    return sum;
}
    function createFooterData(gridApi, rowData) {
       
      var result = [];
      
 
  
  
      result.push({
        @foreach($columnDefs as $col)
        @if($col['type'] == 'currencyField')
        {{$col['field']}}: sum(rowData, '{{$col['field']}}'),
        @endif
        @endforeach
      });
     
    
      return result;
    }
    @endif

    
    
 

   
    
})();

/** LAYOUT FUNCTIONS **/ 
    function layout_init(){
        
        window['layout_id{{ $grid_id }}'] = {{$grid_layout_id}};
      
        @if($grid_layout_type == 'default_new')
        if(default_layout_saved == 0){
            default_layout_saved = 1;
            layout_save{{ $grid_id }}();
        }
     
        @else
        
        layout_load(window['layout_id{{ $grid_id }}'],1);
        @endif
    }
    
    function layout_delete(){
        $.ajax({
		url: '/delete_grid_config/'+window['layout_id{{ $grid_id }}'],
		contentType: false,
		processData: false,
		success: function(data) { 
		   
            toastNotify('View deleted.','success', false);
            if(data.default_id){
                layout_load(data.default_id)
            }else{
               setTimeout(function(){ location.reload(); }, 500);
            }
        }
        });
    }
    
    function layout_create(layout_id){
       
        var layout = {};
        layout.colState = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getColumnState();
        layout.groupState = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getColumnGroupState();
        
        layout.filterState = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterModel();
        
        var pivot = {};
        var pivotMode =window['grid_{{ $grid_id }}'].gridOptions.columnApi.isPivotMode();
        if(pivotMode){
            var pivot_mode = 1;
            pivot.colState =window['grid_{{ $grid_id }}'].gridOptions.columnApi.getPivotColumns();
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
                window['gridlayouts_{{ $grid_id }}'].items = JSON.parse(data.menu);
                window['gridlayouts_{{ $grid_id }}'].refresh();
    		    toastNotify('Layout saved.','success');
    		}
    	});
    }
    
    function layout_save{{ $grid_id }}(){
    
        var layout = {};
        layout.colState =window['grid_{{ $grid_id }}'].gridOptions.columnApi.getColumnState();
        layout.groupState =window['grid_{{ $grid_id }}'].gridOptions.columnApi.getColumnGroupState();
     
        layout.filterState =window['grid_{{ $grid_id }}'].gridOptions.api.getFilterModel();
        //  layout.searchtext = $("#searchtext{{ $grid_id }}").val();
        
        var pivot = {};
        var pivotMode =window['grid_{{ $grid_id }}'].gridOptions.columnApi.isPivotMode();
        if(pivotMode){
            var pivot_mode = 1;
          
        }else{
            var pivot_mode = 0;
        }
           
        var data = {layout : layout, layout_id: window['layout_id{{ $grid_id }}'], pivot: pivot, pivot_mode: pivot_mode,  query_string: {!! $query_string !!}};
       
        $.ajax({
            type: 'post',
            url: '{{ url($menu_route."/aggrid_layout_save") }}',
            data: data,
    		success: function(data) { 
    		   //////console.log(data);
    		    window['layout_id{{ $grid_id }}'] = data.layout_id;
    		    toastNotify(data.message,data.status);
    		}
    	});
    }
 
    function layout_load(layout_id, first_load = 0){
        
        if(first_load){
            
                var data = {!! json_encode($layout_init) !!};
    		    
                var state = JSON.parse(data.settings);
               
                ////console.log(state);
                if(data.auto_group_col_sort){
                    //////console.log(window['grid_{{ $grid_id }}'].gridOptions);
                    window['grid_{{ $grid_id }}'].gridOptions.autoGroupColumnDef.sort = data.auto_group_col_sort;    
                }
                if(data.columnDefs){
                   window['grid_{{ $grid_id }}'].gridOptions.api.setColumnDefs(data.columnDefs);
                }
                if(state){
                if(state.colState){
                ////////console.log(state.colState);{

                   window['grid_{{ $grid_id }}'].gridOptions.columnApi.applyColumnState({state:state.colState,applyOrder: true,});
                }
                if(state.groupState){
                   window['grid_{{ $grid_id }}'].gridOptions.columnApi.setColumnGroupState(state.groupState);
                }
               
                if(state.filterState){
                   window['grid_{{ $grid_id }}'].gridOptions.api.setFilterModel(state.filterState);
                }
                }
               // window['grid_{{ $grid_id }}'].gridOptions.columnApi.autoSizeAllColumns();
               
                
                setTimeout(function(){
                    window['grid_{{ $grid_id }}'].gridOptions.columnApi.autoSizeAllColumns();
                    
                    if(state && state.searchtext){
                       $("#searchtext{{ $grid_id }}").val(state.searchtext);
                    }
                },200);
                
               
                
                @if(session('role_level') == 'Admin')
                $('#layoutsbtn_delete{{ $grid_id }}').removeAttr('disabled');
                $('#layoutsbtn_duplicate{{ $grid_id }}').removeAttr('disabled');
                $('#layoutsbtn_save{{ $grid_id }}').removeAttr('disabled');
                
                @endif
                
                @if(!empty(request()->query_builder_id))
                setTimeout(function(){
                
                sidebarform('querybuilder', '/report_query/'+{{request()->query_builder_id}}, 'Query Builder', '','60%'); 
                },500);
                @endif
        }else{
        
        $("#searchtext{{ $grid_id }}").val('');
        filter_cleared{{ $grid_id }} = 0;
  
    	var ajax_data = {aggrid: 1, layout_id: layout_id, grid_reference: 'grid_{{ $grid_id }}', query_string: {!! $query_string !!} };
      ////console.log(ajax_data);
        $.ajax({
            type: 'post',
            url: '{{ url($menu_route."/aggrid_layout_data") }}',
            data: ajax_data,
            beforeSend: function(){
                $('#layoutsbtn_delete{{ $grid_id }}').attr('disabled','disabled');
                $('#layoutsbtn_duplicate{{ $grid_id }}').attr('disabled','disabled');
                $('#layoutsbtn_save{{ $grid_id }}').attr('disabled','disabled');
            },
    		success: function(data) { 
               //console.log('layout_load');
                //console.log(data);
    		    @if(session('role_level') == 'Admin')
                window['gridlayouts_{{ $grid_id }}'].items = JSON.parse(data.menu);
                window['gridlayouts_{{ $grid_id }}'].refresh();
                @endif
                var state = JSON.parse(data.settings);
               
                ////console.log(state);
                if(data.auto_group_col_sort){
                    //////console.log(window['grid_{{ $grid_id }}'].gridOptions);
                    window['grid_{{ $grid_id }}'].gridOptions.autoGroupColumnDef.sort = data.auto_group_col_sort;    
                }
                if(data.columnDefs){
                   window['grid_{{ $grid_id }}'].gridOptions.api.setColumnDefs(data.columnDefs);
                }
                if(state){
                if(data.pivot_mode == 1){
                 //  window['grid_{{ $grid_id }}'].gridOptions.columnApi.setPivotMode(true);
                   
                }else{
                  // window['grid_{{ $grid_id }}'].gridOptions.columnApi.setPivotMode(false);
                }
                
               
                
                
                if(state.colState){
                ////////console.log(state.colState);{

                   window['grid_{{ $grid_id }}'].gridOptions.columnApi.applyColumnState({state:state.colState,applyOrder: true,});
                }
                if(state.groupState){
                   window['grid_{{ $grid_id }}'].gridOptions.columnApi.setColumnGroupState(state.groupState);
                }
               
                if(state.filterState){
                   window['grid_{{ $grid_id }}'].gridOptions.api.setFilterModel(state.filterState);
                }
                }
                window['grid_{{ $grid_id }}'].gridOptions.columnApi.autoSizeAllColumns();
               
                
                setTimeout(function(){
                    
                    if(state && state.searchtext){
                       $("#searchtext{{ $grid_id }}").val(state.searchtext);
                    }
                },200);
                
               
                window['layout_id{{ $grid_id }}'] = layout_id;
                @if(session('role_level') == 'Admin')
                $('#layoutsbtn_delete{{ $grid_id }}').removeAttr('disabled');
                $('#layoutsbtn_duplicate{{ $grid_id }}').removeAttr('disabled');
                $('#layoutsbtn_save{{ $grid_id }}').removeAttr('disabled');
                
                @endif
                /*
                if(data.name){
                    $('#layout_title{{ $grid_id }}').text(data.name);
                }else{
                    $('#layout_title{{ $grid_id }}').text('');
                }
                */
                
              
                
                @if(!empty(request()->query_builder_id))
                setTimeout(function(){
                
                sidebarform('querybuilder', '/report_query/'+{{request()->query_builder_id}}, 'Query Builder', '','60%'); 
                },500);
                @endif
    		}
    	});
        }
    }
    


document.getElementById('filterclear{{ $grid_id }}').addEventListener('click', function() {
   
   
    if(filter_cleared{{ $grid_id }} == 0){
        $("#searchtext{{ $grid_id }}").val('');
        // save temp state
        var temp_state = {};
        temp_state.colState = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getColumnState();
        temp_state.groupState = window['grid_{{ $grid_id }}'].gridOptions.columnApi.getColumnGroupState();
       
        temp_state.filterState = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterModel();
      
        window['gridstate_{{ $grid_id }}'] = temp_state;
        
        window['grid_{{ $grid_id }}'].gridOptions.api.setFilterModel(null);
        window['grid_{{ $grid_id }}'].gridOptions.api.onFilterChanged();
        filter_cleared{{ $grid_id }} = 1;
        searching_detail = false;
        searching_detail_ids = [];
        $("#filterclear{{ $grid_id }}").text('Restore Filters');
        
        @if($soft_delete)
            $.get( "filter_soft_delete/{{$module_id}}/1", function( data ) {
            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            });
        @endif
    }else{
    
        // restore temp state
        if( window['gridstate_{{ $grid_id }}']){
       
            if(window['gridstate_{{ $grid_id }}'].colState){ 
            window['grid_{{ $grid_id }}'].gridOptions.columnApi.applyColumnState({state:window['gridstate_{{ $grid_id }}'].colState,applyOrder: true,});
            }
            if(window['gridstate_{{ $grid_id }}'].groupState){
            window['grid_{{ $grid_id }}'].gridOptions.columnApi.setColumnGroupState(window['gridstate_{{ $grid_id }}'].groupState);
            }
          
           
            
            if(window['gridstate_{{ $grid_id }}'].filterState){
            window['grid_{{ $grid_id }}'].gridOptions.api.setFilterModel(window['gridstate_{{ $grid_id }}'].filterState);
            }
            
            window['grid_{{ $grid_id }}'].gridOptions.api.onFilterChanged();
            filter_cleared{{ $grid_id }} = 0;
            searching_detail = false;
            searching_detail_ids = [];
            
        }
        @if($soft_delete)
            $.get( "filter_soft_delete/{{$module_id}}/0", function( data ) {
            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            });
        @endif
        $("#filterclear{{ $grid_id }}").text('Clear Filters');
        
    }
    
     if($("#searchtext{{ $grid_id }}").val() == '' || $("#searchtext{{ $grid_id }}").val() == null){
        window['grid_{{ $grid_id }}'].gridOptions.api.setQuickFilter(null);
        @if($serverside_model)
        window['grid_{{ $grid_id }}'].gridOptions.refresh();
        @endif
    }else{
        window['grid_{{ $grid_id }}'].gridOptions.api.setQuickFilter($("#searchtext{{ $grid_id }}").val());
        @if($serverside_model)
        window['grid_{{ $grid_id }}'].gridOptions.refresh();
        @endif
    }
    
    @if($serverside_model)
    window['grid_{{ $grid_id }}'].gridOptions.refresh();
    @endif
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
       $("#"+id).html('<span  class="e-btn-icon fa fa-check"></span> Approve'); 
       if(title == ''){
       title = 'Approve';
       }
    }
    
    if(icon == 'approve_manager'){
       $("#"+id).html('<span  class="e-btn-icon fa fa-check-double"></span> Approve Manager'); 
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
       $("#"+id).html('<span  class="e-btn-icon fa fa-undo"></span>'); 
       if(title == ''){
       title = 'Reverse';
       }
    }
    
    $("#"+id).attr('title',title); 
}
</script>
@endsection
@section('css')

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
@if($master_detail)
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
    font-weight: 500;
}
.name-field{
    background-color:#eaeaea;
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



#gridtoolbar{{ $grid_id }} .e-input-group.e-ddl{
    border-radius: 4px;
}


#gridcontainer{{ $grid_id }}{
display: flex;
flex-direction: column;
height: 100%;
}





.e-btn .e-btn-icon, .e-css.e-btn .e-btn-icon {
    display: inline-block;
    font-size: 12px;
    margin-top: 2px;
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

#extrabtnsdropdown{{ $grid_id }} button span{
margin-right: 5px;
}
#accountinfo{{ $grid_id }}:disabled{
display:none;
}
#pbxinfo{{ $grid_id }}:disabled{
display:none;
}
#resellerinfo{{ $grid_id }}:disabled{
display:none;
}
#communicationsinfo{{ $grid_id }}:disabled{
display:none;
}
#servicesinfo{{ $grid_id }}:disabled{
display:none;
}
#moduleinfo{{ $grid_id }}:disabled{
display:none;
}

.gridheader:visible{
    min-height:60px;    
    
}
.gridheader #gridtoolbar{{ $grid_id }}{
    min-height:60px;    
}
.gridheader .e-toolbar .e-toolbar-items .e-toolbar-item,.gridheader .e-toolbar .e-toolbar-items .e-toolbar-item>*{
    height:60px !important;   
  
    background: #ffffff;

}
.gridheader .e-toolbar ,.gridheader .e-toolbar .e-toolbar-items{
    background: #ffffff;
}
.ag-side-buttons {
    padding-top: 0px !important;
}

.ag-side-buttons > .ag-side-button:first-child .ag-side-button-button {
    border-top: 0px;
}
</style>
<style>
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
.ag-pivot-mode-panel{
    display:none !important;
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
.toolbar_grid_buttons{
    height:100%;    
}

.la, .las {
    font-family: "Line Awesome Free" !important;  
    font-weight: 900;
}
.layout_btns button i{
font-size: 18px;
}
.layout_btns ul i{
margin-right: 6px;
font-size: 16px;
}
.gridheader i{
    font-size:20px;    
}
.toolbar_grid_buttons .dropdown-toggle:after{
    display:none;    
}
</style>
@endsection
