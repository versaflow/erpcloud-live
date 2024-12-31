@section('grid_sidebar')


<aside id="appsidebardiv" class="appsidebar @if(!empty($hide_toolbar_items)) d-none @endif" style="overflow-x: hidden;">
<div class="sidebar_contact_item"></div>
<div id="app_sidebar_tabs" class="e-background"></div>



<div id="guides_sidebar_tab" class="d-none guidestab">
<div id='guide_content' class="d-none"></div>
</div>

@if($db_table=='crm_product_categories')
<div id="category_sidebar_tab" class="d-none categorytab">
<div id='category_content' class=""></div>
</div>
@endif

@if( $module_id == 1923 )
<div id="callcenter_sidebar_tab" class="d-none callcentertab">
<div id='callcenter_content'></div>
</div>
@endif

@if($communications_panel)
@if($communications_type == 'account' || $communications_type == 'pbx')
<div id="subscriptions_sidebar_tab" class="d-none subscriptionstab">
<div id='subscriptions_content'></div>
</div>
@endif
<div id="account_sidebar_tab" class="d-none accounttab">
<div id='account_info' class="p-4"></div>
<div id='account_accordion'></div>
</div>
<div id="files_sidebar_tab" class="d-none filestab">
    <div id="files_form">
    <div id="sidebar_droparea" class="py-0 text-center" style="height: auto; overflow: auto">
    
    <input type="file" id="sidebar_fileupload">
    </div>
    </div>
    <div id="sidebar_files_result"></div>
</div>
@endif

<div id="notes_sidebar_tab" class="d-none notestab">
    <div id="notes_form">
    <textarea id="sidebar_note"  name="sidebar_note"></textarea>
    <button type="button" class="btn btn-sm k-button" id="addnotebtn">Add Note</button>
    </div>
    <div id="sidebar_notes_result"></div>
</div>


<div id="linked_modules_sidebar_tab" class="d-none linked_modulestab">
    <div id="sidebar_linked_modules_result"></div>
</div>

<div id="emails_sidebar_tab" class="d-none emailstab">
    <div id="sidebar_emails_result"></div>
</div>

</aside>
@stop


@push('sidebar-scripts')



<script>

/*
Call center functions
*/

    function checkCallCompleted(id){
        
        $.ajax({
            type: 'get',
            url: 'call_center_call_completed/'+id,
            success: function (data){
          
                if(data.status == 'success'){
                    $("#call_form_container").removeClass('d-none');
                }else{
                    toastNotify(data.message,data.status);
                }
            }
        })
    }
    
    function queueNextCall(id){
        ////console.log('queueNextCall 1');
        var comments = $("#call_comments").val();
        if(comments.length < 5){
            toastNotify('A detailed comment is required. Enter at least 10 characters','warning');
            return false;
        }
        var data = {id: id, call_comments: $("#call_comments").val(), call_status: $("#call_status").val()};
       
        ////console.log(data);
        $.ajax({
            type: 'post',
            data: data,
            url: 'call_center_queue_next',
            success: function (data){
              
                if(data.status == 'success'){
                    selectNextCallInQueue();
                }else{
                    toastNotify(data.message,data.status);
                }
            }
        })
    }
    
    function selectNextCallInQueue(){
     
       window['grid_{{ $master_grid_id }}'].gridOptions.refresh();
    }
    
    
</script>


<script>

$(document).on('click','.sublist_item', function(){

});

function minimize_app_sidebar(){
   
    if(window['app_sidebar']){
        setTimeout(function(){
            
            $("#guides_sidebar_tab").addClass('d-none');
            $("#guide_content").addClass('d-none');
            
            @if( $module_id == 1923 )
            $("#callcenter_sidebar_tab").addClass('d-none');
            $("#callcente_content").addClass('d-none');
            @else
            $("#guides_sidebar_tab").addClass('d-none');
            $("#guide_content").addClass('d-none');
            @endif
            window['app_sidebar'].width = 52;
            window['app_sidebar'].refresh();
            
        },500);
 
   
    }
}

function maximize_app_sidebar(){
   
    if(window['app_sidebar']){
        
        @if( $module_id == 1923 )
        $("#callcenter_sidebar_tab").removeClass('d-none');
        $("#callcente_content").removeClass('d-none');
        @else
        $("#guides_sidebar_tab").removeClass('d-none');
        $("#guide_content").removeClass('d-none');
        @endif
    
        var width = (isMobile())?'100%':'400';
        window['app_sidebar'].width = width;
        window['app_sidebar'].refresh();
    }
}

