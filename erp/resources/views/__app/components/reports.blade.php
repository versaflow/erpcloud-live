@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
    
	
@endif

@section('scripts')
<!-- Reference either Knockout or AngularJS, if you do -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.5/jszip.min.js"></script>
 
<!-- DevExtreme themes -->
<link rel="stylesheet" href="https://cdn3.devexpress.com/jslib/19.1.5/css/dx.common.css">
<link rel="stylesheet" href="https://cdn3.devexpress.com/jslib/19.1.5/css/dx.material.teal.light.css">
 
<!-- DevExtreme library -->
<script type="text/javascript" src="https://cdn3.devexpress.com/jslib/19.1.5/js/dx.all.js"></script>
<script src="https://unpkg.com/devextreme-aspnet-data@2.4.2/js/dx.aspnet.data.js"></script>
<!-- <script type="text/javascript" src="https://cdn3.devexpress.com/jslib/19.1.5/js/dx.web.js"></script> -->
<!-- <script type="text/javascript" src="https://cdn3.devexpress.com/jslib/19.1.5/js/dx.viz.js"></script> -->
<!-- <script type="text/javascript" src="https://cdn3.devexpress.com/jslib/19.1.5/js/dx.viz-web.js"></script> -->

@endsection
@section('content')
<div class="container-fluid p-4" id="report_layout">
    @if($enable_chart)
    <div id="pivotgrid-chart"></div>
    @endif
    <div id="pivotgrid"></div>
</div>
@if(!empty(request()->export_excel))
<a id="export_pivot" onlick="exportPivot" href="javascript:void(0);">Export</a>
@endif
@endsection
@push('page-styles')
<style>
.dx-pivotgrid-toolbar{
    display: none !important;
}

.currency {
    text-align: center;
}

