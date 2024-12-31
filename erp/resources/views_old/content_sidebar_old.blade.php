<div class="card h-100 " id="content_sidebar">
   
    <div class="card-header p-0">
        <ul class="nav nav-pills nav-fill pt-2 p-1" role="tablist" id="right_sidebar_ul">
          
            
            @if($module_id == 1923)
            <li class="nav-item" id="content_sidebar_interactions_li" name="Sales">
                <a class="nav-link mb-0 px-0 py-1 " data-bs-toggle="tab" href="#content_sidebar_interactions" role="tab"  title="Sales">
                <i class="fas fa-envelope text-sm me-1"></i> 
                </a>
            </li>
            @endif
           
            
            @if($module_id == 1944 || $has_module_guides)
            <li class="nav-item" id="content_sidebar_guides_li" name="Guides">
            <a class="nav-link mb-0 px-0 py-1 " data-bs-toggle="tab" href="#content_sidebar_guides" role="tab"  title="Guides">
            <i class="fas fa-book text-sm me-1"></i> 
            </a>
            </li>
            @endif
            <li class="nav-item" id="content_sidebar_grid_li" name="Layouts">
            <a id="content_sidebar_first_tab" class="nav-link mb-0 px-0 py-1 " data-bs-toggle="tab" href="#content_sidebar_grid" role="tab"  title="Grid">
            <i class="fas fa-table  text-sm me-1"></i> 
            </a>
            </li>
                 
            @if($communications_panel)
            <li class="nav-item" id="content_sidebar_row_info_li" name="Customer Info">
                <a class="nav-link mb-0 px-0 py-1 " data-bs-toggle="tab" href="#content_sidebar_row_info" role="tab"  title="Customer Info">
                <i class="fas fa-user text-sm me-1"></i> 
                </a>
            </li>
            @endif
            @if($module_id != 1923)
            <li class="nav-item" id="content_sidebar_interactions_li" name="Interactions">
                <a class="nav-link mb-0 px-0 py-1" data-bs-toggle="tab" href="#content_sidebar_interactions" role="tab"  title="Interactions">
                <i class="fas fa-envelope text-sm me-1"></i> 
                </a>
            </li>
            @endif
           
            <li class="nav-item" id="content_sidebar_row_history_li" name="Row Data">
                <a class="nav-link mb-0 px-0 py-1 " data-bs-toggle="tab" href="#content_sidebar_row_history" role="tab"  title="Row Data">
                <i class="fas fa-calendar-alt text-sm me-1"></i> 
                </a>
            </li>
            
            
          
          
        </ul>
    </div>
    <div class="card-body p-sm-1 p-md-2" >
    <div class="tab-content" id="nav-tabContent" style=" font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif,'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol' !important">
        
         @if($module_id == 1944 || $has_module_guides)
        <div class="tab-pane fade" id="content_sidebar_guides">
            <div class="card">
              
                <div class="card-header p-1 pt-2 px-2 border"><h4 class="ps-0  ms-2 mt-0 mb-0 text-sm font-weight-bolder opacity-6">Guides</h4></div>
                <div class="card-body p-2 border" >
                    <div id="content_sidebar_guides_accordion" ></div>
                </div>
            </div>
        </div>
       
        @endif
        <div class="tab-pane fade" id="content_sidebar_grid">
           
            <div class="card">
                <div class="card-header p-1 pt-2 px-2 border"><h4 class="ps-0  ms-2 mt-0 mb-0 text-sm font-weight-bolder opacity-6">Layouts</h4></div>
                <div class="card-body p-2 border" >
                    
                    <div id='grid_views'></div>
                      
                    
                </div>
               
                <div class="card">
                    <div class="card-header p-1 pt-2 px-2 border"><h4 class="ps-0  ms-2 mt-0 mb-0 text-sm font-weight-bolder opacity-6">Reports</h4></div>
                    <div class="card-body p-2 border" >
                        <div id='grid_reports'></div>
                    </div>
                </div>
                
                @if(!empty($aggrid_charts))
               
                <div class="card">
                <div class="card-header p-1 pt-2 px-2 border"><h4 class="ps-0  ms-2 mt-0 mb-0 text-sm font-weight-bolder opacity-6">Charts</h4></div>
                <div class="card-body p-2 border" >
                @yield('aggridcharts')
                </div>
                </div>
               
                @endif
                
            </div>
            
         
        </div>
       
    
        @if($communications_panel)
        <div class="tab-pane fade " id="content_sidebar_row_info">
            <div class="card">
                <div class="card-header p-1 pt-2 px-2 border"><h4 class="ps-0  ms-2 mt-0 mb-0 text-sm font-weight-bolder opacity-6">Customer Info</h4></div>
                <div class="card-body p-2 border" >
                    <div id="content_rowinfo_html" class="p-2" style="font-size: 13px;"></div>
                    <div id="content_rowinfo_accordion"></div>
                </div>
            </div>
        </div>
        @endif
        <div class="tab-pane fade " id="content_sidebar_row_history">
            <div class="card">
                <div class="card-header p-1 pt-2 px-2 border"><h4 class="ps-0  ms-2 mt-0 mb-0 text-sm font-weight-bolder opacity-6">Row Data</h4></div>
                <div class="card-body p-2 border" >
                    <div id="content_rowhistory_accordion"></div>
                    
                </div>
            </div>
            <div class="card">
                <div class="card-header p-1 pt-2 px-2 border"><h4 class="ps-0  ms-2 mt-0 mb-0 text-sm font-weight-bolder opacity-6">Related Modules</h4></div>
                <div class="card-body p-2 border" >
                    <div id="content_linked_records_html" class="p-2" style="font-size: 13px;"></div>
                </div>
            </div>
        </div>
        
       
        <div class="tab-pane fade" id="content_sidebar_interactions">
            <div class="card">
                <div class="card-header p-1 pt-2 px-2 border"><h4 class="ps-0  ms-2 mt-0 mb-0 text-sm font-weight-bolder opacity-6">@if($module_id == 1923) Sales @else Interactions @endif</h4></div>
                <div class="card-body p-2 border" >
                    @if($module_id == 1923)
                    <div id="content_interactions_sales"></div>
                    @endif
                    <div id="content_interactions_accordion"></div>
                </div>
            </div>
        </div>
       
    
        
   
    </div>
    </div>
</div>
<div class="grid_layout"></div>
<div class="layouts_list"></div>
<div class="guide_context"></div>
<div class="interaction_context"></div>
<div class="sidebar_account_info"></div>
<div class="sidebar_subscription_info"></div>
@if($module_id == 1944)
<div class="widget_context"></div>
<div class="workboardreports_context"></div>
<div class="chart_context"></div>
<div id="chartcontent_container" class="d-none">
@foreach($charts as $chart)
<div id="chartcontent{{$chart->id}}"> 
<div id="chart{{$chart->id}}" class="widget_context widget_type-{{$chart->widget_type}}" data-attr-id="{{$chart->id}}" data-attr-layout-link="{{$chart->layout_link}}"> </div>
</div>
@endforeach
</div>
@endif

@push('page-scripts')