function set_layout_row_tracking_details(){

    if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
        var module_id = '{{$detail_module_id}}';
        var selected = window['selectedrow_detail{{ $master_grid_id }}'];
    }else{
        var module_id = '{{$module_id}}';
        var selected = window['selectedrow_{{ $master_grid_id }}']; 
    }
    var layout_id = window['layout_id{{ $master_grid_id }}'];
    $.get('layout_row_tracking_details/'+module_id+'/'+selected.rowId+'/'+layout_id, function(data){
   
        
        $("#layout_row_tracking_status").text(data.timer_status);
        $("#layout_row_tracking_start_time").text(data.start_time);
        $("#layout_row_tracking_duration").text(data.duration);
       
    });
   
}

$("#layout_row_tracking_start").click(function(){

    
    if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
        var module_id = '{{$detail_module_id}}';
        var selected = window['selectedrow_detail{{ $master_grid_id }}'];
    }else{
        var module_id = '{{$module_id}}';
        var selected = window['selectedrow_{{ $master_grid_id }}']; 
    }
    var layout_id = window['layout_id{{ $master_grid_id }}'];
    gridAjax('layout_row_tracking_start/'+module_id+'/'+selected.rowId+'/'+layout_id);
   
});

$("#layout_row_tracking_pause").click(function(){
   
    
    if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
        var module_id = '{{$detail_module_id}}';
        var selected = window['selectedrow_detail{{ $master_grid_id }}'];
    }else{
        var module_id = '{{$module_id}}';
        var selected = window['selectedrow_{{ $master_grid_id }}']; 
    }
    var layout_id = window['layout_id{{ $master_grid_id }}'];
    
    gridAjax('layout_row_tracking_pause/'+module_id+'/'+selected.rowId+'/'+layout_id);
   
    
});

$("#layout_row_tracking_complete").click(function(){
  
    
    if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
        var module_id = '{{$detail_module_id}}';
        var selected = window['selectedrow_detail{{ $master_grid_id }}'];
    }else{
        var module_id = '{{$module_id}}';
        var selected = window['selectedrow_{{ $master_grid_id }}']; 
    }
    var layout_id = window['layout_id{{ $master_grid_id }}'];
    
    gridAjax('layout_row_tracking_complete/'+module_id+'/'+selected.rowId+'/'+layout_id);  
   
});
$("#layout_row_tracking_incomplete").click(function(){
   
    
    if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
        var module_id = '{{$detail_module_id}}';
        var selected = window['selectedrow_detail{{ $master_grid_id }}'];
    }else{
        var module_id = '{{$module_id}}';
        var selected = window['selectedrow_{{ $master_grid_id }}']; 
    }
    var layout_id = window['layout_id{{ $master_grid_id }}'];
    
    gridAjax('layout_row_tracking_incomplete/'+module_id+'/'+selected.rowId+'/'+layout_id);  
   
})

    @if(is_dev())
     window['app_sidebar'] = new ej.navigations.Sidebar({
    animate:false,
    enableDock: true,
    closeOnDocumentClick: false,
    enableGestures: false,
    showBackdrop: false,
    type:'Push',
    target: '#grid_{{ $master_grid_id }}',
    position: 'Right',
    isOpen: true,
    width: (isMobile())?'100%':'400',
    zIndex:2000,
    created: function(args){
       
        create_sidebar_tabs();
        //setTimeout(minimize_app_sidebar(),500);
    },
    close: function(args){
        args.cancel = true
        
    },
    open: function(args){
    }
},"#appsidebardiv");
    @else
 window['app_sidebar'] = new ej.navigations.Sidebar({
    animate:false,
    enableDock: false,
    closeOnDocumentClick: false,
    enableGestures: false,
    showBackdrop: false,
    type:'Push',
    target: '#grid_{{ $master_grid_id }}',
    position: 'Right',
    isOpen: true,
    width: (isMobile())?'100%':'400',
    zIndex:2000,
    created: function(args){
       
        create_sidebar_tabs();
        //setTimeout(minimize_app_sidebar(),500);
    },
    close: function(args){
        args.cancel = true
        
    },
    open: function(args){
    }
},"#appsidebardiv");
@endif