.intro { 
  background-color: yellow;
}
.dx-column-header .dx-pivotgrid-fields-area-head tr{
    display: inline-grid;
}
.dx-column-header .dx-pivotgrid-fields-area-head td {
    padding: 4px 2px;
}
</style>
@endpush
@push('page-scripts')
<script>

    pivot_initialized = 0;
    var dataSource = {!! $datasource !!};
    // date group header
    
    $.each(dataSource, function (i, field) {
        $.each(field, function (key, value) {
            if (key.indexOf("splitdate") >= 0){
                field[key] = new Date(value);
            }
        })
    });
    
    @if($enable_chart)
        var pivotGridChart = $("#pivotgrid-chart").dxChart({
            commonSeriesSettings: {
                type: "bar"
            },
            tooltip: {
                enabled: true,
            },
            size: {
                height: 320
            },
            adaptiveLayout: {
                width: 450
            }
        }).dxChart("instance");
    @endif
    
    pivotGrid = $("#pivotgrid").dxPivotGrid({
        allowSorting: true,
        allowSortingBySummary: true,
        allowFiltering: true,
        @if($show_column_grand_totals)
        showColumnGrandTotals: true,
        @else
        showColumnGrandTotals: false,
        @endif
        showRowGrandTotals: true,
        @if($show_row_totals)
        showRowTotals: true,
        @else
        showRowTotals: false,
        @endif
        showColumnTotals: false,
        allowFiltering:true,
        @if(empty($screenshot))
        height: 570,
        @endif
        showBorders: true,
        allowExpandAll: true,
        fieldChooser: {
            @if(check_access('1,31'))
                enabled: true,
            @else
                enabled: false,
            @endif
            height: 700,
            layout: 1,
            width: 900
        },
        fieldPanel: {
            visible: true,
            showFilterFields: false
        },
        headerFilter: {
            allowSearch: true,
            showRelevantValues: true,
            width: 300,
            height: 400
        },
        dataSource: {
            @if(!empty($calculated_fields))
            fields: {!! $calculated_fields !!},
            @endif
            store: dataSource,
        },
        export: {
            enabled: true,
            customizeExcelCell: function(e) {
                e.numberFormat = '#,##0.00';
            }
        },
        onContentReady: function(e){
           pivot_initialized++;
           if(pivot_initialized == 2){
                $("#report_layout").addClass('report_initialized');
                @if(!empty(request()->export_excel))
                //this.exportToExcel();
                @endif
           }
        },
        onInitialized: function(e){
        },
        onFileSaving: function(e) {
            e.fileName = '{{ str_replace(" ","_",$menu_name."_".date("Y m d")) }}';
        },
        onCellPrepared: function(e) {
            if (e.cell.value && e.area == "data") {
                e.cellElement.text(parseFloat(e.cell.value).toFixed(2));
            }
            if (e.cell.value && e.cell.value < 0 &&  e.area == "data") {
                var dataField = e.component.getDataSource().getAreaFields("data")[e.cell.dataIndex];
                if(dataField.caption === "Variance") {
                    e.cellElement.css("color", "#ED8585");
                }
            }
            
            if (e.area == "column") {
               

                if (e.cellElement[0].textContent.indexOf(")") >= 0){
                    if(e.component._options.dataSource.fields && e.component._options.dataSource.fields.length > 0){
                        var i;
                        var skip = false;
                        for (i = 0; i < e.component._options.dataSource.fields.length; ++i) {
                        if(e.component._options.dataSource.fields[i].caption == e.cellElement[0].textContent && e.component._options.dataSource.fields[i].summaryType == 'custom'){
                        skip = true;
                        }
                        }
                    
                    }else{
                        skip = true;
                    }
                    
                    if(!skip){
                        var title_arr = e.cellElement[0].textContent.split(" ").splice(-2);
                        e.cellElement[0].textContent = title_arr[0];
                    }
                }else if(e.cellElement[0].textContent.indexOf(" ") >= 0){
                    if(e.component._options.dataSource.fields && e.component._options.dataSource.fields.length > 0){
                        var i;
                        var skip = false;
                        for (i = 0; i < e.component._options.dataSource.fields.length; ++i) {
                            if(e.component._options.dataSource.fields[i].caption == e.cellElement[0].textContent && e.component._options.dataSource.fields[i].summaryType == 'custom'){
                                skip = true;
                            }
                        }
                    }else{
                        skip = true;
                    }
                  
                    if(!skip){
                        var title_arr = e.cellElement[0].textContent.split(" ").splice(-1);
                         e.cellElement[0].textContent = title_arr[0];
                    }
                }
            }
            
            if (e.cell.rowType == 'GT' || e.cell.rowType == 'T') {  
                e.cellElement.css({ 'font-weight': 'bold' });  
            }  
        },
        onContextMenuPreparing: function (e) {
            
        // date column type
            
        // Filtering off all non-data fields
        
        if (e.field && e.field.area == 'data') {
           
            // Obtaining the PivotGrid's data source
            var dataSource = e.component.getDataSource();
 
            // Implementing a click event handler for the context menu items
            var changeSummaryType = function (clickedItem) {
                dataSource.field(e.field.index, {
                    summaryType: clickedItem.itemData.value
                });
                dataSource.load();
            };
 
            // Declaring an array of summary types to be present in the context menu
            var items = [
                { text: 'Sum', value: 'sum', onItemClick: changeSummaryType },
                { text: 'Avg', value: 'avg', onItemClick: changeSummaryType },
                { text: 'Min', value: 'min', onItemClick: changeSummaryType },
                { text: 'Max', value: 'max', onItemClick: changeSummaryType },
                { text: 'Count', value: 'count', onItemClick: changeSummaryType },
                { text: 'Custom', value: 'custom', onItemClick: changeSummaryType },
            ];
 
            // Applying the "selected" style to the item that represents the current summary type
            $.each(items, function (_, item) {
                if (item.value == dataSource.field(e.field.index).summaryType)
                    item.selected = true;
            });
 
            // Pushing the array of summary types to the array of context menu items
            Array.prototype.push.apply(e.items, items)
        }

        
        @if(check_access('1,31'))
     
        if((!e.area || e.area != 'row') && e.columnFields !== undefined ){
            var query_builder = {
            beginGroup: true,
            text: 'Query Builder',
            icon: 'formula',
            onItemClick: function(){
                sidebarform('querybuilder', '/report_query_edit/{{$id}}', 'Query Builder','', '80%');
            }
            };
            e.items.unshift(query_builder);
           
        }
        @endif
        
        if (e.area == 'row') {   
            var toggleRowTotals = function (e) {
             
                if(pivotGrid._options.showRowTotals){
                    pivotGrid.option('showRowTotals',false);
                    
                }else{
                    pivotGrid.option('showRowTotals',true);
                }
                $.get("{{ url($menu_route.'/report_config_setting/'.$id.'/show_row_totals') }}");
            };
             var toggleTotalBtn = { text: 'Toggle Row Total', onItemClick: toggleRowTotals, icon: 'collapse'};
             e.items.push(toggleTotalBtn);
            
        }
        
        if (e.area == 'row') {   
            var toggleColumnGrandTotals = function (e) {
             
                if(pivotGrid._options.showColumnGrandTotals){
                    pivotGrid.option('showColumnGrandTotals',false);
                    
                }else{
                    pivotGrid.option('showColumnGrandTotals',true);
                }
                $.get("{{ url($menu_route.'/report_config_setting/'.$id.'/show_column_grand_totals') }}");
            };
             var toggleGrandTotalBtn = { text: 'Toggle Column Grand Total', onItemClick: toggleColumnGrandTotals, icon: 'collapse'};
             e.items.push(toggleGrandTotalBtn);
            
        }
        
        var contextmenu = e.items;          
        $.each(contextmenu, function (i,item) {
        
            if(item && item.text && item.text == "Show Field Chooser"){
                e.items[i].text = "Field Chooser";
                if (e.area && e.area == 'row') { 
                    e.items.splice(i, 1); 
                }
            }
        });
        
        var contextmenu = e.items;         
        $.each(contextmenu, function (i,item) {
        
            if(item && item.text && item.text == "Export to Excel file"){
                e.items[i].text = "Excel Export";
                if (e.area && e.area == 'row') {  
                    e.items.splice(i, 1);
                }
            }
        });
    },
        
        stateStoring: {
            enabled: true,
            type: "custom",
            customLoad: function () {
                var d = new $.Deferred();
                setTimeout(function() {
                    var url = "{{ url($menu_route.'/report_config_load/'.$id.'/') }}";
                    $.ajax({
                        url: url,
                        method: "GET",
                        dataType: "json",
                        success: function (data) {
                           
                            if(data.error){
                            }else{
                                var state = JSON.parse(data);
                                var state = $.parseJSON(state);
                             
                                d.resolve(state);
                            }
                        },
                    });
                    
                }, 1000);
                return d.promise();
            },
            customSave: function (state) {
                sendStorageRequest("json", "PUT", state);
            },
        },
    }).dxPivotGrid("instance");
    
    @if($enable_chart)
        pivotGrid.bindChart(pivotGridChart, {
            dataFieldsDisplayMode: "splitPanes",
            alternateDataFields: false
        });
    @endif

function sendStorageRequest (dataType, method, data) {
  
    if(method == "GET")
        var url = "{{ url($menu_route.'/report_config_load/'.$id.'/') }}";
    if(method == "PUT")
        var url = "{{ url($menu_route.'/report_config_save/'.$id.'/') }}";
    
    var deferred = new $.Deferred;
    var storageRequestSettings = {
        url: url,
        headers: {
            "Accept" : "text/html",
            "Content-Type" : "text/html"
        },
        method: method,
        dataType: dataType,
        success: function (data) {
            deferred.resolve(data);
        //   alert("LOAD: " + data);
        },
        fail: function (error) {
            deferred.reject();
        //   alert("LOAD FAILURE");
        }
    };
    if (data) {
        storageRequestSettings.data = JSON.stringify(data);
    }
    $.ajax(storageRequestSettings);
    return deferred.promise();
}

function exportPivot(){
    pivotGrid.exportToExcel();
}
</script>
@endpush