@if($module_id == 1944)
<!-- charts start -->
<script>
    let charts = [];
    function renderDonutChart(id, name, chart_data, sum_field){
        var data = chart_data;
        
        if(chart_data.length == 0){
            $('#chart'+id).html('<p style="font-size: 18px; font-style: normal; font-weight: normal; font-family: inherit;">'+name+'</p><br><p>No Data Returned</p>')
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
                      showSpinner("#chartcontent"+id);
                    },
                    success: function(data){
                        hideSpinner("#chartcontent"+id);
                        charts[id].series[0].dataSource = data;
                        charts[id].refresh();
                    }
                })
            }
    
        });
        charts[id].appendTo('#chart'+id);
        }
    }
    
    function renderFunnelChart(id, name, chart_data, sum_field){
        var data = chart_data;
        if(chart_data.length == 0){
            $('#chart'+id).html('<p style="font-size: 18px; font-style: normal; font-weight: normal; font-family: inherit;">'+name+'</p><br><p>No Data Returned</p>')
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
                          showSpinner("#chartcontent"+id);
                        },
                        success: function(data){
                            hideSpinner("#chartcontent"+id);
                            charts[id].series[0].dataSource = data;
                            charts[id].refresh();
                        }
                    })
                }
            });
            charts[id].appendTo('#chart'+id);
        }
    }
    
    function renderPyramidChart(id, name, chart_data, sum_field){
        var data = chart_data;
        if(chart_data.length == 0){
            $('#chart'+id).html('<p style="font-size: 18px; font-style: normal; font-weight: normal; font-family: inherit;">'+name+'</p><br><p>No Data Returned</p>')
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
                          showSpinner("#chartcontent"+id);
                        },
                        success: function(data){
                            hideSpinner("#chartcontent"+id);
                            charts[id].series[0].dataSource = data;
                            charts[id].refresh();
                        }
                    })
                }
            });
            charts[id].appendTo('#chart'+id);
        }
    }
    
    function renderStackedColumnChart(id, name, chart_data, sum_field){
          if(chart_data.length == 0){
          
            $('#chart'+id).html('<p style="font-size: 18px; font-style: normal; font-weight: normal; font-family: inherit;">'+name+'</p><br><p>No Data Returned</p>')
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
        charts[id].appendTo('#chart'+id);
        }
    
    }
    
    function renderLineChart(id, name, chart_data, sum_field){ 
          if(chart_data.length == 0){
          
            $('#chart'+id).html('<p style="font-size: 18px; font-style: normal; font-weight: normal; font-family: inherit;">'+name+'</p><br><p>No Data Returned</p>')
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
        charts[id].appendTo('#chart'+id);
        }
    }
    
    function renderSpeedometerChart(id, name, chart_data){
      
        if(chart_data.length == 0){
          
            $('#chart'+id).html('<p style="font-size: 18px; font-style: normal; font-weight: normal; font-family: inherit;">'+name+'</p><br><p>No Data Returned</p>')
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
                      showSpinner("#chartcontent"+id);
                    },
                    success: function(data){
                        hideSpinner("#chartcontent"+id);
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
        
       
        charts[id].appendTo('#chart'+id);
        
       
        }
    }    
    
    function renderCharts(){
       
        @foreach($charts as $chart)
            @if($chart->widget_type == 'Stacked Column')
            renderStackedColumnChart({{$chart->id}},'{{$chart->name}}',{!! json_encode($chart->chart_data) !!},'{{$chart->sum_field}}');
            @endif
            @if($chart->widget_type == 'Line')
            renderLineChart({{$chart->id}},'{{$chart->name}}',{!! json_encode($chart->chart_data) !!},'{{$chart->sum_field}}');
            @endif
            @if($chart->widget_type == 'Pyramid')
            renderPyramidChart({{$chart->id}},'{{$chart->name}}',{!! json_encode($chart->chart_data) !!},'{{$chart->sum_field}}');
            @endif
            @if($chart->widget_type == 'Donut')
            renderDonutChart({{$chart->id}},'{{$chart->name}}',{!! json_encode($chart->chart_data) !!},'{{$chart->sum_field}}');
            @endif
            @if($chart->widget_type == 'Funnel')
            renderFunnelChart({{$chart->id}},'{{$chart->name}}',{!! json_encode($chart->chart_data) !!},'{{$chart->sum_field}}');
            @endif
            @if($chart->widget_type == 'Speedometer')
            renderSpeedometerChart({{$chart->id}},'{{$chart->name}}',{!! json_encode($chart->chart_data)  !!});
            @endif
            @if($chart->widget_type == 'Grid')
                $.get('/{{$chart->slug}}/minigrid?layout_id={{$chart->id}}', function(data) {
                    $('#chart{{$chart->id}}').html(data);
                });
            @endif
           
           
        @endforeach
        @if(is_superadmin())
        create_widget_context();
        @endif
    }
    
    
    
    //$(document).off('click', '#content_sidebar_dashboard_link').on('click', '#content_sidebar_dashboard_link', function() {
    //    filter_charts_accordion();
    //});
    
    
    function filter_charts_accordion(){
       
        var project_id = false;
        if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
         
            var selected = window['selectedrow_detail{{ $master_grid_id }}'];
            if(selected.project_id){
            var project_id = selected.project_id;
            }
        }else{
          
            var selected = window['selectedrow_{{ $master_grid_id }}'];  
            if(selected.id){
            var project_id = selected.id;
            }
        }
        
       
        if(project_id){
        $(content_charts_accordion.items).each(function(index, el){
            if(el.project_id == project_id){
                content_charts_accordion.enableItem(index,true);
            }else{
                content_charts_accordion.enableItem(index,false);
            }
        });
        }
     
    }
    
    $(document).ready(function() {
        content_charts_accordion = new ej.navigations.Accordion({
            items: {!! json_encode($chart_accordion) !!},
            expandMode: 'Single',
            headerTemplate: '<div class="widget_context" data-attr-id="${id}"  data-attr-layout-link="${layout_link}">${header}</div>',
            created: function(){
                renderCharts();
            },
            expanded: function(args){
                if(args.item.id && charts[args.item.id]){
                    charts[args.item.id].width = '100%';
                    charts[args.item.id].refresh();
                }
            }
        });
        
        //Render initialized Accordion component
        content_charts_accordion.appendTo('#content_charts_accordion'); 
    })


</script>
<!-- charts end -->
@endif

<script>
function refresh_sidebar_files(){
        if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
        var module_id = '{{$detail_module_id}}';
        var selected = window['selectedrow_detail{{ $master_grid_id }}'];
        }else{
        var module_id = '{{$module_id}}';
        var selected = window['selectedrow_{{ $master_grid_id }}']; 
        }
        var selected_id = 0;
        @if($db_table == 'crm_accounts' || $db_table == 'crm_suppliers')
        selected_id = selected.rowId;
        @elseif($communications_type == 'account')
        selected_id = selected.account_id;
        @elseif($communications_type == 'pbx')
        selected_id = selected.domain_uuid;
        @elseif($communications_type == 'supplier')
        selected_id = selected.supplier_id;
        @endif
        
        
        
        $.get('app_sidebar_files_datasource/{{$communications_type}}/'+selected_id, function(data) {
           
            $("#sidebar_files_result").html(data);
        });
    }
