
<!-- Cards with badge -->
@if(!empty($role_charts))
   
    
    @foreach($role_charts as $i => $aggrid_chart)
   
    <div id="chartpanel{{$aggrid_chart->id}}" class="e-panel @if($aggrid_chart->enabled === 0) e-disabled @endif" data-row="{{$aggrid_chart->row}}" data-col="{{$aggrid_chart->col}}" data-sizeX="{{$aggrid_chart->sizex}}" data-sizeY="{{$aggrid_chart->sizey}}">
    <div class="e-panel-container">
    <div class="card chart-card" id="chart-card{{$aggrid_chart->instance_id}}{{$aggrid_chart->id}}" style="border: none;box-shadow: none;">
    <div class="card-header border-bottom py-2">{{$aggrid_chart->text}}</div>
    <div class="card-body">
        <div id="aggrid-container{{$aggrid_chart->instance_id}}{{$aggrid_chart->id}}" class="aggrid_chart aggrid_chart{{$aggrid_chart->role_id}}"  style="height:400px" data-instance_id="{{$aggrid_chart->instance_id}}" data-cidb="{{$aggrid_chart->cidb}}"  data-is_chart="{{$aggrid_chart->is_chart}}" data-route="{{$aggrid_chart->slug}}" data-id="{{$aggrid_chart->id}}"  data-layout_url="{{$aggrid_chart->layout_url}}" data-edit_url="{{$aggrid_chart->edit_url}}">
        </div>
        <div id="aggrid-chart{{$aggrid_chart->id}}" >
        </div>
    </div>
    </div>
     <div class="row text-end m-0 p-0"><span class="px-4" id="timetoload{{$aggrid_chart->instance_id}}{{$aggrid_chart->id}}"></span></div>
    </div>
    </div>
    @endforeach
@endif
<!--/ Cards with badge -->