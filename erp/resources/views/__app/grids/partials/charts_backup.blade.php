
@section('aggridcharts')
<!-- Cards with badge -->
@if(!empty($aggrid_charts))
<div class="row pt-2 pb-1 px-2 gx-2" id="aggrid_charts{{$module_id}}" class="pt-1" >
</div>
<div class="aggrid-chart-header"></div>
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
    function createModalFromDiv(divId, title = false) {
        // Get the jQuery object of the specified div
        var $div = $('#' + divId);
        
        var chart_id = $('#' + divId).closest(".aggrid-charts-col").attr("data-attr-id");
        initialize_sidebar_chart(chart_id);
        // Create a unique ID for the modal
        var modalId = divId + 'Modal';
        if (!title) {
            title = 'Modal Title';
        }

        // Create the modal structure with bigger and resizable settings
        var modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1" role="dialog" aria-labelledby="${modalId}Label" aria-hidden="true">
                <div class="modal-dialog modal-xl resizable-modal" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="${modalId}Label">${title}</h5>
                            <button type="button" class="btn btn-icon close" data-dismiss="modal" aria-label="Close">
                               <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <!-- Content will be moved here -->
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Append the modal HTML to the body
        $('body').append(modalHtml);

        // Move the content of the div into the modal body
        $('#' + modalId + ' .modal-body').append($div.contents());

        // Show the modal
        $('#' + modalId).modal('show');
        // Manually handle close button click to dismiss the modal
        $('#' + modalId + ' .close').click(function () {
            $('#' + modalId).modal('hide');
        });
        // Handle modal close event to move content back to its original spot
        $('#' + modalId).on('hidden.bs.modal', function () {
            // Move content back to its original spot
            $div.append($('#' + modalId + ' .modal-body').contents());

            // Remove the modal from the DOM
            $('#' + modalId).remove();
        });

      
    }
</script>



<script>
    $(document).ready(function(){
        $(document).off('click', '.chart-toggle-btn').on('click', '.chart-toggle-btn', function(e) {
            //console.log('chart-toggle-btn clicked',e);
           
            if ($(this).closest(".card").find(".card-body").is(':hidden')) {
                var chart_id = $(this).closest(".aggrid-charts-col").attr("data-attr-id");
                initialize_sidebar_chart(chart_id);
            }
            $(this).closest(".card").find(".card-body").toggle();
        });
    });
    
    @if(!empty($aggrid_charts))
    
    
    function initialize_sidebar_chart(id){
        $(".aggrid-container").each(function(i, obj){
            var divid = $(obj).attr('id');
            var chart_id = divid.replace('aggrid-container', "");
            var chart_route = $(obj).attr('data-attr-chart-route');
            if(parseInt(id) == parseInt(chart_id)){
                if($("#aggrid-container"+chart_id).html() == ''){
                    $("#aggrid-chart"+chart_id).html("");
                    $.get('/'+chart_route+'/minigrid?layout_id='+chart_id+'&chart_container=aggrid-chart'+chart_id, function(data) {
                       $("#aggrid-container"+chart_id).html(data);
                    });
                }
            }
        })
        @if(is_superadmin())
        aggrid_charts_context.refresh();
        @endif
    }
    
    function refresh_sidebar_chart(id){
        $(".aggrid-container").each(function(i, obj){
            var divid = $(obj).attr('id');
            var chart_id = divid.replace('aggrid-container', "");
            var chart_route = $(obj).attr('data-attr-chart-route');
            if(parseInt(id) == parseInt(chart_id)){
                $("#aggrid-chart"+chart_id).html("");
                $.get('/'+chart_route+'/minigrid?layout_id='+chart_id+'&chart_container=aggrid-chart'+chart_id, function(data) {
                   $("#aggrid-container"+chart_id).html(data);
                });
            }
        })
        @if(is_superadmin())
        aggrid_charts_context.refresh();
        @endif
    }
    
    function refresh_sidebar_charts(){
        
        $(".aggrid-container").each(function(i, obj){
            var divid = $(obj).attr('id');
            var chart_id = divid.replace('aggrid-container', "");
            var chart_route = $(obj).attr('data-attr-chart-route');
          
            $("#aggrid-chart"+chart_id).html("");
            $.get('/'+chart_route+'/minigrid?layout_id='+chart_id+'&chart_container=aggrid-chart'+chart_id, function(data) {
               $("#aggrid-container"+chart_id).html(data);
            });
        })
        @if(is_superadmin())
        aggrid_charts_context.refresh();
        @endif
    }
    function load_charts_ajax(){
        $.get('/get_sidebar_charts_html/{{$module_id}}', function(data) {
            //console.log('load_charts_ajax');
            //console.log(data);
            $("#aggrid_charts{{$module_id}}").html(data.html);
            //setup_sidebar_charts();
            @if(is_superadmin())
            aggrid_charts_context.refresh();
            @endif
        });
    }
    
    function setup_sidebar_charts(){
        
        $(".aggrid-container").each(function(i, obj){
            var divid = $(obj).attr('id');
            var chart_id = divid.replace('aggrid-container', "");
            var chart_route = $(obj).attr('data-attr-chart-route');
          
            $("#aggrid-chart"+chart_id).html("");
            $.get('/'+chart_route+'/minigrid?layout_id='+chart_id+'&chart_container=aggrid-chart'+chart_id, function(data) {
               $("#aggrid-container"+chart_id).html(data);
            });
        })
        @if(is_superadmin())
        // initialize sortable
        
        $("#aggrid_charts{{$module_id}}").sortable({
            cursor: "move",  
            items: ".aggrid-charts-col",
            handle: '.aggrid-chart-header',
            start: function(e) {
            //console.log('start',e);
            },
            stop: function(e) {
            //console.log('stop',e);
              var dataArray = Array.from(document.querySelectorAll('#aggrid_charts{{$module_id}} .aggrid-charts-col')).filter(e => e.hasAttribute('data-attr-id')).map(e => ({ id: e.getAttribute('data-attr-id'), role_id: e.getAttribute('data-attr-role_id') }));
               
            //console.log('dataArray',dataArray);

                $.ajax({
                url: '/charts_sort/{{$module_id}}',
                type:'post',
                data: {charts: dataArray},
                success: function(data) { 
                
                
                }
                }); 
            }
        });
        aggrid_charts_context.refresh();
        @endif        
    }
    @endif
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
           
        ];
        context_agc_id = false;
        
        var menuOptions = {
            target: '.aggrid-chart-header',
            items: items,
            beforeItemRender: contextmenurender,
            beforeOpen: function(args){
                
                // toggle context items on header
               
              
                
                context_agc_id =  $(args.event.target).closest('.aggrid-charts-col').find('.aggrid-chart-header').attr('data-attr-id');
                context_agc_link = $(args.event.target).closest('.aggrid-charts-col').find('.aggrid-chart-header').attr('data-attr-link');
                context_agc_edit_link = $(args.event.target).closest('.aggrid-charts-col').find('.aggrid-chart-header').attr('data-attr-edit-link');
                    
                 
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
        create_aggrid_charts_context();
        @endif
        
        
    @endif
});
</script>
@endpush