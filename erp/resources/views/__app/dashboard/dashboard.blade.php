@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
@endif
@push('styles')
   
   
    <link rel="stylesheet" href="https://cdn.syncfusion.com/ej2/20.4.49/material3.css" />
@endpush

@push('scripts')
   
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.syncfusion.com/ej2/20.4.49/dist/ej2.min.js"></script>
     <script>
    //var syncfusion_key23136 = 'Ngo9BigBOggjHTQxAR8/V1NHaF5cWWdCf1FpRmJGdld5fUVHYVZUTXxaS00DNHVRdkdgWH5fcnRQRWdeVkB0WEo=';
    var syncfusion_key = 'Mgo+DSMBaFt/QHRqVVhkX1pFdEBBXHxAd1p/VWJYdVt5flBPcDwsT3RfQF5jSH9RdkJgXXxecnBRQQ==;Mgo+DSMBPh8sVXJ0S0J+XE9AdVRDX3xKf0x/TGpQb19xflBPallYVBYiSV9jS31Td0RhWXhddHdVRGZfVg==;ORg4AjUWIQA/Gnt2VVhkQlFaclxJXGFWfVJpTGpQdk5xdV9DaVZUTWY/P1ZhSXxQdkRiW39Zc3BWRmJUUUM=;OTI3MTM4QDMyMzAyZTM0MmUzMEtmVWJ2TDM1UVZpalkvN2xoRVVqcjd1TjRaMWh1TnhJMzdoMzVnLzlza1U9;OTI3MTM5QDMyMzAyZTM0MmUzMGlsWTZXSTArbm1LMSs4M25NVE5mMWlLd2pIeVVJakp0WDkvU09kUnhPK0k9;NRAiBiAaIQQuGjN/V0Z+WE9EaFtBVmJLYVB3WmpQdldgdVRMZVVbQX9PIiBoS35RdUViWH1ed3dRRWBfWEBx;OTI3MTQxQDMyMzAyZTM0MmUzMGp3QTF5VCtOZUJBL0RlK29CWS8yNDlJRytESGI1eGhyTUtDSS9SajRIQ289;OTI3MTQyQDMyMzAyZTM0MmUzMEFlMTlabjNkaHREYkVOZTFtSGxGa0JUZEFYRE5ZKytpZGU3NDdVa3BhL0U9;Mgo+DSMBMAY9C3t2VVhkQlFaclxJXGFWfVJpTGpQdk5xdV9DaVZUTWY/P1ZhSXxQdkRiW39Zc3BWRmRVUkM=;OTI3MTQ0QDMyMzAyZTM0MmUzMElFUnBvQVpLZ3VMQTFXT1RYY1AvV3d2YjUrV1lrWXVwMnZFT2FFRjVqWFU9;OTI3MTQ1QDMyMzAyZTM0MmUzMEk0eWc5Q1NDTy9RQjlvZ29Rb2FaMDFyWDFsdGpta0hlZyt3bUlPVHYydWs9;OTI3MTQ2QDMyMzAyZTM0MmUzMGp3QTF5VCtOZUJBL0RlK29CWS8yNDlJRytESGI1eGhyTUtDSS9SajRIQ289';
   
    ej.base.registerLicense(syncfusion_key);
    </script>
@endpush

@section('content')
<div class="custom-container items-center justify-center">
  <div class="max-h-full overflow-y-auto p-0" id="dasboard_container">
          
        @include('__app.dashboard.dashboard_cards')
        <div class="inline p-2" id="control">
            <div id="dashboard_default"></div>
        </div>
        
    </div>
</div>
<ul id="dashboard_context" class="m-0"></ul>
@endsection

@push('page-styles')

<style>
.menu_row{
    font-size:13px;
    background-color: {{ $color_scheme['second_row_color'] }};
}

.custom-container {
  height: calc(100vh - 40px) !important;
}
.hide-link {
  opacity: 0;
  overflow: hidden;
  height: 0;
  width: 0;
  display: block;
}

</style>
@endpush

@push('page-scripts')

<script>



let charts = [];
// initialize dashboardlayout component
var panels = {!! json_encode($dashboard_state) !!};
//console.log(panels);
var dashboard = new ej.layouts.DashboardLayout({
    cellSpacing: [20, 20],
    allowResizing: true,
    columns: 4,
    panels: {!! json_encode($dashboard_state) !!},
    created: renderCharts,
    dragStop: savePanels,
    resizeStop: savePanels
    
});
// render initialized dashboardlayout
dashboard.appendTo('#dashboard_default');
////console.log(d



