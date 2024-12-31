<div class="control-section p-2">
    <div class="content-wrapper">
        <div id="menu_sort">
        </div>
        
    </div>
</div>
<style>
    
    #menu_sort_gridcontrol_toolbarItems{
        position: -webkit-sticky !important;
        position: sticky !important;
        top: 45px !important;
        z-index: 1 !important;
    }
</style>
<script>
    location_select = null;
    menu_location = '{{$location}}';
    menu_id_to_delete = null;
    function render_menu_select(){
        
        if(location_select == null){
        var menu_locations = {!! json_encode($locations) !!};
        
        // initialize DropDownList component
        location_select = new ej.dropdowns.DropDownList({
            //set the data to dataSource property
            dataSource: menu_locations,
            select:function(args){
               
                menu_location = args.itemData.value;
                update_menu_manager_datasource();
            }
        });
        location_select.appendTo('#location');
     
        }
    }
    
    function update_menu_manager_datasource(){
        //console.log('update_menu_manager_datasource');
        if(menu_location){
            $.ajax({
               url: 'sf_menu_manager_datasource/'+menu_location+'/{{$module_id}}',
               success: function(data){
                   menu_manager.dataSource = data;
                   menu_manager.refresh();
                }
            });
        }
    }
    
    menu_manager = new ej.treegrid.TreeGrid({
        dataSource: {!! json_encode($menu_data) !!},
        enableStickyHeader: true,
        //toolbar: ['Add', 'Edit', 'Delete', {text: 'Permissions', tooltipText: 'Permissions', id: 'permissions'}, {text: 'Select Menu', tooltipText: 'Select Menu', id: 'location'}],
        toolbar: [{text: 'Save', tooltipText: 'Save', id: 'save_menu'},'Add', 'Edit', 'Delete', {text: 'Duplicate', tooltipText: 'Duplicate', id: 'duplicate'}, {text: 'Build Permissions', tooltipText: 'Build Permissions', id: 'buildpermissions'}, {text: 'Permissions', tooltipText: 'Permissions', id: 'permissions'}],
        toolbarClick: function (args) {
            if (args.item.id === 'location') {
               // render_menu_select();
            }
            if (args.item.id === 'permissions') {
                var records = menu_manager.getSelectedRecords();
                if(records.length > 0){
                    var menu_id = records[0].id;
                    sidebarview('menu_permissions','menu_permissions/'+menu_id,records[0].menu_name+ ' permissions');
                }
            }
            if (args.item.id === 'buildpermissions') {
               gridAjax('menu_manager_build_permissions', null, 'get');
            }
            if (args.item.id === 'duplicate') {
                var records = menu_manager.getSelectedRecords();
                if(records.length > 0){
                    var menu_id = records[0].id;
                    
                    gridAjax('/{{ $menu_manager_url }}/duplicate', {"id" : menu_id}, 'post');
                }
            }
            
            if (args.item.id === 'save_menu') {
              
                var records = menu_manager.getCurrentViewRecords();
                var rows = [];
                $.each(records, function(i,el){
                    rows.push({'id':el.id,'parent_id':el.parent_id,'menu_name':el.menu_name});
                });
                //console.log(rows);
               
                var module_id = '{{$module_id}}';
                if(module_id){
                    var postdata =  {"location" : menu_location, rows: rows};
                }else{
                    var postdata =  {"module_id" : module_id,"location" : menu_location, rows: rows};
                }
                gridAjax('/menu_manager_bulk_edit', postdata, 'post');
                
               
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
              
                var url = '{{$menu_manager_url}}/edit?location='+menu_location;
                if(menu_location == 'related_items_menu' || menu_location == 'module_actions' || menu_location == 'grid_menu'){
                    var url = url+'&render_module_id={{$module_id}}';
                }
                //console.log(url);
                sidebarform('menu_add',url, 'Add Menu');
            }
            if(args.requestType == 'beginEdit'){
                args.cancel = true;
                var records = menu_manager.getSelectedRecords();
                if(records.length > 0){
                    var menu_id = records[0].id;
                
                    sidebarform('menu_add','{{$menu_manager_url}}/edit/'+menu_id, 'Edit Menu - '+records[0].menu_name);
                }
            }
            if(args.requestType == 'delete'){
                var records = menu_manager.getSelectedRecords();
                if(records.length > 0){
                    menu_id_to_delete = records[0].id;
                }
            }
            ////console.log('actionBegin');
            ////console.log(args);
        },
        recordDoubleClick: function(args){
            args.cancel=true;
        },
        actionComplete: function(args){
            if(args.requestType == 'delete'){
                
                if(menu_id_to_delete != null){
                    var menu_id = menu_id_to_delete;
                    gridAjax('/{{ $menu_manager_url }}/delete', {"id" : menu_id}, 'post');
                }
                menu_id_to_delete = null;
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
            { field: 'menu_name', headerText: 'Menu Name', width: 200, textAlign: 'Left' },
            { field: 'menu_type', headerText: 'Menu Type', width: 200, textAlign: 'Left' },
        ],
        created: function(args){
            //setTimeout(function(){render_menu_select();},500);
        },
        rowDrop: function(args){
            
            ////console.log(args);
            ////console.log(menu_location);
            /*
            if(menu_location =='gridtab' && args.dropPosition == "middleSegment"){
                args.cancel=true;
            }else{
            
                var start_row = menu_manager.getRowByIndex(args.fromIndex);
                var stop_row = menu_manager.getRowByIndex(args.dropIndex);
                var start_row_info = menu_manager.getRowInfo(start_row);
                var stop_row_info = menu_manager.getRowInfo(stop_row); 
                var postdata = {position: args.dropPosition, id: start_row_info.rowData.id, target_id: stop_row_info.rowData.id}
               
           
                var url = 'menu_sort_ajax/{{$location}}/'+start_row_info.rowData.id+'/'+stop_row_info.rowData.id+'/'+args.dropPosition;
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
            }
            */
        }
    });
    menu_manager.appendTo('#menu_sort');

</script>