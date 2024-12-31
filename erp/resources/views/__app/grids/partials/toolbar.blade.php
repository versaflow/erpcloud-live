@php 
    if(!empty($module_footer_cards)){
        $module_card_lines = collect($module_footer_cards)->sortBy('footer_line')->groupBy('footer_line');
    }
@endphp
@section('layouts_toolbar')
@parent
  @if(!empty($module_context_builder_menu) && count($module_context_builder_menu) > 0)
    <ul id="context_builder{{ $grid_id }}" class="m-0"></ul>
    @endif
<!-- toolbar templates START -->
<div id="toolbar_template_external_links{{ $grid_id }}" class="d-flex @if(!empty($hide_toolbar_items)) d-none @endif">


@if(!empty($related_items_menu_menu) && count($related_items_menu_menu) > 0)  
<ul class="e-btn-group" id="related_items_menu_menu{{ $grid_id }}"></ul>
@endif


</div>
@if(session('role_level') == 'Admin')
<div id="toolbar_template_filters{{ $grid_id }}" class="@if(!empty($hide_toolbar_items)) d-none @endif">
    @if(!empty($layout_field_filters))
   @foreach($layout_field_filters as $layout_field_filter)
    <input type="text" class="form-control" id="layout_filter_{{$layout_field_filter->field}}{{ $grid_id }}" />
   @endforeach
   @endif
</div>
@endif

<div id="toolbar_template_branding{{ $grid_id }}" class="@if(!empty($hide_toolbar_items)) d-none @endif">
 
    <h6 class="font-weight-bolder mb-0" id="module_name{{ $grid_id }}" style="user-select: text;"> @if(!empty($module_tooltip))<i id="module_tooltip{{ $grid_id }}" class="fas fa-info-circle" style="font-size: 14px;"></i>@endif {{$module_name}} - <span id="layout_name{{$grid_id}}"></span></h6>

</div>

@if(!empty($workspace_filter_datasource) && count($workspace_filter_datasource) > 0)
<div id="toolbar_template_workspace_filter{{ $grid_id }}" class="@if(!empty($hide_toolbar_items)) d-none @endif">
<div id="workspace_filter_{{$grid_id}}" class="toolbar_role_filter"></div>
</div>
@endif

<div id="toolbar_template_grid_btns{{ $grid_id }}" class="@if(!empty($hide_toolbar_items)) d-none @endif">
   
    <div class="toolbar_grid_buttons align-items-center d-flex" id="gridactions{{ $grid_id }}">  


       
      <div class="e-btn-group" id="grid_btns{{ $grid_id }}">
      @if($communications_panel && !str_contains($grid_id,'detail'))

  
 
        @endif
        
        @if(session('role_level')=='Admin')
        <button title="Toggle Filters" id="clear_filters{{ $grid_id }}" class="e-btn" onClick="clear_filters{{$grid_id}}()" ><span  class="e-btn-icon e-icons e-filter-clear"></span></button>
        @endif
       <button title="Toggle Kanban" id="{{ $grid_id }}ToggleKanban" class="e-btn {{ $grid_id }}ToggleKanban d-none"><span  class="e-btn-icon fas fa-exchange-alt"></span></button> 
       <button title="Refresh Data" id="{{ $grid_id }}Refresh" class="e-btn {{ $grid_id }}Refresh"><span  class="e-btn-icon fa fa-retweet"></span></button> 

       
      
       
        @if($access['is_add'])
            <button title="Create Record" id="{{ $grid_id }}Add" class="e-btn" ><span  class="e-btn-icon fa fa-plus"></span></button>
        @endif
          
        @if($access['is_add'] && !in_array($db_table,['call_records_inbound','call_records_outbound']))
            <button title="Duplicate Record" id="{{ $grid_id }}Duplicate" class="e-btn" ><span  class="e-btn-icon fa fa-copy"></span></button>
        @endif
    
        @if($access['is_edit'])
            <button title="Edit Record" id="{{ $grid_id }}Edit" class="e-btn" ><span  class="e-btn-icon fas fa-pen"></span></button>
        @endif
        
        @if($access['is_delete'])
             @if(($db_table == 'crm_accounts' || $db_table == 'sub_services'))
            <button title="Cancel" id="{{ $grid_id }}Delete" class="e-btn" ><span  class="e-btn-icon fa fa-times"></span></button>
            @else
            <button title="Delete Record" id="{{ $grid_id }}Delete" class="e-btn" ><span  class="e-btn-icon fa fa-trash"></span></button>
            @endif
        @endif
       
        @if($db_table == 'crm_documents' || $db_table == 'crm_supplier_documents')
             <button title="Approve" id="{{ $grid_id }}Approve" class="e-btn" ><span  class="e-btn-icon fa fa-check"></span></button>
        @endif
           
        
        <!--<div class="dropdown">
        <button class="e-btn dropdown-toggle" type="button" id="linkedrecords{{ $grid_id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
        <span class="e-btn-icon fa fa-caret-down"></span>
        </button>   
        <ul class="dropdown-menu" aria-labelledby="linkedrecords{{ $grid_id }}" id="linkedrecordsdropdown{{ $grid_id }}">
        
        </ul> </div>-->
      
    </div>
  
        <div class="searchinputgroup searchgroup{{ $grid_id }}">
        <input  type="text"  id="searchtext{{ $grid_id }}" class="gridsearch k-textbox"/>
        <button class="e-btn d-none" id="search{{ $grid_id }}" title="Search"><i class="search-icon fas fa-search" ></i></button>
        </div>
        
         @if($access['is_view'] && (in_array($db_table,['crm_documents','crm_supplier_documents','crm_supplier_import_documents'])))
            <button title="View Record" id="{{ $grid_id }}View" class="e-btn ms-2" > View Document</button>
        @endif
 
       
 
    </div>
</div>
 
@if(!empty($pbx_menu_menu) && count($pbx_menu_menu) > 0)  
<!--<div id="toolbar_template_pbx_menu{{ $grid_id }}" >
<ul class="e-btn e-btn-group" id="pbx_menu_menu{{ $grid_id }}"></ul>
</div>-->
@endif

<div id="toolbar_template_grid_action_btns{{ $grid_id }}" class="d-flex @if(!empty($hide_toolbar_items)) d-none @endif">
    
    @if(!empty($status_dropdown) && !empty($status_dropdown['status_key']))  
    <div id="status_dropdown{{ $grid_id }}" class="status_dropdown  me-2"></div>
    @endif
    
    @if($master_detail && !empty($detail_grid['status_dropdown']))  
    <div id="status_dropdowndetail{{ $grid_id }}" class="status_dropdown  me-2"></div>
    @endif
    
    @if(!empty($adminbtns_menu) && count($adminbtns_menu) > 0)  
    <ul class="e-btn-group" id="adminbtns_menu{{ $grid_id }}"></ul>
    @endif
    
    @if(!empty($grid_menu_menu) && count($grid_menu_menu) > 0)  
    <ul class="e-btn-group" id="grid_menu_menu{{ $grid_id }}"></ul>
    @endif
  
    @if($master_detail && !empty($detail_grid['grid_menu_menu']) && count($detail_grid['grid_menu_menu']) > 0)  
    <ul class="e-btn-group d-none" id="grid_menu_menudetail{{ $grid_id }}"></ul>
    @endif
</div>




<!-- toolbar templates END -->