function render_sidebar_files_uploader(){
        try{
        if(window['sidebar_filesuploader'] && window['sidebar_filesuploader'].isRendered){
       
            window['sidebar_filesuploader'].destroy();    
        }
        }catch(e){
            
        }
        window['sidebar_filesuploader'] =  new ej.inputs.Uploader({
        asyncSettings: {
        saveUrl: '{{$menu_route}}/addfile',
        },
        htmlAttributes: {name: 'file_name[]'},
        showFileList: true,
        dropArea: document.getElementById('sidebar_droparea'),
        uploading: function(args){
         
            if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
            var module_id = '{{$detail_module_id}}';
            var selected = window['selectedrow_detail{{ $master_grid_id }}'];
            }else{
            var module_id = '{{$module_id}}';
            var selected = window['selectedrow_{{ $master_grid_id }}']; 
            }
           
            var selected_id = selected.rowId;
            @if($db_table == 'crm_accounts' || $db_table == 'crm_suppliers')
            selected_id = selected.rowId;
            @elseif($communications_type == 'account')
            selected_id = selected.account_id;
            @elseif($communications_type == 'supplier')
            selected_id = selected.supplier_id;
            @endif
            
           
            
            if(!selected_id){
                toastNotify('Select a record','warning');
                args.cancel=true;
            }else{
                var upload_module_id = module_id;
                @if($communications_type == 'account')
                var upload_module_id = 343;    
                @endif
                @if($communications_type == 'supplier')
                var upload_module_id = 78;    
                @endif
                
                args.customFormData = [{row_id:selected_id},{module_id: upload_module_id},{communications_type:'{{$communications_type}}'}];
            } 
        },
        success: function(args){
            refresh_sidebar_files();
        },
        failure: function(args){
         
            toastNotify('File upload failed','warning');
        },
        },'#sidebar_fileupload');
       
        // render initialized Uploader
    }
   
    $(document).off('click', '.deletefiletbtn').on('click', '.deletefiletbtn', function() {
      
        var file_id = $(this).attr('data-file-id');
        if(file_id > ''){
               $.ajax({
                url: '/{{$menu_route}}/deletefile',
                type:'post',
                data: {file_id: file_id},
                success: function(data) { 
            refresh_sidebar_files();
                }
              });  
        }
    });
    
  
    
    function create_guides_context(){
        $('body').append('<ul id="contextguides{{$grid_id}}" class="m-0"></ul>');
        var items = [
            {
                id: "guide_add",
                text: "Add",
                iconCss: "fa fa-plus",
            },
            {
                id: "guide_edit",
                text: "Edit",
                iconCss: "fas fa-pen",
            },
            {
                id: "guide_delete",
                text: "Delete",
                iconCss: "fas fa-trash",
            },
            {
                id: "guide_list",
                text: "List",
                iconCss: "fa fa-list",
            },
           
        ];
        var context_guide_id = false;
        var context_guide_projectid = false;
        var menuOptions = {
            target: '.guide_context',
            items: items,
            beforeItemRender: contextmenurender,
            beforeOpen: function(args){
                
                // toggle context items on header
                
                    context_guide_id = $(args.event.target).attr('data-attr-id');
                    context_guide_role_id = $(args.event.target).attr('data-attr-role_id');
                    context_guide_module_id = $(args.event.target).attr('data-attr-module_id');
                
            },
            select: function(args){
               
                if(args.item.id === 'guide_edit') {
                     sidebarform('guide_edit','/{{$guides_url}}/edit/'+context_guide_id, 'Guide Edit');
                }
                if(args.item.id === 'guide_delete') {
                    gridAjaxConfirm('/{{$guides_url}}/delete', 'Delete policy?', {"id" : context_guide_id}, 'post');
                }
                @if($module_id == 1944)
                    if(args.item.id === 'guide_add') {
                         sidebarform('guide_edit','/{{$guides_url}}/edit?role_id='+context_guide_role_id, 'Guide Add');
                    }
                    if(args.item.id === 'guide_list') {
                         viewDialog('guide_edit','/{{$guides_url}}?role_id='+context_guide_role_id, 'Guides');
                    }
                @else
                    if(args.item.id === 'guide_add') {
                         sidebarform('guide_edit','/{{$guides_url}}/edit?module_id='+context_guide_module_id, 'Guide Add');
                    }
                    if(args.item.id === 'guide_list') {
                         viewDialog('guide_edit','/{{$guides_url}}?module_id='+context_guide_module_id, 'Guides');
                    }
                
                @endif
            }
        };
      
        // Initialize ContextMenu control.
        contextguides{{$grid_id}} = new ej.navigations.ContextMenu(menuOptions, '#contextguides{{$grid_id}}');  
    }
    
    function create_interactions_context(){
        $('body').append('<ul id="contextinteractions" class="m-0"></ul>');
        var items = [
            {
                id: "interaction_add",
                text: "Add",
                iconCss: "fa fa-plus",
            },
            {
                id: "interaction_edit",
                text: "Edit",
                iconCss: "fas fa-pen",
            },
            {
                id: "interaction_delete",
                text: "Delete",
                iconCss: "fas fa-trash",
            },
            {
                id: "interaction_list",
                text: "List",
                iconCss: "fa fa-list",
            },
           
        ];
        var context_interaction_id = false;
        var context_interaction_projectid = false;
        var menuOptions = {
            target: '.interaction_context',
            items: items,
            beforeItemRender: contextmenurender,
            beforeOpen: function(args){
                
                // toggle context items on header
                
                    context_interaction_id = $(args.event.target).attr('data-edit_id');
                    context_interaction_route = $(args.event.target).attr('data-route_url');
                
            },
            select: function(args){
               
                if(args.item.id === 'interaction_edit') {
                     sidebarform('interaction_edit','/'+context_interaction_route+'/edit/'+context_interaction_id, 'Edit');
                }
                if(args.item.id === 'interaction_delete') {
                    gridAjaxConfirm('/'+context_interaction_route+'/delete', 'Delete policy?', {"id" : context_interaction_id}, 'post');
                }
                if(args.item.id === 'interaction_add') {
                     sidebarform('interaction_edit','/'+context_interaction_route+'/edit?role_id='+context_interaction_role_id, 'Add');
                }
                if(args.item.id === 'interaction_list') {
                     viewDialog('interaction_edit','/'+context_interaction_route, 'interactions');
                }
            }
        };
      
        // Initialize ContextMenu control.
        contextinteractions = new ej.navigations.ContextMenu(menuOptions, '#contextinteractions');  
    }
    
    @if(is_superadmin() && $module_id == 1944)
    
        context_widget_id = false;
        context_widget_layout_link = false;
        context_widget_projectid = false;
    function create_widget_context(){
        $('body').append('<ul id="contextwidgets" class="m-0"></ul>');
        var items = [
            {
                id: "widget_open",
                text: "Open",
                iconCss: "fas fa-external-link-square-alt",
            },
            {
                id: "widget_list",
                text: "List",
                iconCss: "fa fa-list",
            },
            {
                id: "widget_edit",
                text: "Edit",
                iconCss: "fas fa-pen",
            },
            {
                id: "widget_remove",
                text: "Remove",
                iconCss: "fa fa-trash",
            },
           
        ];
       
        var menuOptions = {
            target: '.widget_context',
            items: items,
            beforeItemRender: contextmenurender,
            beforeOpen: function(args){
                
                // toggle context items on header
                
                    context_widget_id = $(args.event.target).attr('data-attr-id');
                    context_widget_layout_link = $(args.event.target).attr('data-attr-layout-link');
                ////console.log($(args.event.target));
            },
            select: function(args){
               
                if(args.item.id === 'widget_edit') {
                     sidebarform('widget_edit','/{{$layouts_url}}/edit/'+context_widget_id, 'Widget Edit');
                }
                
                if(args.item.id === 'widget_list') {
                     viewDialog('layouts_list','/{{$layouts_url}}?show_on_dashboard=1', 'Layouts');
                }
                
                if(args.item.id === 'widget_remove') {
                    gridAjaxConfirm('/dashboard_widget_remove/'+context_widget_id, 'Remove Widget?');
                }
                
                if(args.item.id === 'widget_open') {
                    ////console.log(context_widget_layout_link);
                    window.open(context_widget_layout_link,'_blank');
                }
                
            }
        };
      
        // Initialize ContextMenu control.
        contextwidgets = new ej.navigations.ContextMenu(menuOptions, '#contextwidgets');  
    }
    
    
    function create_workboard_reports_context(){
        $('body').append('<ul id="workboard_reports_context" class="m-0"></ul>');
        var items = [
            {
                id: "wr_open",
                text: "Open",
                iconCss: "fas fa-external-link-square-alt",
            },
            {
                id: "wr_edit",
                text: "Edit",
                iconCss: "fas fa-pen",
            },
            {
                id: "wr_list",
                text: "List",
                iconCss: "fa fa-list",
            },
           
        ];
       context_wr_id = false;
       context_wr_layout_link = false;
        var menuOptions = {
            target: '.workboardreports_context',
            items: items,
            beforeItemRender: contextmenurender,
            beforeOpen: function(args){
                
                // toggle context items on header
                
                    context_wr_id = $(args.event.target).closest('.e-list-item').attr('data-attr-id');
                    context_wr_layout_link = $(args.event.target).closest('.e-list-item').attr('data-attr-layout-link');
                ////console.log(context_wr_id);
                ////console.log(context_wr_layout_link);
                ////console.log($(args.event.target));
            },
            select: function(args){
               
                if(args.item.id === 'wr_edit') {
                     sidebarform('wr_edit','/{{$layouts_url}}/edit/'+context_wr_id, 'Edit');
                }
                
                if(args.item.id === 'wr_list') {
                     viewDialog('layouts_list','/{{$layouts_url}}?layout_type=Report', 'Layouts');
                }
                
                
                
                if(args.item.id === 'wr_open') {
                    ////console.log(context_wr_layout_link);
                    window.open(context_wr_layout_link,'_blank');
                }
                
            }
        };
      
        // Initialize ContextMenu control.
        workboard_reports_context = new ej.navigations.ContextMenu(menuOptions, '#workboard_reports_context');  
    }
    create_workboard_reports_context();
    
    function remove_chart_accordion(){
       
        if(context_widget_id){
            $(content_charts_accordion.items).each(function(index, el){
       
                if(el.id == context_widget_id){
                    content_charts_accordion.removeItem(index);
                    content_charts_accordion.dataBind();
                    setTimeout(filter_charts_accordion(),2000);
                }
            });
        }
    }
    @endif
    
    @if($communications_panel)
    content_rowinfo_accordion = new ej.navigations.Accordion({
        items: [],
        expandMode: 'Single',
        expanding: function(){
            @if(session('role_level') == 'Admin' && $communications_panel)
            window['sidebar_subscription_info_context'].refresh();
            @endif
        }
    });
    
    //Render initialized Accordion component
    content_rowinfo_accordion.appendTo('#content_rowinfo_accordion'); 
    @endif
    
    
    content_rowhistory_accordion = new ej.navigations.Accordion({
        items: [],
        expandMode: 'Single',
    });
    
    //Render initialized Accordion component
    content_rowhistory_accordion.appendTo('#content_rowhistory_accordion'); 
    
    
     
                
        content_interactions_accordion = new ej.navigations.Accordion({
            items: [],
            expandMode: 'Single',
            @if(session('role_level') == 'Admin')
            created: function(){
                create_interactions_context();
            }
            @endif
        });
        
        //Render initialized Accordion component
        content_interactions_accordion.appendTo('#content_interactions_accordion'); 
 
    
    @if($module_id == 1944 || $has_module_guides)
    /*
    content_sidebar_global_guides_accordion = new ej.navigations.Accordion({
        items: [],
        expandMode: 'Single',
        headerTemplate: '<div class="guide_context" data-attr-role_id="${role_id}" data-attr-module_id="${module_id}" data-attr-id="${id}">${header}</div>',
        @if(is_superadmin())
        created: function(){
           
        },
        @endif
    });
    
    //Render initialized Accordion component
    content_sidebar_global_guides_accordion.appendTo('#content_sidebar_global_guides_accordion');
    */
    content_sidebar_guides_accordion = new ej.navigations.Accordion({
        items: [],
        expandMode: 'Single',
        headerTemplate: '<div class="guide_context" data-attr-role_id="${role_id}" data-attr-module_id="${module_id}" data-attr-id="${id}">${header}</div>',
       
        created: function(){
            create_guides_context();
            $.get('get_sidebar_module_guides/{{$module_id}}', function(data) {
            
                content_sidebar_guides_accordion.items = data.accordion;
                content_sidebar_guides_accordion.refresh();
                /*
                content_sidebar_global_guides_accordion.items = data.global_accordion;
                content_sidebar_global_guides_accordion.refresh();
                */
                @if(is_superadmin())
                contextguides{{$grid_id}}.refresh();
                guides_accordion_sort();
                @endif
                
                contextinteractions.refresh();
            });
        }
    });
    
    //Render initialized Accordion component
    content_sidebar_guides_accordion.appendTo('#content_sidebar_guides_accordion');
   
    @endif
    
 
    
    function guides_accordion_sort(){
       /*
        $("#content_sidebar_global_guides_accordion").sortable({
            containment: "parent",
            handle: '.e-acrdn-header',
            start: function(e) {
            //console.log('start',e);
            },
            stop: function(e) {
              var dataArray = Array.from(document.querySelectorAll('#content_sidebar_global_guides_accordion .guide_context')).filter(e => e.hasAttribute('data-attr-id')).map(e => ({ id: e.getAttribute('data-attr-id'), role_id: e.getAttribute('data-attr-role_id') }));
               

                $.ajax({
                url: '/guides_sort',
                type:'post',
                data: {guides: dataArray},
                success: function(data) { 
                
                
                }
                }); 
            }
        });
        */
        $("#content_sidebar_guides_accordion").sortable({
            containment: "parent",
            handle: '.e-acrdn-header',
            start: function(e) {
            //console.log('start',e);
            },
            stop: function(e) {
              var dataArray = Array.from(document.querySelectorAll('#content_sidebar_guides_accordion .guide_context')).filter(e => e.hasAttribute('data-attr-id')).map(e => ({ id: e.getAttribute('data-attr-id'), role_id: e.getAttribute('data-attr-role_id') }));
               

                $.ajax({
                url: '/guides_sort',
                type:'post',
                data: {guides: dataArray},
                success: function(data) { 
                
                
                }
                }); 
            }
        });
    }

    function get_sidebar_row_info(){
        var project_id = 0;
        if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
            var module_id = '{{$detail_module_id}}';
            var selected = window['selectedrow_detail{{ $master_grid_id }}'];
            var project_id = (selected && selected.project_id) ? selected.project_id : 0;
        }else{
            var module_id = '{{$module_id}}';
            var selected = window['selectedrow_{{ $master_grid_id }}']; 
            var project_id = (selected && selected.id) ? selected.id : 0;
        }
        if(selected){
            $.get('get_sidebar_row_info/'+module_id+'/'+selected.rowId, function(data) {
                //console.log(data);
                @if($communications_panel)
                content_rowinfo_accordion.items = data.rowinfo_accordion;
                content_rowinfo_accordion.refresh();
                if(content_rowinfo_accordion.expandedIndices.length === 0){
                //content_rowinfo_accordion.expandItem(true,0);
                }
                if(data.rowinfo_html){
                    $("#content_rowinfo_html").html(data.rowinfo_html);
                   
                }
                @endif
                
                if(data.linked_records_html){
                    $("#content_linked_records_html").html(data.linked_records_html);
                }else{
                    $("#content_linked_records_html").html('');
                }
                
                content_rowhistory_accordion.items = data.rowhistory_accordion;
                content_rowhistory_accordion.refresh();
                
                @if($module_id == 1923)
                if(data.sales_html){
                    $("#content_interactions_sales").html(data.sales_html);
                }else{
                    $("#content_interactions_sales").html('');
                }
                @endif
               
                content_interactions_accordion.items = data.interactions_accordion;
                content_interactions_accordion.refresh();
                if(content_interactions_accordion.expandedIndices.length === 0){
                //content_interactions_accordion.expandItem(true,0);
                }
                @if(session('role_level') == 'Admin' && $communications_panel)
               
                window['sidebar_customer_info_context'].refresh();
             
                @endif
            });
            
            @if($module_id == 1944)
                
                grid_reports_data = new ej.data.DataManager({
                    url: '/workboard_reports/'+project_id,
                    adaptor: new ej.data.UrlAdaptor(),
                    crossDomain: true,
                });    
        
                
                grid_reports.dataSource = grid_reports_data;
                grid_reports.dataBind();
                
                filter_charts_accordion(project_id);
            
            @endif
            setTimeout(render_sidebar_files_uploader,500)
           
        }
        @if($module_id == 1944 || $has_module_guides)
        $.get('get_sidebar_module_guides/'+module_id, function(data) {
         
            content_sidebar_guides_accordion.items = data.accordion;
            content_sidebar_guides_accordion.refresh();
            /*
            content_sidebar_global_guides_accordion.items = data.global_accordion;
            content_sidebar_global_guides_accordion.refresh();
           */
            @if(is_superadmin())
                contextguides{{$grid_id}}.refresh();
                guides_accordion_sort();
            @endif
            
            contextinteractions.refresh();
        });
        @endif
    }
 
   
    // related modules
    // related_items_menu_menu
 @if(!empty($related_items_menu_menu) && count($related_items_menu_menu) > 0)   
 /*
    var related_items_menuMenuItems = @php echo json_encode($related_items_menu_menu); @endphp;
    //console.log(related_items_menuMenuItems);
    // top_menu initialization
    var related_items_menu{{ $grid_id }} = new ej.navigations.Menu({
        items: related_items_menuMenuItems,
        height: 'auto',
        width: '100%',
        orientation: 'Vertical',
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
    },'#related_items_menu{{ $grid_id }}');
    */
    @endif
  
    

    
    var grid_views_data = new ej.data.DataManager({
        url: '/content_sidebar_grids/{{$module_id}}/Layout',
        adaptor: new ej.data.UrlAdaptor(),
        crossDomain: true,
    });
    
    // initialize ListBox component
    var grid_views = new ej.dropdowns.ListBox({
        cssClass: 'layouts_list',
        @if(is_superadmin())
        allowDragAndDrop: true,
        @endif
        dataSource: grid_views_data,
        beforeItemRender: function(args){ 
            $(args.element).addClass(args.item.cssClass); 
            if(window['layout_id{{ $grid_id }}'] == args.item.id){
                $(args.element).addClass('e-selected'); 
            }
            $.each(args.item.htmlAttributes, function(k, v){
                $(args.element).attr(k,v); 
            });
        },
        actionComplete: function(args){
            // drag and drop
            ////console.log('actionComplete');
            ////console.log(args);
        },
        created: function(args){
            
            create_layouts_context{{$master_grid_id}}();
        },
        dataBound: function(args){
            ////console.log('dataBound');
            refresh_layout_context_menus{{$grid_id}}();
        },
        deselectList: function(){
            grid_views.selectAll(false);
        },
        change: function(args){
            // unselect reports list
            if(args.event && grid_reports){
                grid_reports.deselectList();
            }
        }
    });
    grid_views.appendTo('#grid_views');
    
    @if($module_id == 1944)
    
        grid_reports_data = new ej.data.DataManager({
            url: '/workboard_reports',
            adaptor: new ej.data.UrlAdaptor(),
            crossDomain: true,
        });
        
        // initialize ListBox component
        grid_reports = new ej.dropdowns.ListBox({
            fields: { groupBy: 'module', text: 'text', value: 'value' },
            dataSource: grid_reports_data,
            beforeItemRender: function(args){ 
              
                $(args.element).find('a').attr('target','_blank'); 
                $(args.element).addClass(args.item.cssClass); 
                if(window['layout_id{{ $grid_id }}'] == args.item.id){
                    $(args.element).addClass('e-selected'); 
                }
                $.each(args.item.htmlAttributes, function(k, v){
                    $(args.element).attr(k,v); 
                });
                
            },
            actionComplete: function(args){
                //console.log('actionComplete',args);
            },
            created: function(){
                
            },
            deselectList: function(){
                grid_reports.selectAll(false);
            },
            change: function(args){
                // unselect reports list
                if(args.event && grid_views){
                    grid_views.deselectList();
                }
            },
            dataBound: function(){
             workboard_reports_context.refresh();
            }
        });
        grid_reports.appendTo('#grid_reports');
    @else
        var grid_reports_data = new ej.data.DataManager({
            url: '/content_sidebar_grids/{{$module_id}}/Report',
            adaptor: new ej.data.UrlAdaptor(),
            crossDomain: true,
        });
        
        // initialize ListBox component
        var grid_reports = new ej.dropdowns.ListBox({
            cssClass: 'layouts_list',
            @if(is_superadmin())
            allowDragAndDrop: true,
            @endif
            dataSource: grid_reports_data,
            beforeItemRender: function(args){ 
                $(args.element).addClass(args.item.cssClass); 
                if(window['layout_id{{ $grid_id }}'] == args.item.id){
                    $(args.element).addClass('e-selected'); 
                }
                $.each(args.item.htmlAttributes, function(k, v){
                    $(args.element).attr(k,v); 
                });
            },
            actionComplete: function(args){
                // drag and drop
                ////console.log('actionComplete');
                ////console.log(args);
            },
            created: function(){
                setTimeout(function(){contextlayouts{{ $grid_id }}.refresh();},1000)
            },
            deselectList: function(){
                grid_reports.selectAll(false);
            },
            change: function(args){
                // unselect reports list
                if(args.event && grid_views){
                    grid_views.deselectList();
                }
            },
            dataBound: function(args){
              
                refresh_layout_context_menus{{$grid_id}}();
            }
        });
        grid_reports.appendTo('#grid_reports');
    @endif
    
    /*
    var grid_dashboards_data = new ej.data.DataManager({
        url: '/content_sidebar_grids/{{$module_id}}/dashboard',
        adaptor: new ej.data.UrlAdaptor(),
        crossDomain: true,
    });
    
    // initialize ListBox component
    var grid_dashboards = new ej.dropdowns.ListBox({
        @if(is_superadmin())
        allowDragAndDrop: true,
        @endif
        dataSource: grid_dashboards_data,
        beforeItemRender: function(args){ 
            $(args.element).addClass(args.item.cssClass); 
            $.each(args.item.htmlAttributes, function(k, v){
                $(args.element).attr(k,v); 
            });
        },
        actionComplete: function(args){
            // drag and drop
            //console.log('actionComplete');
            //console.log(args);
        }
    });
    grid_dashboards.appendTo('#grid_dashboards');*/
    
    function refresh_content_sidebar_layouts{{$grid_id}}(){
      
        grid_views.refresh();
        grid_reports.refresh();
        //grid_dashboards.refresh();
    }
    
    $(document).on('click','.grid_layout',function(e){
       var layout_id = $(this).attr('data-view_id');
       layout_load{{$grid_id}}(layout_id);
    });
    
   
    
 
    @if($layout_filter_user)
   
    window['layout_filter_user_{{ $grid_id }}'] = new ej.dropdowns.DropDownList({
    	dataSource: {!! json_encode($layout_user_datasource) !!},
        placeholder: 'Filter user',
        popupWidth: 'auto',
        //Set true to show header title
        select: function(args){
          
            
            // Get a reference to the filter instance
            var filterInstance = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterInstance('join_user_id'); 
            
           
            // Set the filter model
            filterInstance.setModel({
                filterType: 'set',
                values: [args.itemData.text],
            });
            
            // Tell grid to run filter operation again
            window['grid_{{ $grid_id }}'].gridOptions.api.onFilterChanged();
        }
    }, '#layout_filter_user{{ $grid_id }}');
 
    @endif
    

    
  
    
    
    
    $(document).off('click', '#addnotebtn').on('click', '#addnotebtn', function() {
          if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
            var module_id = '{{$detail_module_id}}';
            var selected = window['selectedrow_detail{{ $master_grid_id }}'];
            }else{
            var module_id = '{{$module_id}}';
            var selected = window['selectedrow_{{ $master_grid_id }}']; 
            }
            var selected_id = 0;
            if(selected && selected.rowId){
                selected_id = selected.rowId;
            }
        
        if(!selected_id){
            
            toastNotify('Select a record','warning');
        }else{
          
            var note = $("#sidebar_note").val();
            if(note > ''){
                
         
                   $.ajax({
                    url: '/{{$menu_route}}/addnote',
                    type:'post',
                    data: {note:note,module_id: module_id, row_id:selected_id},
                    success: function(data) { 
                       
                        $("#sidebar_note").val('');
                        get_sidebar_row_info();
                    }
                  });  
            }else{
                toastNotify('Note field cannot be blank','warning');
            }
        }   
    });
    
    $(document).off('click', '.deletenotebtn').on('click', '.deletenotebtn', function() {

        var note_id = $(this).attr('data-note-id');
        if(note_id > ''){
            $.ajax({
                url: '/{{$menu_route}}/deletenote',
                type:'post',
                data: {note_id: note_id},
                success: function(data) { 
   
                    get_sidebar_row_info();
                }
            });  
        }
    });
    
    function get_favorites_list(){
       
        $.get('get_favorites_list', function(data) {
            $("#favorites-list").html(data);
        });
    }
    
    $(document).off('click', '#favorites_link_add').on('click', '#favorites_link_add', function() {
          
      
        var link_url = $("#favorite_link").val();
      
        if(link_url > ''){
               $.ajax({
                url: '/favorite_add',
                type:'post',
                data: {link_url:link_url},
                success: function(data) { 
    
                    $("#favorite_link").val('');
                    get_favorites_list();
                }
              });  
        }else{
            toastNotify('Link cannot be blank','warning');
        }
    });
    
     $(document).off('click', '#favorites_current_add').on('click', '#favorites_current_add', function() {
          
        var layout_id = window['layout_id{{ $grid_id }}'];
        if(layout_id > ''){
               $.ajax({
                url: '/favorite_add',
                type:'post',
                data: {layout_id:layout_id},
                success: function(data) { 
                    $("#favorite_link").val('');
                    get_favorites_list();
                }
              });  
        }else{
            toastNotify('layout_id not set','warning');
        }
    });
    
    $(document).off('click', '.favorites_delete').on('click', '.favorites_delete', function() {

        var link_id = $(this).attr('data-link-id');
        if(link_id > ''){
            $.ajax({
                url: '/favorite_delete',
                type:'post',
                data: {link_id: link_id},
                success: function(data) { 
   
                    get_favorites_list();
                }
            });  
        }
    });
    
    $(function(){
    //get_favorites_list();
    initNavs();
    })
     
 