function savePanels(args) {
    //console.log(savePanels);
    //console.log(args);
    if(args.isInteracted && args.name == "resizeStop"){
        resizeChart(args.element.id);
    }
    dashboard_state = dashboard.serialize();
    ////console.log(dashboard_state);
       $.ajax({
    	url: '/save_dashboard_state',
    	data: {dashboard_state: dashboard_state},
    	type: 'post',
    	success: function(data){
    	    ////console.log(data);
    	}
    });
}

function renderCharts(){
    @foreach($panels as $panel)
        @if($panel->widget_type == 'Stacked Column')
        renderStackedColumnChart({{$panel->id}},'{{$panel->panel_name}}',{!! json_encode($panel->chart_data) !!},'{{$panel->sum_field}}');
        @endif
        @if($panel->widget_type == 'Line')
        renderLineChart({{$panel->id}},'{{$panel->panel_name}}',{!! json_encode($panel->chart_data) !!},'{{$panel->sum_field}}');
        @endif
        @if($panel->widget_type == 'Pyramid')
        renderPyramidChart({{$panel->id}},'{{$panel->panel_name}}',{!! json_encode($panel->chart_data) !!},'{{$panel->sum_field}}');
        @endif
        @if($panel->widget_type == 'Donut')
        renderDonutChart({{$panel->id}},'{{$panel->panel_name}}',{!! json_encode($panel->chart_data) !!},'{{$panel->sum_field}}');
        @endif
        @if($panel->widget_type == 'Funnel')
        renderFunnelChart({{$panel->id}},'{{$panel->panel_name}}',{!! json_encode($panel->chart_data) !!},'{{$panel->sum_field}}');
        @endif
        @if($panel->widget_type == 'Speedometer')
        renderSpeedometerChart({{$panel->id}},'{{$panel->panel_name}}',{!! json_encode($panel->chart_data)  !!});
        @endif
       
    @endforeach
}

function resizeChart(id){
    if(charts[id])
    charts[id].refresh(); 
}
function renderDonutChart(id, name, chart_data, sum_field){
    var data = chart_data;
    
    if(chart_data.length == 0){
        $('#panel'+id).html('<p style="font-size: 18px; font-style: normal; font-weight: normal; font-family: inherit;">'+name+'</p><br><p>No Data Returned</p>')
    }else{
   
    charts[id] = new ej.charts.AccumulationChart({
        //Initializing Series
        title: name,
        series: [
            {
                dataSource: data,
                border: { width: 1 },
                dataLabel: {
                    visible: true,
                    name: 'text',
                    position: 'Outside',
                    font: {
                        fontWeight: '600',
                    },
                    connectorStyle:{length : '20px', type: 'Curve'}
                },
                xName: 'x', radius: ej.base.Browser.isDevice ? '40%' : '70%',
                yName: 'y', startAngle: ej.base.Browser.isDevice ? 30 : 62,
                innerRadius: '65%', name: 'Project',
                explodeOffset: '10%'
            }
        ],
        enableSmartLabels: true,
        enableBorderOnMouseMove:false,
        legendSettings: {
            visible: false,
            position: 'Top'
        },
        centerLabel:{
            text : name,
            hoverTextFormat: '${point.x} <br> '+sum_field+' <br> ${point.y}%',
            textStyle: {
                fontWeight: '600',
                size: ej.base.Browser.isDevice ? '8px' : '15px'
            },
        },
        pointRender: function (args) {
            var selectedTheme = location.hash.split('/')[1];
            selectedTheme = selectedTheme ? selectedTheme : 'Material';
            if (selectedTheme === 'fluent') {
                args.fill = seriesColor[args.point.index % 10];
            }
            else if (selectedTheme === 'bootstrap5') {
                args.fill = seriesColor[args.point.index % 10];
            }
            if (selectedTheme.indexOf('dark') > -1) {
                if (selectedTheme.indexOf('material') > -1) {
                    args.border.color = '#303030';
                }
                else if (selectedTheme.indexOf('bootstrap5') > -1) {
                    args.border.color = '#212529';
                }
                else if (selectedTheme.indexOf('bootstrap') > -1) {
                    args.border.color = '#1A1A1A';
                }
                else if (selectedTheme.indexOf('fabric') > -1) {
                    args.border.color = '#201f1f';
                }
                else if (selectedTheme.indexOf('fluent') > -1) {
                    args.border.color = '#252423';
                }
                else if (selectedTheme.indexOf('bootstrap') > -1) {
                    args.border.color = '#1A1A1A';
                }
                else if (selectedTheme.indexOf('tailwind') > -1) {
                    args.border.color = '#1F2937';
                }
                else {
                    args.border.color = '#222222';
                }
            }
            else if (selectedTheme.indexOf('highcontrast') > -1) {
                args.border.color = '#000000';
            }
            else {
                args.border.color = '#FFFFFF';
            }
        },
        reloadData: function(){
            $.ajax({
                url: 'getchartdata/'+id,
                beforeSend: function(){
                  showSpinner("#panelcontent"+id);
                },
                success: function(data){
                    hideSpinner("#panelcontent"+id);
                    charts[id].series[0].dataSource = data;
                    charts[id].refresh();
                }
            })
        }

    });
    charts[id].appendTo('#panel'+id);
    }
}

