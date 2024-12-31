<div class="control-section p-2">
    <div class="content-wrapper">
        <div id="fields_sort">
        </div>
        
    </div>
</div>
<script>
   
    
    fields_manager = new ej.treegrid.TreeGrid({
        dataSource: {!! json_encode($fields_data) !!},
        editSettings: { allowEditing: true, allowAdding: true, mode: 'Cell' },
        toolbar: [{text: 'Save', tooltipText: 'Save', id: 'save_menu'},'Add', 'Edit', {text: 'Edit Field', id: 'edit_field'}],
        toolbarClick: function (args) {
          
           
            if (args.item.id === 'edit_field') {
                var records = fields_manager.getSelectedRecords();
                if(records.length > 0){
                    var fields_id = records[0].id;
                    
                    sidebarform('/{{ $module_fields_url }}/edit/'+fields_id);
                }
            }
            
            if (args.item.id === 'save_menu') {
              
                var records = fields_manager.getCurrentViewRecords();
                var rows = [];
                $.each(records, function(i,el){
                    rows.push({'id':el.id,'parent_id':el.parent_id,'fields_name':el.fields_name});
                });
                //console.log(rows);
               
                var module_id = '{{$module_id}}';
                if(module_id){
                    var postdata =  {"location" : fields_location, rows: rows};
                }else{
                    var postdata =  {"module_id" : module_id,"location" : fields_location, rows: rows};
                }
                gridAjax('/fields_manager_bulk_edit', postdata, 'post');
                
               
            }
        },
      
        enableAltRow: true,
        enableCollapseAll: true,
        selectionSettings: {type:'Single',mode:'Row'},
        actionBegin: function(args){
          
            if(args.requestType == 'add'){
                args.cancel = true;
              
                var url = '{{$fields_manager_url}}/edit?location='+fields_location;
                if(fields_location == 'related_items_menu' || fields_location == 'module_actions' || fields_location == 'grid_menu'){
                    var url = url+'&render_module_id={{$module_id}}';
                }
                //console.log(url);
                sidebarform('fields_add',url, 'Add Menu');
            }
            if(args.requestType == 'beginEdit'){
                args.cancel = true;
                var records = fields_manager.getSelectedRecords();
                if(records.length > 0){
                    var fields_id = records[0].id;
                
                    sidebarform('fields_add','{{$fields_manager_url}}/edit/'+fields_id, 'Edit Menu - '+records[0].fields_name);
                }
            }
        },
        actionComplete: function(args){
            if(args.requestType == 'delete'){
                
                if(fields_id_to_delete != null){
                    var fields_id = fields_id_to_delete;
                    gridAjax('/{{ $fields_manager_url }}/delete', {"id" : fields_id}, 'post');
                }
                fields_id_to_delete = null;
            }
            ////console.log('actionComplete');
            ////console.log(args);
        },
        idMapping: 'id',
        treeColumnIndex: 1,
        allowRowDragAndDrop: true,
        parentIdMapping: 'parent_id',
        columns: [
            { field: 'id', headerText: 'Menu ID', isPrimaryKey: true, width: 50, textAlign: 'Right' },
            { field: 'fields_name', headerText: 'Menu Name', width: 200, textAlign: 'Left' },
            { field: 'fields_type', headerText: 'Menu Type', width: 200, textAlign: 'Left' },
        ],
        created: function(args){
            //setTimeout(function(){render_fields_select();},500);
        },
        rowDrop: function(args){
            
            ////console.log(args);
            ////console.log(fields_location);
            /*
            if(fields_location =='gridtab' && args.dropPosition == "middleSegment"){
                args.cancel=true;
            }else{
            
                var start_row = fields_manager.getRowByIndex(args.fromIndex);
                var stop_row = fields_manager.getRowByIndex(args.dropIndex);
                var start_row_info = fields_manager.getRowInfo(start_row);
                var stop_row_info = fields_manager.getRowInfo(stop_row); 
                var postdata = {position: args.dropPosition, id: start_row_info.rowData.id, target_id: stop_row_info.rowData.id}
               
           
                var url = 'fields_sort_ajax/{{$location}}/'+start_row_info.rowData.id+'/'+stop_row_info.rowData.id+'/'+args.dropPosition;
                $.ajax({
                    type: 'get',
                    url: url,
                    beforeSend: function(){
                        showSpinner('#fields_sort .e-gridcontent');
                    },
                    success: function(data) {
                        
                        hideSpinner('#fields_sort .e-gridcontent');
                        toastNotify('Menu updated.','info');
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        hideSpinner('#fields_sort .e-gridcontent');
                        toastNotify('Menu update failed.','error');
                        
                    }
                });
            }
            */
        }
    });
    fields_manager.appendTo('#fields_sort');

</script>