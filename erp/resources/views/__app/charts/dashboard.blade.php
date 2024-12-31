@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
@endif


@section('content')
<div class="my-3 mx-4">
    <div class="row text-end">
    @if(!empty($dashboard_role_datasource) && count($dashboard_role_datasource) > 0) 
    <div class="col mb-3">
    <div id="dashboard_instance_filter"></div>
    <div id="dashboard_role_filter"></div>
    </div>
    @endif
    </div>
    <div id="container">
  
    @foreach($instance_ids as $instance_id)
    <div id="instance_tab{{$instance_id}}" >
        <!--element which is going to render the dashboardlayout-->
        @foreach($role_ids as $role_id)
        @if(!empty($aggrid_charts[$instance_id][$role_id]) && count($aggrid_charts[$instance_id][$role_id]) > 0)
      
        <div id="dashboard_container{{$instance_id}}{{$role_id}}"  class="role_dashboard @if($role_id!=session('role_id')) d-none @endif " data-attr-role-id="{{$role_id}}"  data-attr-instance-id="{{$instance_id}}">
                @include('__app.charts.dashboard_ajax',['role_charts' => $aggrid_charts[$instance_id][$role_id]])
               
        </div>
        @endif
        @endforeach
    </div>
    @endforeach
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

    #dashboard_container .e-panel {
        transition: none !important;
        height: auto !important;
    }

</style>
@endpush

@push('page-scripts')


<script>
current_role_id = {{session('role_id')}};
current_instance_id = 1;
function render_dashboard(){
   
    // initialize dashboardlayout component
    
    @foreach($instance_ids as $instance_id)
    @foreach($role_ids as $role_id)
    @if(!empty($aggrid_charts[$instance_id][$role_id]) && count($aggrid_charts[$instance_id][$role_id]) > 0)
    window['dashboard{{$instance_id}}{{$role_id}}']  = new ej.layouts.DashboardLayout({
        cellAspectRatio: 100/105,
        allowResizing: true,
        cellSpacing: [20, 20],
        columns:6,
        dragStop: savePanels{{$instance_id}}{{$role_id}},
        resizeStop: savePanels{{$instance_id}}{{$role_id}},
        created:function(args){
           // console.log( window['dashboard{{$instance_id}}{{$role_id}}'].panels);
           // console.log( window['dashboard{{$instance_id}}{{$role_id}}']);
            var sortedPanels = this.panels.sort(function(a, b) {
                    if (a.row === b.row) {
                        return a.col - b.col;
                    }
                    return a.row - b.row;
                });

            //    console.log('Panels in display order:', sortedPanels);
          
            @if(session('role_id') == $role_id)
                load_charts{{$role_id}}(sortedPanels);
            @else
                setTimeout(load_charts{{$role_id}}(sortedPanels),5000);
            @endif   
        }
    });
    // render initialized dashboardlayout
    window['dashboard{{$instance_id}}{{$role_id}}'].appendTo('#dashboard_container{{$instance_id}}{{$role_id}}');
    @endif
    @endforeach
    @endforeach
    
}

@foreach($instance_ids as $instance_id)
    @foreach($role_ids as $role_id)
    function savePanels{{$instance_id}}{{$role_id}}(args) {
        ////console.log(savePanels);
        ////console.log(args);
        dashboard_state = window['dashboard{{$instance_id}}{{$role_id}}'].serialize();
           $.ajax({
        	url: '/save_dashboard_panels/',
        	data: {dashboard_state: dashboard_state, instance_id: {{$instance_id}}},
        	type: 'post',
        	success: function(data){
        	    //////console.log(data);
        	}
        });
    }
    @endforeach
@endforeach

$(document).ready(function(){
    
    
    render_dashboard();
})


function restorePanels(){
    
}