function create_sidebar_tabs(){
    window['sidebar_tabs'] = new ej.navigations.Tab({
        heightAdjustMode: 'Auto',
        overflowMode: 'Scrollable',
        headerPlacement: 'Right',
        items: [
            
            @if( $module_id == 1923 )
            {
                id: 'callcenter_tab',
                cssClass: 'callcenter_tab',
                header: { 'title': 'Callcenter','iconCss': 'fas fa-user', 'cssClass': 'callcenter_tab'},
                content: '#callcenter_sidebar_tab'
            },
            @endif
            {
                id: 'guides_tab',
                cssClass: 'guides_tab',
                header: { 'title': 'Guides','iconCss': 'fas fa-book', 'cssClass': 'guides_tab'},
                content: '#guides_sidebar_tab'
            },
        
            @if($communications_panel)
            {
                id: 'account_tab',
                header: { 'title': 'Account','iconCss': 'far fa-user' },
                content: '#account_sidebar_tab'
            },
            @if($communications_type == 'account' || $communications_type == 'pbx')
            {
                id: 'subscriptions_tab',
                cssClass: 'subscriptions_tab',
                header: { 'title': 'Subscriptions','iconCss': 'far fa-list-alt', 'cssClass': 'subscriptions_tab'},
                content: '#subscriptions_sidebar_tab'
            },
            @endif
            @endif
           
          
            @if($communications_panel)
            {
                id: 'files_tab',
                header: { 'title': 'Files','iconCss': 'far fa-file-alt' },
                content: '#files_sidebar_tab'
            },
            @endif
            {
                id: 'notes_tab',
                header: { 'title': 'Files','iconCss': 'far fa-sticky-note' },
                content: '#notes_sidebar_tab'
            },
            
            {
                id: 'linked_modules_tab',
                header: { 'title': 'Linked Modules','iconCss': 'fas fa-link' },
                content: '#linked_modules_sidebar_tab'
            },
            {
                id: 'emails_tab',
                header: { 'title': 'Emails','iconCss': 'far fa-envelope' },
                content: '#emails_sidebar_tab'
            },
        	@if($db_table=='crm_product_categories')
            {
                id: 'category_tab',
                header: { 'title': 'Category','iconCss': 'fas fa-bullhorn' },
                content: '#category_sidebar_tab'
            },
            @endif
            {
                id: 'minimize_tab',
                header: { 'title': 'Minimize','iconCss': 'far fa-arrow-alt-circle-right' },
            },
           
        ],
        created: function(args){   
           
            create_sidebar_accordion();
           
            @if( $module_id == 1923 )
            $("#callcenter_sidebar_tab").removeClass('d-none');
         
            @else
            $("#guides_sidebar_tab").removeClass('d-none');
            @endif
            
            /* guides context menu */
            @if(is_superadmin())
            
         
                $('body').append('<ul id="guides_context" class="m-0"></ul>');
                var context_items = [{
                        id: "context_gridtab_edit",
                        text: "Edit Guide",
                        iconCss: "fas fa-list",
                        url: '{{$crud_url}}/edit/{{$module_id}}',
                        data_target: 'sidebarform',
                }
                ];
                var menuOptions = {
                    target: '#guides_sidebar_tab',
                    items: context_items,
                    beforeItemRender: dropdowntargetrender
                };
            
                // Initialize ContextMenu control
                window['app_sidebar_context'] = new ej.navigations.ContextMenu(menuOptions, '#guides_context');
                
                @if($communications_type > '')
                    $('body').append('<ul id="sidebar_account_context" class="m-0"></ul>');
                    $('body').append('<ul id="sidebar_contact_context" class="m-0"></ul>');
                    var context_items = [{
                            id: "context_gridtabaccount_edit",
                            text: "Edit",
                            iconCss: "fas fa-list",
                            url: '#',
                    }
                    ];
                    var menuOptions = {
                        target: '#account_info',
                        items: context_items,
                        beforeItemRender: dropdowntargetrender,
                        select: function(args){
                            if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
                            var module_id = '{{$detail_module_id}}';
                            var selected = window['selectedrow_detail{{ $master_grid_id }}'];
                            }else{
                            var module_id = '{{$module_id}}';
                            var selected = window['selectedrow_{{ $master_grid_id }}']; 
                            }
                            var selected_id = 0;
                            @if($db_table == 'crm_accounts' || $db_table == 'crm_suppliers')
                            selected_id = selected.rowId;
                            @elseif($communications_type == 'account')
                            selected_id = selected.account_id;
                            @elseif($communications_type == 'pbx')
                            selected_id = selected.domain_uuid;
                            @elseif($communications_type == 'supplier')
                            selected_id = selected.supplier_id;
                            @endif
                           
                            @if($communications_type == 'account')
                            sidebarform('accountedit','{{$accounts_url}}/edit/'+selected_id);
                            @elseif($communications_type == 'pbx')
                            sidebarform('accountedit','account_edit_pbx_domain/'+selected_id);
                            @elseif($communications_type == 'supplier')
                            sidebarform('accountedit','{{$suppliers_url}}/edit/'+selected_id);
                            @endif
                        }
                    };
                
                    // Initialize ContextMenu control
                    window['app_sidebar_context_account'] = new ej.navigations.ContextMenu(menuOptions, '#sidebar_account_context');
                    
                     var context_items = [{
                            id: "context_gridtabcontact_edit",
                            text: "Edit Contact",
                            iconCss: "fas fa-list",
                            url: '#',
                    }
                    ];
                    var menuOptions = {
                        target: '.sidebar_contact_item',
                        items: context_items,
                        beforeItemRender: dropdowntargetrender,
                        
                        beforeOpen: function(args){
                            contact_context_id{{$grid_id}} = false;
                            
                            if( $(args.event.target).hasClass('sidebar_contact_item')){
                                contact_context_id{{$grid_id}} = $(args.event.target).attr('data-contact-id');
                            }else{
                                contact_context_id{{$grid_id}} = $(args.event.target).closest('.sidebar_contact_item').attr('data-contact-id');
                            }
                        },
                        select: function(args){
                            if(!contact_context_id{{$grid_id}}){
                                toastNotify('No contact id found','warning');
                                
                            }else{
                                
                                @if($communications_type == 'account')
                                sidebarform('accountedit','{{$accounts_contact_url}}/edit/'+contact_context_id{{$grid_id}});
                                @elseif($communications_type == 'supplier')
                                sidebarform('accountedit','{{$suppliers_contact_url}}/edit/'+contact_context_id{{$grid_id}});
                                @endif
                            }
                        }
                    };
                
                    // Initialize ContextMenu control
                    window['app_sidebar_contact_context'] = new ej.navigations.ContextMenu(menuOptions, '#sidebar_contact_context');
                @endif
         
            @endif
           
            
        },
        selected: function(args){
            
           
            if(this.getItemIndex('minimize_tab') == args.selectedIndex){
                minimize_app_sidebar();
            }else{
                maximize_app_sidebar();
            }
            if(this.getItemIndex('guides_tab') == args.selectedIndex){
                $("#guides_sidebar_tab").removeClass('d-none');
            }
          
            @if($communications_panel)
            @if($communications_type == 'account' || $communications_type == 'pbx')
          
            if(this.getItemIndex('subscriptions_tab') == args.selectedIndex){
                $("#subscriptions_sidebar_tab").removeClass('d-none');
            }
            @endif
            if(this.getItemIndex('account_tab') == args.selectedIndex){
                $("#account_sidebar_tab").removeClass('d-none');
            }
            
            if(this.getItemIndex('files_tab') == args.selectedIndex){
                $("#files_sidebar_tab").removeClass("d-none");
            }
            @endif
          
            if(this.getItemIndex('notes_tab') == args.selectedIndex){
                $("#notes_sidebar_tab").removeClass("d-none");
            }
            
            if(this.getItemIndex('linked_modules_tab') == args.selectedIndex){
                $("#linked_modules_sidebar_tab").removeClass("d-none");
            }
            if(this.getItemIndex('category_tab') == args.selectedIndex){
                $("#category_sidebar_tab").removeClass("d-none");
            }
            
            if(this.getItemIndex('emails_tab') == args.selectedIndex){
                $("#emails_sidebar_tab").removeClass("d-none");
            }
            
            if(this.getItemIndex('callcenter_tab') == args.selectedIndex){
                $("#callcenter_sidebar_tab").removeClass('d-none');
            }
        }
    });
    
    //Render initialized Tab component
    window['sidebar_tabs'].appendTo('#app_sidebar_tabs');    
}


