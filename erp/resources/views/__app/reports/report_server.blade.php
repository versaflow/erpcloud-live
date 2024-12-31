@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
	
@endif

@section('content')

<style>       
.ag-unselectable {
     transition: top 100s, left 100s;
}
</style>

<div class="col p-0 m-0 h-100">
<div id="gridtoolbar{{ $grid_id }}" class="report-toolbar"></div>


<div id="toolbar_template_title{{ $grid_id }}">
<h5 class="grid-title" id="title{{ $grid_id }}">{{ ($menu_name) }}</h5>
</div>

<div id="toolbar_template_gridbuttons{{ $grid_id }}" style="margin-right:10px">
    
    <div class="k-widget k-button-group">
        <button title="Clear All Filters" id="filterclear{{ $grid_id }}" class="k-button"><span  class="e-btn-icon fas fa-filter"></span></button>
        @if($access['is_edit'])
            <button title="Query Builder" id="query_builder{{ $grid_id }}" class="k-button" ><span  class="e-btn-icon fa fa-database"></span></button>
        @endif
       
    </div>
</div>


<div id="grid_{{ $grid_id }}" class="ag-theme-alpine"  style="height: calc(100% - 40px) !important;"></div>
@endsection



@push('page-scripts')

<script type="text/javascript" charset="utf-8">
  
@if(!request()->ajax())
window['original_title'] = document.title;
@endif

$(document).off('click', '#query_builder{{ $grid_id }}').on('click', '#query_builder{{ $grid_id }}', function() {
    sidebarform('reportsc{{ $grid_id }}','/report_query/{{ $report_id }}','Query Builder');
});
document.getElementById('filterclear{{ $grid_id }}').addEventListener('click', function() {
    window['grid_{{$grid_id}}'].gridOptions.api.setFilterModel(null);
    window['grid_{{$grid_id}}'].gridOptions.api.onFilterChanged();
});


/** TOOLBAR **/

    default_layout_saved = 0;
    window['toolbar{{ $grid_id }}'] = new ej.navigations.Toolbar({
        items: [
            { template:'#toolbar_template_title{{ $grid_id }}', align: 'left'},
            { template: "#toolbar_template_gridbuttons{{ $grid_id }}", align: 'left' },
        ]
    });
    window['toolbar{{ $grid_id }}'].appendTo('#gridtoolbar{{ $grid_id }}');


