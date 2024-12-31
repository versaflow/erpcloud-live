

<div id='chart_accordion'>  
@foreach($charts as $chart)
   <div class="chartproject chartproject{{$chart->project_id}}">     
        <div>   
            <div> {{$chart->name}} </div>
        </div>
        <div> 
            <div id="chartcontent{{$chart->id}}"> 
                <div id="chart{{$chart->id}}"> </div>
            </div> 
         </div>
    </div>
@endforeach
</div>

<script>

    $(function(){
        ej.base.enableRipple(true);
        
        //Initialize Accordion component
        
        chart_accordion = new ej.navigations.Accordion({},'#chart_accordion');
        
    })
    
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

$(document).ready(function() {
    renderCharts();
})
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
       
    @endforeach
}
function resizeCharts(){
    //console.log(charts);

    $(Object.keys(charts)).each(function(index, key){
    
        charts[key].refresh(); 
    });
}

$(document).off('click', '#content_sidebar_dashboard_link').on('click', '#content_sidebar_dashboard_link', function() {
    resizeCharts();
});

</script>

<style>

</style>
