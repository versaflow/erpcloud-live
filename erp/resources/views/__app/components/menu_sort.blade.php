<?php
  
    if($location == 'unlisted'){
     
        $template = '<div>
        <span class="far fa-edit menu-edit menu-action" onClick="menu_edit(${id})" data-nav-id="${id}"></span> <span class="fas fa-key menu-permissions menu-action" onClick="menu_permissions(${id})" data-nav-id="${id}" ></span> <span class="fas fa-trash menu-delete menu-action" onClick="menu_delete(${id})" data-nav-id="${id}"> </span>${id} ${text}
        <span class="e-badge e-badge-warning" style="margin-left:10px;margin-top: 5px;font-size: 12px;">${location}</span>
        </div>';
    }else{
       
        if(!check_access('1,31')){
            $template = '<div class="row mx-0 align-items-center">
            <span class="far fa-edit menu-edit menu-action mr-1" onClick="menu_edit(${id})" data-nav-id="${id}"></span> 
            <span class="fas fa-key menu-permissions menu-action mr-1" onClick="menu_permissions(${id})" data-nav-id="${id}" ></span> 
            ${text}
            <span class="e-badge e-badge-primary" style="margin-left:10px;margin-top: 5px;font-size: 12px;">${access}</span>
            ${if(target>"")}<span class="e-badge e-badge-warning" style="margin-left:10px;margin-top: 5px;font-size: 12px;">${target}</span>${/if}
            </div>';
        }else{
            $template = '<div class="row mx-0 align-items-center">
            <span class="far fa-edit menu-edit menu-action mr-1" onClick="menu_edit(${id})" data-nav-id="${id}"></span>
            <span class="fas fa-key menu-permissions menu-action mr-1" onClick="menu_permissions(${id})" data-nav-id="${id}" >
            </span> <span class="fas fa-trash menu-delete menu-action mr-1" onClick="menu_delete(${id})" data-nav-id="${id}"> </span>
            ${text}
            <span class="e-badge e-badge-primary" style="margin-left:10px;margin-top: 5px;font-size: 12px;">${access}</span>
            ${if(target>"")}<span class="e-badge e-badge-warning" style="margin-left:10px;margin-top: 5px;font-size: 12px;">${target}</span>${/if}
            </div>';
        }
    }
?>
<div id="{{$location}}treeview"></div>
<script>
    
    var {{$location}}data = new ej.data.DataManager({
        url: '/menu_datasource/{{$location}}/{{$module_id}}',
        adaptor: new ej.data.UrlAdaptor(),
        crossDomain: true,
    });
    
    window['{{$location}}treeObj'] = new ej.navigations.TreeView({
            fields: {   
                        dataSource: {{$location}}data, 
                        id: 'id', 
                        parentID: 'parentID',
                        hasChildren: 'hasChildren', 
                        text: 'text',
                    },
            fullRowSelect: false,
            allowDragAndDrop: true,
            nodeDropped: function(args) {
                //console.log('nodeDropped');
                //console.log(args);
                var postdata = {
                    id: args.draggedNodeData.id, // moved node
                    replace_id: args.droppedNodeData.id, // dropped on node
                    order: args.dropIndex, // dropIndex before or after dropped on node
                    parent_id: args.draggedNodeData.parentID, // moved node
                    replace_parent_id:  args.droppedNodeData.parentID, // dropped on node
                    level: args.dropLevel,
                    is_subitem: args.droppedNodeData.expanded,
                };
              
                
                $.ajax({
                    type: 'post',
                    url: '/menu_manager/sort',
                    data: postdata,
                    success: function(data) {
                        window['{{$location}}treeObj'].refresh();
                        toastNotify('Saved', 'success');
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        window['{{$location}}treeObj'].refresh();
                        if (error_msg == '')
                            error_msg = textStatus;
                        toastNotify(error_msg, 'error');
                        
                    }
                });
            },

            @if($template)
                nodeTemplate: '#{{$location}}treeTemplate',
            @endif
    });
 
    window['{{$location}}treeObj'].appendTo('#{{$location}}treeview');
    

        
</script>
<style>
    .menu-action{
        padding:1px;
    }
    
</style>
@if($template)
    <script id="{{$location}}treeTemplate" type="text/x-template">
    {!! $template!!}
    </script>
@endif