</script>
<script>

/*
Call center functions
*/

    $(document).off('click', '#call_completed').on('click', '#call_completed', function() {
       
        var id = $(this).attr('data-call-id');
      
        $.ajax({
            type: 'get',
            url: 'call_center_call_completed/'+id,
            success: function (data){
      
                if(data.status == 'success'){
                    $("#call_form_container").removeClass('d-none');
                }else{
                    toastNotify(data.message,data.status);
                }
            }
        })
    });
    
    $(document).off('click', '#call_submit').on('click', '#call_submit', function() {
      
        var id = $(this).attr('data-call-id');
      
         var comments = $("#call_comments").val();
        if(comments.length < 5){
            toastNotify('A detailed comment is required. Enter at least 10 characters','warning');
            return false;
        }
        var data = {id: id, call_comments: $("#call_comments").val(), call_status: $("#call_status").val()};
       
        $.ajax({
            type: 'post',
            data: data,
            url: 'call_center_queue_next',
            success: function (data){
              
                if(data.status == 'success'){
                    selectNextCallInQueue();
                }else{
                    toastNotify(data.message,data.status);
                }
            }
        })
    });
    
    function checkCallCompleted(id){
        
        $.ajax({
            type: 'get',
            url: 'call_center_call_completed/'+id,
            success: function (data){
          
                if(data.status == 'success'){
                    $("#call_form_container").removeClass('d-none');
                }else{
                    toastNotify(data.message,data.status);
                }
            }
        })
    }
    
    function queueNextCall(id){
        var comments = $("#call_comments").val();
        if(comments.length < 5){
            toastNotify('A detailed comment is required. Enter at least 10 characters','warning');
            return false;
        }
        var data = {id: id, call_comments: $("#call_comments").val(), call_status: $("#call_status").val()};
       
        $.ajax({
            type: 'post',
            data: data,
            url: 'call_center_queue_next',
            success: function (data){
              
                if(data.status == 'success'){
                    selectNextCallInQueue();
                }else{
                    toastNotify(data.message,data.status);
                }
            }
        })
    }
    
    function selectNextCallInQueue(){
     
       window['grid_{{ $master_grid_id }}'].gridOptions.refresh();
    }
    
    
    
    