<div id="gridheadertoolbar{{ $grid_id }}" class="grid-toolbar bg-task-inactive" @if(str_contains($grid_id,'detail')) style="display:none " @endif></div>

@endsection

@push('page-scripts')

<script data-turbo-track="reload" id="toolbarscripts">
isGridBusy{{$grid_id}} = false;
@if(!empty($workspace_filter_datasource) && count($workspace_filter_datasource) > 0)
@if(!empty($workspace_filter_selected))
    window['workspace_filter_current{{$grid_id}}'] = {{$workspace_filter_selected}};
    @else
    window['workspace_filter_current{{$grid_id}}'] = 0;
    @endif
    window['workspace_filter_{{ $grid_id }}'] = new ej.dropdowns.DropDownList({
    	dataSource: {!! json_encode($workspace_filter_datasource) !!},
    	fields: {text: 'name', value: 'id'},
        placeholder: '{{$workspace_filter_placeholder}}',
        width: '150px',
        popupWidth: 'auto',
        height: '35px',
        value: window['workspace_filter_current{{$grid_id}}'],
        //cssClass: 'ms-2',
        //Set true to show header title
        triggerValueChange: function (args){
            //if(args){
            const foundItem = this.dataSource.find(item => item.id === this.value);
            
            var filterInstance = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterInstance('join_role_id'); 
            // console.log(filterInstance);
            // console.log(args);
            // console.log(foundItem);
            // console.log(this.value);
            //console.log(args.itemData.full_name);
            
            // Set the filter model
            filterInstance.setModel({
            filterType: 'set',
            values: [foundItem.name],
            });
            
            window['grid_{{ $grid_id }}'].gridOptions.api.onFilterChanged();
            //}
        },
        select: function(args){
        
            if(args.isInteracted && args.itemData && args.itemData.id){
                
                var update_val = args.itemData.id;
              
                window['workspace_filter_current{{$grid_id}}'] = update_val;
                set_workspace_filter{{$grid_id}}(args);
                
            }
        },
        created: function(args){
            setTimeout(function(){
                window['workspace_filter_{{ $grid_id }}'].value = window['workspace_filter_current{{$grid_id}}'];
                window['workspace_filter_{{ $grid_id }}'].triggerValueChange();
            },500);
        },
    
    }, '#workspace_filter_{{ $grid_id }}');

    function set_workspace_filter{{$grid_id}}(args){
            console.log('set_workspace_filter');
                
            var update_val = args.itemData.id;
       // if(!isGridBusy{{$grid_id}}){   
            try{
            
                var filterInstance = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterInstance('join_role_id'); 
                // console.log(filterInstance);
                // console.log(args.itemData.full_name);
                if(args.itemData.name == 'All' || args.itemData.name == 'None'){
                    window['grid_{{ $grid_id }}'].gridOptions.api.destroyFilter('join_role_id');
                }else{
                    // Set the filter model
                    filterInstance.setModel({
                        filterType: 'set',
                        values: [args.itemData.name],
                    });
                }
                
                window['grid_{{ $grid_id }}'].gridOptions.api.onFilterChanged();
                
                //restore expanded state
                
                
            }catch(e){
                console.log(e);
            } 
            
            window['workspace_filter_current{{$grid_id}}'] = update_val;
            
            
            
            
           // $.when(gridAjax('update_workspace_role_filter/{{$module_id}}/'+update_val)).then(function(){
                setTimeout(function(){
                //refresh_main_menu_datasource(update_val);
                $.get('get_sidebar_module_guides/{{$module_id}}', function(data) {
                 
                    guides_accordion_refresh{{$grid_id}}();
                });
                refresh_module_footer_cards{{$grid_id}}();
               
                
                },500)
               
           // });
       // }else{
      //      setTimeout(() => set_workspace_filter{{$grid_id}}(update_val), 200); 
      //  }
    }

@endif
        
@if(!empty($status_dropdown) && !empty($status_dropdown['status_key']))

window['status_dropdown{{ $grid_id }}'] = new ej.dropdowns.DropDownList({
	dataSource: {!! json_encode($status_dropdown["options"]) !!},
    placeholder: '{{$status_dropdown["label"]}}',
    cssClass: 'status_dropdown  status_dropdown{{$grid_id}} me-2',
    popupWidth: 'auto',
    width:'120px',
    //Set true to show header title
    change: function(args){
      
       
        if(args.isInteracted && args.value){
            var update_val = args.value;
            if(args.itemData.value){
            var update_val = args.itemData.value;
            }
            // ajax update status
            if(window['selectedrow_{{ $grid_id }}'] && window['selectedrow_{{ $grid_id }}'].rowId){
              
                gridAjax('status_dropdown_update/{{$module_id}}/{{$status_dropdown["status_key"]}}/'+update_val+'/'+window['selectedrow_{{ $grid_id }}'].rowId);
            }else{
                args.cancel = true;
                this.value = null;
                toastNotify('Please select a record');
            }
        }
    }
}, '#status_dropdown{{ $grid_id }}');
@endif

@if($master_detail && !empty($detail_grid["status_dropdown"])) 

window['status_dropdowndetail{{ $grid_id }}'] = new ej.dropdowns.DropDownList({
	dataSource: {!! json_encode($detail_grid["status_dropdown"]["options"]) !!},
    placeholder: '{{$detail_grid["status_dropdown"]["label"]}}',
    cssClass: 'status_dropdown  status_dropdowndetail{{$grid_id}} me-2',
    popupWidth: 'auto',
    width:'120px',
    //Set true to show header title
    change: function(args){
        
        if(args.isInteracted && args.itemData && args.value){
             var update_val = args.value;
            if(args.itemData.value){
            var update_val = args.itemData.value;
            }
            // ajax update status
            if(window['selectedrow_detail{{ $grid_id }}'] && window['selectedrow_detail{{ $grid_id }}'].rowId){
                gridAjax('status_dropdown_update/{{$detail_grid["module_id"]}}/{{$detail_grid["status_dropdown"]["status_key"]}}/'+update_val+'/'+window['selectedrow_detail{{ $grid_id }}'].rowId);
            }else{
                args.cancel = true;
                this.value = null;
                toastNotify('Please select a record');
            }
        }
    }
}, '#status_dropdowndetail{{ $grid_id }}');
@endif
$(document).ready(function(){
    @if($has_sort)
    if(isMobile()){
        $("#mobileSortToggle{{ $grid_id }}").addClass('btn btn-xs btn-light');
    }
    @endif
})

$(document).on('click','#mobileSortToggle{{ $grid_id }}',function(){

    if(!isMobile()){
        return false;    
    }

    if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){ 
        detailSortToggle{{ $grid_id }}();
    }else{
        if(window['grid_{{ $grid_id }}'].gridOptions.suppressRowDrag){ 
            window['grid_{{ $grid_id }}'].gridOptions.suppressRowDrag = false;
            window['grid_{{ $grid_id }}'].gridOptions.rowDragEntireRow = true;
            alert('Sort enabled');
        }else{
            window['grid_{{ $grid_id }}'].gridOptions.suppressRowDrag = true;
            window['grid_{{ $grid_id }}'].gridOptions.rowDragEntireRow = false;
            alert('Sort disabled');
        }
        window['grid_{{ $grid_id }}'].gridOptions.api.redrawRows();
    }
})


@if($master_detail)

class ClickableStatusBarComponent{{ $master_grid_id }} {
  params;
  eGui;
  buttonListener1;
  buttonListener2;

