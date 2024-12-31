@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
@endif
@section('styles')
@parent
<link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
@endsection
@section('content')

<div class="container-fluid bg-white" style="height: 100% !important;overflow-y: scroll;" id="dashboard_container">
 
        
        @foreach($instances as $instance)
        
        <div class="py-4">
        
        <div class="row px-4">
        <h1 class="w-100 mb-0">{{$instance->name}}</h1>
        <hr class="mt-1 mb-4 w-100">
        </div>
        @if($instance->id == 1)
        <div class="row  px-4">
        @if(count($user_stats) > 0)
        @foreach ($user_stats as $user_stat)
        <div class="col-sm p-0">
        <div class="card">
            <div class="card-header">{{$user_stat->username}}</div>
            <div class="card-body py-2 px-3">
            <p class="text-muted">Start Time: {{$user_stat->start_time}}<br>
            Total Completed Tasks: {{$user_stat->completed}}<br>
            Hours Tracked: {{$user_stat->hours_spent}}<br>
            Project tasks: {{$user_stat->project_tasks_total}}<br>
            Project tasks completed: {{$user_stat->project_tasks_completed}}
            </p>
            <div class="progress">
            <div class="progress-bar" role="progressbar" style="width: {{$user_stat->complete_percentage}}%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">{{$user_stat->complete_percentage}}%</div>
            </div>
            </div>
        </div>
        </div>
        @endforeach
        @endif
        </div>
        @endif
        
        <div class="row px-4">
        <h4 class="w-100 mt-2">Processes</h4>
        <hr class="mt-1 mb-4 w-100">
        </div>
        <div class="row px-4">
        @if(count($instance->processes) > 0)
        @foreach ($instance->processes as $process)
        <div class="col-sm p-0">
        <div class="card">
            <div class="card-header bg-primary text-white">{{$process->name}}</div>
            <div class="card-body py-2 px-3">
            <h6>{{$process->completed}}/{{$process->total}} Completed</h6>
             <a href="{{$process->link}}" class="hide-link stretched-link" target="_blank">Open</a>
            </div>
        </div>
        </div>
        @endforeach
        @endif
        </div>
        
        <div class="row px-4">
        <h4 class="w-100 mt-2">KPI</h4>
        <hr class="mt-1 mb-4 w-100">
        </div>
        <div class="row px-4">
        @if(count($instance->kpis) > 0)
        @foreach ($instance->kpis as $kpi)
        <div class="col-sm p-0">
        <div class="card kpicard" data-ledger-link="{{$kpi->ledger_link}}" data-trx-link="{{$kpi->trx_link}}">
            <div class="card-header bg-primary text-white">{{$kpi->name}}</div>
            <div class="card-body py-2 px-3">
            <h6>{{$kpi->total}}</h6>
             <a href="{{$kpi->layout_link}}" class="hide-link stretched-link" target="_blank">Open</a>
            </div>
        </div>
        </div>
        @endforeach
        @endif
        </div>
        <div class="row px-4">
        <h4 class="w-100 mt-4">Current Assets</h4>
        <hr class="mt-1 mb-4 w-100">
        </div>
        <div class="row px-4">
        @if(count($instance->assets) > 0)
        @foreach ($instance->assets as $asset)
        <div class="col-sm-3 p-0">
        <div class="card kpicard"  data-ledger-link="{{$asset->ledger_link}}" data-trx-link="{{$asset->trx_link}}">
            <div class="card-header bg-primary text-white">{{$asset->name}}</div>
            <div class="card-body py-2 px-3">
            <h6>{{$asset->total}}</h6>
           
            </div>
        </div>
        </div>
        @endforeach
        @endif
        </div>
       
    </div>
    @endforeach
</div>

@endsection
@push('page-styles')

<style>
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
    @if(is_superadmin())
    
    $('body').append('<ul id="dashboard_context" class="m-0"></ul>');
    var context_items = [
        {
            id: "context_ledger",
            text: "Ledger",
            iconCss: "fas fa-list",
        },
        {
            id: "context_transactions",
            text: "Transactions",
            iconCss: "fas fa-list",
        },
    ];
    var menuOptions = {
        target: '.kpicard',
        items: context_items,
        beforeItemRender: dropdowntargetrender,
        
                
        beforeOpen: function(args){
            // toggle context items on header
           
            if( $(args.event.target).hasClass('kpicard')){ 
                data_ledger_link = $(args.event.target).attr('data-ledger-link');
                data_trx_link = $(args.event.target).attr('data-trx-link');
            }else{
                data_ledger_link = $(args.event.target).closest('.kpicard').attr('data-ledger-link');
                data_trx_link = $(args.event.target).closest('.kpicard').attr('data-trx-link');
            }
            if(data_ledger_link > ''){
                dashboard_context.enableItems(['Ledger'], true);        
            }else{
                dashboard_context.enableItems(['Ledger'], false); 
            }
            if(data_trx_link > ''){
                dashboard_context.enableItems(['Transactions'], true);        
            }else{
                dashboard_context.enableItems(['Transactions'], false); 
            }
        },
        select: function(args){
           // //console.log(data_ledger_link);
            //console.log(data_trx_link);
          
            if(args.item.id === 'context_ledger') {
                window.open(data_ledger_link,"_blank");
            }
            if(args.item.id === 'context_transactions') {
                window.open(data_trx_link,"_blank");
            }
        }
    };
    
    // Initialize ContextMenu control
    dashboard_context = new ej.navigations.ContextMenu(menuOptions, '#dashboard_context');
    
    @endif

  
</script>
@endpush