</script>

<!-- sidebar nav visibility -->
<script>

  
    $(document).off('click', '#content_sidebar_dashboard_li').on('click', '#content_sidebar_dashboard_li', function() {
        load_charts_ajax();
    });
    
    @if(is_superadmin())
    
    // TABS DRAG AND DROP
    var isDragging = false;
    var dragThreshold = 10; // Adjust this threshold as needed
    $("#right_sidebar_ul").sortable({
        containment: "parent",
        axis: "x",
        start: function(e, ui) {
            // Reset isDragging flag on each drag start
            isDragging = false;
        },
        stop: function(e, ui) {
            // If the drag stopped and it was a drag (not a click), handle it here
            if (isDragging) {
                // Handle drag behavior
                ////console.log("Dragged and dropped");
                save_sidebar_nav_order();
            }
        },
        // Update isDragging flag based on mouse movement distance
        change: function(e, ui) {
            if (!isDragging) {
                // Calculate the horizontal distance moved during the drag
                var dx = Math.abs(ui.position.left - ui.originalPosition.left);
                // Check if the distance exceeds the threshold
                if (dx >= dragThreshold) {
                    isDragging = true;
                }
            }
        }
    });
    
    function save_sidebar_nav_order(){
        var dataArray = [];
        $("#right_sidebar_ul li").each(function () {
            dataArray.push({
                id: $(this).attr('id'),
                hidden: $(this).hasClass('d-none') ? 1 : 0
            });
        });
      
        sidebar_state = JSON.stringify(dataArray);
        ////console.log(sidebar_state);
        window['sidebarNavOrder'] = sidebar_state;
        // Send AJAX request to save the order and visibility to the database
        $.ajax({
            type: 'POST',
            url: 'save_sidebar_state', // Your server-side script to handle the saving
            data: { module_id: {{$module_id}}, sidebar_state: sidebar_state },
            success: function (response) {
                ////console.log('Order and visibility saved successfully');
            },
            error: function (xhr, status, error) {
                //console.error('Error saving order and visibility:', error);
            }
        });
        
    }
    
    $(document).ready(function() {
        // CONTEXT MENU FOR VISIBILITY
        $('body').append('<ul id="sidebar_nav_context_el" class="m-0"></ul>');
        // Initialize Syncfusion window['sidebar_nav_context']
        window['sidebar_nav_context'] = new ej.navigations.ContextMenu({
            target: '#right_sidebar_ul',
            items: getMenuItems(), // Get menu items dynamically
            select: onMenuItemSelect // Event handler for menu item selection
        });
        window['sidebar_nav_context'].appendTo('#sidebar_nav_context_el');
    });
    // Function to dynamically generate menu items based on li elements
    function getMenuItems() {
        var menuItems = [];
    
        // Iterate over each li item
        $('#right_sidebar_ul li').each(function () {
            var itemId = $(this).attr('id');
            var itemName = $(this).attr('name');
            var isVisible = !$(this).hasClass('d-none'); // Check visibility
    
            // Add menu item for each li item
            menuItems.push({
                text: itemName,
                id: itemId,
                iconCss: isVisible ? 'e-icons e-check' : '',
                items: [] // Submenu items if needed
            });
        });
    
        return menuItems;
    }
    
    // Event handler for menu item selection
    function onMenuItemSelect(args) {
        var targetId = args.item.id;
        var targetElement = $('#' + targetId);
    
        // Toggle visibility based on current visibility state
        if (targetElement.hasClass('d-none')) {
            targetElement.removeClass('d-none');
            args.item.iconCss = 'e-icons e-check'; // Update icon to checked
        } else {
            targetElement.addClass('d-none');
            args.item.iconCss = ''; // Update icon to empty (unchecked)
        }
    
        // Refresh the context menu to reflect changes
        if(window['sidebar_nav_context']){
        window['sidebar_nav_context'].refresh();
        save_sidebar_nav_order();
        }
    }
    
    @endif
    
    function load_sidebar_nav_order() {
        window['sidebarNavOrder'] = [];
        @if(!empty($sidebar_state))
        window['sidebarNavOrder'] = JSON.parse('{!! $sidebar_state !!}');
        @endif
        window['sidebarNavOrder'].forEach(function (item) {
            var $item = $("#" + item.id);
            $item.detach().appendTo("#right_sidebar_ul");
    
            // Check if the item should be hidden based on the new dataArray
            if (item.hidden === 1) {
                $item.addClass('d-none'); // Add the d-none class to hide the item
            } else {
                $item.removeClass('d-none'); // Remove the d-none class to show the item
            }
        });
        //$("#right_sidebar_ul").find('.nav-item').first().click();
        //$("#right_sidebar_ul").find('.nav-item').first().addClass('show active');
        //var tab = $("#right_sidebar_ul").find('a').first().attr('href');
        //$(tab).addClass('show active');
        $('#right_sidebar_ul li:first-child a').tab('show'); 
    }
    
    $(document).ready(function() {
        load_sidebar_nav_order(); 
        
        @if(!empty($aggrid_charts))
        load_charts_ajax();
        @endif
    });