  init(params) {
    this.params = params;

    this.eGui = document.createElement('div'); // Create a container div

    // Create the first button
    const button1 = document.createElement('button');
    button1.innerHTML = 'Maximize';
    button1.classList.add('e-btn'); // Add the "e-btn" class
    button1.classList.add('btn-sm'); // Add the "e-btn" class
    button1.style.margin = '5px';
    button1.style.padding = '0 5px';

    // Create the second button
    const button2 = document.createElement('button');
    button2.innerHTML = 'Open';
    button2.classList.add('e-btn'); // Add the "e-btn" class
    button2.classList.add('btn-sm'); // Add the "e-btn" class
    button2.style.margin = '5px';
    button2.style.padding = '0 5px';

    this.buttonListener1 = this.onButton1Clicked.bind(this);
    button1.addEventListener('click', this.buttonListener1);

    this.buttonListener2 = this.onButton2Clicked.bind(this);
    button2.addEventListener('click', this.buttonListener2);

    // Append the buttons to the container
    this.eGui.appendChild(button1);
    this.eGui.appendChild(button2);
  }

  getGui() {
    return this.eGui;
  }

  destroy() {
    this.eGui.querySelector('button:first-child').removeEventListener('click', this.buttonListener1);
    this.eGui.querySelector('button:last-child').removeEventListener('click', this.buttonListener2);
  }

  onButton1Clicked() {
   if (window['grid_{{ $master_grid_id }}'].gridOptions.isDetailGridMaximized) {
       console.log('restore');
      // If the detail grid is maximized, restore it to its original height
      window['grid_{{ $master_grid_id }}'].gridOptions.detailRowAutoHeight = null;
      window['grid_{{ $master_grid_id }}'].gridOptions.detailRowHeight = 280; // Change 200 to your desired height
      window['grid_{{ $master_grid_id }}'].gridOptions.api.redrawRows();
      window['grid_{{ $master_grid_id }}'].gridOptions.api.resetRowHeights();
    } else {
       console.log('maximizes');
      // Maximize the detail grid to full height
     
      window['grid_{{ $master_grid_id }}'].gridOptions.detailRowAutoHeight = true;
      window['grid_{{ $master_grid_id }}'].gridOptions.api.resetRowHeights();
      window['grid_{{ $master_grid_id }}'].gridOptions.api.redrawRows();
    }

    // Toggle the state
    window['grid_{{ $master_grid_id }}'].gridOptions.isDetailGridMaximized = !window['grid_{{ $master_grid_id }}'].gridOptions.isDetailGridMaximized;
  }

  onButton2Clicked() {
      ////console.log('{$detail_menu_route}}');
    window.open('{{$detail_menu_route}}','_blank');
  }
}
@endif




class LastRefreshStatusBarComponent{{ $master_grid_id }} {
  params;
  eGui;

  init(params) {
    this.params = params;

    this.eGui = document.createElement('div'); // Create a container div
    this.eGui.classList.add('ag-status-name-value');
    this.eGui.classList.add('ag-status-panel');
    this.eGui.classList.add('d-block');
    var refresh_time_html = '';
    // Create the first button
    var timestamp = getRefreshTime();
    
    @if($incentive_footer)
    var refresh_time_html = '<span id="incentives_footer">{!! ($incentive_footer == "Superadmin") ? "" : $incentive_footer !!}</span>';
    @else
    
    @if($has_sort)
    var refresh_time_html = '<b>Sorted: </b>Manual | <b>Last refresh: </b>&nbsp;<span id="last_refresh_time{{$grid_id}}">'+timestamp+'</span>';
    @else
    var refresh_time_html = '<b>Last refresh: </b>&nbsp;<span id="last_refresh_time{{$grid_id}}">'+timestamp+'</span>';
    @endif
    @endif

    
    @if(!empty($module_footer_cards))
        var footer_cards_html = '<div id="footer_cards_html{{$grid_id}}">';
        @foreach($module_card_lines as $line => $module_footer_cards)
            @foreach($module_footer_cards as $key => $module_footer_card)
            footer_cards_html += '<b>{!!str_replace("'","",$module_footer_card->title)!!}: </b>&nbsp;<span>{!!str_replace("'","",$module_footer_card->result)!!}</span>';
            @if ($key < count($module_footer_cards) - 1)
                footer_cards_html += '&nbsp;&nbsp;';
            @endif
            @endforeach
            
            //footer_cards_html += '<br>';
        @endforeach
        footer_cards_html += '</div>';
        this.eGui.innerHTML = '<div>'+refresh_time_html+'</div>'+footer_cards_html;
    @else
    
        this.eGui.innerHTML = '<div>'+refresh_time_html+'</div>';
    @endif
    
  }

  refresh() {
    var timestamp = getRefreshTime();
    $("#last_refresh_time{{$grid_id}}").text(timestamp);
    return this.eGui;
  }
  getGui() {
    return this.eGui;
  }

  destroy() {
    
  }

 
}


@if(!empty($module_footer_cards))
class FooterCardsStatusBarComponent{{ $master_grid_id }} {
  params;
  eGui;

  init(params) {
    this.params = params;

    this.eGui = document.createElement('div'); // Create a container div
    this.eGui.classList.add('ag-status-name-value');
    this.eGui.classList.add('ag-status-panel');
    this.eGui.setAttribute("id", "footer_cards_html{{$grid_id}}");
  
    // Create the first button
    var footer_cards_html = '';
    @foreach($module_footer_cards as $key => $module_footer_card)
    footer_cards_html += '<b>{!!$module_footer_card->title!!}: </b>&nbsp;<span>{!!$module_footer_card->result!!}</span>';
    @if ($key < count($module_footer_cards) - 1)
        footer_cards_html += '&nbsp;|&nbsp;';
    @endif
    @endforeach
  
    this.eGui.innerHTML = footer_cards_html;
    
  }
  
  getGui() {
    return this.eGui;
  }
  
 
  

  destroy() {
    
  }

 
}
    
@endif

function refresh_module_footer_cards{{$grid_id}}(){
    console.log('refresh_module_footer_cards');
    var footer_card_url = 'get_module_footer_cards/{{$module_id}}';
    @if(!empty($workspace_filter_datasource) && count($workspace_filter_datasource) > 0)
    if(window['workspace_filter_current{{$grid_id}}']){
        footer_card_url += '/'+window['workspace_filter_current{{$grid_id}}'];
    }
    @endif
    $.ajax({
        type: 'get',
        url: footer_card_url,
        success: function (data){
            console.log(footer_card_url);
            console.log(data);
            if(data.html){
                $("#footer_cards_html{{$grid_id}}").html(data.html);
            }
            @if($module_id == 2018)
            if(data.task_in_progress == 0){
                $("#gridheadertoolbar{{$grid_id}}").addClass('bg-task-inactive');
                $("#gridheadertoolbar{{$grid_id}}").removeClass('bg-task-active');
                $(".ag-status-bar").addClass('bg-task-inactive');
                $(".ag-status-bar").removeClass('bg-task-active');
            }else{
                
                $("#gridheadertoolbar{{$grid_id}}").addClass('bg-task-active');
                $("#gridheadertoolbar{{$grid_id}}").removeClass('bg-task-inactive');
                $(".ag-status-bar").addClass('bg-task-active');
                $(".ag-status-bar").removeClass('bg-task-inactive');
            }
        
            @if($incentive_footer)
            if(data.incentive_footer_html){
            $("#incentives_footer").html(data.incentive_footer_html);
            }else{
            $("#incentives_footer").html('');
            }
            @endif
            @endif
        }
        
    })
}