function refresh_guide_content{{$module_id}}(){
	$.get('app_sidebar_guides_datasource/{{$module_id}}', function(data) {
        $("#guide_content").html(data);
	});
}

function create_sidebar_accordion(){
    
    
	
    
	$.get('app_sidebar_guides_datasource/{{$module_id}}', function(data) {
        $("#guide_content").html(data);
	});
	

	
	@if($communications_panel)
	    render_account_accordion([]);
	    render_sidebar_files_uploader();
	@endif
	render_notes_textarea();
	refresh_sidebar_emails();

    
}








function render_account_accordion(datasource){
  
    window['account_accordion'] = new ej.navigations.Accordion({
        items: datasource,
        expandMode: 'Single',
        expanded: function(args){
              window['app_sidebar_contact_context'].refresh();
        }
    });
    
    //Render initialized Accordion component
    window['account_accordion'].appendTo('#account_accordion'); 
}

    function refresh_sidebar_emails(){
        if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
        var module_id = '{{$detail_module_id}}';
        var selected = window['selectedrow_detail{{ $master_grid_id }}'];
        }else{
        var module_id = '{{$module_id}}';
        var selected = window['selectedrow_{{ $master_grid_id }}']; 
        }
        var selected_id = 0;
       
        if(selected && selected.rowId){
            selected_id = selected.rowId;
        }
        
        $.get('app_sidebar_emails_datasource/'+module_id+'/'+selected_id, function(data) {
       
            $("#sidebar_emails_result").html(data);
        });
    }
    
	@if($db_table=='crm_product_categories')
    function refresh_sidebar_category(){
        if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
        var module_id = '{{$detail_module_id}}';
        var selected = window['selectedrow_detail{{ $master_grid_id }}'];
        }else{
        var module_id = '{{$module_id}}';
        var selected = window['selectedrow_{{ $master_grid_id }}']; 
        }
        var selected_id = 0;
       
        if(selected && selected.rowId){
            selected_id = selected.rowId;
        }
        
        
    }
    @endif
    
    function refresh_sidebar_guides(){
        if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
        var module_id = '{{$detail_module_id}}';
        var selected = window['selectedrow_detail{{ $master_grid_id }}'];
        }else{
        var module_id = '{{$module_id}}';
        var selected = window['selectedrow_{{ $master_grid_id }}']; 
        }
        var selected_id = 0;
     
        if(module_id == 1944 && selected && selected.id){
            selected_id = selected.id;
        }else if(selected && selected.module_id){
            selected_id = selected.module_id;
        }
      
        if(module_id == 1944){
        
        	$.get('app_sidebar_guides_datasource/1944/'+selected_id, function(data) {
                $("#guide_content").html(data);
        	});
        }else if(selected_id){
        	$.get('app_sidebar_guides_datasource/'+selected_id, function(data) {
                $("#guide_content").html(data);
        	});
            

        }else{
             $("#guide_content").html('');
        }
    }
    
    function refresh_sidebar_notes(){
        if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
        var module_id = '{{$detail_module_id}}';
        var selected = window['selectedrow_detail{{ $master_grid_id }}'];
        }else{
        var module_id = '{{$module_id}}';
        var selected = window['selectedrow_{{ $master_grid_id }}']; 
        }
        var selected_id = 0;
       
        if(selected && selected.rowId){
            selected_id = selected.rowId;
        }
        
        $.get('app_sidebar_notes_datasource/'+module_id+'/'+selected_id, function(data) {
       
            $("#sidebar_notes_result").html(data);
        });
    }
    
    function refresh_sidebar_linked_modules(){
        if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
        var module_id = '{{$detail_module_id}}';
        var selected = window['selectedrow_detail{{ $master_grid_id }}'];
        }else{
        var module_id = '{{$module_id}}';
        var selected = window['selectedrow_{{ $master_grid_id }}']; 
        }
        var selected_id = 0;
       
        if(selected && selected.rowId){
            selected_id = selected.rowId;
        }
        
        $.get('app_sidebar_linked_modules_datasource/'+module_id+'/'+selected_id, function(data) {
       
            $("#sidebar_linked_modules_result").html(data);
        });
    }
    
    $(document).off('click', '#addnotebtn').on('click', '#addnotebtn', function() {
          if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
            var module_id = '{{$detail_module_id}}';
            var selected = window['selectedrow_detail{{ $master_grid_id }}'];
            }else{
            var module_id = '{{$module_id}}';
            var selected = window['selectedrow_{{ $master_grid_id }}']; 
            }
            var selected_id = 0;
            if(selected && selected.rowId){
                selected_id = selected.rowId;
            }
        
        if(!selected_id){
            
            toastNotify('Select a record','warning');
        }else{
          
            var note = $("#sidebar_note").val();
            if(note > ''){
                
         
                   $.ajax({
                    url: '/{{$menu_route}}/addnote',
                    type:'post',
                    data: {note:note,module_id: module_id, row_id:selected_id},
                    success: function(data) { 
                       
                        $("#sidebar_note").val('');
                        refresh_sidebar_notes();
                    }
                  });  
            }else{
                toastNotify('Note field cannot be blank','warning');
            }
        }   
    });
    
    $(document).off('click', '.deletenotebtn').on('click', '.deletenotebtn', function() {

        var note_id = $(this).attr('data-note-id');
        if(note_id > ''){
            $.ajax({
                url: '/{{$menu_route}}/deletenote',
                type:'post',
                data: {note_id: note_id},
                success: function(data) { 
   
                                refresh_sidebar_notes();
                }
            });  
        }
    });
    
    @if($module_id == 1923 )
	    function refresh_sidebar_callcenter(){
            
            if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
              
            var module_id = '{{$detail_module_id}}';
            var selected = window['selectedrow_detail{{ $master_grid_id }}'];
            }else{
               
            var module_id = '{{$module_id}}';
            var selected = window['selectedrow_{{ $master_grid_id }}']; 
            }
           
            var selected_id = 0;
           
            selected_id = selected.account_id;
            
            
            
           
            $.get('app_sidebar_callcenter/'+module_id+'/'+selected.rowId+'/'+selected_id, function(data) {
                
                $("#callcenter_content").html(data);
            });
        }
	@endif
