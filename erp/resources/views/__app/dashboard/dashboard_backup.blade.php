@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
@endif
@section('styles')
@parent
<link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
@endsection

@section('scripts')
@parent
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>

@endsection
@include('__app.dashboard.dashboard_toolbar')
@section('content')
<div class="kpicard"></div>
@yield('dashboard_toolbar')
<div class="custom-container items-center justify-center">
  <div class="max-h-full overflow-y-auto" id="dasboard_container">


@include('__app.dashboard.dashboard_cards')


      <div class="w-full pb-10 ">
                
       
            <div aria-label="group of cards" class="container-fluid mx-auto px-2 flex items-start justify-center">
        
                <div class="w-full container-fluid mx-auto grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-2 pt-6 gap-8 " id="iframe_sort_row">
                     @foreach($iframes as $module_iframe)
                        
                        <div class="border-gray-300 bg-white dark:bg-gray-800 shadow rounded iframe_container relative w-full" data-module-id="{{ $module_iframe->id}}">
                            
                        <div class="grid grid-cols-2 gap-4 menu_row p-2">
                            <div class="col-auto">
                                <h1 class="mb-0 mt-2" style="font-size:0.9rem;font-weight:bold;">{{$module_iframe->name}}</h1>
                            </div>
                            <div class="col k-button-group justify-end">
                                <a class="k-button" href="{{ $module_iframe->external_url }}" target="_blank">Open</a>
                                <a class="k-button refresh_btn" href="javascript:void(0)">Refresh</a>
                                <a class="k-button remove_btn" href="javascript:void(0)">Remove</a>
                                <a class="k-button pin-icon" href="javascript:void(0)">Sort</a>
                            </div>
                        </div>
                             <iframe id="iframe_id{{$module_iframe->id}}" data-attr-id="{{$module_iframe->id}}" src="{{ $module_iframe->iframe_url }}" width="100%" frameborder="0px" height="400px" onerror="alert('Failed')" style="margin-bottom:-5px;"><!-- //required for browser compatibility --></iframe> 
                        </div>
                
                       
                     @endforeach
                </div>
            </div>
        </div>
        
 {{-- @include('__app.dashboard.dashboard_instances_kpi') --}}

</div>
</div>



@endsection
@push('page-styles')

<style>
.menu_row{
    font-size:13px;
    background-color: {{ $color_scheme['second_row_color'] }};
}

.custom-container {
  height: calc(100vh - 80px) !important;
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
$(document).on("click", ".refresh_btn", function(e){
//console.log('dashboard contextmenu');
    var iframe_id = $(this).closest('.iframe_container').find('iframe').attr('id');
    //console.log(iframe_id);
   
    $('#'+iframe_id).attr('src', $('#'+iframe_id).attr('src'));
});

$(document).on("click", ".remove_btn", function(e){
    var iframe_container = $(this).closest('.iframe_container');
    var layout_id = $(this).closest('.iframe_container').find('iframe').attr('data-attr-id');
    ////console.log(iframe_id);
   
	$.ajax({
		url: '/remove_dashboard/'+layout_id,
		type: 'get',
		success: function(data){
		    //console.log(data);
		    toastNotify(data.message,data.status);
		    iframe_container.remove();
		}
	})
    
});
  @if(is_superadmin())    
  $(function () {
   const iframeSortRow = document.getElementById('iframe_sort_row');
    new Sortable(iframeSortRow, {
        animation: 150, // Speed of the animation during sorting (in milliseconds)
        handle: '.pin-icon', // Use the pin-icon as the handle for dragging
        onEnd: function(evt) {
            // This function will be called when the sorting ends
            // You can perform any additional actions after sorting here
            // For example, you can send an AJAX request to update the server-side order.
            ////console.log('onEnd');
            ////console.log(evt);
            var layout_id = $(evt.item).attr('data-module-id');
            
            ////console.log(layout_id);
            ////console.log('/update_dashboard_sort_order/'+layout_id+'/'+evt.oldIndex+'/'+evt.newIndex);
           
            if(layout_id){
    	    	$.ajax({
        			url: '/update_dashboard_sort_order/'+layout_id+'/'+evt.oldIndex+'/'+evt.newIndex,
        			type: 'get',
        			success: function(data){
        			    //console.log(data);
        			}
        		});
            }
		
        }
    });
  });
   @endif
</script>

<script>
  
    
    function dashboard_refresh(){
        /*
        $.get('dashboard?return_container=1', function(data) {
            $("#dasboard_container").html(data);
            dashboard_context.refresh();
        });
        */
    }
    
    setInterval(function(){
        $.get('dashboard?return_container=1', function(data) {
           dashboard_refresh();
        });
    }, 1000*60*5);
    
    

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

