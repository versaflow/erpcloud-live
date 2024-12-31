<!-- Cards with badge -->
@if(!empty($aggrid_charts))
<div class="row gx-4 mx-4" id="chart_row">
    @foreach($aggrid_charts as $aggrid_chart)
    <div class="col col-lg-6 mb-4 chart-col" id="chart-col{{$aggrid_chart->id}}">
    <div class="card border chart-card" id="chart-card{{$aggrid_chart->id}}">
    <div class="card-header border-bottom py-2">{{$aggrid_chart->text}}</div>
    <div class="card-body">
        <div id="aggrid-container{{$aggrid_chart->id}}" class="aggrid_chart" @if(!$aggrid_chart->is_chart) style="height:300px" @endif data-is_chart="{{$aggrid_chart->is_chart}}" data-route="{{$aggrid_chart->slug}}" data-id="{{$aggrid_chart->id}}"  data-layout_url="{{$aggrid_chart->layout_url}}" data-edit_url="{{$aggrid_chart->edit_url}}">
        </div>
        <div id="aggrid-chart{{$aggrid_chart->id}}">
        </div>
    </div>
    </div>
    </div>
    @endforeach
</div>
@endif
<!--/ Cards with badge -->