@if($communications_panel)
    function refresh_sidebar_files(){
        if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
        var module_id = '{{$detail_module_id}}';
        var selected = window['selectedrow_detail{{ $master_grid_id }}'];
        }else{
        var module_id = '{{$module_id}}';
        var selected = window['selectedrow_{{ $master_grid_id }}']; 
        }
        var selected_id = 0;
        @if($db_table == 'crm_accounts' || $db_table == 'crm_suppliers')
        selected_id = selected.rowId;
        @elseif($communications_type == 'account')
        selected_id = selected.account_id;
        @elseif($communications_type == 'pbx')
        selected_id = selected.domain_uuid;
        @elseif($communications_type == 'supplier')
        selected_id = selected.supplier_id;
        @endif
        
        
        
        $.get('app_sidebar_files_datasource/{{$communications_type}}/'+selected_id, function(data) {
           
            $("#sidebar_files_result").html(data);
        });
    }
	
    function refresh_account_accordion(){
       
        
        if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
        var module_id = '{{$detail_module_id}}';
        var selected = window['selectedrow_detail{{ $master_grid_id }}'];
        }else{
        var module_id = '{{$module_id}}';
        var selected = window['selectedrow_{{ $master_grid_id }}']; 
        }
        var selected_id = 0;
        @if($db_table == 'crm_accounts' || $db_table == 'crm_suppliers')
        selected_id = selected.rowId;
        @elseif($communications_type == 'account')
        selected_id = selected.account_id;
        @elseif($communications_type == 'pbx')
        selected_id = selected.domain_uuid;
        @elseif($communications_type == 'supplier')
        selected_id = selected.supplier_id;
        @endif
        
   
        
        $.get('app_sidebar_account_datasource/{{$communications_type}}/'+selected_id, function(data) {
      
            window['account_accordion'].items = data.accordion;
            window['account_accordion'].refresh();
            $("#account_info").html(data.info);
       
          
        });
       
        @if($communications_type == 'account' || $communications_type == 'pbx' || $module_id == 343)
      
        
        
        var grid_account_id = selected.account_id;
        @if($module_id == 343)
     
        var grid_account_id = selected_id;
        @endif
       
        $.get('app_sidebar_subscription_datasource/'+module_id+'/'+selected.rowId+'/'+grid_account_id, function(data) {
           
            $("#subscriptions_content").html(data);
        });
        @endif
    }