function getRefreshTime() {
  const now = new Date();
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');
  
  const formattedTime = `${hours}:${minutes}:${seconds}`;
  
  return formattedTime;
}

function progressCellRenderer(params){
    if(params.value == 0){
        var html = ` <div class="w-full bg-gray-200 rounded-full dark:bg-gray-700 inline-block align-middle">
        <div class="bg-blue-600 text-xs font-medium text-blue-100 text-center p-0.5 leading-none rounded-full" style="width: `+params.value+`%"></div>
        </div>`;
    }else{
        var html = ` <div class="w-full bg-gray-200 rounded-full dark:bg-gray-700 inline-block align-middle">
        <div class="bg-blue-600 text-xs font-medium text-blue-100 text-center p-0.5 leading-none rounded-full" style="width: `+params.value+`%"> `+params.value+`%</div>
        </div>`;
    }
    
    // ////console.log(params.value);
    return html;
}

@if($row_tooltips)

  

    if(typeof window['rowtooltip{{$module_id}}'] === 'undefined'){
    class rowtooltip{{$module_id}} {
      eGui;
      init(params) {
        const eGui = (this.eGui = document.createElement('div'));
        var row = params.api.getDisplayedRowAtIndex(params.rowIndex);
        if(row && row.data){
        const data = row.data;
     
        eGui.classList.add('custom-tooltip');
        //@ts-ignore
        eGui.style['background-color'] = '#fff';
        eGui.innerHTML = '';
        @foreach($columnDefs as $col)
        @if($col['row_tooltip'])
        @if(str_contains($col['dbtype'],'textarea'))
        if(data.{{$col['field']}}){
        eGui.innerHTML += `<p>
        <b>{{$col['headerName']}}: </b><br>
         <span>${data.{{$col['field']}}.replace(/\n/g, "<br>")}</span>
        </p>`;
        }
        @else
        if(data.{{$col['field']}}){
        eGui.innerHTML += `<p>
            <b>{{$col['headerName']}}: </b><span>${data.{{$col['field']}}}</span>
        </p>`;
        }
        @endif
        @endif
        @endforeach
        }
      }
    
      getGui() {
        return this.eGui;
      }
    }
    window['rowtooltip{{$module_id}}']  = rowtooltip{{$module_id}};
    }
@endif
@if(!empty($detail_grid['row_tooltips']))
 if(typeof window['rowtooltipdetail{{$detail_grid["module_id"]}}'] === 'undefined'){
    class rowtooltipdetail{{$detail_grid['module_id']}} {
      eGui;
      init(params) {
        const eGui = (this.eGui = document.createElement('div'));
        var row = params.api.getDisplayedRowAtIndex(params.rowIndex);
        if(row && row.data){
        const data = row.data;
        //console.log(params);
        //console.log(data);
        eGui.classList.add('custom-tooltip');
        //@ts-ignore
        eGui.style['background-color'] = '#fff';
        eGui.innerHTML = '';
        @foreach($detail_col_defs as $col)
        @if($col['row_tooltip'])
        @if(str_contains($col['dbtype'],'textarea'))
        if(data.{{$col['field']}}){
        eGui.innerHTML += `<p>
        <b>{{$col['headerName']}}: </b><br>
         <span>${data.{{$col['field']}}.replace(/\n/g, "<br>")}</span>
        </p>`;
        }
        @else
        if(data.{{$col['field']}}){
        eGui.innerHTML += `<p>
            <b>{{$col['headerName']}}: </b><span>${data.{{$col['field']}}}</span>
        </p>`;
        }
        @endif
        @endif
        @endforeach
      }
      }
    
      getGui() {
        return this.eGui;
      }
    }
    window['rowtooltipdetail{{$detail_grid["module_id"]}}'] = rowtooltipdetail{{$detail_grid['module_id']}};
 }
@endif

// filter clear
function clear_filters{{$grid_id}}() {
   @if(is_superadmin())
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
            } else {
                // restore temp state
                if( window['gridstate_{{ $grid_id }}']) {
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
                    @if(!empty($workspace_filter_datasource) && count($workspace_filter_datasource) > 0)
                        setTimeout(function(){
                        window['workspace_filter_{{ $grid_id }}'].triggerValueChange();
                        },200)
                    @endif
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
    @endif
}

/** SYNCFUSION COMPONENTS **/

searchtext{{ $grid_id }} = new ej.inputs.TextBox({
	showClearButton: true,
	width:160,
	placeholder: 'Grid search',
	change: function(e){
	    
	  
        var search_val = searchtext{{ $grid_id }}.value;
        if(search_val > ''){
            search_val = search_val.trim();
        }
        if($("#grid_{{$grid_id}}").hasClass('d-none')){
        //kanban search
          //  searchKanban(search_val);
        }else{
        
            if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
              
               var grid_api = detail_grid_api.api;
              
            }else{
                
                if(window['grid_{{ $grid_id }}'].gridOptions && window['grid_{{ $grid_id }}'].gridOptions.api){
                    var grid_api = window['grid_{{ $grid_id }}'].gridOptions.api;    
                }else if(window['grid_{{ $grid_id }}'].api){
                    var grid_api = window['grid_{{ $grid_id }}'].api;   
                }
            }
       
            if(search_val == 'Yes'){
                search_val = '1';    
            }
            if(search_val == 'No'){
                search_val = '0';    
            }
           
                //console.log(search_val);
            if(search_val == '' || search_val == null){
                if(searching_detail){
                    searching_detail = false;
                    searching_detail_ids = [];
                    window['grid_{{ $grid_id }}'].gridOptions.api.onFilterChanged();
                    if(detail_grid_api && detail_grid_api.api){
                        detail_grid_api.api.setQuickFilter(' ');
                    }
                }
                searchtext{{ $grid_id }}.value = '';
                grid_api.setQuickFilter(' ');
                
                @if($serverside_model)
                if(window['grid_{{ $grid_id }}'].gridOptions){
                    
    
                window['grid_{{ $grid_id }}'].gridOptions.refresh();
                }
                @endif
            }else{
                //console.log(111);
                
                @if($master_detail)
                    /*
                    if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
                        grid_api.setQuickFilter(search_val);
                        @if($serverside_model)
                        
                        if(window['grid_{{ $grid_id }}'].gridOptions){
                        window['grid_{{ $grid_id }}'].gridOptions.refresh();
                        }
                        @endif
                    }else{ */
                     //console.log(222);
                        search_detail{{ $grid_id }}();
                        setTimeout(function(){
                            if(detail_grid_api && detail_grid_api.api){
                                detail_grid_api.api.setQuickFilter(search_val);
                            }
                            
                        },1000);
                     // }
                @else
                    grid_api.setQuickFilter(search_val);
                    @if($serverside_model)
                    
                    if(window['grid_{{ $grid_id }}'].gridOptions){
                    window['grid_{{ $grid_id }}'].gridOptions.refresh();
                    }
                    @endif
                @endif
               
            }
        }
	},
},'#searchtext{{ $grid_id }}');