/** AGGRID **/
var gridOptions = {
    debug: true,
    //enableCharts: true,
    //enableRangeSelection: true,
    // adds subtotals
    groupIncludeFooter: true,
    // includes grand total
    groupIncludeTotalFooter: true,
    pivotRowTotals: 'after',
    suppressContextMenu:true,
    suppressCellFocus:true,
    pagination: true,
    animateRows: true,
    paginationAutoPageSize:true,
    rowModelType: 'serverSide',
    rowSelection: 'single',
    multiSortKey: 'ctrl',
    serverSideStoreType: 'partial',
    columnDefs: {!! json_encode($colDefs) !!},
    rowData: {!! json_encode($row_data) !!},
    columnTypes: {
        defaultField: {
            filter: 'agTextColumnFilter',
            filterParams: {
                suppressAndOrCondition: true
            }
        },
        booleanField: {
            filter: 'agSetColumnFilter',
            filterParams: {
                suppressAndOrCondition: true,
                values: ['Yes','No',],
                buttons: ['clear', 'apply'],
            },
            cellRenderer: function(params){
                if(params.value === 1 || params.value === "true"){
                    return "Yes";
                }else{
                    return "No";
                }
            }
        },
        checkboxField: {
            filter: 'agSetColumnFilter',
            filterParams: {
                values: params =>  {
                    params.success(params.colDef.filter_options);
                }
            },
        },
        intField: {
            filter: 'agNumberColumnFilter',
            //headerClass: 'ag-right-aligned-header',
            //cellClass: 'ag-cell-numeric-right',
           // valueFormatter: currencyValueFormatter,
        },
        currencyField: {
            filter: 'agNumberColumnFilter',
           valueFormatter: function(params){
               if(params.value === undefined){
                   return params.value;
               }
             //console.log(params);
              return parseFloat(params.value).toFixed(2);
           }
        },
        sortField:{
            rowDrag: params => !params.node.group,
        },
        fileField: {
            cellRenderer: function(params){
                if(params.value > ''){
                    var files = params.value.split(",");
                    var cell_value = '';
                    var url = "{{ uploads_url($module_id) }}";
                    @if($module_id == 365)
                    var url = "{{ attachments_url() }}";
                    @endif
                    for(var key in files)
                    {
                        cell_value += '<a target="new" href="'+url+files[key]+'"> '+ files[key] +' </a> ';
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
              
                for(var key in files)
                {
                    cell_value += '<img src="'+url+files[key]+'" class="gridimage" height="10px" style="margin-left:10px" /> ';
                }
                return cell_value;
            }
        },
        
        
    },
    defaultColDef: {
        filter: 'agTextColumnFilter',
        minWidth: 100,
        // allow every column to be aggregated
        enableValue: true,
        // allow every column to be grouped
        enableRowGroup: true,
        // allow every column to be pivoted
        enablePivot: false,
        sortable: true,
        filter: true,
        filterParams: {
            suppressAndOrCondition: true
        },
    },
    
    sideBar: true,
   
    onSortChanged : function(){
        saveState();
    },
    onFilterChanged : function(){
        saveState();
    },
    onColumnRowGroupChanged : function(){
        saveState();
    },
    onColumnMoved : function(){
        saveState();
    },
    onColumnPivotModeChanged : function(){
        saveState();
    },
    onColumnValueChanged : function(){
        saveState();
    },
    onColumnPivotChanged : function(){
        saveState();
    },
    onGridReady: function(){
        // load state
        loadState();
    },
    rowHeight: 26,
    getRowHeight: function(params){
      
        if(params.node.group){
            return 50;
        }
        return 26;
    },
    pivotMode: true,
};

function saveState(){
    
    var layout = {};
    layout.colState = gridOptions.columnApi.getColumnState();
    layout.groupState = gridOptions.columnApi.getColumnGroupState();
  
    layout.filterState = gridOptions.api.getFilterModel();
    
   
    var pivotMode = gridOptions.columnApi.isPivotMode();
    if(pivotMode){
        layout.pivot_mode = 1;
    }else{
        layout.pivot_mode = 0;
    }
       
    var postdata = {layout : layout, report_id: {{$report_id}} };
  
    $.ajax({
        type: 'post',
        url: '{{ url($menu_route."/report_state_save") }}',
        data: postdata,
		success: function(data) { 
		   // toastNotify('Layout saved.','success');
		}
	});
}

function loadState(){
    
    var postdata = {report_id: {{$report_id}} };
    $.ajax({
        type: 'post',
        url: '{{ url($menu_route."/report_state_load") }}',
        data: postdata,
    	success: function(data) { 
    	   
    	    if(data && Object.keys(data).length > 0){
    	      
            var state = JSON.parse(data);
    	  
            
            if(state && state.colState){
                
    	   
                if(state.pivot_mode == 1){
                    gridOptions.columnApi.setPivotMode(true);
                }else{
                    gridOptions.columnApi.setPivotMode(false);
                }
                
    	    
                if(state.colState){
                    gridOptions.columnApi.applyColumnState(state.colState);
                }
    	   
                if(state.groupState){
                    gridOptions.columnApi.setColumnGroupState(state.groupState);
                }
    	  
               
    	  
                if(state.filterState){
                    gridOptions.api.setFilterModel(state.filterState);
                }
            }
            
    	    }
    	}
    });
}


window['grid_{{ $grid_id }}'] = new agGrid.Grid(document.querySelector('#grid_{{ $grid_id }}'), gridOptions);

/** AGGRID DATASOURCE **/

var datasource = {
    getRows(params) {
       //console.log('getRows');
       //console.log(params);
       
        window['grid_{{ $grid_id }}'].gridOptions.api.deselectAll();
       
        
        fetch('/{{$menu_route}}/report_data/{{$report_id}}', {
            method: 'post',
            body: JSON.stringify(params.request),
            headers: {"Content-Type": "application/json; charset=utf-8"}
        })
        .then(httpResponse => httpResponse.json())
        .then(response => {
            @if(is_dev() || session('user_id') == 1)
            try{
            var pivotMode = window['grid_{{ $grid_id }}'].gridOptions.columnApi.isPivotMode();
            //console.log(pivotMode);
            //console.log(response);
            //console.log(params);
            if(pivotMode){
                
               // window['grid_{{ $grid_id }}'].gridOptions.columnApi.setSecondaryColumns(response.pivotColumns);
               // addPivotColDefs(params.request, response, params.columnApi);
            }
            }catch(e){
                //console.log(e);
            }
            @endif

            params.successCallback(response.rows, response.lastRow);
            gridOptions.columnApi.autoSizeAllColumns();
        
        })
        .catch(error => {
            params.failCallback();
        })
    }
};

gridOptions.api.setServerSideDatasource(datasource);



</script>
@endpush

@push('page-styles')

<style>


</style>
@endpush