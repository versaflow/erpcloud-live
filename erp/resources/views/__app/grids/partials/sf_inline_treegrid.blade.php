<div class="control-section w-100">
    <div class="content-wrapper">
        <div id="inline_grid"></div>
    </div>
</div>


<script>

  function update_inline_grid_datasource(){
        //console.log('update_menu_manager_datasource');
        if(menu_location){
            $.ajax({
               url: '{{ url("/project_tasks_datasource/".$task_id) }}',
               success: function(data){
                   inline_grid.dataSource = data;
                   inline_grid.refresh();
                }
            });
        }
    }
    var inlinegrid_data = new ej.data.DataManager({
        url: '{{ url("/project_tasks_datasource/".$task_id) }}',
        adaptor:new ej.data.WebApiAdaptor(),
        crossDomain: true,
    });
    //console.log('inlinegrid_data');
    //console.log(inlinegrid_data);
    
    var inline_grid = new ej.treegrid.TreeGrid({
        idMapping: 'id',
        hasChildMapping: 'isParent',
        parentIdMapping: 'parent_id',
        treeColumnIndex: 1,
        
        dataSource: {!! json_encode($rows) !!},
        editSettings: { allowEditing: true, allowAdding: true, allowDeleting: true, mode: 'Normal', newRowPosition:'Top' },
        allowPaging: false,
        toolbar: ['Add', 'Edit', 'Delete', 'Update', 'Cancel',{text: 'Save sort', tooltipText: 'Save', id: 'save_sort'}],
        width: '100%',
        height: '100%',
        rowHeight: 20,
        allowRowDragAndDrop: true,   
        toolbarClick: function (args) {
        
            
            if (args.item.id === 'save_sort') {
              
                var records = inline_grid.getCurrentViewRecords();
                var rows = [];
                $.each(records, function(i,el){
                    rows.push({'id':el.id,'parent_id':el.parent_id,'name':el.name});
                });
                ////console.log(rows);
               
                
                var postdata =  {"task_id" : '{{$task_id}}', rows: rows};
                
                gridAjax('/task_checklist_bulk_edit', postdata, 'post');
                
               
            }
        },
        
        actionBegin: actionBegin,
        rowDrop: function(args){
            /*
            //console.log('rowDrop');
            //console.log(args);
            
            var start_row = inline_grid.getRowByIndex(args.fromIndex);
            var stop_row = inline_grid.getRowByIndex(args.dropIndex);
            var start_row_info = inline_grid.getRowInfo(start_row);
            var stop_row_info = inline_grid.getRowInfo(stop_row); 
            var postdata = {position: args.dropPosition, id: start_row_info.rowData.id, target_id: stop_row_info.rowData.id}
           
       
            var url = 'task_checklist_row_drop/{{$task_id}}/'+start_row_info.rowData.id+'/'+stop_row_info.rowData.id+'/'+args.dropPosition;
            $.ajax({
                type: 'get',
                url: url,
                beforeSend: function(){
                    showSpinner('#menu_sort .e-gridcontent');
                },
                success: function(data) {
                    
                    hideSpinner('#menu_sort .e-gridcontent');
                    toastNotify('Menu updated.','info');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    hideSpinner('#menu_sort .e-gridcontent');
                    toastNotify('Menu update failed.','error');
                    
                }
            });
            */
        },
        columns: [
            {
                field: 'id', isPrimaryKey: true, headerText: 'ID', textAlign: 'Right',visible:false,
                validationRules: { required: false, number: true }, width: 80
            },
            {
                field: 'completed', headerText: 'Completed', width: 120, editType: 'booleanedit',
                type: 'boolean', displayAsCheckBox: true
            },
            {
                field: 'name', headerText: 'Name',
                validationRules: { required: true }
            },
        ],
    });
    inline_grid.appendTo('#inline_grid');

    function actionBegin(args) {
        //console.log('actionBegin');
        //console.log(args);
        
        var postdata = {type: args.requestType, action: args.action, post_data: args.data}; 
        
        $.ajax({
            type: 'post',
            url: '{{ url("/project_tasks_update/".$task_id) }}',
            data: postdata,
            success: function(data) {
              
            },
            error: function(jqXHR, textStatus, errorThrown) {
              
            }
        });

    }


 

</script>