$(document).off('click', '.searchgroup{{ $grid_id }} .e-clear-icon').on('click', '.searchgroup{{ $grid_id }} .e-clear-icon', function(e) {
   
        
   e.preventDefault();
   searchtext{{ $grid_id }}.value = '';
   searchtext{{ $grid_id }}.dataBind();
});


$(document).off('click', '.search{{ $grid_id }} .e-clear-icon').on('click', '.search{{ $grid_id }} .e-clear-icon', function(e) {
    searchgrid{{$grid_id}}();
});

function searchgrid{{$grid_id}}(){
    if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
        
       var grid_api = detail_grid_api.api;
    }else{
        
        if(window['grid_{{ $grid_id }}'].gridOptions && window['grid_{{ $grid_id }}'].gridOptions.api){
            var grid_api = window['grid_{{ $grid_id }}'].gridOptions.api;    
        }else if(window['grid_{{ $grid_id }}'].api){
            var grid_api = window['grid_{{ $grid_id }}'].api;   
        }
    }
	 
    
    if(searchtext{{ $grid_id }}.value == '' || searchtext{{ $grid_id }}.value == null){
        grid_api.setQuickFilter(null);
        @if($serverside_model)
            if(window['grid_{{ $grid_id }}'].gridOptions){
            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            }
        @endif
    }else{
        
        var search_val = searchtext{{ $grid_id }}.value;
        if(search_val == 'Yes'){
            search_val = '1';    
        }
        if(search_val == 'No'){
            search_val = '0';    
        }
        grid_api.setQuickFilter(search_val);
        @if($serverside_model)  
            if(window['grid_{{ $grid_id }}'].gridOptions){
            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            }
        @endif
    }
}




    
    

var contextviewid{{$grid_id}} = false;
var contextviewtype{{$grid_id}} = 'default';
function create_layouts_context{{$grid_id}}(){
 $('body').append('<ul id="contextlayouts{{ $grid_id }}" class="m-0"></ul>');
        var layout_items = [
            @if($module_id!=526)
            {
                id: "layoutsbtn_manage{{ $grid_id }}",
                text: "Sort",
                iconCss: "fas fa-list",
            },
            @endif
            {
                id: "layoutsbtn_save{{ $grid_id }}",
                text: "Save",
                iconCss: "fa fa-save",
            },
            {
                id: "layoutsbtn_duplicate{{ $grid_id }}",
                text: "Duplicate",
                iconCss: "fa fa-plus",
            },
            {
                id: "layoutsbtn_edit{{ $grid_id }}",
                text: "Edit",
                iconCss: "fas fa-pen",
            },
            
            {
                id: "layoutsbtn_create{{ $grid_id }}",
                text: "Save as new",
                iconCss: "fa fa-copy",
            },
            
            {
                id: "layoutsbtn_delete{{ $grid_id }}",
                text: "Delete",
                iconCss: "fa fa-trash",
            },
            
            {
                id: "layoutsbtn_globaldefault{{ $grid_id }}",
                text: "Set as default",
                iconCss: "fa fa-star",
            },
            
            {
                id: "layout_tracking_enable{{ $grid_id }}",
                text: "Enable Tracking",
                iconCss: "fas fa-toggle-on",
            },
            {
                id: "layout_tracking_disable{{ $grid_id }}",
                text: "Disable Tracking",
                iconCss: "fas fa-toggle-off",
            },
            {
                id: "chart_remove{{ $grid_id }}",
                text: "Remove Chart",
                iconCss: "fas fa-exchange-alt",
            }, 
            /*
            {
                id: "layoutsbtn_resettodefault{{ $grid_id }}",
                text: "Reset to default",
                iconCss: "fa fa-sync",
            },
            {
                id: "layoutsbtn_email{{ $grid_id }}",
                text: "Email",
                iconCss: "fa fa-file",
            },
           
            {
                id: "layout_auto_form_on{{ $grid_id }}",
                text: "Auto form on",
                iconCss: "fas fa-toggle-on",
            },
            {
                id: "layout_auto_form_off{{ $grid_id }}",
                text: "Auto form off",
                iconCss: "fas fa-toggle-off",
            },
            
            
            {
                id: "layout_convert_to_report{{ $grid_id }}",
                text: "Convert to report",
                iconCss: "fas fa-exchange-alt",
            },
            
            {
                id: "layout_convert_to_layout{{ $grid_id }}",
                text: "Convert to layout",
                iconCss: "fas fa-exchange-alt",
            }, 
            */ 
        ];
        
        var menuOptions = {
            target: '.layouts_list',
            items: layout_items,
            beforeItemRender: contextmenurender,
            beforeOpen: function(args){
               console.log('beforeOpen');
              console.log($(args.event.target));
                
                
                
                
                if($(args.event.target).hasClass('.e-list-item')){
                    contextviewid{{$grid_id}} = $(args.event.target).attr('data-view_id');
                }else{
                    contextviewid{{$grid_id}} = $(args.event.target).closest('.e-list-item').attr('data-view_id');
                }
                ////console.log(contextviewid{{$grid_id}});
                
                // toggle context items on header
                if($(args.event.target).hasClass('is_card') == 1){
                    contextviewtype{{$grid_id}} = 'card';
                   
                }else{
                    contextviewtype{{$grid_id}} = 'default';
                }
              
                    var item_list = ['Sort','Save as new','Save current','Delete','Copy','Edit','Set as default','Email'];
                    
                    contextlayouts{{ $grid_id }}.enableItems(item_list, true);
                 
                    if($(args.event.target).attr('data-track_layout') == 1){
                        contextlayouts{{ $grid_id }}.enableItems(['Disable Tracking'], true);
                        contextlayouts{{ $grid_id }}.enableItems(['Enable Tracking'], false);
                    }else {
                        contextlayouts{{ $grid_id }}.enableItems(['Disable Tracking'], false);
                        contextlayouts{{ $grid_id }}.enableItems(['Enable Tracking'], true);
                    }
                    /*
                    
                    if($(args.event.target).attr('data-auto_form') == 1){
                      
                  
                        contextlayouts{{ $grid_id }}.enableItems(['Auto form off'], true);
                        contextlayouts{{ $grid_id }}.enableItems(['Auto form on'], false);
                    }else {
                       
                  
                        contextlayouts{{ $grid_id }}.enableItems(['Auto form off'], false);
                        contextlayouts{{ $grid_id }}.enableItems(['Auto form on'], true);
                    }
                    
                   
                    
                  
                    if($(args.event.target).attr('data-layout_type') == "Report"){
                      
                        contextlayouts{{ $grid_id }}.enableItems(['Convert to layout'], true);
                        contextlayouts{{ $grid_id }}.enableItems(['Convert to report'], false);
                    }else{
                        contextlayouts{{ $grid_id }}.enableItems(['Convert to layout'], false);
                        contextlayouts{{ $grid_id }}.enableItems(['Convert to report'], true);
                    }
                    */
                    if($(args.event.target).attr('data-has_chart') == "1"){
                        contextlayouts{{ $grid_id }}.enableItems(['Remove Chart'], true);
                    }else{
                        contextlayouts{{ $grid_id }}.enableItems(['Remove Chart'], false);
                    }
                    
                    contextlayouts{{ $grid_id }}.enableItems(item_list, true); 
                    
                   // contextviewid{{$grid_id}} = $(args.event.target).attr('data-view-id'); 
              
                
                
              
                    
                
              
                
                
                
            },
            select: function(args){
                if(args.item.text === 'Set as default') {
                  
               
                    gridAjax('layout_set_default/'+contextviewid{{$grid_id}});
                }
                
                if(args.item.text === 'Reset to default') {
                    gridAjax('layout_reset_to_default/'+contextviewid{{$grid_id}});
                }
                
                if(args.item.text === 'Show Chart') {
                    $("#gridChart{{$grid_id}}").removeClass('d-none');
                }
                if(args.item.text === 'Hide Chart') {
                    $("#gridChart{{$grid_id}}").addClass('d-none');
                }
               
            }
        };
      
        // Initialize ContextMenu control.
        contextlayouts{{ $grid_id }} = new ej.navigations.ContextMenu(menuOptions, '#contextlayouts{{ $grid_id }}');  
         
}