@endif







function render_notes_textarea(){
    // Initialize TextBox component
    var textareaObject = new ej.inputs.TextBox({
        placeholder: 'Enter note',
    },'#sidebar_note');
     
}

 function render_sidebar_files_uploader(){
  
        if(typeof window['sidebar_filesuploader'] === 'undefined'){
        }else{
            window['sidebar_filesuploader'].destroy();    
        }
        window['sidebar_filesuploader'] =  new ej.inputs.Uploader({
        asyncSettings: {
        saveUrl: '{{$menu_route}}/addfile',
        },
        htmlAttributes: {name: 'file_name[]'},
        showFileList: true,
        dropArea: document.getElementById('sidebar_droparea'),
        uploading: function(args){
         
            if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
            var module_id = '{{$detail_module_id}}';
            var selected = window['selectedrow_detail{{ $master_grid_id }}'];
            }else{
            var module_id = '{{$module_id}}';
            var selected = window['selectedrow_{{ $master_grid_id }}']; 
            }
           
            var selected_id = selected.rowId;
            @if($db_table == 'crm_accounts' || $db_table == 'crm_suppliers')
            selected_id = selected.rowId;
            @elseif($communications_type == 'account')
            selected_id = selected.account_id;
            @elseif($communications_type == 'supplier')
            selected_id = selected.supplier_id;
            @endif
            
           
            
            if(!selected_id){
                toastNotify('Select a record','warning');
                args.cancel=true;
            }else{
                var upload_module_id = module_id;
                @if($communications_type == 'account')
                var upload_module_id = 343;    
                @endif
                @if($communications_type == 'supplier')
                var upload_module_id = 78;    
                @endif
                
                args.customFormData = [{row_id:selected_id},{module_id: upload_module_id},{communications_type:'{{$communications_type}}'}];
            } 
        },
        success: function(args){
            refresh_sidebar_files();
        },
        failure: function(args){
         
            toastNotify('File upload failed','warning');
        },
        },'#sidebar_fileupload');
       
        // render initialized Uploader
    }
    
    $(document).off('click', '.deletefiletbtn').on('click', '.deletefiletbtn', function() {
      
        var file_id = $(this).attr('data-file-id');
        if(file_id > ''){
               $.ajax({
                url: '/{{$menu_route}}/deletefile',
                type:'post',
                data: {file_id: file_id},
                success: function(data) { 
            refresh_sidebar_files();
                }
              });  
        }
    });
