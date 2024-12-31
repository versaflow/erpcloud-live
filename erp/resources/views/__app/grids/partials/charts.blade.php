
@section('aggridcharts')
<!-- Cards with badge -->
@if(!empty($aggrid_charts))
<div id="sidebar_charts"></div>
<div class="sidebar_chart"></div>
@endif
<!--/ Cards with badge -->

@endsection

@push('page-styles')

<style>

.row-fluid {
    overflow-x: auto;
    white-space: nowrap;
    flex-wrap: nowrap;
}

.row-fluid .col-lg-3 {
     display: inline-block;
     float: none;
}

  .agg_card_detail{
    margin-bottom:0;
    font-size:12px;
    font-weight: 400 !important;
  }
  .module_card .badge-light {
    color: #606060;
  }
</style>
@endpush

@push('page-scripts')
<script>

$(document).ready(function() {
    
    @if(!empty($aggrid_charts))
    
 
    
   
    
    
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
            /*
            {
                id: "agc_remove",
                text: "Remove",
                iconCss: "fas fa-trash",
            },
            {
                id: "agc_refresh_single",
                text: "Refresh This Chart",
                iconCss: "fas fa-sync",
            },
            {
                id: "agc_refresh",
                text: "Refresh All Charts",
                iconCss: "fas fa-sync",
            },
            */
           
        ];
        context_agc_id = false;
        
        var menuOptions = {
            target: '.sidebar_chart',
            items: items,
            beforeItemRender: contextmenurender,
            beforeOpen: function(args){
                
                // toggle context items on header
               
              
                
                context_agc_id =  $(args.event.target).closest('.sidebar_chart').attr('data-id');
                context_agc_link = $(args.event.target).closest('.sidebar_chart').attr('data-layout_url');
                context_agc_edit_link = $(args.event.target).closest('.sidebar_chart').attr('data-edit_url');
                    
                 
            },
            select: function(args){
                if(args.item.id === 'agc_edit' && context_agc_edit_link) {
                    sidebarform('agc_edit',context_agc_edit_link, 'Edit');
                }
                if(args.item.id === 'agc_refresh') {
                    refresh_sidebar_charts();
                }
                
                if(args.item.id === 'agc_refresh_single') {
                    refresh_sidebar_chart(context_agc_id);
                }
                if(args.item.id === 'agc_open' && context_agc_id) {
                     window.open(context_agc_link, '_blank');
                }
                
                if(args.item.id === 'agc_remove') {
                    
                    $.ajax({
                        url: '/remove_aggrid_chart/'+context_agc_id,
                        type: 'get',
                        success: function(data) {
                            $("#aggrid-charts-col"+context_agc_id).remove();
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
        
        
    @endif

    @if(!empty($aggrid_charts))
    var sidebar_charts_data = new ej.data.DataManager({
        url: '/content_sidebar_charts/{{$module_id}}',
        adaptor: new ej.data.UrlAdaptor(),
        crossDomain: true,
    });
    
    // initialize ListBox component
     sidebar_charts = new ej.dropdowns.ListBox({
        cssClass: 'charts_list',
        @if(is_superadmin())
        allowDragAndDrop: true,
        @endif
        dataSource: sidebar_charts_data,
        beforeItemRender: function(args){ 
            $(args.element).addClass(args.item.cssClass); 
            if(window['layout_id{{ $grid_id }}'] == args.item.id){
                $(args.element).addClass('e-selected'); 
            }
            $.each(args.item.htmlAttributes, function(k, v){
                $(args.element).attr(k,v); 
            });
        },
        
        drop: function(args){
            // drag and drop
            $.ajax({
                url: '/charts_sort/{{$module_id}}',
                type:'post',
                data: {charts: sidebar_charts.listData},
                success: function(data) { 
                
                
                }
            }); 
        },
        created: function(args){
            create_aggrid_charts_context();
           
        },
        dataBound: function(args){
            setTimeout(function(){
                aggrid_charts_context.refresh();
            },500)
           
        },
        
        change: function(args){
            var itemData = args.items[0];
            var page_module_id = '{{$module_id}}';
            if(page_module_id == itemData.module_id){
                layout_load{{$grid_id}}(itemData.id);
            }else{
                window.open('/'+itemData.slug+'?layout_id='+itemData.id+'&chart_container=aggrid-chart'+itemData.id,'_blank');
            }
            //viewDialog('chart'+itemData.id,'/'+itemData.slug+'/minigrid?layout_id='+itemData.id+'&chart_container=aggrid-chart'+itemData.id)
        
        },
    });
    sidebar_charts.appendTo('#sidebar_charts');
    @endif
    
});
</script>
@endpush