@foreach($role_ids as $role_id)
function load_charts{{$role_id}}(sorted_panels, id = false, index = 0) {
    var charts = sorted_panels;
    
    
    //console.log(charts);
    if (index >= charts.length) {
        // All charts loaded
        @if(is_superadmin())
        aggrid_charts_context.refresh();
        charts_sortable();
        @endif
        return;
    }

    var obj = charts[index];
    var chart_id = obj.id.replace('chartpanel','');
    var chart_container = $("#aggrid-container1"+chart_id);
    
    var chart_id = chart_container.attr('data-id');
    var chart_route = chart_container.attr('data-route');
    var is_chart = chart_container.attr('data-is_chart');
    var instance_id = chart_container.attr('data-instance_id');
    var cidb = chart_container.attr('data-cidb');
    ////console.log(is_chart);
    if (id && parseInt(id) !== parseInt(chart_id)) {
        load_charts{{$role_id}}(sorted_panels, id, index + 1); // Load next chart
        return;
    }

    load_chart(chart_id, chart_route, is_chart, instance_id, cidb, function() {
        load_charts{{$role_id}}(sorted_panels, id, index + 1); // Load next chart after this one is done
    });
}
@endforeach

function refresh_chart(chart_id) {
  
    console.log('refresh_chart');
    var chart_container = $("#aggrid-container"+chart_id);
    console.log(chart_container);
    console.log(chart_id);
    
    var chart_id = chart_container.attr('data-id');
    var chart_route = chart_container.attr('data-route');
    var is_chart = chart_container.attr('data-is_chart');
    var instance_id = chart_container.attr('data-instance_id');
    var cidb = chart_container.attr('data-cidb');

    load_chart(chart_id, chart_route, is_chart, instance_id, cidb);
}