</script>
@endpush

@push('page-styles') 

<style>
#appsidebar_accordion .e-acrdn-content {
    padding: 10px;
}
#appsidebar_accordion{
    border: none !important;
}
.e-accordion .e-acrdn-item.e-select.e-selected:first-child {
    border-top: 0px solid #eaeaea;
}

#appsidebardiv .e-toolbar .e-toolbar-items .e-toolbar-item:not(.e-separator) {
    padding-left: 0px !important;
}

.e-accordion .e-acrdn-item.e-selected.e-select>.e-acrdn-header {
    background: #d1ebff;
    border-width: 0px;
}
.sidebar-btn{
    font-size: 12px !important;
    padding: 3px 6px !important;
}
#app_sidebar_tabs .e-icons.e-tab-icon{padding-left:2px;}

#app_sidebar_tabs .e-tab-header .e-toolbar-item .e-tab-wrap {
    padding: 0 6px;
}
#app_sidebar_tabs .e-tab-header .e-toolbar-item .e-tab-icon, .e-#app_sidebar_tabs .e-tab-header .e-toolbar-item .e-tab-icon::before {
    font-size: 14px;
}
.staff-absent .e-acrdn-header{background:red !important;}
#guides_sidebar_tab{min-height:300px}
#appsidebardiv .e-tab-header {
    position: sticky !important; 
    height: 40px;
    top: 0;
    z-index: 9999;
    background-color: #f1f1f1;
}
  #app_sidebar_tabs  {       
height: 100% !important; 
} 
#app_sidebar_tabs .e-tab-header .e-toolbar-item:last-child {
    width:100%;
}
 #appsidebardiv .e-toolbar .e-toolbar-pop {        
        position: inherit !important; 
    } 
    .trackingbtn{font-size:12px;height:26px;}
</style>
@endpush