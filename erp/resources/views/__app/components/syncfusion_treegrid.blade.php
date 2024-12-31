<div class="control-section">
    <div class="content-wrapper">
        <div id="menu_sort">
        </div>
    </div>
</div>
<script>
    treeGridObj = new ej.treegrid.TreeGrid({
        dataSource: {!! json_encode($menu_data) !!},
        idMapping: 'id',
        treeColumnIndex: 1,
        @if($location == 'gridtab')
        allowRowDragAndDrop: true,
        @else
        allowRowDragAndDrop: true,
        parentIdMapping: 'parent_id',
        @endif
        columns: [
            { field: 'id', headerText: 'Menu ID', isPrimaryKey: true, width: 50, textAlign: 'Right' },
            { field: 'menu_name', headerText: 'Menu Name', width: 200, textAlign: 'Left' },
        ],
        created: function(args){
            setTimeout(function(){treeGridObj.collapseAll();},500);
        },
        rowDrop: function(args){
            
            var start_row = treeGridObj.getRowByIndex(args.fromIndex);
            var stop_row = treeGridObj.getRowByIndex(args.dropIndex);
            var start_row_info = treeGridObj.getRowInfo(start_row);
            var stop_row_info = treeGridObj.getRowInfo(stop_row); 
            var postdata = {position: args.dropPosition, id: start_row_info.rowData.id, target_id: stop_row_info.rowData.id}
            
            var url = 'menu_sort_ajax/{{$location}}/'+start_row_info.rowData.id+'/'+stop_row_info.rowData.id+'/'+args.dropPosition;
            $.ajax({
                type: 'get',
                url: url,
                success: function(data) {
                  //console.log(data);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                  
                    
                }
            });
        }
    });
    treeGridObj.appendTo('#menu_sort');

</script>