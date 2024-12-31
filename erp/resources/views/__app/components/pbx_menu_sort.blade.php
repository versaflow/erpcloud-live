<div class="control-section p-2">
    <div class="content-wrapper">
        <div id="menu_sort">
        </div>
        
    </div>
</div>
<script>
   
    
    menu_manager = new ej.treegrid.TreeGrid({
        dataSource: {!! json_encode($menu_data) !!},
        idMapping: 'menu_item_uuid',
        treeColumnIndex: 1,
        allowRowDragAndDrop: true,
        parentIdMapping: 'menu_item_parent_uuid',
        columns: [
            { field: 'menu_item_uuid', headerText: 'Menu ID', isPrimaryKey: true, width: 50, textAlign: 'Right' },
            { field: 'menu_item_title', headerText: 'Menu Title', width: 200, textAlign: 'Left' },
            { field: 'menu_item_order', headerText: 'Order', width: 200, textAlign: 'Left' },
        ],
        created: function(args){
            setTimeout(function(){menu_manager.collapseAll();},500);
        },
        rowDrop: function(args){
            //console.log(args);
          
            
                var start_row = menu_manager.getRowByIndex(args.fromIndex);
                var stop_row = menu_manager.getRowByIndex(args.dropIndex);
                var start_row_info = menu_manager.getRowInfo(start_row);
                var stop_row_info = menu_manager.getRowInfo(stop_row); 
                var postdata = {position: args.dropPosition, id: start_row_info.rowData.menu_item_uuid, target_id: stop_row_info.rowData.menu_item_uuid}
                
                var url = 'pbx_menu_sort_ajax/'+start_row_info.rowData.menu_item_uuid+'/'+stop_row_info.rowData.menu_item_uuid+'/'+args.dropPosition;
                $.ajax({
                    type: 'get',
                    url: url,
                    success: function(data) {
                      ////console.log(data);
                      toastNotify('Menu updated','success');
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                      
                        
                    }
                });
        }
    });
    menu_manager.appendTo('#menu_sort');

</script>