function renderFunnelChart(id, name, chart_data, sum_field){
    var data = chart_data;
    if(chart_data.length == 0){
        $('#panel'+id).html('<p style="font-size: 18px; font-style: normal; font-weight: normal; font-family: inherit;">'+name+'</p><br><p>No Data Returned</p>')
    }else{
       
        
        charts[id] = new ej.charts.AccumulationChart({
            //Initializing Series
            series: [{
            type: 'Funnel', dataSource: data, xName: 'x', yName: 'y',
            neckWidth: '15%',
            neckHeight: '18%',
            gapRatio:0.03,
            width:'45%',
            height:'80%',
            name: name,
            dataLabel: {
                visible: true, position: 'Inside',
               name: 'text',font:{fontWeight:'600'},connectorStyle: {length:'20px'}
            },
            explode: false,
            }],
            legendSettings: { visible: false },
            //Initializing Tooltip
            tooltip: {enable: false, format: '${point.x} : <b>${point.y}</b>' },
            enableAnimation: false,
            // custom code start
            load: function (args) {
            var funnelTheme = location.hash.split('/')[1];
            funnelTheme = funnelTheme ? funnelTheme : 'Material';
            args.accumulation.theme = (funnelTheme.charAt(0).toUpperCase() +
                funnelTheme.slice(1)).replace(/-dark/i, 'Dark').replace(/contrast/i, 'Contrast');
            if (args.accumulation.availableSize.width < args.accumulation.availableSize.height) {
                args.accumulation.series[0].height = '70%';
                args.accumulation.series[0].width = '80%';
            }
            },
            //Initializing Title
            title: name,
            reloadData: function(){
                $.ajax({
                    url: 'getchartdata/'+id,
                    beforeSend: function(){
                      showSpinner("#panelcontent"+id);
                    },
                    success: function(data){
                        hideSpinner("#panelcontent"+id);
                        charts[id].series[0].dataSource = data;
                        charts[id].refresh();
                    }
                })
            }
        });
        charts[id].appendTo('#panel'+id);
    }
}

function renderPyramidChart(id, name, chart_data, sum_field){
    var data = chart_data;
    if(chart_data.length == 0){
        $('#panel'+id).html('<p style="font-size: 18px; font-style: normal; font-weight: normal; font-family: inherit;">'+name+'</p><br><p>No Data Returned</p>')
    }else{
        charts[id] = new ej.charts.AccumulationChart({
            //Initializing Series
            series: [{
                    type: 'Pyramid', 
                    dataSource: data, xName: 'x', yName: 'y', width: '45%', height: '80%',
                    neckWidth: '15%', gapRatio: 0.03, name: 'Food',
                    dataLabel: {
                        name: 'text', visible: true, position: 'Outside',connectorStyle: {length: '20px'}, font: {
                            fontWeight: '600', 
                        }
                    }, explode: true, emptyPointSettings: { mode: 'Drop', fill: 'red' },
               }],
            legendSettings: {
                visible: false
            },
            onLegendClick: function(args){
               
            },
            tooltip: {enable: true, format: '${point.x} : <b>${point.y} '+sum_field+'</b>',header:''},
            textRender: function (args) {
                args.text = args.text;
            },
    
            //Initializing Chart Title
            title: name,
            reloadData: function(){
                $.ajax({
                    url: 'getchartdata/'+id,
                    beforeSend: function(){
                      showSpinner("#panelcontent"+id);
                    },
                    success: function(data){
                        hideSpinner("#panelcontent"+id);
                        charts[id].series[0].dataSource = data;
                        charts[id].refresh();
                    }
                })
            }
        });
        charts[id].appendTo('#panel'+id);
    }
}