function load_chart(chart_id, chart_route, is_chart, instance_id, cidb,callback) {
    
    
    ////console.log('load_chart');
    ////console.log(chart_id);
   // //console.log(is_chart);
    if(is_chart === "1"){
        var layout_url = '/'+chart_route+'/minigrid?layout_id='+chart_id+'&chart_container=aggrid-chart'+chart_id;    
    }else{
        var layout_url = '/'+chart_route+'/minigrid?layout_id='+chart_id;    
    }
    
    if(instance_id != 1){
        
        layout_url += '&cidb='+cidb;  
    }
    
    //console.log(instance_id);
    //console.log(cidb);
    if(instance_id != 1){
    //console.log(layout_url);
    }
    startTime = new Date().getTime();
    $.ajax({
        url: layout_url,
        beforeSend: function() {
             startTime = new Date().getTime();
    ////console.log('beforeSend');
            showSpinner("#chart-card" + instance_id + chart_id);
        },
        success: function(data) {
        //console.log('success');
        
        var endTime = new Date().getTime();
        var elapsedTimeInSeconds = (endTime - startTime) / 1000; // Convert milliseconds to seconds
        //console.log("Request took " + elapsedTimeInSeconds + " seconds.");
            $("#timetoload" + instance_id + chart_id).html("Request took " + elapsedTimeInSeconds + " seconds.");
   // //console.log(data);
            hideSpinner("#chart-card" + instance_id + chart_id);
            $("#aggrid-container" + instance_id + chart_id).html(data);
            if (typeof callback === 'function') {
                @if(is_superadmin())
                aggrid_charts_context.refresh();
                @endif
             
                callback(); // Call the callback function after loading this chart
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            //console.log('error');
            //console.log(jqXHR);
            //console.log(textStatus);
            //console.log(errorThrown);
             var redirectUrl = jqXHR.getResponseHeader('Location');
            if (redirectUrl) {
                //console.log(redirectUrl);
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
                  
                    current_role_id = role_id;
                    //swith role dashboards
                    
                    $('.role_dashboard').each(function(i,obj){
                        var dashboard_role_id = $(obj).attr('data-attr-role-id');
                        var dashboard_instance_id = $(obj).attr('data-attr-instance-id');
                        if(dashboard_role_id == role_id){
                            $(obj).removeClass('d-none');
                        }else{
                            $(obj).addClass('d-none');
                        }
                    })
                    
                       setTimeout(function(){
                            
                      
                        @foreach($instance_ids as $instance_id)
                        @foreach($role_ids as $role_id)
                        
                        @if(!empty($aggrid_charts[$instance_id][$role_id]) && count($aggrid_charts[$instance_id][$role_id]) > 0)
                        window['dashboard{{$instance_id}}{{$role_id}}'].refresh();
                        @endif
                        
                        @endforeach
                        @endforeach
                            
                        },1000);
                    
                    
                }
            },
            created: function(args){
                
            },
        
        }, '#dashboard_role_filter');
    
    @endif
    
    

    
    @if(!empty($dashboard_instance_datasource) && count($dashboard_instance_datasource) > 0)
        window['dashboard_instance_current'] = {{$dashboard_instance_selected}};
        window['dashboard_instance_filter'] = new ej.dropdowns.DropDownList({
        	dataSource: {!! json_encode($dashboard_instance_datasource) !!},
        	fields: {text: 'name', value: 'id'},
            placeholder: '{{$dashboard_instance_placeholder}}',
            width: '150px',
            popupWidth: 'auto',
            //cssClass: 'ms-2',
            //Set true to show header title
          
            select: function(args){
                if(args.isInteracted && args.itemData && args.itemData.id){
                    var instance_id = args.itemData.id;
                  
                    current_instance_id = instance_id;
                    //swith instance dashboards
                    //console.log('instance switch');
                    //console.log(current_instance_id);
                    //console.log(current_role_id);
                    $('.role_dashboard').each(function(i,obj){
                        var dashboard_role_id = $(obj).attr('data-attr-role-id');
                        var dashboard_instance_id = $(obj).attr('data-attr-instance-id');
                        if(dashboard_role_id == current_role_id){
                            $(obj).removeClass('d-none');
                        }else{
                            $(obj).addClass('d-none');
                        }
                    })
                    
                       setTimeout(function(){
                            
                      
                        @foreach($instance_ids as $instance_id)
                        @foreach($role_ids as $role_id)
                        
                        
                        @if(!empty($aggrid_charts[$instance_id][$role_id]) && count($aggrid_charts[$instance_id][$role_id]) > 0)
                        window['dashboard{{$instance_id}}{{$role_id}}'].refresh();
                        @endif
                        
                        @endforeach
                        @endforeach
                            
                        },1000);
                    
                    
                }
            },
            created: function(args){
                
            },
        
        }, '#dashboard_instance_filter');
    
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
                id: "agc_list",
                text: "List",
                iconCss: "fas fa-list",
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
            target: '.role_dashboard .card-header',
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
                  
                }
                
                if(args.item.id === 'agc_refresh_single') {
                    
                    refresh_chart(context_agc_id);
                }
                if(args.item.id === 'agc_open' && context_agc_id) {
                     window.open(context_agc_link, '_blank');
                }
                if(args.item.id === 'agc_list' && context_agc_id) {
                     window.open("{{ url($layouts_url.'?layout_id=2335') }}", '_blank');
                }
                
                if(args.item.id === 'agc_remove') {
                    $.ajax({
                        url: '/dashboard_tracking_disable/'+context_agc_id,
                        type: 'get',
                        success: function(data) {
                            console.log(current_role_id);
                            console.log(window['dashboard'+current_role_id]);
                            console.log('chartpanel'+context_agc_id);
                            window['dashboard'+current_instance_id+current_role_id].removePanel('chartpanel'+context_agc_id);
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
            //////console.log('stop',e);
              
    			var dataArray = $("#chart_row").find('.aggrid_chart').map(function() {
    				return $(this).attr('data-id');
    			}).get();
            //////console.log('dataArray',dataArray);

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
    
  
    
    
</script>
@endpush