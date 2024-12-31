<div class="control-section p-2">
    <div class="content-wrapper">
        <div id="layout_sort">
        </div>
        
    </div>
</div>
<script>
  
 
    layout_id_to_delete = null;
    layout_manager = new ej.treegrid.TreeGrid({
        dataSource: {!! json_encode($layout_data) !!},
        toolbar: [{text: 'Save', tooltipText: 'Save', id: 'save_menu'},'Add', 'Edit', 'Delete', {text: 'Duplicate', tooltipText: 'Duplicate', id: 'duplicate'}],
        toolbarClick: function (args) {
            if (args.item.id === 'save_menu') {
              
                var records = layout_manager.getCurrentViewRecords();
                var rows = [];
                $.each(records, function(i,el){
                    rows.push({'id':el.id,'parent_id':el.parent_id,'name':el.name});
                });
                //console.log(rows);
               
                var module_id = '{{$module_id}}';
              
                var postdata =  {"module_id" : module_id, rows: rows};
                
                gridAjax('/layouts_sort_save', postdata, 'post');
                   
            }
            
            if (args.item.id === 'duplicate') {
                var records = layout_manager.getSelectedRecords();
                if(records.length > 0){
                    var layout_id = records[0].id;
                    
                    gridAjax('/{{ $layouts_url }}/duplicate', {"id" : layout_id}, 'post');
                }
            }
        },
        
        enableAltRow: true,
        enableCollapseAll: true,
        selectionSettings: {type:'Single',mode:'Row'},
        editSettings: {
            allowAdding: true,
            allowEditing: true,
            allowDeleting: true,
            showDeleteConfirmDialog: true,
            mode: 'Row',
            newRowPosition: 'Above'
        },
        actionBegin: function(args){
          
            
            
            if(args.requestType == 'add'){
                args.cancel = true;
              
                var url = '{{$layouts_url}}/edit?module_id={{$module_id}}';
               
               
                sidebarform('layout_add',url, 'Add Layout');
            }
            if(args.requestType == 'beginEdit'){
                args.cancel = true;
                var records = layout_manager.getSelectedRecords();
                if(records.length > 0){
                    var layout_id = records[0].id;
                
                    sidebarform('layout_add','{{$layouts_url}}/edit/'+layout_id, 'Edit Layout - '+records[0].layout_name);
                }
            }
            if(args.requestType == 'delete'){
                var records = layout_manager.getSelectedRecords();
                if(records.length > 0){
                    layout_id_to_delete = records[0].id;
                }
            }
            ////console.log('actionBegin');
            ////console.log(args);
        },
        actionComplete: function(args){
            if(args.requestType == 'delete'){
                
                if(layout_id_to_delete != null){
                    var layout_id = layout_id_to_delete;
                    gridAjax('/{{ $layouts_url }}/delete', {"id" : layout_id}, 'post');
                }
                layout_id_to_delete = null;
            }
            ////console.log('actionComplete');
            ////console.log(args);
        },
        idMapping: 'id',
        treeColumnIndex: 1,
        allowRowDragAndDrop: true,
        parentIdMapping: 'parent_id',
        columns: [
            { field: 'id', headerText: 'Layout ID', isPrimaryKey: true, width: 50, textAlign: 'Right' },
            { field: 'name', headerText: 'Layout Name', width: 200, textAlign: 'Left' },
            { field: 'type', headerText: 'Layout Type', width: 200, textAlign: 'Left' },
        ],
        created: function(args){
            //setTimeout(function(){render_layout_select();},500);
        },
    });
    layout_manager.appendTo('#layout_sort');

</script>