</script>
<script>
@if(session('role_level') == 'Admin' && $communications_panel)
    $(document).ready(function() {
        // CONTEXT MENU FOR VISIBILITY
        $('body').append('<ul id="sidebar_customer_info_context_el" class="m-0"></ul>');
        // Initialize Syncfusion window['sidebar_nav_context']
        window['sidebar_customer_info_context'] = new ej.navigations.ContextMenu({
            target: '.sidebar_account_info',
            items: [
                {
                    id: "sci_edit",
                    text: "Edit",
                },
                {
                    id: "sci_quote",
                    text: "Create quote",
                },
                {
                    id: "sci_documents",
                    text: "Documents",
                },
                {
                    id: "sci_statement",
                    text: "Statement",
                },
                {
                    id: "sci_complete_statement",
                    text: "Complete Statement",
                },
                {
                    id: "sci_email_statement",
                    text: "Email Statement",
                },
                {
                    id: "sci_reset",
                    text: "Reset and send password",
                },
                {
                    id: "sci_cancel",
                    text: "Cancel account",
                },
            ],
            beforeItemRender: contextmenurender,
            beforeOpen: function(args){
                
                context_accountid{{$grid_id}} = $(args.event.target).closest('#sidebar_account_info').attr('data-id');
                context_partnerid{{$grid_id}} = $(args.event.target).closest('#sidebar_account_info').attr('data-partner-id');
                
                if(context_partnerid{{$grid_id}} == 1){
                    window['sidebar_customer_info_context'].enableItems(['Cancel account'], true);
                }else{
                    window['sidebar_customer_info_context'].enableItems(['Cancel account'], false);
                }
              
            },
            select: function(args){
               
                if(args.item.text === 'Edit'){
                    sidebarform(args.item.id,'{{$accounts_url}}/edit/'+context_accountid{{$grid_id}});
                }
               
                if(args.item.text === 'Create quote'){
                    if(context_partnerid{{$grid_id}} == 1){
                    sidebarform(args.item.id,'{{$documents_url}}/edit?doctype=Quotation&account_id='+context_accountid{{$grid_id}});
                    }else{
                    sidebarform(args.item.id,'{{$documents_url}}/edit?doctype=Quotation&account_id='+context_partnerid{{$grid_id}});
                    }
                }
                if(args.item.text === 'Documents'){
                    if(context_partnerid{{$grid_id}} == 1){
                    window.open('{{$documents_url}}?account_id='+context_accountid{{$grid_id}});
                    }else{
                    window.open('{{$documents_url}}?reseller_user='+context_accountid{{$grid_id}});
                    }
                    
                }
                if(args.item.text === 'Statement'){
                    if(context_partnerid{{$grid_id}} == 1){
                    viewDialog(args.item.id,'statement_pdf/'+context_accountid{{$grid_id}});
                    }else{
                    viewDialog(args.item.id,'statement_pdf/'+context_partnerid{{$grid_id}});
                    }
                    
                }
                if(args.item.text === 'Complete Statement'){
                    if(context_partnerid{{$grid_id}} == 1){
                    viewDialog(args.item.id,'full_statement_pdf/'+context_accountid{{$grid_id}});
                    }else{
                    viewDialog(args.item.id,'full_statement_pdf/'+context_partnerid{{$grid_id}});
                    }
                    
                }
                if(args.item.text === 'Email Statement'){
                    if(context_partnerid{{$grid_id}} == 1){
                    sidebarform(args.item.id,'email_form/statement_email/'+context_accountid{{$grid_id}});
                    }else{
                    sidebarform(args.item.id,'email_form/statement_email/'+context_partnerid{{$grid_id}});
                    }
                    
                }
                if(args.item.text === 'Reset and send password'){
                    gridAjax('send_user_password/'+context_accountid{{$grid_id}});
                }
                if(args.item.text === 'Cancel account'){
                    cancelAccount(context_accountid{{$grid_id}});
                }
               
            }
        });
        window['sidebar_customer_info_context'].appendTo('#sidebar_customer_info_context_el');
    });