function renderStackedColumnChart(id, name, chart_data, sum_field){
      if(chart_data.length == 0){
      
        $('#panel'+id).html('<p style="font-size: 18px; font-style: normal; font-weight: normal; font-family: inherit;">'+name+'</p><br><p>No Data Returned</p>')
    }else{
   
    charts[id] = new ej.charts.Chart({
        //Initializing Primary X Axis
        primaryXAxis: {
            majorGridLines: { width: 0 },
            minorGridLines: { width: 0 },
            majorTickLines: { width: 0 },
            minorTickLines: { width: 0 },
            interval: 1,
            lineStyle: { width: 0 },
            labelIntersectAction: 'Rotate45',
            valueType: 'Category'
        },
        //Initializing Primary Y Axis
        primaryYAxis: {
            title: sum_field,
            lineStyle: { width: 0 },
            majorTickLines: { width: 0 },
            majorGridLines: { width: 1 },
            minorGridLines: { width: 1 },
            minorTickLines: { width: 0 },
            labelFormat: '{value}',
        },
        chartArea: { border: { width: 0 } },
        //Initializing Chart Series
        series: chart_data,
        //Initializing Tooltip
        tooltip: {
            enable: true
        },
        width:  '100%' ,
        //Initializing Chart Title
        title: name,
        legendSettings: {
            enableHighlight :true
        },

    });
    charts[id].appendTo('#panel'+id);
    }

}

function renderLineChart(id, name, chart_data, sum_field){ 
      if(chart_data.length == 0){
      
        $('#panel'+id).html('<p style="font-size: 18px; font-style: normal; font-weight: normal; font-family: inherit;">'+name+'</p><br><p>No Data Returned</p>')
    }else{
        
    var data = chart_data;
   
    var max = 1000;
    $(data).each(function(i,obj){
        data[i]['x'] = new Date(obj['x']);
        data[i]['y'] = parseInt(obj['y']);
        line_max = Math.ceil(obj['y'] / 1000) * 1000;
        if(line_max > max){
            max = line_max;
        }
    });
    
   
    var interval = parseInt(max/10);
    charts[id] = new ej.charts.Chart({
        //Initializing Primary X Axis
        primaryXAxis: {
            valueType: 'DateTime',
            labelFormat: 'yyyy-MM-dd',
            majorGridLines: { width: 0 },
            edgeLabelPlacement: 'Shift'
        },
        //Initializing Primary X Axis
        primaryYAxis: {
            title: sum_field,
            labelFormat: '{value}',
            rangePadding: 'None',
            minimum: 0,
            maximum: max,
            interval: interval,
            lineStyle: { width: 0 },
            majorTickLines: { width: 0 },
            minorTickLines: { width: 0 }
        },
        chartArea: {
            border: {
                width: 0
            }
        },
        //Initializing Chart Series
        series: [
            {
                type: 'Line',
                dataSource: data,
                xName: 'x', width: 2, marker: {
                    visible: true,
                    width: 7,
                    height: 7,
                    isFilled: true
                },
                yName: 'y', name: sum_field,
            },
        ],
        //Initializing Chart Title
        title: name,
        legendSettings: {enableHighlight: true},
        //Initializing Tooltip
        tooltip: {
            enable: true
        },
        width: '100%',

    });
    charts[id].appendTo('#panel'+id);
    }
}

