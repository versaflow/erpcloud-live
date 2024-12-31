<div class="control-section w-100">
    <div class="content-wrapper">
        <div id="inline_grid"></div>
    </div>
</div>


<script>

    var inlinegrid_data = new ej.data.DataManager({
        url: '{{ url("/project_tasks_datasource/".$task_id) }}',
        adaptor: new ej.data.UrlAdaptor(),
        crossDomain: true,
    });
   // //console.log('inlinegrid_data');
    ////console.log(inlinegrid_data);
    
    var inline_grid = new ej.grids.Grid({
        
        dataSource: inlinegrid_data,
        editSettings: { allowEditing: true, allowAdding: true, allowDeleting: true, mode: 'Normal', newRowPosition:'Top' },
        allowPaging: false,
       // toolbar: ['Add', 'Edit', 'Delete', 'Update', 'Cancel','Search'],
        contextMenuItems: [{text: 'Add Record', id: 'customadd', iconCss: 'e-btn-icon e-add e-icons e-icon-left', target: '.e-content'},'Edit', 'Delete', 'Save', 'Cancel'],
        contextMenuClick: function(args){
         if(args.item.id == 'customadd'){
             inline_grid.addRecord();
         }
        },
        width: '100%',
        height: '100%',
        rowHeight: 20,
        allowRowDragAndDrop: true,   
        actionBegin: actionBegin,
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
        
        rowDrop: function(args){
            
            ////console.log('rowDrop');
            ////console.log(args);
            
            var start_row = inline_grid.getRowByIndex(args.fromIndex);
            var stop_row = inline_grid.getRowByIndex(args.dropIndex);
            var start_row_info = inline_grid.getRowInfo(start_row);
            var stop_row_info = inline_grid.getRowInfo(stop_row); 
          
           
            var url = 'task_checklist_row_drop/{{$task_id}}/'+start_row_info.rowData.id+'/'+stop_row_info.rowData.id;
            //     //console.log(url);
            $.ajax({
                type: 'get',
                url: url,
                beforeSend: function(){
                    showSpinner('#menu_sort .e-gridcontent');
                },
                success: function(data) {
                    
                    hideSpinner('#menu_sort .e-gridcontent');
                    //toastNotify('Menu updated.','info');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    hideSpinner('#menu_sort .e-gridcontent');
                    //toastNotify('Menu update failed.','error');
                    
                }
            });
        }
    });
    inline_grid.appendTo('#inline_grid');

    function actionBegin(args) {
        ////console.log('actionBegin');
        ////console.log(args);
        
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
    
    $(document).on('click','#inline_grid_clearbutton',function(){
        ////console.log('inline_grid_clearbutton');
        inline_grid.searchSettings.key='';
        //$("#inline_grid_searchbar").val('');
        //inline_grid.refresh(); 
    });

 

</script>