@endif

@if(session('role_level') == 'Admin' && $communications_panel)
    $(document).ready(function() {
        // CONTEXT MENU FOR VISIBILITY
        $('body').append('<ul id="sidebar_subscription_info_context_el" class="m-0"></ul>');
        // Initialize Syncfusion window['sidebar_nav_context']
        window['sidebar_subscription_info_context'] = new ej.navigations.ContextMenu({
            target: '.sidebar_subscription_info',
            items: [
                {
                    id: "ssi_setup",
                    text: "Send Setup Email",
                },
                {
                    id: "ssi_view",
                    text: "View",
                },
                {
                    id: "ssi_migrate",
                    text: "Migrate",
                },
                {
                    id: "ssi_cancel",
                    text: "Cancel",
                },
                {
                    id: "ssi_cancel",
                    text: "Undo Cancel",
                },
                
            ],
            beforeItemRender: contextmenurender,
            beforeOpen: function(args){
                
                context_accountid{{$grid_id}} = $(args.event.target).closest('.sidebar_subscription_info').attr('data-account-id');
                context_subid{{$grid_id}} = $(args.event.target).closest('.sidebar_subscription_info').attr('data-sub-id');
                context_tocancel{{$grid_id}} = $(args.event.target).closest('.sidebar_subscription_info').attr('data-tocancel');
                
                if(context_tocancel{{$grid_id}} == 1){
                    window['sidebar_subscription_info_context'].enableItems(['Undo Cancel'], true);
                    window['sidebar_subscription_info_context'].enableItems(['Cancel'], false);
                }else{
                    window['sidebar_subscription_info_context'].enableItems(['Undo Cancel'], false);
                    window['sidebar_subscription_info_context'].enableItems(['Cancel'], true);
                }
              
            },
            select: function(args){
    
                if(args.item.text === 'Send Setup Email'){
                    sidebarform(args.item.id,'service_setup_email/'+context_subid{{$grid_id}});
                }
               
                if(args.item.text === 'View'){
                     window.open('{{$subscriptions_url}}?id='+context_subid{{$grid_id}});
                }
                if(args.item.text === 'Migrate'){
                    sidebarform(args.item.id,'subscription_migrate_form/'+context_subid{{$grid_id}});
                }
                if(args.item.text === 'Cancel'){
                    gridAjaxConfirm('/{{ $subscriptions_url }}/cancel?id='+context_subid{{$grid_id}}, 'Cancel Subscription?');
                }
                if(args.item.text === 'Undo Cancel'){
                    gridAjaxConfirm('/{{ $subscriptions_url }}/restore_subscription/'+context_subid{{$grid_id}}, 'Cancel Subscription?');
                }
            }
        });
        window['sidebar_subscription_info_context'].appendTo('#sidebar_subscription_info_context_el');
    });