function refresh_layout_context_menus{{$grid_id}}(){
    @if(is_superadmin())
  
        contextlayouts{{ $grid_id }}.refresh();
    @endif
}
function refresh_guides_context_menus{{$grid_id}}(){
   
}

    @if(session('role_level') == 'Admin')
    @if(!empty($layout_field_filters))
    @foreach($layout_field_filters as $layout_field_filter)
   
   
    window['layout_filter_{{$layout_field_filter->field}}_{{ $grid_id }}'] = new ej.dropdowns.DropDownList({
    	dataSource: {!! json_encode($layout_field_filter->ds) !!},
        placeholder: '{{$layout_field_filter->label}} filter',
        popupWidth: 'auto',
        width:'150px',
        value: '',
        //Set true to show header title
        select: function(args){
            // window['grid_{{ $grid_id }}'].gridOptions.api.destroyFilter('join_role_id');
            console.log('layout_filter select',args);
            
            if(args.itemData.text == 'None'){
                @if($layout_field_filter->field_type == 'select_module')
                window['grid_{{ $grid_id }}'].gridOptions.api.destroyFilter('join_{{$layout_field_filter->field}}'); 
                @else
                window['grid_{{ $grid_id }}'].gridOptions.api.destroyFilter('{{$layout_field_filter->field}}'); 
                @endif
                
            }else{
            
                // Get a reference to the filter instance
                @if($layout_field_filter->field_type == 'select_module')
                var filterInstance = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterInstance('join_{{$layout_field_filter->field}}'); 
                @else
                var filterInstance = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterInstance('{{$layout_field_filter->field}}'); 
                @endif
                @if($layout_field_filter->field_type == 'text')
                
               
                    filterInstance.setModel({
                        filterType: 'text',
                        type: 'equals',
                        filter: args.itemData.text,
                    });
                   
                @elseif($layout_field_filter->field_type == 'date' || $layout_field_filter->field_type == 'datetime')
                
                    filterInstance.setModel({
                        dateFrom: null,
                        dateTo: null,
                        filterType: 'date',
                        type: args.itemData.value,
                    });
                @else
               
                    // Set the filter model
                    filterInstance.setModel({
                        filterType: 'set',
                        values: [args.itemData.text],
                    });
                @endif
                
               
                // Tell grid to run filter operation again
                window['grid_{{ $grid_id }}'].gridOptions.api.onFilterChanged();
            }
        }
    }, '#layout_filter_{{$layout_field_filter->field}}{{ $grid_id }}');
 
    @endforeach
    @endif
    @endif
    
    @if(!empty($module_tooltip))
        // Initialize Essential JS 2 JavaScript Tooltip component
        var module_tooltip = new ej.popups.Tooltip({
        content: '{!! str_replace(PHP_EOL,'',$module_tooltip) !!}'
        });
        module_tooltip.appendTo('#module_tooltip{{ $grid_id }}');
 
    @endif
    
    // layouts toolbar
    window['headertoolbar{{ $grid_id }}'] = new ej.navigations.Toolbar({
        height: '50px',
        overflowMode: 'Scrollable',
        @if($module_id == 2018)
        @if($task_in_progress == 0)
        cssClass: 'bg-task-inactive',
        @else
        cssClass: 'bg-task-active',
        @endif
        @endif
        items: [
            
           
            
            
            
            
   
            
            { template: "#toolbar_template_branding{{ $grid_id }}", align: 'left' },
          
            @if(session('role_level') == 'Admin')
            { template: "#toolbar_template_filters{{ $grid_id }}", align: 'left' },
            @endif
        
            @if(!empty($workspace_filter_datasource) && count($workspace_filter_datasource) > 0)
            { template: "#toolbar_template_workspace_filter{{ $grid_id }}", align: 'left' },
            @endif
            { template: "#toolbar_template_grid_btns{{ $grid_id }}", align: 'left' },
            
            /*
            @if(!empty($pbx_menu_menu) && count($pbx_menu_menu) > 0)  
            { template: "#toolbar_template_pbx_menu{{ $grid_id }}", align: 'right' },
            @endif
            */
            { template: "#toolbar_template_grid_action_btns{{ $grid_id }}", align: 'right' },
          
         
          
            
            
          
            
        
            { template: "#toolbar_template_external_links{{ $grid_id }}", align: 'right' },
            
            
            
        ]
    });
    window['headertoolbar{{ $grid_id }}'].appendTo('#gridheadertoolbar{{ $grid_id }}');
    
      
     @if(!empty($module_context_builder_menu) && count($module_context_builder_menu) > 0)
      var menuOptions = {
      target: '#module_name{{$grid_id}}',
      items: {!! json_encode($module_context_builder_menu) !!},
      beforeItemRender: dropdowntargetrender
      };
      
      new ej.navigations.ContextMenu(menuOptions, '#context_builder{{ $grid_id }}');
      @endif
    
</script>

<script>

