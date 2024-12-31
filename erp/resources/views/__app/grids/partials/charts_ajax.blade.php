<!-- Cards with badge -->
@if(!empty($aggrid_charts))

<div class="row pt-2 pb-1 px-2 gx-2" id="aggrid_charts{{$module_id}}" class="pt-1" >
@foreach($aggrid_charts as $i => $chart)
<div class="col-lg-12 mb-1 aggrid-charts-col" id="aggrid-charts-col{{$chart->id}}"  data-attr-id="{{$chart->id}}" data-attr-role_id="{{$chart->chart_role_id}}">
    
<div class="card h-100 mb-1 mx-0 border">
<div class="card-header py-2 border-bottom aggrid-charts{{$grid_id}} fs-6 fw-bold aggrid-chart-header d-flex justify-content-between align-items-center" data-attr-id="{{$chart->id}}" data-attr-link="{{$chart->layout_url}}" data-attr-edit-link="{{$chart->edit_url}}">
<span style="font-size: 12px;">{{$chart->name}}</span>
<div><button class="btn btn-xs mb-0" onclick="createModalFromDiv('aggrid-card-body{{$chart->id}}','{{$chart->name}}')"><i class="fas fa-external-link-alt"></i></button>
<button class="btn btn-xs mb-0 chart-toggle-btn"><i class="far fa-caret-square-down"></i></button></div>
</div>
<div id="aggrid-card-body{{$chart->id}}" class="card-body p-0 " style="display:none">
<div id="aggrid-chart{{$chart->id}}" class="ag-theme-alpine aggrid-chart d-none">
</div>
<div id="aggrid-chart-loader{{$chart->id}}" class="aggrid-chart-loader">
    <div class="ph-item">
        <div class="ph-col-12">
            <div class="ph-picture"></div>
                <div class="ph-row">
                    <div class="ph-col-12"></div>
                    <div class="ph-col-12"></div>
                    <div class="ph-col-12"></div>
                </div>
        </div>
    </div>
</div>
</div>
    
</div>
<div id="aggrid-container{{$chart->id}}" data-attr-chart-route="{{$chart->url}}" class="aggrid-container d-none"></div>
</div>

@endforeach
</div>
@endif