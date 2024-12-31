
<!-- Cards with badge -->
@if(!empty($aggrid_charts))
    @php
    $html = '';
    $row = 0;
    $col_count = 0;
    @endphp
    
    @foreach($aggrid_charts as $i => $aggrid_chart)
    <div id="chartpanel{{$i}}" class="e-panel" data-row="{{$row}}" data-col="{{$col_count}}" data-sizeX="3" data-sizeY="2">
    <div class="e-panel-container">
    <div class="card chart-card" id="chart-card{{$aggrid_chart->id}}" style="border: none;box-shadow: none;">
    <div class="card-header border-bottom py-2">{{$aggrid_chart->text}}</div>
    <div class="card-body">
        <div id="aggrid-container{{$aggrid_chart->id}}" class="aggrid_chart" @if(!$aggrid_chart->is_chart) style="height:300px" @endif data-is_chart="{{$aggrid_chart->is_chart}}" data-route="{{$aggrid_chart->slug}}" data-id="{{$aggrid_chart->id}}"  data-layout_url="{{$aggrid_chart->layout_url}}" data-edit_url="{{$aggrid_chart->edit_url}}">
        </div>
        <div id="aggrid-chart{{$aggrid_chart->id}}">
        </div>
    </div>
    </div>
    </div>
    </div>
    
    @php
    
    $html .= '
    <div id="chartpanel'.$i.'" class="e-panel" data-row="'.$row.'" data-col="'.$col_count.'" data-sizeX="3" data-sizeY="2">
    <div class="e-panel-container">
    <div class="card border chart-card" id="chart-card'.$aggrid_chart->id.'">
    <div class="card-header border-bottom py-2">'.$aggrid_chart->text.'</div>
    <div class="card-body">
        <div id="aggrid-container'.$aggrid_chart->id.'" class="aggrid_chart" @if(!$aggrid_chart->is_chart) style="height:300px" @endif data-is_chart="'.$aggrid_chart->is_chart.'" data-route="'.$aggrid_chart->slug.'" data-id="'.$aggrid_chart->id.'"  data-layout_url="'.$aggrid_chart->layout_url.'" data-edit_url="'.$aggrid_chart->edit_url.'">
        </div>
        <div id="aggrid-chart'.$aggrid_chart->id.'">
        </div>
    </div>
    </div>
    </div>
    </div>';
    
    @endphp
    
    @php
    $col_count+=3;
    if($i%2 != 0 && $i>0){
    $row++;
    $col_count = 0;
    }
 
    @endphp
    @endforeach
    
    @php
    aa($html);
    @endphp
@endif
<!--/ Cards with badge -->