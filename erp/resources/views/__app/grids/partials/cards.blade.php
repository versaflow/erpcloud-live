
@section('gridcards')
<!-- Cards with badge -->
@if(!empty($module_cards))


<div class="row row-fluid pt-2 pb-1 px-2 gx-2" id="module_cards{{$module_id}}" class="pt-1" >
@foreach($module_cards as $i => $card)



<div class="col-lg-3 mb-1">
<div class="card h-100 mb-1 mx-0 border module-card{{$module_id}}" data-attr-id="{{$card->id}}">
<div class="card-body p-3">
<div class="row m-0 p-0">
<div class="col-12 p-0">
<div class="numbers">
<h6 class="font-weight-bolder mb-0" style="font-size:14px !important;">{!!$card->title!!}</h6>
<p class="text-sm mb-0 text-capitalize font-weight-bold"  style="font-size:12px !important;">{!!$card->result!!}</p>
</div>
</div>
</div>
</div>
</div>
</div>


  


@endforeach

</div>
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
    @if(!empty($module_cards))
    <script>
    function refresh_module_cards{{$module_id}}(){
         
        $.ajax({
            type: 'get',
            url: 'get_module_cards/{{$module_id}}',
            success: function (data){
          
                if(data.html){
                    $("#module_cards{{$module_id}}").html(data.html);
                    @if(is_superadmin())
                    module_cards_context.refresh();
                    @endif
                }
            }
        })
    }
    
    </script>
    @endif
    @if(!empty($module_cards) && is_superadmin())
    <script>
    function refresh_module_cards{{$module_id}}(){
         
        $.ajax({
            type: 'get',
            url: 'get_module_cards/{{$module_id}}',
            success: function (data){
          
                if(data.html){
                    $("#module_cards{{$module_id}}").html(data.html);
                    module_cards_context.refresh();
                }
            }
        })
    }
    function create_module_cards_context(){
    $('body').append('<ul id="module_cards_context" class="m-0"></ul>');
    var items = [
        {
            id: "mc_edit",
            text: "Edit",
            iconCss: "fas fa-pen",
        },
        {
            id: "mc_list",
            text: "List",
            iconCss: "fa fa-list",
        },
       
    ];
    context_mc_id = false;
    
    var menuOptions = {
        target: '.module-card{{$module_id}}',
        items: items,
        beforeItemRender: contextmenurender,
        beforeOpen: function(args){
            
            // toggle context items on header
            
            context_mc_id = $(args.event.target).attr('data-attr-id');
            if(!context_mc_id){
              //console.log('closest');
              //console.log($(args.event.target).closest('.module-card{{$module_id}}'));
              context_mc_id =  $(args.event.target).closest('.module-card{{$module_id}}').attr('data-attr-id');
            }
            //console.log($(args.event.target));
            //console.log(context_mc_id);
             
        },
        select: function(args){
           
            if(args.item.id === 'mc_edit' && context_mc_id) {
                 sidebarform('mc_edit','/{{$module_cards_url}}/edit/'+context_mc_id, 'Edit');
            }
            
            if(args.item.id === 'mc_list') {
                 viewDialog('mc_list','/{{$module_cards_url}}?module_id={{$module_id}}', 'Module Cards');
            }
            
        }
    };
    
    // Initialize ContextMenu control.
    module_cards_context = new ej.navigations.ContextMenu(menuOptions, '#module_cards_context');  
    }
    create_module_cards_context();
    </script>
    @endif
@endpush