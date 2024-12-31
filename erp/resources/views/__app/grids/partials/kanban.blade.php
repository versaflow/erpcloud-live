
<div class="control-section">
    <div class="control_wrapper m-0 ">
        <div class="card">
            <div class="card-body">
                <div id="Kanban"></div>
            </div>
        </div>
    </div>
</div>

<script id="cardTemplate" type="text/x-template">
        <div class='card-template'>
            <div class='e-card-header'>
                <div class='e-card-header-caption'>
                     <div class='e-card-header-title e-tooltip-text'>${ {{ $kanban_card_title }} }</div>
                </div>
                <div class="text-right">
                ${if(created_at>"")}
                <small><span class="text-muted">${formatDateTimeToDate(created_at)}</span></small>
                ${/if}
                </div>
            </div>
            <div class='e-card-content e-tooltip-text'>
               @foreach($kanban_card_fields as $f)
               @if($f['field_type'] == 'select_module')
               <b>{{ $f['label'] }}:</b> ${ {{ 'join_'.$f['field'] }} }<br>
               @else
               <b>{{ $f['label'] }}:</b> ${ {{ $f['field'] }} }<br>
               @endif
               @endforeach
            </div>
        </div>
</script>



<script type="text/javascript">
    function formatDateTimeToDate(datetime){
     
        return moment(datetime).format("YYYY-MM-DD");
    }
    var data = new ej.data.DataManager({
        url: "/{{$menu_route}}/kanban_data?layout_id={{$layout_id}}",
        crudUrl: "/{{$menu_route}}/kanban_update",
        adaptor: new ej.data.UrlAdaptor
    });
    
    var kanbanObj = new ej.kanban.Kanban({
        dataSource: data,
        keyField: 'status',
        enableTooltip: true,
        columns: [
            @foreach($kanban_cols as $status)
            { headerText: '{{ ucwords($status) }}', keyField: '{{ $status }}'},
            @endforeach
        ],
        cardSettings: {
            showHeader: false,
            headerField: 'id',
            template: '#cardTemplate',
        },
        cardDoubleClick: function(args){
            args.cancel=true;
        },
        cardClick:function(args){
            //console.log('cardClick'); 
            //console.log(args); 
            //console.log('{{ $grid_id }}'); 
            if(args.data && args.data.rowId){
                window['selectedrow_{{ $grid_id }}'] = args.data;
            }else{
                window['selectedrow_{{ $grid_id }}'] = null;
            }
            //console.log(window['selectedrow_{{ $grid_id }}']);
        },
        actionComplete: function(args){
            //console.log('actionComplete',args);
            
        },
        sortSettings: {
        sortBy: 'Index',
        direction: 'Descending'
        }
    });
    kanbanObj.appendTo('#Kanban');
    
    function searchKanban(searchValue){
      
        var searchQuery = new ej.data.Query();
        if (searchValue !== '') {
            searchQuery = new ej.data.Query().where('search_text', 'equal', searchValue);
        }
        kanbanObj.query = searchQuery;     
    }

</script>

	      
<style>
 
</style>