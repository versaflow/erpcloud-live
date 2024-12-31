@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
@endif


@section('content')
<div class="my-3 mx-4">
    <div id="container">
        <!--element which is going to render the dashboardlayout-->
        <div id="dashboard_default">
            <div id="dashboard_inline">
                @include('__app.charts.dashboard_ajax')
            </div>
        </div>
    </div>
</div>

@endsection

@push('page-styles')

<style>
   .content {
            vertical-align: middle;
            font-weight: 600;
            font-size: 20px;
            text-align: center;
            line-height: 60px;
        }

        #dashboard_inline .e-panel {
            transition: none !important;
            height: auto !important;
        }

</style>
@endpush

@push('page-scripts')
<script>
$(document).ready(function(){
    // initialize dashboardlayout component
    window['dashboard']  = new ej.layouts.DashboardLayout({
        cellAspectRatio: 100/65,
        allowResizing: true,
        cellSpacing: [20, 20],
        columns:6,
    });
    // render initialized dashboardlayout
    window['dashboard'] .appendTo('#dashboard_default');
    
    
/*
function savePanels(args) {
    //console.log(savePanels);
    //console.log(args);
   
    dashboard_state = window['dashboard'].serialize();
    console.log(window['dashboard_role_filter'].value);
    console.log(dashboard_state);
       $.ajax({
    	url: '/save_dashboard_state/'+,
    	data: {dashboard_state: dashboard_state},
    	type: 'post',
    	success: function(data){
    	    ////console.log(data);
    	}
    });
}
*/
})


function restorePanels(){
    
}


    
   function load_charts(id = false, index = 0) {
    var charts = $(".aggrid_chart");
    if (index >= charts.length) {
        // All charts loaded
        @if(is_superadmin())
        aggrid_charts_context.refresh();
        charts_sortable();
        @endif
        return;
    }

    var obj = charts.eq(index);
    var chart_id = obj.attr('data-id');
    var chart_route = obj.attr('data-route');
    var is_chart = obj.attr('data-is_chart');
    //console.log(is_chart);
    if (id && parseInt(id) !== parseInt(chart_id)) {
        load_charts(id, index + 1); // Load next chart
        return;
    }

    load_chart(chart_id, chart_route, is_chart, function() {
        load_charts(id, index + 1); // Load next chart after this one is done
    });
}

function load_chart(chart_id, chart_route, is_chart, callback) {
    
    console.log('load_chart');
    console.log(chart_id);
    console.log(is_chart);
    if(is_chart === "1"){
        var layout_url = '/'+chart_route+'/minigrid?layout_id='+chart_id+'&chart_container=aggrid-chart'+chart_id;    
    }else{
        var layout_url = '/'+chart_route+'/minigrid?layout_id='+chart_id;    
    }
    console.log(layout_url);
    $.ajax({
        url: layout_url,
        beforeSend: function() {
    console.log('beforeSend');
            showSpinner("#chart-card" + chart_id);
        },
        success: function(data) {
    console.log('success');
    console.log(data);
            hideSpinner("#chart-card" + chart_id);
            $("#aggrid-container" + chart_id).html(data);
            if (typeof callback === 'function') {
                window['dashboard'].refresh();
                callback(); // Call the callback function after loading this chart
            }
        }
    });
}

    
    
    @if(!empty($dashboard_role_datasource) && count($dashboard_role_datasource) > 0)
        window['dashboard_role_current'] = {{$dashboard_role_selected}};
        window['dashboard_role_filter'] = new ej.dropdowns.DropDownList({
        	dataSource: {!! json_encode($dashboard_role_datasource) !!},
        	fields: {text: 'name', value: 'id'},
            placeholder: '{{$dashboard_role_placeholder}}',
            width: '150px',
            popupWidth: 'auto',
            //cssClass: 'ms-2',
            //Set true to show header title
          
            select: function(args){
                if(args.isInteracted && args.itemData && args.itemData.id){
                    var role_id = args.itemData.id;
                    $.get('/dashboard_charts_content/'+role_id, function(data) {
                        $("#dashboard_inline").html(data);
                        setTimeout(function(){
                            load_charts();
                        },300);
                    });
                }
            },
            created: function(args){
                
            },
        
        }, '#dashboard_role_filter');
    
    @endif
    
    
    @if(is_superadmin())
        function create_aggrid_charts_context(){
        $('body').append('<ul id="aggrid_charts_context" class="m-0"></ul>');
        var items = [
            {
                id: "agc_open",
                text: "Open",
                iconCss: "fas fa-info",
            },
            {
                id: "agc_edit",
                text: "Edit",
                iconCss: "fas fa-pen",
            },
            
            {
                id: "agc_remove",
                text: "Remove",
                iconCss: "fas fa-trash",
            },
            {
                id: "agc_refresh_single",
                text: "Refresh",
                iconCss: "fas fa-sync",
            },
            {
                id: "agc_refresh",
                text: "Refresh All",
                iconCss: "fas fa-sync",
            },
            
           
        ];
        context_agc_id = false;
        
        var menuOptions = {
            target: '.card-header',
            items: items,
            beforeItemRender: contextmenurender,
            beforeOpen: function(args){
                
                // toggle context items on header
               
              
                
                context_agc_id =  $(args.event.target).closest('.chart-card').find('.aggrid_chart').attr('data-id');
                context_agc_link = $(args.event.target).closest('.chart-card').find('.aggrid_chart').attr('data-layout_url');
                context_agc_edit_link = $(args.event.target).closest('.chart-card').find('.aggrid_chart').attr('data-edit_url');
                   
                 
            },
            select: function(args){
                if(args.item.id === 'agc_edit' && context_agc_edit_link) {
                    sidebarform('agc_edit',context_agc_edit_link, 'Edit');
                }
                if(args.item.id === 'agc_refresh') {
                    load_charts();
                }
                
                if(args.item.id === 'agc_refresh_single') {
                    load_charts(context_agc_id);
                }
                if(args.item.id === 'agc_open' && context_agc_id) {
                     window.open(context_agc_link, '_blank');
                }
                
                if(args.item.id === 'agc_remove') {
                    //console.log('/dashboard_tracking_disable/'+context_agc_id);
                    $.ajax({
                        url: '/dashboard_tracking_disable/'+context_agc_id,
                        type: 'get',
                        success: function(data) {
                            $("#chart-col"+context_agc_id).remove();
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            toastNotify('An error occured', 'error');
                            
                        },
                    });
                }
                
            }
        };
        
        // Initialize ContextMenu control.
        aggrid_charts_context = new ej.navigations.ContextMenu(menuOptions, '#aggrid_charts_context');  
        }
       
    @endif
    
    @if(is_superadmin())
    function charts_sortable(){
         $("#chart_row").sortable({
            cursor: "move",  
            items: ".chart-col",
            handle: '.card-header',
            stop: function(e) {
            ////console.log('stop',e);
              
    			var dataArray = $("#chart_row").find('.aggrid_chart').map(function() {
    				return $(this).attr('data-id');
    			}).get();
            ////console.log('dataArray',dataArray);

                $.ajax({
                url: '/dashboard_charts_sort',
                type:'post',
                data: {charts: dataArray},
                success: function(data) { 
                
                
                }
                }); 
                
            }
        });
    }   
    @endif
    
    @if(is_superadmin())
    create_aggrid_charts_context();
    @endif
    
    $(document).ready(function() {
        
        @if(is_superadmin())
        charts_sortable();
        @endif
        load_charts();
    });
    
    
</script>
@endpush