// related_items_menu_menu
 @if(!empty($related_items_menu_menu) && count($related_items_menu_menu) > 0)   
    var related_items_menuMenuItems = @php echo json_encode($related_items_menu_menu); @endphp;
    // top_menu initialization
    var related_items_menu{{ $grid_id }} = new ej.navigations.Menu({
        items: related_items_menuMenuItems,
        orientation: 'Horizontal',
        cssClass: 'top-menu e-btn-group related_items_menumenu',
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
                target: '.related_items_menubtn{{ $module_id }}',
                items: context_items,
                beforeItemRender: dropdowntargetrender,
                
                beforeOpen: function(args){
                    // toggle context items on header
                   
                    if( $(args.event.target).hasClass('related_items_menubtn{{ $module_id }}')){
                        data_menu_id = $(args.event.target).attr('data-menu-id');
                        data_button_function = $(args.event.target).attr('data-button-function');
                    }else{
                        data_menu_id = $(args.event.target).closest('li').attr('data-menu-id');
                        data_button_function = $(args.event.target).closest('li').attr('data-button-function');
                    }
                    if(data_button_function > ''){
                        related_items_menu_context{{$module_id}}.enableItems(['Edit Function'], true);        
                    }else{
                        related_items_menu_context{{$module_id}}.enableItems(['Edit Function'], false); 
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
            related_items_menu_context{{$module_id}} = new ej.navigations.ContextMenu(menuOptions, '#related_items_menu_context{{$grid_id}}');
            
            @endif
    
        },
        beforeOpen: function(args){
          
            @if(is_superadmin())
            related_items_menu_context{{$module_id}}.refresh();
            @endif
            var popup_items = [];
            $(args.items).each(function(i, el){
                popup_items.push(el.text);
            });
        
            var selected = window['selectedrow_{{ $grid_id }}'];
          
            {!! button_menu_selected($module_id, 'related_items_menu', $grid_id, 'selected', true) !!}
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
            
            if(args.item.require_grid_id){
                if(window['selectedrow_{{ $grid_id }}'] && window['selectedrow_{{ $grid_id }}'].rowId){
                   
                    var grid_url = args.item.original_url + window['selectedrow_{{ $grid_id }}'].rowId; 
                   
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
</script>
<script>
    
    /** layout filters **/

    
    /** LAYOUT EVENTS **/    
	$(document).off('click', '#layoutsbtn_manage{{ $grid_id }}').on('click', '#layoutsbtn_manage{{ $grid_id }}', function() {
	    viewDialog('gridv{{ $grid_id }}','/{{$layouts_url}}?module_id={{ $module_id }}','Layouts Sort','90%','90%','coreDialog');
	});
	
	$(document).off('click', '#layoutsbtn_create{{ $grid_id }}').on('click', '#layoutsbtn_create{{ $grid_id }}', function() {
	   layout_save{{ $master_grid_id }}(true,contextviewtype{{$grid_id}});
	});
	$(document).off('click', '#layoutsbtn_create_report{{ $grid_id }}').on('click', '#layoutsbtn_create_report{{ $grid_id }}', function() {
	   layout_save{{ $master_grid_id }}(true, 'report');
	});
	$(document).off('click', '#layoutsbtn_create_card{{ $grid_id }}').on('click', '#layoutsbtn_create_card{{ $grid_id }}', function() {
	   layout_save{{ $master_grid_id }}(true, 'card');
	});
	
	$(document).off('click', '#layoutsbtn_save{{ $grid_id }}').on('click', '#layoutsbtn_save{{ $grid_id }}', function(e) {
	    layout_save{{ $master_grid_id }}();
	});
	
	$(document).off('click', '#layoutsbtn_email{{ $grid_id }}').on('click', '#layoutsbtn_email{{ $grid_id }}', function() {
		if(contextviewid{{$grid_id}}){
	    	gridAjax('/layout_email/'+contextviewid{{$grid_id}});
		}	
	});
	
	@if($layout_access['is_add'])
	$(document).off('click', '#layoutsbtn_duplicate{{ $grid_id }}').on('click', '#layoutsbtn_duplicate{{ $grid_id }}', function() {
		if(contextviewid{{$grid_id}}){
	    	gridAjaxConfirm('/{{ $layouts_url }}/duplicate', 'Duplicate layout?', {"id" : contextviewid{{$grid_id}}}, 'post');
		}	
	});
	@endif
	
	@if(is_superadmin())
	$(document).off('click', '#layoutsbtn_querybuilder{{ $grid_id }}').on('click', '#layoutsbtn_querybuilder{{ $grid_id }}', function() {
		if(contextviewid{{$grid_id}}){
            sidebarform('querybuilder', '/report_query?layout_id='+contextviewid{{$grid_id}}, 'Query Builder', '','60%'); 
		}	
	});
	@endif
	
	 
	
	$(document).off('click', '#layoutsbtn_delete{{ $grid_id }}').on('click', '#layoutsbtn_delete{{ $grid_id }}', function() {
        var confirm_text = "Delete layout?"
        var confirmation = confirm(confirm_text);
        if (confirmation) {
	        layout_delete(contextviewid{{$grid_id}});
        }
	});

	$(document).off('click', '#layoutsbtn_showall{{ $grid_id }}').on('click', '#layoutsbtn_showall{{ $grid_id }}', function() {
	    gridview_show_all();
	});
	
	$(document).off('click', '[id^="layoutsbtnload{{ $grid_id }}_"]').on('click', '[id^="layoutsbtnload{{ $grid_id }}_"]', function() {
	  
	    var layout_id = $(this).attr('id').replace("layoutsbtnload{{ $grid_id }}_", "");
	    layout_load{{$grid_id}}(layout_id);
	});
	
	$(document).off('click', '#layoutsbtn_edit{{ $grid_id }}').on('click', '#layoutsbtn_edit{{ $grid_id }}', function() {
	    sidebarform('gridcv{{ $grid_id }}','/{{$layouts_url}}/edit/'+contextviewid{{$grid_id}},'Edit Layout','','90%');
	});
	
	@if(is_superadmin() && !str_contains($db_table,'crm_task'))
        $(document).off('click','#layout_tracking_disable{{ $grid_id }}').on('click','#layout_tracking_disable{{ $grid_id }}', function(){
            if(contextviewid{{$grid_id}}){
                var layout_id =contextviewid{{$grid_id}};
             
                var url = '/layout_tracking_disable/'+layout_id;
                var confirmation = confirm('Remove from {{$workspace_role_name}}?');
                if (confirmation) {
                    $.ajax({
                        url: url,
                        type: 'get',
                        success: function(data) {
                            @if(session('role_level') == 'Admin')
                            refresh_content_sidebar_layouts{{$grid_id}}();
                            @endif
                            toastNotify(data.message, data.status);
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            toastNotify('An error occured', 'error');
                            
                        },
                    });
                }
            }else{
                toastNotify('context id not set', 'error');
            }
        });
    @endif 
    
    
    $(document).off('click','#dashboard_tracking_disable{{ $grid_id }}').on('click','#dashboard_tracking_disable{{ $grid_id }}', function(){
        if(contextviewid{{$grid_id}}){
            var layout_id =contextviewid{{$grid_id}};
         
            var url = '/dashboard_tracking_disable/'+layout_id;
            ////console.log(url);
            var confirmation = confirm('Remove from {{$workspace_role_name}}?');
            if (confirmation) {
                $.ajax({
                    url: url,
                    type: 'get',
                    success: function(data) {
                       
                        toastNotify(data.message, data.status);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        toastNotify('An error occured', 'error');
                        
                    },
                });
            }
        }else{
            toastNotify('context id not set', 'error');
        }
    });
    
    @if(is_superadmin() && !str_contains($db_table,'crm_task'))
        $(document).off('click','#layout_tracking_enable{{ $grid_id }}').on('click','#layout_tracking_enable{{ $grid_id }}', function(){
            if(contextviewid{{$grid_id}}){
            var layout_id =contextviewid{{$grid_id}};
         
            var url = '/layout_tracking_enable/'+layout_id;
            var confirmation = confirm('Add to {{$workspace_role_name}}?');
            if (confirmation) {
                $.ajax({
                    url: url,
                    type: 'get',
                    success: function(data) {
                        @if(session('role_level') == 'Admin')
                        refresh_content_sidebar_layouts{{$grid_id}}();
                        @endif
                      
                        toastNotify(data.message, data.status);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        toastNotify('An error occured', 'error');
                        
                    },
                });
            }
            
            }else{
                toastNotify('context id not set', 'error');
            }
        });
    @endif 
    
    
        $(document).off('click','#dashboard_tracking_enable{{ $grid_id }}').on('click','#dashboard_tracking_enable{{ $grid_id }}', function(){
            if(contextviewid{{$grid_id}}){
            var layout_id =contextviewid{{$grid_id}};
         
            var url = '/dashboard_tracking_enable/'+layout_id;
            var confirmation = confirm('Add to {{$workspace_role_name}}?');
            if (confirmation) {
                $.ajax({
                    url: url,
                    type: 'get',
                    success: function(data) {
                        
                      
                        toastNotify(data.message, data.status);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        toastNotify('An error occured', 'error');
                        
                    },
                });
            }
            
            }else{
                toastNotify('context id not set', 'error');
            }
        });
    
    $(document).off('click','#layout_convert_to_report{{ $grid_id }}').on('click','#layout_convert_to_report{{ $grid_id }}', function(){
        if(contextviewid{{$grid_id}}){
        var layout_id =contextviewid{{$grid_id}};
     
        var url = '/layout_convert_to_report/'+layout_id;
        var confirmation = confirm('Convert to report?');
        if (confirmation) {
            $.ajax({
                url: url,
                type: 'get',
                success: function(data) {
                    
                    layout_load{{$grid_id}}(layout_id);
                    window['grid_{{ $grid_id }}'].gridOptions.columnApi.setPivotMode(true);
                    toastNotify(data.message, data.status);
                    if(grid_views){
                        grid_views.refresh();
                    }
                    if(grid_charts){
                        grid_charts.refresh();
                    }
                    if(grid_reports){
                        grid_reports.refresh();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    toastNotify('An error occured', 'error');
                    
                },
            });
        }
        
        }else{
            toastNotify('context id not set', 'error');
        }
    });
     $(document).off('click','#layout_convert_to_layout{{ $grid_id }}').on('click','#layout_convert_to_layout{{ $grid_id }}', function(){
        if(contextviewid{{$grid_id}}){
        var layout_id =contextviewid{{$grid_id}};
     
        var url = '/layout_convert_to_layout/'+layout_id;
        var confirmation = confirm('Convert to layout?');
        if (confirmation) {
            $.ajax({
                url: url,
                type: 'get',
                success: function(data) {
                    
                   
                    layout_load{{$grid_id}}(layout_id);
                    
                    window['grid_{{ $grid_id }}'].gridOptions.columnApi.setPivotMode(false);
                    toastNotify(data.message, data.status);
                    if(grid_views){
                        grid_views.refresh();
                    }
                    if(grid_charts){
                        grid_charts.refresh();
                    }
                    if(grid_reports){
                        grid_reports.refresh();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    toastNotify('An error occured', 'error');
                    
                },
            });
        }
        
        }else{
            toastNotify('context id not set', 'error');
        }
    });
    
    
     $(document).off('click','#chart_remove{{ $grid_id }}').on('click','#chart_remove{{ $grid_id }}', function(){
        if(contextviewid{{$grid_id}}){
        var layout_id =contextviewid{{$grid_id}};
     
        var url = '/chart_remove/'+layout_id;
        var confirmation = confirm('Remove chart?');
        if (confirmation) {
            $.ajax({
                url: url,
                type: 'get',
                success: function(data) {
                    layout_load{{$grid_id}}(layout_id);
                    toastNotify(data.message, data.status);
                    if(grid_views){
                        grid_views.refresh();
                    }
                    if(grid_charts){
                        grid_charts.refresh();
                    }
                    if(grid_reports){
                        grid_reports.refresh();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    toastNotify('An error occured', 'error');
                    
                },
            });
        }
        
        }else{
            toastNotify('context id not set', 'error');
        }
    });

    
    
    
</script>


@endpush


@push('page-styles')

<style>
.ag-status-bar.bg-task-inactive, #gridheadertoolbar{{$grid_id}}.bg-task-inactive,  #gridheadertoolbar{{$grid_id}}.bg-task-inactive .e-toolbar-items {
     background-color: #e6f5ff !important;
 }
.ag-status-bar.bg-task-active, #gridheadertoolbar{{$grid_id}}.bg-task-active,  #gridheadertoolbar{{$grid_id}}.bg-task-active .e-toolbar-items {
     /*background-color: #B5D5C5 !important;*/
     background-color: #e6f5ff !important;
 }
       


#gridheadertoolbar{{ $grid_id }} .e-input-group.e-ddl{
    border-radius: 4px;
}
#linkedrecordsdropdown{{ $grid_id }}{
margin-top: 0 !important;
padding: 0px !important;
}
#linkedrecordsdropdown{{ $grid_id }} button{
width:100%;   
justify-content: left;
border-radius:0px !important;
}
#linkedrecordsdropdown{{ $grid_id }} a{
width:100%;   
justify-content: left;
border-radius:0px !important;
}
#linkedrecordsdropdown{{ $grid_id }} button span{
margin-right: 5px;
}
#linkedrecordsdropdown{{ $grid_id }} a span{
margin-right: 5px;
}
#linkedrecords{{ $grid_id }} {
    border-top-left-radius: 0px !important;
    border-bottom-left-radius: 0px !important;
    border-top-right-radius: 4px !important;
    border-bottom-right-radius: 4px !important;
    
    border-left: 0 !important;
}