function renderSpeedometerChart(id, name, chart_data){
  
    if(chart_data.length == 0){
      
        $('#panel'+id).html('<p style="font-size: 18px; font-style: normal; font-weight: normal; font-family: inherit;">'+name+'</p><br><p>No Data Returned</p>')
    }else{
       
    charts[id] = new ej.circulargauge.CircularGauge({
        title: name,
        background:'transparent',
        titleStyle: { size: '18px', fontFamily: 'inherit' },
        centerY: '70%',
        reloadData: function(){
            $.ajax({
                url: 'getchartdata/'+id,
                beforeSend: function(){
                  showSpinner("#panelcontent"+id);
                },
                success: function(data){
                    hideSpinner("#panelcontent"+id);
                    charts[id].axes[0].pointers[0].value = data.percentage;
                    charts[id].axes[0].annotations[0].content = data.percentage+'%'+ ' - '+data.total+'/'+data.target,
                    charts[id].refresh();
                }
            })
        },
        axes: [{
            radius: '100%',
           
            minimum: 0,
            maximum: chart_data.target,
            lineStyle: { width: 0 },
            majorTicks: { interval: parseInt((chart_data.target/5)*1),width: 0, },
            minorTicks: { width: 0 },
            labelStyle: {
                useRangeColor: false, position: 'Outside', autoAngle: true,
                font: { size: '13px', fontFamily: 'inherit' }
            },
            startAngle: 270, endAngle: 90,
            pointers: [{
                animation: { enable: true, duration: 900 },
                value: chart_data.percentage,
                radius: '80%',
                color: '#757575',
                pointerWidth: 7,
                cap: {
                    radius: 8,
                    color: '#757575',
                    border: { width: 0 }
                },
                needleTail: {
                    color: '#757575',
                    length: '15%'
                },
            }],
            
            annotations: [
                {
                    content: chart_data.percentage+'%'+ ' - '+chart_data.total+'/'+chart_data.target,
                    angle: 0, zIndex: '1',
                    radius: '30%'
                }
            ],
            
            ranges: [
                {
                    start: 0,
                    end: parseInt((chart_data.target/5)*1),
                    startWidth: 5, endWidth: 10,
                    radius: '102%',
                    color: '#82b944',
                },
                {
                    start: parseInt((chart_data.target/5)*1),
                    end: parseInt((chart_data.target/5)*2),
                    startWidth: 10, endWidth: 15,
                    radius: '102%',
                    color: '#a1cb43',
                }, {
                    start: parseInt((chart_data.target/5)*2),
                    end:parseInt((chart_data.target/5)*3),
                    startWidth: 15, endWidth: 20,
                    radius: '102%',
                    color: '#ddec12',
                },
                {
                    start: parseInt((chart_data.target/5)*3),
                    end:  parseInt((chart_data.target/5)*4),
                    startWidth: 20, endWidth: 25,
                    radius: '102%',
                    color: '#ffbc00',
                },
                {
                    start:parseInt((chart_data.target/5)*4),
                    end: chart_data.target,
                    startWidth: 25, endWidth: 30,
                    radius: '102%',
                    color: '#ff6000',
                },
            ]
        }],
        load: function (args) {

        }
    });
    
    charts[id].appendTo('#panel'+id);
    }
}

@foreach($panels as $panel)
$("body").on('dblclick', '#panel{{$panel->id}}', function(e) {
    window.open('{{ url($panel->layout_url) }}','_blank');
});
@endforeach


    var context_items = [
        {
            id: "context_open",
            text: "Open",
            iconCss: "fas fa-list",
        },
        {
            id: "context_refresh",
            text: "Refresh",
            iconCss: "fas fa-sync",
        },
        {
            id: "context_edit",
            text: "Edit",
            iconCss: "fas fa-edit",
        },
        {
            id: "context_delete",
            text: "Remove",
            iconCss: "fas fa-trash",
        },
    ];
    var menuOptions = {
        target: '.panelchart',
        items: context_items,
        beforeOpen: function(args){
            // toggle context items on header
           
            if( $(args.event.target).hasClass('panelchart')){ 
                data_report_link = $(args.event.target).attr('data-report-link');
                data_report_id = $(args.event.target).attr('data-report-id');
            }else{
                data_report_link = $(args.event.target).closest('.panelchart').attr('data-report-link');
                data_report_id = $(args.event.target).closest('.panelchart').attr('data-report-id');
            }
           
        },
        select: function(args){
           // //console.log(data_report_link);
            //console.log(data_report_id);
          
            if(args.item.id === 'context_open') {
                window.open(data_report_link,"_blank");
            }
            if(args.item.id === 'context_refresh') {
                charts[data_report_id].reloadData();
            }
            if(args.item.id === 'context_edit') {
               sidebarform('editchart','{{$layouts_url}}/edit/'+data_report_id)
            }
            if(args.item.id === 'context_delete') {
               
                $.ajax({
                    url: 'removechart/'+data_report_id,
                    beforeSend: function(){
                      showSpinner("#panelcontent"+data_report_id);
                    },
                    success: function(data){
                        hideSpinner("#panelcontent"+data_report_id);
                        dashboard.removePanel(data_report_id.toString());
                    }
                })
            }
        }
    };
    
    // Initialize ContextMenu control
    dashboard_context = new ej.navigations.ContextMenu(menuOptions, '#dashboard_context');



</script>
@endpush