@endif
</script>
@endpush

@push('page-styles')
<style>
#content_charts_accordion.e-accordion .e-acrdn-item .e-acrdn-panel .e-acrdn-content {
    padding: 0 !important;
}
.widget_type-Grid{
    min-height:500px;
    height: 500px;
}



#content_charts_accordion .e-acrdn-item.e-overlay{
    display:none !important;
}
#content_sidebar .tab-pane {
    height: calc(100vh - 210px) !important;
    max-height: calc(100vh - 210px) !important;
    overflow-y: auto !important;
}

#content_sidebar .e-acrdn-header{
    padding: 0 16px;
    min-height: 26px !important;
    max-height: 26px !important;
    height: 26px !important;
    line-height: 26px !important;
    margin: 0;
}

#content_sidebar .e-toggle-icon{
    
    min-height: 26px !important;
    max-height: 26px !important;
    height: 26px !important;
}

#content_sidebar{
font-size:12px;
}
#content_sidebar .e-acrdn-header-content{

    font-size:12px !important;
}

#content_sidebar .e-acrdn-content, #content_sidebar .e-acrdn-content strong, #content_sidebar .e-acrdn-content p, #content_sidebar .e-acrdn-content h1
, #content_sidebar .e-acrdn-content h2, #content_sidebar .e-acrdn-content h3, #content_sidebar .e-acrdn-content h4, #content_sidebar .e-acrdn-content h5
, #content_sidebar .e-acrdn-content h6{

    font-size:12px !important;
}

#content_sidebar .card-body, #content_sidebar .card-body strong, #content_sidebar .card-body p, #content_sidebar .card-body h1
, #content_sidebar .card-body h2, #content_sidebar .card-body h3, #content_sidebar .card-body h4, #content_sidebar .card-body h5
, #content_sidebar .card-body h6{

    font-size:12px !important;
}

#content_sidebar .e-list-item{
    
    padding: 0 16px;
    height: 26px !important;
    line-height: 26px !important;
    font-size:12px;
}
</style>
@endpush