#gridheadertoolbar{{ $grid_id }} .e-toolbar-left  .e-toolbar-item{
   padding-left: 12px !important;
}
#gridheadertoolbar{{ $grid_id }} .e-toolbar-right  .e-toolbar-item{
   padding-left: 12px !important;
}
#gridheadertoolbar{{ $grid_id }} .e-toolbar-right  .e-toolbar-item .e-menu-wrapper {
   padding-right: 0px !important;
}
#gridheadertoolbar{{ $grid_id }}, #gridheadertoolbar{{ $grid_id }} .e-toolbar-items{
    background-color: {{ $color_scheme['second_row_color'] }};
}


.searchinputgroup .e-input-group {
    border-top-left-radius: 0px;
    border-bottom-left-radius: 0px;
    border-left: 0px;
}
.e-menu-wrapper ul #layout_header{{ $grid_id }}.e-menu-item.e-menu-caret-icon {
    padding-right: 6px;
}
#layout_header{{ $grid_id }}{
    font-weight:bold;
    color: {{$color_scheme['second_row_text_color']}};
    user-select: text !important;
    cursor: text !important;
}

#related_items_menumenutop{
    border-left:0px !Important;
    border-top-left-radius: 0px !important;
    border-bottom-left-radius: 0px !important;
}
#grid_btns{{ $grid_id }},#grid_btns{{ $grid_id }} .e-btn:last-child{

    border-top-right-radius: 0px !important;
    border-bottom-right-radius: 0px !important;    
}
.searchinputgroup .e-input-group{
    height: 35px;
}
#adminbtns_menu{{ $grid_id }} .e-menu-item.e-menu-caret-icon{
padding: 0 10px !important;
}

#grid_menu_menu{{ $grid_id }} .e-menu-item.e-menu-caret-icon{
padding: 0 10px !important;
}
#adminbtns_menu{{ $grid_id }} .e-caret{
display: none !important;
}

#grid_menu_menu{{ $grid_id }} .e-caret{
display: none !important;
}
#adminbtns_menu{{ $grid_id }} .e-menu-icon{
margin-right: 0px !important;
}

#grid_menu_menu{{ $grid_id }} .e-menu-icon{
margin-right: 0px !important;
}
.toolbar_role_filter{
    height:35px;
    font-weight:bold;
}
#gridheadertoolbar{{ $grid_id }} .e-input-group{
    height: 35px;
    font-weight: bold;
}
#gridheadertoolbar{{ $grid_id }} .e-input-group .e-input{
    height: 35px;
}
#gridheadertoolbar{{ $grid_id }} .e-btn-group{
    height: 35px;
}

#gridheadertoolbar{{ $grid_id }} .top_menu .e-caret{
   display: none;
}
</style>

@endpush