@php 
if($communications_type){
    $first_tab = 'customer';
}elseif($show_subscriptions_tab){
    $first_tab = 'services';
}else{
    $first_tab = 'system';
}
@endphp

<div class="card h-100 " id="content_sidebar">
    <div id="sidebartitle{{$grid_id}}" class="text-center">All Customers</div>
    <input type="hidden" id="sidebar_accountid{{$grid_id}}" />
    <div class="card-header p-0">
        <ul class="nav nav-pills nav-fill pt-1 p-1" role="tablist" id="right_sidebar_ul">
                     
                @if($communications_type)
                <li class="nav-item" id="content_sidebar_row_info_li" name="Customer">
                    <a class="nav-link mb-0 px-0 py-0 " data-bs-toggle="tab" href="#content_sidebar_row_info{{$grid_id}}" role="tab"  title="Customer Info">
                    <i class="fas fa-user text-sm me-1"></i><br>Customer
                    </a>
                </li>
                @endif
                
                @if($show_subscriptions_tab)  
                    <li class="nav-item" id="content_sidebar_services_li" name="Services">
                        <a id="content_sidebar_first_tab" class="nav-link mb-0 px-0 py-0 " data-bs-toggle="tab" href="#content_sidebar_telecloud{{$grid_id}}" role="tab"  title="Subscriptions">
                            <i class="fas fa-cloud  text-sm me-1"></i><br>Services
                        </a>
                    </li>
                @endif
                
                @if(session('role_level') == 'Admin')
                    @if($show_products_tab)
                    <li class="nav-item" id="content_sidebar_products_li" name="Products">
                        <a class="nav-link mb-0 px-0 py-0 " data-bs-toggle="tab" href="#content_sidebar_products{{$grid_id}}" role="tab"  title="Products">
                            <i class="fas fa-box-open text-sm me-1"></i><br>Products
                        </a>
                    </li>
                    @endif
                @endif
                
                @if(session('role_level') == 'Admin')
                    <li class="nav-item" id="content_sidebar_grid_li" name="Related">
                        <a id="content_sidebar_first_tab" class="nav-link mb-0 px-0 py-0 " data-bs-toggle="tab" href="#content_sidebar_grid{{$grid_id}}" role="tab"  title="Library">
                            <i class="fas fa-desktop  text-sm me-1"></i><br>Library
                        </a>
                    </li>
                @endif
                
                @if(is_superadmin())
                    <li class="nav-item" id="content_sidebar_support_li" name="Operations">
                        <a class="nav-link mb-0 px-0 py-0 " data-bs-toggle="tab" href="#content_sidebar_support{{$grid_id}}" role="tab"  title="Superadmin">
                            <i class="fas fa-server  text-sm me-1"></i><br>Operations
                        </a>
                    </li>
                @endif
        </ul>
    </div>
    <div class="card-body p-sm-1 p-md-2" >
    <div class="tab-content" id="nav-tabContent" style=" font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif,'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol' !important">
        
        
        @if(session('role_level') == 'Admin') 
        <div class="tab-pane fade @if($first_tab == 'system') active show @endif" id="content_sidebar_grid{{$grid_id}}">
         
             @if(session('role_level') == 'Admin')
            <div class="row m-0 p-0 d-none">
        
            <div class="col  p-1"> 
            <input type="text" class="form-control" placeholder="Type here..."  id="global_search{{$grid_id}}">
            </div>
            </div>
            @endif
            
            <div class="text-muted p-2 text-center">Right click to view options</div> -->
           
            <div id='accordion_html_superadmin{{$grid_id}}'></div>
            <div id='accordion_html_system{{$grid_id}}'></div>

       </div>
    
        @endif
        @if($communications_type)
        <div class="tab-pane fade @if($first_tab == 'customer') active show @endif" id="content_sidebar_row_info{{$grid_id}}">
            @if(session('role_level') == 'Admin')
            <div class="row m-0 p-0 d-none">
            <div class="col  p-1"> 
            <input type="text" class="form-control" placeholder="Type here..."  id="customer_search{{$grid_id}}">
            </div>
            </div>
            @endif
            <div class="text-muted p-2 text-center">Right click to view options</div>
        <div id="content_rowinfo_accordion{{$grid_id}}"></div>
        </div>
        @endif
        
        
        
        @if(session('role_level') == 'Admin') 
        @if($show_products_tab)
        <div class="tab-pane fade " id="content_sidebar_products{{$grid_id}}">
            @if(session('role_level') == 'Admin')
            <div class="row m-0 p-0 d-none">
            <div class="col  p-1"> 
            <input type="text" class="form-control" placeholder="Type here..."  id="product_search{{$grid_id}}">
            </div>
            </div>
            @endif
            <div class="text-muted p-2 text-center">Right click to view options</div>
            <div id="content_products_accordion{{$grid_id}}"></div>
        </div>
        @endif
        @endif
        
        @if($show_subscriptions_tab)  
        <div class="tab-pane fade @if($first_tab == 'services') active show @endif" id="content_sidebar_telecloud{{$grid_id}}"> 
        
            @if($show_subscriptions_tab)
            
            <div id="content_subscriptions_listview{{$grid_id}}" class="mt-3 d-none"></div>
       
            @endif
            <div class="text-muted p-2 text-center">Select a customer</div>
           @if(is_main_instance())
            @if(session('role_level') != 'Customer')
             <div class="row m-0 p-0 d-none">
            <div class="col  p-1 "> 
            <input type="text" id='panel_switcher{{$grid_id}}'/>
            </div>
            </div>
            @endif
            
        @endif
           
            
           
            <div id='telecloud_services_balances{{$grid_id}}'></div>
            
            @if(is_main_instance())
            <div id='telecloud_listview{{$grid_id}}' class="mt-3"></div>
            @endif
            
            
            
            
          
            
            
           
              @if(is_main_instance())
            
            <div id="kb_listview{{$grid_id}}" class="mt-3"></div>
       
            @endif   
        </div>
        @endif  
        
        
        @if(is_superadmin())
        <div class="tab-pane fade" id="content_sidebar_support{{$grid_id}}"> 
        
     
        @if(!empty($services_admin_menu) && count($services_admin_menu) > 0) 
        <div id="services_admin_menu{{$grid_id}}"></div>
        <div id='telecloud_admin_container{{$grid_id}}' class="mt-2">
        </div> 
       
        @endif 
        
        
        
        @if(is_main_instance())
        
        <div id="kbinternal_listview{{$grid_id}}" class="mt-3"></div>
        
        @endif   
        </div>
        
        @endif
       
        
        
       
      
       
    
        
   
    </div>
    </div>
</div>
<div class="grid_layout"></div>
<div class="layouts_list"></div>
<div class="guide_context"></div>
<div class="kbitem_context"></div>
<div class="event_context"></div>
<div class="newsletter_context"></div>
<div class="sidebar_account_info"></div>
<div class="sidebar_subscription_info"></div>
<div class="sidebar_linked_module"></div>

         
        @if($has_module_guides) 
        <div id="sidebar_system_global_guides{{$grid_id}}" class="d-none">
            <div id="content_sidebar_global_guides_accordion{{$grid_id}}" ></div>
        </div>
        <div id="sidebar_system_guides{{$grid_id}}" class="d-none">
            <div id="content_sidebar_guides_accordion{{$grid_id}}" ></div>
        </div>
      
        @endif
     
        <div id="sidebar_system_layouts{{$grid_id}}" class="d-none">
            <div id='grid_views{{$grid_id}}'></div>
        </div>
        
        <div id="sidebar_system_reports{{$grid_id}}" class="d-none">
            <div id='grid_reports{{$grid_id}}'></div>
        </div>
        
        <div id="sidebar_system_charts{{$grid_id}}" class="d-none">
            <div id='grid_charts{{$grid_id}}'></div>
        </div>
        
        <div id="sidebar_system_row_history{{$grid_id}}" class="d-none">
            <div id="row_history_html"></div>
        </div>
        
        <div id="sidebar_system_row_files{{$grid_id}}" class="d-none">
            <div id="files_form">
            <div id="sidebar_droparea" class="py-0 text-center" style="height: auto; overflow: auto">
            
            <input type="file" id="sidebar_fileupload{{$grid_id}}">
            </div>
            </div>
            <div id="row_files_html{{$grid_id}}"></div>
        </div>
        
        <div id="sidebar_system_related_modules{{$grid_id}}" class="d-none">
            <ul id="content_linked_records_html{{$grid_id}}"></ul>
        </div>
        
        <div id="sidebar_system_events{{$grid_id}}" class="d-none">
            <input id="events_listbox{{$grid_id}}" />
        </div>

@push('page-scripts')



<script>
function refresh_sidebar_files{{$grid_id}}(){
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
        
        
        console.log('app_sidebar_files_datasource/{{$communications_type}}/'+selected_id);
        $.get('app_sidebar_files_datasource/{{$communications_type}}/'+selected_id, function(data) {
           console.log(data);
            $("#row_files_html{{$grid_id}}").html(data);
        });
    }
function render_sidebar_files_uploader{{$grid_id}}(){
   // ////console.log('render_sidebar_files_uploader{{$grid_id}}');
    try{
    if(window['sidebar_filesuploader'] && window['sidebar_filesuploader'].isRendered){
    
    //window['sidebar_filesuploader'].destroy();    
    }
    }catch(e){
    
    }
    setTimeout(function(){
       
   
        
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
            refresh_sidebar_files{{$grid_id}}();
        },
        failure: function(args){
         
            toastNotify('File upload failed','warning');
        },
        },'#sidebar_fileupload{{$grid_id}}');
       
        // render initialized Uploader
    },500)
    }
   
    $(document).ready(function() {
    setTimeout(render_sidebar_files_uploader{{$grid_id}},500)
    });
    $(document).off('click', '.deletefiletbtn').on('click', '.deletefiletbtn', function() {
      
        var file_id = $(this).attr('data-file-id');
        if(file_id > ''){
               $.ajax({
                url: '/{{$menu_route}}/deletefile',
                type:'post',
                data: {file_id: file_id},
                success: function(data) { 
            refresh_sidebar_files{{$grid_id}}();
                }
              });  
        }
    });
    
  
    
    function create_guides_context{{$grid_id}}(){
        $('body').append('<ul id="contextguides{{$grid_id}}" class="m-0"></ul>');
        var items = [
            {
                id: "guide_add",
                text: "Add",
                iconCss: "fa fa-plus",
            },
            {
                id: "guide_edit",
                text: "Edit",
                iconCss: "fas fa-pen",
            },
            {
                id: "guide_delete",
                text: "Delete",
                iconCss: "fas fa-trash",
            },
            {
                id: "guide_list",
                text: "List",
                iconCss: "fa fa-list",
            },
           
        ];
        var context_guide_id = false;
        var context_guide_projectid = false;
        var menuOptions = {
            target: '.guide_context',
            items: items,
            beforeItemRender: contextmenurender,
            beforeOpen: function(args){
                
                // toggle context items on header
                
                    context_guide_id = $(args.event.target).attr('data-attr-id');
                    context_guide_role_id = $(args.event.target).attr('data-attr-role_id');
                    context_guide_module_id = $(args.event.target).attr('data-attr-module_id');
                
            },
            select: function(args){
               
                if(args.item.id === 'guide_edit') {
                     sidebarform('guide_edit','/{{$guides_url}}/edit/'+context_guide_id, 'Guide Edit');
                }
                if(args.item.id === 'guide_delete') {
                    gridAjaxConfirm('/{{$guides_url}}/delete', 'Delete policy?', {"id" : context_guide_id}, 'post');
                }
            
                    if(args.item.id === 'guide_add') {
                         sidebarform('guide_edit','/{{$guides_url}}/edit?module_id='+context_guide_module_id, 'Guide Add');
                    }
                    if(args.item.id === 'guide_list') {
                         viewDialog('guide_edit','/{{$guides_url}}?module_id='+context_guide_module_id, 'Guides');
                    }
            }
        };
      
        // Initialize ContextMenu control.
        contextguides{{$grid_id}} = new ej.navigations.ContextMenu(menuOptions, '#contextguides{{$grid_id}}');  
    }
    
    function create_newsletters_context{{$grid_id}}(){
        $('body').append('<ul id="contextnewsletters{{$grid_id}}" class="m-0"></ul>');
        var items = [
            {
                id: "newsletter_add",
                text: "Add",
                iconCss: "fa fa-plus",
            },
            {
                id: "newsletter_edit",
                text: "Edit",
                iconCss: "fas fa-pen",
            },
            {
                id: "newsletter_delete",
                text: "Delete",
                iconCss: "fas fa-trash",
            },
            {
                id: "newsletter_list",
                text: "List",
                iconCss: "fa fa-list",
            },
           
        ];
        var context_newsletter_id = false;
        var context_newsletter_projectid = false;
        var menuOptions = {
            target: '.newsletter_context',
            items: items,
            beforeItemRender: contextmenurender,
            beforeOpen: function(args){
                
                // toggle context items on header
                
                    context_newsletter_id = $(args.event.target).attr('data-edit_id');
                    context_newsletter_route = $(args.event.target).attr('data-route_url');
                
            },
            select: function(args){
               
                if(args.item.id === 'newsletter_edit') {
                     sidebarform('newsletter_edit','/'+context_newsletter_route+'/edit/'+context_newsletter_id, 'Edit');
                }
                if(args.item.id === 'newsletter_delete') {
                    gridAjaxConfirm('/'+context_newsletter_route+'/delete', 'Delete policy?', {"id" : context_newsletter_id}, 'post');
                }
                if(args.item.id === 'newsletter_add') {
                     sidebarform('newsletter_edit','/'+context_newsletter_route+'/edit?role_id='+context_newsletter_role_id, 'Add');
                }
                if(args.item.id === 'newsletter_list') {
                     viewDialog('newsletter_edit','/'+context_newsletter_route, 'newsletters');
                }
            }
        };
      
        // Initialize ContextMenu control.
        contextnewsletters{{$grid_id}} = new ej.navigations.ContextMenu(menuOptions, '#contextnewsletters{{$grid_id}}');  
    }
    
    @if(is_superadmin())
    function create_events_context{{$grid_id}}(){
        $('body').append('<ul id="contextevents{{$grid_id}}" class="m-0"></ul>');
        var items = [
            {
                id: "event_add",
                text: "Add",
                iconCss: "fa fa-plus",
            },
            {
                id: "event_edit",
                text: "Edit",
                iconCss: "fas fa-pen",
            },
            {
                id: "event_list",
                text: "List",
                iconCss: "fa fa-list",
            },
           
        ];
        var context_event_id = false;
        var context_event_projectid = false;
        var menuOptions = {
            target: '.event_context',
            items: items,
            beforeItemRender: contextmenurender,
            beforeOpen: function(args){
                // toggle context items on header
                context_event_id = $(args.event.target).closest('.event_context').attr('data-id');
                
            },
            select: function(args){
                ////console.log('context_event_id',context_event_id);
                if(args.item.id === 'event_edit') {
                     sidebarform('event_edit','/{{$events_url}}/edit/'+context_event_id, 'Event Edit');
                }
               
                if(args.item.id === 'event_add') {
                     sidebarform('event_edit','/{{$events_url}}/edit?module_id={{$module_id}}', 'Event Add');
                }
                if(args.item.id === 'event_list') {
                     viewDialog('event_edit','/{{$events_url}}?module_id={{$module_id}}', 'Events');
                }
              
            }
        };
      
        // Initialize ContextMenu control.
        contextevents{{$grid_id}} = new ej.navigations.ContextMenu(menuOptions, '#contextevents{{$grid_id}}');  
    }
    
    function create_services_admin_context{{$grid_id}}(){
        $('body').append('<ul id="contextservices_admin{{$grid_id}}" class="m-0"></ul>');
        var items = [
            
            {
                id: "context_menu_edit_1",
                text: "Edit  Menu",
                iconCss: "fas fa-list",
                url: 'sf_menu_manager/{{$module_id}}/services_admin_menu',
                data_target: 'view_modal',
            },
       
        ];
      
        var menuOptions = {
            target: '#services_admin_menu{{$grid_id}}',
            items: items,
            beforeItemRender: contextmenurender,
        };
      
        // Initialize ContextMenu control.
        contextservices_admin{{$grid_id}} = new ej.navigations.ContextMenu(menuOptions, '#contextservices_admin{{$grid_id}}');  
    }
    
    function create_telecloud_listview_context{{$grid_id}}(){
        $('body').append('<ul id="contexttelecloud_listview{{$grid_id}}" class="m-0"></ul>');
        var items = [
            
            {
                id: "context_menu_edit_1",
                text: "Edit Voice Menu",
                iconCss: "fas fa-list",
                url: 'sf_menu_manager/{{$module_id}}/telecloud_menu',
                data_target: 'view_modal',
            },
            {
                id: "context_menu_edit_1",
                text: "Edit SMS Menu",
                iconCss: "fas fa-list",
                url: 'sf_menu_manager/{{$module_id}}/sms_menu',
                data_target: 'view_modal',
            },
       
        ];
      
        var menuOptions = {
            target: '#telecloud_listview{{$grid_id}}',
            items: items,
            beforeItemRender: contextmenurender,
        };
      
        // Initialize ContextMenu control.
        contexttelecloud_listview_admin{{$grid_id}} = new ej.navigations.ContextMenu(menuOptions, '#contexttelecloud_listview{{$grid_id}}');  
    }
    $(document).ready(function() {
         @if(session('role_level') == 'Admin')
        create_events_context{{$grid_id}}();
        @endif
        @if($show_subscriptions_tab)
        
            @if(session('role_level') == 'Admin')
            @if(!empty($services_admin_menu) && count($services_admin_menu) > 0) 
        create_services_admin_context{{$grid_id}}();
        @endif
        @endif
        @if(is_main_instance())
        create_telecloud_listview_context{{$grid_id}}();
        @endif
        @endif
    });
    
    @endif

    
    @if($communications_type)
    content_rowinfo_accordion{{$grid_id}} = new ej.navigations.Accordion({
        items: [],
        expandMode: 'Single',
        
    });
    
    //Render initialized Accordion component
    content_rowinfo_accordion{{$grid_id}}.appendTo('#content_rowinfo_accordion{{$grid_id}}'); 
    @endif
    
  
 
        
    @if($show_products_tab)
        content_products_accordion{{$grid_id}} = new ej.navigations.Accordion({
            items: [],
            expandMode: 'Single',
        });
        
        //Render initialized Accordion component
        content_products_accordion{{$grid_id}}.appendTo('#content_products_accordion{{$grid_id}}'); 
    @endif
    
 
    
    
     
       
 
    
    @if($has_module_guides)
    
    content_sidebar_global_guides_accordion{{$grid_id}} = new ej.navigations.Accordion({
        items: [],
        expandMode: 'Single',
        headerTemplate: '<div class="guide_context" data-attr-role_id="${role_id}" data-attr-module_id="${module_id}" data-attr-id="${id}">${header}</div>',
        @if(is_superadmin())
        created: function(){
           
        },
        @endif
    });
    
    //Render initialized Accordion component
    content_sidebar_global_guides_accordion{{$grid_id}}.appendTo('#content_sidebar_global_guides_accordion{{$grid_id}}');
    
    content_sidebar_guides_accordion{{$grid_id}} = new ej.navigations.Accordion({
        items: [],
        expandMode: 'Single',
        headerTemplate: '<div class="guide_context ${cssClass}" data-attr-role_id="${role_id}" data-attr-module_id="${module_id}" data-attr-id="${id}">${header}</div>',
       
        created: function(){
            create_guides_context{{$grid_id}}();
            $.get('get_sidebar_module_guides/{{$module_id}}', function(data) {
            
                content_sidebar_guides_accordion{{$grid_id}}.items = data.accordion;
                content_sidebar_guides_accordion{{$grid_id}}.refresh();
                $("#guides_count{{$grid_id}}").html("("+data.accordion.length+")");
                $("#global_guides_count{{$grid_id}}").html("("+data.global_accordion.length+")");
                
                content_sidebar_global_guides_accordion{{$grid_id}}.items = data.global_accordion;
                content_sidebar_global_guides_accordion{{$grid_id}}.refresh();
                
                @if(is_superadmin())
                contextguides{{$grid_id}}.refresh();
                guides_accordion_sort{{$grid_id}}();
                @endif
                
            });
        }
    });
    
    //Render initialized Accordion component
    content_sidebar_guides_accordion{{$grid_id}}.appendTo('#content_sidebar_guides_accordion{{$grid_id}}');
   
    @endif
    
    function guides_accordion_refresh{{$grid_id}}(){
        //console.log('guides_accordion_refresh11');
        setTimeout(function(){
             var module_id = '{{$module_id}}';
          var guides_url = 'get_sidebar_module_guides/'+module_id;
          if(window['workspace_filter_current{{$grid_id}}']){
            guides_url += '/'+window['workspace_filter_current{{$grid_id}}'];
          }
            
          $.get(guides_url, function(data) {
         
            content_sidebar_guides_accordion{{$grid_id}}.items = data.accordion;
            content_sidebar_guides_accordion{{$grid_id}}.refresh();
            $("#guides_count{{$grid_id}}").html("("+data.accordion.length+")");
            $("#global_guides_count{{$grid_id}}").html("("+data.global_accordion.length+")");
            
            content_sidebar_global_guides_accordion{{$grid_id}}.items = data.global_accordion;
            content_sidebar_global_guides_accordion{{$grid_id}}.refresh();
           
            @if(is_superadmin())
                contextguides{{$grid_id}}.refresh();
                guides_accordion_sort{{$grid_id}}();
            @endif
            
        });
            
        },800);
    }
    @if($module_id == 2018)
    function guides_accordion_refresh(){
        //console.log('guides_accordion_refresh22');
        setTimeout(function(){
             var module_id = '{{$module_id}}';
          var guides_url = 'get_sidebar_module_guides/'+module_id;
          if(window['workspace_filter_current{{$grid_id}}']){
            guides_url += '/'+window['workspace_filter_current{{$grid_id}}'];
          }
            
          $.get(guides_url, function(data) {
         
        
            content_sidebar_guides_accordion{{$grid_id}}.items = data.accordion;
            content_sidebar_guides_accordion{{$grid_id}}.refresh();
            $("#guides_count{{$grid_id}}").html("("+data.accordion.length+")");
            $("#global_guides_count{{$grid_id}}").html("("+data.global_accordion.length+")");
            
            content_sidebar_global_guides_accordion{{$grid_id}}.items = data.global_accordion;
            content_sidebar_global_guides_accordion{{$grid_id}}.refresh();
           
            @if(is_superadmin())
                contextguides{{$grid_id}}.refresh();
                guides_accordion_sort{{$grid_id}}();
            @endif
            
        });
            
        },800);
    }
    @endif
    
    function guides_accordion_sort{{$grid_id}}(){
       
        $("#content_sidebar_global_guides_accordion{{$grid_id}}").sortable({
            containment: "parent",
            handle: '.e-acrdn-header',
            start: function(e) {
            ////console.log('start',e);
            },
            stop: function(e) {
              var dataArray = Array.from(document.querySelectorAll('#content_sidebar_global_guides_accordion{{$grid_id}} .guide_context')).filter(e => e.hasAttribute('data-attr-id')).map(e => ({ id: e.getAttribute('data-attr-id'), role_id: e.getAttribute('data-attr-role_id') }));
               

                $.ajax({
                url: '/guides_sort',
                type:'post',
                data: {guides: dataArray},
                success: function(data) { 
                
                
                }
                }); 
            }
        });
        
        $("#content_sidebar_guides_accordion{{$grid_id}}").sortable({
            containment: "parent",
            handle: '.e-acrdn-header',
            start: function(e) {
            ////console.log('start',e);
            },
            stop: function(e) {
              var dataArray = Array.from(document.querySelectorAll('#content_sidebar_guides_accordion{{$grid_id}} .guide_context')).filter(e => e.hasAttribute('data-attr-id')).map(e => ({ id: e.getAttribute('data-attr-id'), role_id: e.getAttribute('data-attr-role_id') }));
               
                ////console.log(dataArray);
                ////console.log(content_sidebar_guides_accordion{{$grid_id}});
                ////console.log(content_sidebar_guides_accordion{{$grid_id}}.items);
                $.ajax({
                url: '/guides_sort',
                type:'post',
                data: {guides: dataArray},
                success: function(data) { 
                    guides_accordion_refresh{{$grid_id}}();
                
                }
                }); 
            }
        });
    }

    function get_sidebar_row_info{{$grid_id}}(){
        @if(is_main_instance())
         @if($show_subscriptions_tab)
        pbx_domain_switcher_select();
        @endif
        @endif
        var active_tab = $("#right_sidebar_ul").find('.nav-link.active').attr('href');
       
        var project_id = 0;
        if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
            var module_id = '{{$detail_module_id}}';
            var selected = window['selectedrow_detail{{ $master_grid_id }}'];
            var project_id = (selected && selected.project_id) ? selected.project_id : 0;
        }else{
            var module_id = '{{$module_id}}';
            var selected = window['selectedrow_{{ $master_grid_id }}']; 
            var project_id = (selected && selected.id) ? selected.id : 0;
        }
        if(selected){
            if(active_tab != '#content_sidebar_guides'){
            $.get('get_sidebar_row_info/'+module_id+'/'+selected.rowId, function(data) {
               //console.log(data);
               
               if(data.sidebar_title > ''){
                   $("#sidebartitle{{$grid_id}}").html('<b>'+data.sidebar_title+'</b>');
               }else{
                   $("#sidebartitle{{$grid_id}}").html('');
               }
               
               if(data.sidebar_accountid > ''){
                   $("#sidebar_accountid{{$grid_id}}").val(data.sidebar_accountid);
               }else{
                   $("#sidebar_accountid{{$grid_id}}").val(0);
               }
               
                @if($show_statement_tab)
                    if(data.statement_html){
                        $("#content_statement_html").html(data.statement_html);
                    }else{
                        $("#content_statement_html").html('');
                    }
                @endif
                
                @if($show_subscriptions_tab)
                    try{
                    @if(is_main_instance())
                    if(data.telecloud_listview){
                        console.log(data.telecloud_listview);
                        telecloud_listview{{ $grid_id }}.dataSource = data.telecloud_listview;
                        telecloud_listview{{ $grid_id }}.refresh();
                        console.log('telecloud_listview{{ $grid_id }}');
                        console.log(telecloud_listview{{ $grid_id }});
                        //if(content_subscriptions_listview{{$grid_id}}.expandedIndices.length === 0){
                        //content_subscriptions_listview{{$grid_id}}.expandItem(true,0);
                        //}
                    }
                    @endif
                    if(data.subscriptions_listview){
                        //console.log(data.subscriptions_listview);
                        content_subscriptions_listview{{$grid_id}}.dataSource = data.subscriptions_listview;
                        content_subscriptions_listview{{$grid_id}}.refresh();
                        //if(content_subscriptions_listview{{$grid_id}}.expandedIndices.length === 0){
                        //content_subscriptions_listview{{$grid_id}}.expandItem(true,0);
                        //}
                    }
                    if(data.subscriptions_listview.length > 0){
                        $("#content_subscriptions_listview{{$grid_id}}").removeClass('d-none');
                    }else{
                        $("#content_subscriptions_listview{{$grid_id}}").addClass('d-none');
                    }
                    console.log(data.kb_listview);
                    if(data.kb_listview){
                        //console.log(data.kb_listview);
                        window['kb_listview{{$grid_id}}'].dataSource = data.kb_listview;
                        window['kb_listview{{$grid_id}}'].refresh();
                        //if(content_kb_listview{{$grid_id}}.expandedIndices.length === 0){
                        //content_kb_listview{{$grid_id}}.expandItem(true,0);
                        //}
                    }
                    if(data.kb_listview.length > 0){
                        $("#kb_listview{{$grid_id}}").removeClass('d-none');
                    }else{
                        $("#kb_listview{{$grid_id}}").addClass('d-none');
                    }
                    if(data.subscriptions_accordion){
                        //content_subscriptions_listview{{$grid_id}}.items = data.subscriptions_accordion;
                        //content_subscriptions_listview{{$grid_id}}.refresh();
                        //if(content_subscriptions_listview{{$grid_id}}.expandedIndices.length === 0){
                        //content_subscriptions_listview{{$grid_id}}.expandItem(true,0);
                        //}
                    }
                    window['sidebar_subscription_info_context{{$grid_id}}'].refresh();
                    }catch(e){
                        //console.log(e);
                    }
                @endif
                
                
                @if($show_products_tab)
                if(data.products_accordion){
                content_products_accordion{{$grid_id}}.items = data.products_accordion;
                content_products_accordion{{$grid_id}}.refresh();
                if(content_products_accordion{{$grid_id}}.expandedIndices.length === 0){
                content_products_accordion{{$grid_id}}.expandItem(true,0);
                }
                }
                @endif
                
                
                @if($communications_type)
                content_rowinfo_accordion{{$grid_id}}.items = data.rowinfo_accordion;
                content_rowinfo_accordion{{$grid_id}}.refresh();
                if(content_rowinfo_accordion{{$grid_id}}.expandedIndices.length === 0){
                content_rowinfo_accordion{{$grid_id}}.expandItem(true,1);
                }
                //if(data.rowinfo_html){
                  // $("#content_rowinfo_html").html(data.rowinfo_html);
                   
               // }
                @endif
               // //console.log(data);
               // //console.log(data.linked_records_html);
               // //console.log(data.linked_records_count);
               // //console.log($("#content_linked_records_html{{$grid_id}}"));
               
               
                if(data.services_balances && data.services_balances > ''){
                    $("#telecloud_services_balances{{$grid_id}}").html(data.services_balances);
                }
                
                @if(session('role_level') == 'Admin')
                if(data.linked_records_json){
                    linked_records_listbox{{$grid_id}}.dataSource = data.linked_records_json;
                    $("#related_modules_count{{$grid_id}}").html("("+data.linked_records_count+")");
                }else{
                    linked_records_listbox{{$grid_id}}.dataSource = [];
                    
                    $("#related_modules_count{{$grid_id}}").html("(0)");
                }
             
                if(data.row_history_html){
                    $("#row_history_html{{$grid_id}}").html(data.row_history_html);
                }
                if(data.row_files_html){
                    $("#row_files_html{{$grid_id}}").html(data.row_files_html);
                }
                @if($module_id == 1923)
                if(data.sales_html){
                    $("#content_interactions_sales{{$grid_id}}").html(data.sales_html);
                }else{
                    $("#content_interactions_sales{{$grid_id}}").html('');
                }
                @endif
               
                @endif
              
                
                @if(session('role_level') == 'Admin')
                window['sidebar_linked_modules_context{{$grid_id}}'].refresh();
                @endif
                @if($communications_type)
               
                window['sidebar_customer_info_context'].refresh();
              window['sidebar_filesuploader'].clearAll();
                @endif
            });
            
       
            }
        }
    
    }
 
   
    // related modules
    // related_items_menu_menu
 @if(!empty($related_items_menu_menu) && count($related_items_menu_menu) > 0)   
 /*
    var related_items_menuMenuItems = @php echo json_encode($related_items_menu_menu); @endphp;
    ////console.log(related_items_menuMenuItems);
    // top_menu initialization
    var related_items_menu{{ $grid_id }} = new ej.navigations.Menu({
        items: related_items_menuMenuItems,
        height: 'auto',
        width: '100%',
        orientation: 'Vertical',
        created: function(args){
            
      
            @if(is_superadmin())
            
            $('body').append('<ul id="related_items_menu_context{{$grid_id}}" class="m-0"></ul>');
            var context_items = [
                {
                    id: "context_gridtab_edit",
                    text: "Edit Menu",
                    iconCss: "fas fa-list",
                    url: 'sf_menu_manager/{{$module_id}}/related_items_menu',
                    data_target: 'view_modal',
                },
                {
                    id: "edit_menu_btn",
                    text: "Edit",
                    iconCss: "fas fa-list",
                },
                {
                    id: "edit_menu_btn_function",
                    text: "Edit Function",
                    iconCss: "fas fa-list",
                },
            ];
            var menuOptions = {
                target: '.related_items_menubtn{{ $module_id }}',
                items: context_items,
                beforeItemRender: dropdowntargetrender,
                
                beforeOpen: function(args){
                    // toggle context items on header
                   
                    if( $(args.event.target).hasClass('related_items_menubtn{{ $module_id }}')){
                        data_menu_id = $(args.event.target).attr('data-menu-id');
                        data_button_function = $(args.event.target).attr('data-button-function');
                    }else{
                        data_menu_id = $(args.event.target).closest('li').attr('data-menu-id');
                        data_button_function = $(args.event.target).closest('li').attr('data-button-function');
                    }
                    if(data_button_function > ''){
                        related_items_menu_context{{$module_id}}.enableItems(['Edit Function'], true);        
                    }else{
                        related_items_menu_context{{$module_id}}.enableItems(['Edit Function'], false); 
                    }
                },
                select: function(args){
                    if(args.item.id === 'edit_menu_btn') {
                        sidebarform('editmenubtn','{{$menu_manager_url}}/edit/'+data_menu_id);
                    }
                    if(args.item.id === 'edit_menu_btn_function') {
                        sidebarform('editfunctionbtn','/code_edit/'+data_button_function);
                    }
                }
            };
            
            // Initialize ContextMenu control
            related_items_menu_context{{$module_id}} = new ej.navigations.ContextMenu(menuOptions, '#related_items_menu_context{{$grid_id}}');
            
            @endif
    
        },
        beforeOpen: function(args){
          
            @if(is_superadmin())
            related_items_menu_context{{$module_id}}.refresh();
            @endif
            var popup_items = [];
            $(args.items).each(function(i, el){
                popup_items.push(el.text);
            });
        
            var selected = window['selectedrow_{{ $grid_id }}'];
          
            {!! button_menu_selected($module_id, 'related_items_menu', $grid_id, 'selected', true) !!}
        },
        beforeItemRender: function(args){
            var el = args.element;   
            $(el).find("a").attr("title",args.item.title);
            if(args.item.border_top){
              
               $(el).addClass("menu_border_top");
            }
            
            $(el).attr("data-menu-id",args.item.menu_id);
            $(el).attr("data-button-function",args.item.button_function);
            
            if(args.item.confirm_text) {
                $(el).find("a").attr("confirm-text",args.item.confirm_text);
            }   
            if(args.item.new_tab == 1) {
            var el = args.element;
            $(el).find("a").attr("target","_blank");
            }
            if(args.item.cssClass) {
                $(el).addClass(args.item.cssClass);
            }
             
            @if(!empty($menus_newtab) && $menus_newtab === true)
            if(args.item.data_target == '' && args.item.url > '' && args.item.url != "#"){
                var el = args.element;
                $(el).find("a").attr("target","_blank");
            }
            @endif
            if(args.item.new_tab == 1) {
               var el = args.element;
               $(el).find("a").attr("target","_blank");
            }
            if(args.item.data_target == 'javascript') {
               $(el).find("a").attr("data-target",args.item.data_target);
               $(el).find("a").attr("js-target",args.item.url);
               $(el).find("a").attr("id",args.item.url);
               $(el).find("a").attr("href","javascript:void(0)");
            }else if(args.item.data_target == 'transaction' || args.item.data_target == 'transaction_modal') {
               $(el).find("a").attr("data-target",args.item.data_target);
               $(el).find("a").attr("href","javascript:void(0)");
               $(el).find("a").attr("modal_url",args.item.url);
            }else if(args.item.data_target) {
               $(el).find("a").attr("data-target",args.item.data_target);
              
            }
            
                // add row id to module menus
            
            if(args.item.require_grid_id){
                if(window['selectedrow_{{ $grid_id }}'] && window['selectedrow_{{ $grid_id }}'].id){
                   
                    var grid_url = args.item.original_url + window['selectedrow_{{ $grid_id }}'].id; 
                   
                    if(args.item.data_target == 'transaction' || args.item.data_target == 'transaction_modal') {
                        $(el).find("a").attr("modal_url",grid_url);
                        $(el).find("a").attr("href","javascript:void(0)");
                    }else{
                        $(el).find("a").attr("href",grid_url);
                    }
                }
            }
            
        },
    },'#related_items_menu{{ $grid_id }}');
    */
    @endif
  
    

    
    var grid_views{{$grid_id}}_data = new ej.data.DataManager({
        url: '/content_sidebar_grids/{{$module_id}}/Layout',
        adaptor: new ej.data.UrlAdaptor(),
        crossDomain: true,
    });
    
    // initialize ListBox component
    var grid_views{{$grid_id}} = new ej.dropdowns.ListBox({
        cssClass: 'layouts_list',
        @if(is_superadmin())
        allowDragAndDrop: true,
        @endif
        dataSource: grid_views{{$grid_id}}_data,
        beforeItemRender: function(args){ 
            $(args.element).addClass(args.item.cssClass); 
            if(window['layout_id{{ $grid_id }}'] == args.item.id){
                $(args.element).addClass('e-selected'); 
            }
            $.each(args.item.htmlAttributes, function(k, v){
                $(args.element).attr(k,v); 
            });
        },
        drop: function(args){
            // drag and drop
         //  //console.log('drop');
           // //console.log(args);
            var ids =  grid_views{{$grid_id}}.jsonData.map(function(item) {
            return item.id;
            });
            $.ajax({
                url: '/layouts_sort',
                type:'post',
                data: {ids: ids},
                success: function(data) { 
                    
                }
            }); 
        },
        created: function(args){
            
            create_layouts_context{{$master_grid_id}}();
        },
        dataBound: function(args){
            $("#layouts_count{{$grid_id}}").html("("+args.items.length+")");
            refresh_layout_context_menus{{$grid_id}}();
        },
        deselectList: function(){
            grid_views{{$grid_id}}.selectAll(false);
        },
        change: function(args){
            // unselect reports list
            if(args.event && grid_reports{{$grid_id}}){
                grid_reports{{$grid_id}}.deselectList();
            }
        }
    });
    grid_views{{$grid_id}}.appendTo('#grid_views{{$grid_id}}');
    
    
    var grid_charts{{$grid_id}}_data = new ej.data.DataManager({
        url: '/content_sidebar_grids/{{$module_id}}/Chart',
        adaptor: new ej.data.UrlAdaptor(),
        crossDomain: true,
    });
    
    // initialize ListBox component
    var grid_charts{{$grid_id}} = new ej.dropdowns.ListBox({
        cssClass: 'layouts_list',
        @if(is_superadmin())
        allowDragAndDrop: true,
        @endif
        dataSource: grid_charts{{$grid_id}}_data,
        beforeItemRender: function(args){ 
            $(args.element).addClass(args.item.cssClass); 
            if(window['layout_id{{ $grid_id }}'] == args.item.id){
                $(args.element).addClass('e-selected'); 
            }
            $.each(args.item.htmlAttributes, function(k, v){
                $(args.element).attr(k,v); 
            });
        },
        drop: function(args){
            // drag and drop
         //  //console.log('drop');
           // //console.log(args);
            var ids =  grid_charts{{$grid_id}}.jsonData.map(function(item) {
            return item.id;
            });
            $.ajax({
                url: '/layouts_sort',
                type:'post',
                data: {ids: ids},
                success: function(data) { 
                    
                }
            }); 
        },
        created: function(args){
        },
        dataBound: function(args){
            $("#charts_count{{$grid_id}}").html("("+args.items.length+")");
            //////console.log('dataBound');
            refresh_layout_context_menus{{$grid_id}}();
        },
        deselectList: function(){
            grid_charts{{$grid_id}}.selectAll(false);
        },
        change: function(args){
            // unselect reports list
            if(args.event && grid_reports{{$grid_id}}){
                grid_reports{{$grid_id}}.deselectList();
            }
        }
    });
    grid_charts{{$grid_id}}.appendTo('#grid_charts{{$grid_id}}');
    
  
        var grid_reports{{$grid_id}}_data = new ej.data.DataManager({
            url: '/content_sidebar_grids/{{$module_id}}/Report',
            adaptor: new ej.data.UrlAdaptor(),
            crossDomain: true,
        });
        
        // initialize ListBox component
        var grid_reports{{$grid_id}} = new ej.dropdowns.ListBox({
            cssClass: 'layouts_list',
            @if(is_superadmin())
            allowDragAndDrop: true,
            @endif
            dataSource: grid_reports{{$grid_id}}_data,
            beforeItemRender: function(args){ 
                $(args.element).addClass(args.item.cssClass); 
                if(window['layout_id{{ $grid_id }}'] == args.item.id){
                    $(args.element).addClass('e-selected'); 
                }
                $.each(args.item.htmlAttributes, function(k, v){
                    $(args.element).attr(k,v); 
                });
            },
            actionComplete: function(args){
                // drag and drop
                //////console.log('actionComplete');
                //////console.log(args);
            },
            drop: function(args){
                // drag and drop
             //  //console.log('drop');
               // //console.log(args);
                var ids =  grid_reports{{$grid_id}}.jsonData.map(function(item) {
                return item.id;
                });
                $.ajax({
                    url: '/layouts_sort',
                    type:'post',
                    data: {ids: ids},
                    success: function(data) { 
                        
                    }
                }); 
            },
            created: function(){
                setTimeout(function(){contextlayouts{{ $grid_id }}.refresh();},1000)
            },
            deselectList: function(){
                grid_reports{{$grid_id}}.selectAll(false);
            },
            change: function(args){
                // unselect reports list
                if(args.event && grid_views{{$grid_id}}){
                    grid_views{{$grid_id}}.deselectList();
                }
            },
            dataBound: function(args){
                $("#reports_count{{$grid_id}}").html("("+args.items.length+")");
              
                refresh_layout_context_menus{{$grid_id}}();
            }
        });
        grid_reports{{$grid_id}}.appendTo('#grid_reports{{$grid_id}}');
    
    
    /*
    var grid_dashboards_data = new ej.data.DataManager({
        url: '/content_sidebar_grids/{{$module_id}}/dashboard',
        adaptor: new ej.data.UrlAdaptor(),
        crossDomain: true,
    });
    
    // initialize ListBox component
    var grid_dashboards = new ej.dropdowns.ListBox({
        @if(is_superadmin())
        allowDragAndDrop: true,
        @endif
        dataSource: grid_dashboards_data,
        beforeItemRender: function(args){ 
            $(args.element).addClass(args.item.cssClass); 
            $.each(args.item.htmlAttributes, function(k, v){
                $(args.element).attr(k,v); 
            });
        },
        actionComplete: function(args){
            // drag and drop
            ////console.log('actionComplete');
            ////console.log(args);
        }
    });
    grid_dashboards.appendTo('#grid_dashboards');*/
    
    function refresh_content_sidebar_layouts{{$grid_id}}(){
      
        if(grid_views{{$grid_id}}){
            grid_views{{$grid_id}}.refresh();
        }
        if(grid_charts{{$grid_id}}){
            grid_charts{{$grid_id}}.refresh();
        }
        if(grid_reports{{$grid_id}}){
            grid_reports{{$grid_id}}.refresh();
        }
        //grid_dashboards.refresh();
    }
    
    
    $(document).on('click','.grid_layout',function(e){
       var layout_id = $(this).attr('data-view_id');
       layout_load{{$grid_id}}(layout_id);
    });
    
   
    
 
    @if(isset($layout_filter_user))
   
    window['layout_filter_user_{{ $grid_id }}'] = new ej.dropdowns.DropDownList({
    	dataSource: {!! json_encode($layout_user_datasource) !!},
        placeholder: 'Filter user',
        popupWidth: 'auto',
        //Set true to show header title
        select: function(args){
          
            
            // Get a reference to the filter instance
            var filterInstance = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterInstance('join_user_id'); 
            
           
            // Set the filter model
            filterInstance.setModel({
                filterType: 'set',
                values: [args.itemData.text],
            });
            
            // Tell grid to run filter operation again
            window['grid_{{ $grid_id }}'].gridOptions.api.onFilterChanged();
        }
    }, '#layout_filter_user{{ $grid_id }}');
 
    @endif
    

    
  
    
    
    
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
                        get_sidebar_row_info{{$grid_id}}();
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
   
                    get_sidebar_row_info{{$grid_id}}();
                }
            });  
        }
    });
     
 
</script>
<script>

/*
Call center functions
*/

    $(document).off('click', '#call_completed').on('click', '#call_completed', function() {
       
        var id = $(this).attr('data-call-id');
      
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
    });
    
   
    
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
        //////console.log('queueNextCall 2');
        var comments = $("#call_comments").val();
        if(comments.length < 5){
            toastNotify('A detailed comment is required. Enter at least 10 characters','warning');
            return false;
        }
        var data = {id: id, call_comments: $("#call_comments").val(), call_status: $("#call_status").val()};
       
        //////console.log(data);
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

  
   
    
    @if(is_superadmin())
    /*
    // TABS DRAG AND DROP
    var isDragging = false;
    var dragThreshold = 10; // Adjust this threshold as needed
    $("#right_sidebar_ul").sortable({
        containment: "parent",
        axis: "x",
        start: function(e, ui) {
            // Reset isDragging flag on each drag start
            isDragging = false;
        },
        stop: function(e, ui) {
            // If the drag stopped and it was a drag (not a click), handle it here
            if (isDragging) {
                // Handle drag behavior
                //////console.log("Dragged and dropped");
                save_sidebar_nav_order();
            }
        },
        // Update isDragging flag based on mouse movement distance
        change: function(e, ui) {
            if (!isDragging) {
                // Calculate the horizontal distance moved during the drag
                var dx = Math.abs(ui.position.left - ui.originalPosition.left);
                // Check if the distance exceeds the threshold
                if (dx >= dragThreshold) {
                    isDragging = true;
                }
            }
        }
    });
    
    function save_sidebar_nav_order(){
        var dataArray = [];
        $("#right_sidebar_ul li").each(function () {
            dataArray.push({
                id: $(this).attr('id'),
                hidden: $(this).hasClass('d-none') ? 1 : 0
            });
        });
      
        sidebar_state = JSON.stringify(dataArray);
        //////console.log(sidebar_state);
        window['sidebarNavOrder'] = sidebar_state;
        // Send AJAX request to save the order and visibility to the database
        $.ajax({
            type: 'POST',
            url: 'save_sidebar_state', // Your server-side script to handle the saving
            data: { module_id: {{$module_id}}, sidebar_state: sidebar_state },
            success: function (response) {
                //////console.log('Order and visibility saved successfully');
            },
            error: function (xhr, status, error) {
                //console.error('Error saving order and visibility:', error);
            }
        });
        
    }
    */
    
    /*
    $(document).ready(function() {
        // CONTEXT MENU FOR VISIBILITY
        $('body').append('<ul id="sidebar_nav_context_el" class="m-0"></ul>');
        // Initialize Syncfusion window['sidebar_nav_context']
        window['sidebar_nav_context'] = new ej.navigations.ContextMenu({
            target: '#right_sidebar_ul',
            items: getMenuItems(), // Get menu items dynamically
            beforeOpen: function(args){
                this.items = getMenuItems();
            },
            select: onMenuItemSelect // Event handler for menu item selection
        });
        window['sidebar_nav_context'].appendTo('#sidebar_nav_context_el');
    });
    // Function to dynamically generate menu items based on li elements
    function getMenuItems() {
        var menuItems = [];
    
        // Iterate over each li item
        $('#right_sidebar_ul li').each(function () {
            var itemId = $(this).attr('id');
            var itemName = $(this).attr('name');
            var isVisible = !$(this).hasClass('d-none'); // Check visibility
            ////console.log($(this));
            ////console.log(itemId);
            ////console.log(itemName);
            ////console.log(isVisible);
            // Add menu item for each li item
            menuItems.push({
                text: itemName,
                id: itemId,
                iconCss: isVisible ? 'e-icons e-check' : '',
                items: [] // Submenu items if needed
            });
        });
    
        return menuItems;
    }
    */
    // Event handler for menu item selection
    function onMenuItemSelect(args) {
        var targetId = args.item.id;
        var targetElement = $('#' + targetId);
    
        // Toggle visibility based on current visibility state
        if (targetElement.hasClass('d-none')) {
            targetElement.removeClass('d-none');
            args.item.iconCss = 'e-icons e-check'; // Update icon to checked
        } else {
            targetElement.addClass('d-none');
            args.item.iconCss = ''; // Update icon to empty (unchecked)
        }
    
        // Refresh the context menu to reflect changes
        if(window['sidebar_nav_context']){
        window['sidebar_nav_context'].refresh();
        save_sidebar_nav_order();
        }
    }
    
    @endif
    
   // function load_sidebar_nav_order() {
        /*
        window['sidebarNavOrder'] = [];
        @if(!empty($sidebar_state))
        window['sidebarNavOrder'] = JSON.parse('{!! $sidebar_state !!}');
        @endif
*/
        /*
        window['sidebarNavOrder'].forEach(function (item) {
            var $item = $("#" + item.id);
            $item.detach().appendTo("#right_sidebar_ul");
    
            // Check if the item should be hidden based on the new dataArray
            if (item.hidden === 1) {
                $item.addClass('d-none'); // Add the d-none class to hide the item
            } else {
                $item.removeClass('d-none'); // Remove the d-none class to show the item
            }
        });
        */
        //$("#right_sidebar_ul").find('.nav-item').first().click();
        //$("#right_sidebar_ul").find('.nav-item').first().addClass('show active');
        //var tab = $("#right_sidebar_ul").find('a').first().attr('href');
        //$(tab).addClass('show active');
       // $('#right_sidebar_ul li:first-child a').tab('show'); 
   // }
    /*
    $(document).ready(function() {
        load_sidebar_nav_order(); 
    });
    */
     $(document).ready(function() {
    $('#right_sidebar_ul li:first-child a').tab('show'); 
     })
</script>
<script>



@if($communications_type)
    $(document).ready(function() {
        // CONTEXT MENU FOR VISIBILITY
        $('body').append('<ul id="sidebar_customer_info_context_el{{$grid_id}}" class="m-0"></ul>');
        // Initialize Syncfusion window['sidebar_nav_context']
        window['sidebar_customer_info_context'] = new ej.navigations.ContextMenu({
            target: '.sidebar_account_info',
            items: [
                {
                    id: "sci_edit",
                    text: "Edit",
                },
                {
                    id: "sci_quote",
                    text: "Create quote",
                },
                {
                    id: "sci_documents",
                    text: "Documents",
                },
                {
                    id: "sci_statement",
                    text: "Statement",
                },
                {
                    id: "sci_complete_statement",
                    text: "Complete Statement",
                },
                {
                    id: "sci_email_statement",
                    text: "Email Statement",
                },
                {
                    id: "sci_reset",
                    text: "Reset and send password",
                },
                {
                    id: "sci_cancel",
                    text: "Cancel account",
                },
            ],
            beforeItemRender: contextmenurender,
            beforeOpen: function(args){
                
                context_accountid{{$grid_id}} = $(args.event.target).closest('#sidebar_account_info').attr('data-id');
                context_partnerid{{$grid_id}} = $(args.event.target).closest('#sidebar_account_info').attr('data-partner-id');
                
                if(context_partnerid{{$grid_id}} == 1){
                    window['sidebar_customer_info_context'].enableItems(['Cancel account'], true);
                }else{
                    window['sidebar_customer_info_context'].enableItems(['Cancel account'], false);
                }
              
            },
            select: function(args){
               
                if(args.item.text === 'Edit'){
                    sidebarform(args.item.id,'{{$accounts_url}}/edit/'+context_accountid{{$grid_id}});
                }
               
                if(args.item.text === 'Create quote'){
                    if(context_partnerid{{$grid_id}} == 1){
                    sidebarform(args.item.id,'{{$documents_url}}/edit?doctype=Quotation&account_id='+context_accountid{{$grid_id}});
                    }else{
                    sidebarform(args.item.id,'{{$documents_url}}/edit?doctype=Quotation&account_id='+context_partnerid{{$grid_id}});
                    }
                }
                if(args.item.text === 'Documents'){
                    if(context_partnerid{{$grid_id}} == 1){
                    window.open('{{$documents_url}}?account_id='+context_accountid{{$grid_id}});
                    }else{
                    window.open('{{$documents_url}}?reseller_user='+context_accountid{{$grid_id}});
                    }
                    
                }
                if(args.item.text === 'Statement'){
                    if(context_partnerid{{$grid_id}} == 1){
                    viewDialog(args.item.id,'statement_pdf/'+context_accountid{{$grid_id}});
                    }else{
                    viewDialog(args.item.id,'statement_pdf/'+context_partnerid{{$grid_id}});
                    }
                    
                }
                if(args.item.text === 'Complete Statement'){
                    if(context_partnerid{{$grid_id}} == 1){
                    viewDialog(args.item.id,'full_statement_pdf/'+context_accountid{{$grid_id}});
                    }else{
                    viewDialog(args.item.id,'full_statement_pdf/'+context_partnerid{{$grid_id}});
                    }
                    
                }
                if(args.item.text === 'Email Statement'){
                    if(context_partnerid{{$grid_id}} == 1){
                    sidebarform(args.item.id,'email_form/statement_email/'+context_accountid{{$grid_id}});
                    }else{
                    sidebarform(args.item.id,'email_form/statement_email/'+context_partnerid{{$grid_id}});
                    }
                    
                }
                if(args.item.text === 'Reset and send password'){
                    gridAjax('send_user_password/'+context_accountid{{$grid_id}});
                }
                if(args.item.text === 'Cancel account'){
                    cancelAccount(context_accountid{{$grid_id}});
                }
               
            }
        });
        window['sidebar_customer_info_context'].appendTo('#sidebar_customer_info_context_el{{$grid_id}}');
        
        
        // setup statement listview
        
        
    });
@endif


</script>
<script>
    $(document).off('click', '#content_sidebar_guides_li').on('click', '#content_sidebar_guides_li', function() {
        //////console.log('tab click');
        get_sidebar_row_info{{$grid_id}}();
    });
  
    
    
</script>
<script>

    @if(session('role_level') == 'Admin')
    window['superadmin_accordion{{$grid_id}}'] = new ej.navigations.Accordion({
        expandMode: 'Single',
        items: [
         
            // @if($has_module_guides) 
        // { header: '<div class="sidebar-acco-header">Company Policies <span id="global_guides_count{{$grid_id}}"></span></div>', content: '#sidebar_system_global_guides{{$grid_id}}' },
        { header: '<div class="sidebar-acco-header">Guides <span id="guides_count{{$grid_id}}"></span></div>', content: '#sidebar_system_guides{{$grid_id}}' },
        // @endif
        
     
        { header: '<div class="sidebar-acco-header">Layouts <span id="layouts_count{{$grid_id}}"></span></div>', content: '#sidebar_system_layouts{{$grid_id}}'},
        { header: '<div class="sidebar-acco-header">Reports <span id="reports_count{{$grid_id}}"></span></div>', content: '#sidebar_system_reports{{$grid_id}}' },
        { header: '<div class="sidebar-acco-header">Charts <span id="charts_count{{$grid_id}}"></span></div>', content: '#sidebar_system_charts{{$grid_id}}' },
        { header: '<div class="sidebar-acco-header">Events ({{$events_count}})</div>', content: '#sidebar_system_events{{$grid_id}}' },
        ],
        expanded: function(args){
            
               
            if(args.isExpanded){
                $(args.item.content).removeClass('d-none');
            }
            try{
                refresh_layout_context_menus{{$grid_id}}();
                contextguides{{$grid_id}}.refresh();
                guides_accordion_sort{{$grid_id}}();
                
            }catch(e){}
        },
        created: function(args){
            setTimeout(function(){
            if( window['superadmin_accordion{{$grid_id}}_expanded'] == 'layouts'){
                expand_layouts_accordion();
            }else if( window['superadmin_accordion{{$grid_id}}_expanded'] == 'reports'){
                expand_reports_accordion();
            }else if( window['superadmin_accordion{{$grid_id}}_expanded'] == 'charts'){
                expand_charts_accordion();
            }
                
            },500)
        }
        
    });
    
    //Render initialized Accordion component
    window['superadmin_accordion{{$grid_id}}'].appendTo('#accordion_html_superadmin{{$grid_id}}');
    @endif

  @if(session('role_level') == 'Admin')
    window['system_accordion{{$grid_id}}'] = new ej.navigations.Accordion({
        expandMode: 'Single',
        items: [
         
        { header: '<div class="sidebar-acco-header">Row History</div>', content: '#sidebar_system_row_history{{$grid_id}}' },
        { header: '<div class="sidebar-acco-header">Row Files</div>', content: '#sidebar_system_row_files{{$grid_id}}' },
        { header: '<div class="sidebar-acco-header">Related Modules <span id="related_modules_count{{$grid_id}}">(0)</span></div>', content: '#sidebar_system_related_modules{{$grid_id}}' },
       
      
     
        ],
        expanded: function(args){
            
               
            if(args.isExpanded){
                $(args.item.content).removeClass('d-none');
            }
          
        },
        created: function(args){
            setTimeout(function(){
            if( window['system_accordion{{$grid_id}}_expanded'] == 'layouts'){
                expand_layouts_accordion();
            }else if( window['system_accordion{{$grid_id}}_expanded'] == 'reports'){
                expand_reports_accordion();
            }else if( window['system_accordion{{$grid_id}}_expanded'] == 'charts'){
                expand_charts_accordion();
            }
                
            },500)
        }
        
    });
    
    //Render initialized Accordion component
    window['system_accordion{{$grid_id}}'].appendTo('#accordion_html_system{{$grid_id}}');
    
    
    function setup_related_modules_context{{$grid_id}}(){
        
            $('body').append('<ul id="sidebar_linked_modules_context{{$grid_id}}_el" class="m-0"></ul>');
            // Initialize Syncfusion window['sidebar_nav_context']
            //console.log('linked_records_listbox{{$grid_id}} created2');
            window['sidebar_linked_modules_context{{$grid_id}}'] = new ej.navigations.ContextMenu({
                target: '.sidebar_linked_module',
                items: [
                    {
                        id: "slm_edit",
                        text: "Edit",
                    },
                    {
                        id: "slm_add",
                        text: "Add",
                    },
                    /*
                    {
                        id: "slm_filtered_list",
                        text: "Filtered List",
                    },*/
                    {
                        id: "slm_list",
                        text: "List",
                    },
                    
                ],
                beforeItemRender: contextmenurender,
                beforeOpen: function(args){
                    
                    context_slm_list{{$grid_id}} = $(args.event.target).closest('.sidebar_linked_module').attr('data-list-url');
                    context_slm_filtered_list{{$grid_id}} = $(args.event.target).closest('.sidebar_linked_module').attr('data-filtered-list-url');
                    context_slm_add{{$grid_id}} = $(args.event.target).closest('.sidebar_linked_module').attr('data-add-url');
                    context_slm_edit{{$grid_id}} = $(args.event.target).closest('.sidebar_linked_module').attr('data-edit-url');
                },
                select: function(args){
                   
                    if(args.item.text === 'Edit'){
                        sidebarform(args.item.id,context_slm_edit{{$grid_id}});
                    }
                    if(args.item.text === 'Add'){
                        sidebarform(args.item.id,context_slm_add{{$grid_id}});
                    }
                    if(args.item.text === 'Filtered List'){
                        window.open(context_slm_filtered_list{{$grid_id}},'_blank');
                    }
                    if(args.item.text === 'List'){
                        window.open(context_slm_list{{$grid_id}},'_blank');
                    }
                }
            });
            window['sidebar_linked_modules_context{{$grid_id}}'].appendTo('#sidebar_linked_modules_context{{$grid_id}}_el');
            
            //console.log( window['sidebar_linked_modules_context{{$grid_id}}']);
    }
    setup_related_modules_context{{$grid_id}}();
    linked_records_listbox{{$grid_id}} = new ej.dropdowns.ListBox({
        beforeItemRender: function(args){ 
            $(args.element).addClass(args.item.cssClass); 
            $.each(args.item.htmlAttributes, function(k, v){
                $(args.element).attr(k,v); 
            });
        },
        created: function(){
            // CONTEXT MENU FOR VISIBILITY
            
        },
        dataBound: function(args){
            window['sidebar_linked_modules_context{{$grid_id}}'].refresh();
        },
        change: function(args){
          if(args && args.items && args.items[0] && args.items[0].list_url){
              window.open(args.items[0].list_url, '_blank');
          }
        },
    });
    
    linked_records_listbox{{$grid_id}}.appendTo('#content_linked_records_html{{$grid_id}}');
 
    
    events_listbox{{$grid_id}} = new ej.dropdowns.ListBox({
        dataSource: {!! json_encode($sidebar_module_events_json) !!},
      
        fields: { groupBy: 'type', text: 'text', value: 'value' },
        beforeItemRender: function(args){ 
            $(args.element).addClass(args.item.cssClass); 
            $.each(args.item.htmlAttributes, function(k, v){
                $(args.element).attr(k,v); 
            });
        },
        created: function(){
            // CONTEXT MENU FOR VISIBILITY
            
        },
        dataBound: function(args){
         
        },
        change: function(args){
          if(args && args.items && args.items[0] && args.items[0].list_url){
              window.open(args.items[0].list_url, '_blank');
          }
        },
    });
    
    events_listbox{{$grid_id}}.appendTo('#events_listbox{{$grid_id}}');

    function expand_layouts_accordion(){
        
       
        @if($has_module_guides)  
        window['system_accordion{{$grid_id}}'].expandItem(true,1);
        @else
        window['system_accordion{{$grid_id}}'].expandItem(true,0);
        @endif
        
        window['system_accordion{{$grid_id}}_expanded'] = 'layouts';
    }
    function expand_reports_accordion(){
        @if($has_module_guides)  
        window['system_accordion{{$grid_id}}'].expandItem(true,2);
        @else
        window['system_accordion{{$grid_id}}'].expandItem(true,1);
        @endif
        window['system_accordion{{$grid_id}}_expanded'] = 'reports';
    }
    function expand_charts_accordion(){
        @if($has_module_guides)  
        window['system_accordion{{$grid_id}}'].expandItem(true,3);
        @else
        window['system_accordion{{$grid_id}}'].expandItem(true,2);
        @endif
        window['system_accordion{{$grid_id}}_expanded'] = 'charts';
    }
    @endif

    
</script>


<script>
   // global search and company dropdown
    $(document).ready(function() {
        @if(session('role_level') == 'Admin')
        // global search
        
    
        var search_result_template =  '<div><div class="row m-0 p-0 border-top global_search_row">'+
        '<div class="search_icon_col col-auto px-1 d-flex align-items-center justify-content-center"><i class="global_search_icon ${icon}"></i></div>'+ 
        '<div class="col px-0"><b class="p-0">${name}</b> ${if(type=="Product")} ${desc} | Qty: (${qty}) ${/if}</div>'+
        '</div>'+
        '<div class="row m-0 p-0 global_search_row">'+
        
       
        '<div class="col px-2">'+
        '<div class="m-0 p-0 ${if(status=="Deleted")} bg-red ${/if}">'+  
       
        '${if(email_link>"")} <a class="btn btn-sm external_link pb-1 pt-1" href="${email_link}" data-target="form_modal">Email</a> ${/if} '+
        '${if(type=="Customers" || type=="Resellers" || type=="Reseller Users")} ${desc} ${/if} '+
        '${if(type=="Product")}<p> Retail Monthly: ${price_tax} | Wholesale Monthly: ${reseller_price_tax} </p>'+
        '<p>Retail 12 Months: ${price_tax_12} | Wholesale 12 Months: ${reseller_price_tax_12}</p> ${/if}'+
        '</div>'+
        '</div>'+
        '${if(type=="Product")} <div class="col-auto px-1 d-flex align-items-center justify-content-center">'+
        '<a class="k-button external_link" href="${product_link}" target="_blank">Pricelist</a>'+
        '</div>${/if}'+
        '${if(type=="Customers" || type=="Resellers")} <div class="col-auto px-1 d-flex align-items-center justify-content-center">'+
        '<a class="k-button external_link" href="${support_link}" target="_blank">Support</a>'+
        '</div>${/if}'+
        '</div></div>';
        global_search_timeout = false;
        window['global_search{{$grid_id}}'] = new ej.dropdowns.AutoComplete({
        dataSource: new ej.data.DataManager({
        url: '/global_search?search_type=system',
        adaptor: new ej.data.UrlAdaptor(),
        crossDomain: true,
        }),
        cssClass: 'global_search_input',
        minLength: 2,
        showClearButton:true,
        //width:150,
        //fields: { groupBy: 'type', value: 'name' },
        fields: {value: 'name' },
        placeholder:"Global search ",
        //sortOrder: 'Ascending',
        popupWidth: 'auto',
        
        //add delay to search
        
        filtering: function (e) {
        
            e.preventDefaultAction = true;
            
            if (global_search_timeout) { clearTimeout(global_search_timeout); }
            global_search_timeout = setTimeout(() => {
            const query = new ej.data.Query()
            .addParams('keyword', e.text)
            ;
            
            e.updateData(window['global_search{{$grid_id}}'].dataSource, query);
            }, 600);
        },
        
        select: function(args){
        //////console.log('select',args);
        ////////console.log(args.itemData);
        
        if(args.isInteracted){
        
        if($(args.e.target).hasClass('external_link')){
        
        }else if($(args.e.target).hasAttr('data-target')){
        
        }else{
        if(args.itemData && args.itemData.link){
        
        window.open(args.itemData.link);
        }
        }
        
        setTimeout(function(){
        
        window['global_search{{$grid_id}}'].clear();
        window['global_search{{$grid_id}}'].focusIn();

        window['global_search{{$grid_id}}'].filter();
       // window['global_search{{$grid_id}}'].refresh();
        },500);
        
        
        }
        },
        actionBegin: function(args){
        //////console.log('actionBegin',args);
        },
        actionComplete: function(args){
            ///////console.log('actionComplete',args);
            if(args.name == 'actionComplete' && args.request == 'POST'){
                window['global_search{{$grid_id}}'].first_result = false;
                if(args.result && args.result.length > 0){
                window['global_search{{$grid_id}}'].first_result = args.result[0];
                }
            }
        },
        open: function(args){
        //////console.log('open',args);
        },
        change: function(args){
            //////console.log('change',args);
            //////console.log(window['global_search{{$grid_id}}'].first_result);
            
            if (args.event && args.event.keyCode === 13) {
                if(window['global_search{{$grid_id}}'].first_result && window['global_search{{$grid_id}}'].first_result.link){
                    window.open(window['global_search{{$grid_id}}'].first_result.link);
                }
            }
        },
        close: function(args){
        //////console.log('global_search close');
        //////console.log(args.event.target);
        if(args.event && args.event.target && $(args.event.target).hasClass('e-clear-icon')){
        
        args.cancel=false;    
        }else if(args.event && args.event.target && $(args.event.target).hasClass('external_link')){
        args.cancel=true;    
        
        }else if(args.event && args.event.target && $(args.event.target).parents('.global_search_row').length){
        
        args.cancel=true;    
        }else if(args.event && args.event.target && $(args.event.target).parents('.searchinputgroup').length){
        
        args.cancel=true;    
        }
        //////console.log(args);
        },
        
        itemTemplate: search_result_template,
        
        });
        
        //render the component
        window['global_search{{$grid_id}}'].appendTo('#global_search{{$grid_id}}');
        
        
        product_search_timeout = false;
        window['product_search{{$grid_id}}'] = new ej.dropdowns.AutoComplete({
        dataSource: new ej.data.DataManager({
        url: '/global_search?search_type=product',
        adaptor: new ej.data.UrlAdaptor(),
        crossDomain: true,
        }),
        cssClass: 'product_search_input',
        minLength: 2,
        showClearButton:true,
        //width:150,
        //fields: { groupBy: 'type', value: 'name' },
        fields: {value: 'name' },
        placeholder:"Product search ",
        //sortOrder: 'Ascending',
        popupWidth: 'auto',
        
        //add delay to search
        
        filtering: function (e) {
        
            e.preventDefaultAction = true;
            
            if (product_search_timeout) { clearTimeout(product_search_timeout); }
            product_search_timeout = setTimeout(() => {
            const query = new ej.data.Query()
            .addParams('keyword', e.text)
            ;
            
            e.updateData(window['product_search{{$grid_id}}'].dataSource, query);
            }, 600);
        },
        
        select: function(args){
        //////console.log('select',args);
        ////////console.log(args.itemData);
        
        if(args.isInteracted){
        
        if($(args.e.target).hasClass('external_link')){
        
        }else if($(args.e.target).hasAttr('data-target')){
        
        }else{
        if(args.itemData && args.itemData.link){
        
        window.open(args.itemData.link);
        }
        }
        
        setTimeout(function(){
        
        window['product_search{{$grid_id}}'].clear();
        window['product_search{{$grid_id}}'].focusIn();

        window['product_search{{$grid_id}}'].filter();
       // window['product_search{{$grid_id}}'].refresh();
        },500);
        
        
        }
        },
        actionBegin: function(args){
        //////console.log('actionBegin',args);
        },
        actionComplete: function(args){
            ///////console.log('actionComplete',args);
            if(args.name == 'actionComplete' && args.request == 'POST'){
                window['product_search{{$grid_id}}'].first_result = false;
                if(args.result && args.result.length > 0){
                window['product_search{{$grid_id}}'].first_result = args.result[0];
                }
            }
        },
        open: function(args){
        //////console.log('open',args);
        },
        change: function(args){
            //////console.log('change',args);
            //////console.log(window['product_search{{$grid_id}}'].first_result);
            
            if (args.event && args.event.keyCode === 13) {
                if(window['product_search{{$grid_id}}'].first_result && window['product_search{{$grid_id}}'].first_result.link){
                    window.open(window['product_search{{$grid_id}}'].first_result.link);
                }
            }
        },
        close: function(args){
        //////console.log('product_search close');
        //////console.log(args.event.target);
        if(args.event && args.event.target && $(args.event.target).hasClass('e-clear-icon')){
        
        args.cancel=false;    
        }else if(args.event && args.event.target && $(args.event.target).hasClass('external_link')){
        args.cancel=true;    
        
        }else if(args.event && args.event.target && $(args.event.target).parents('.product_search_row').length){
        
        args.cancel=true;    
        }else if(args.event && args.event.target && $(args.event.target).parents('.searchinputgroup').length){
        
        args.cancel=true;    
        }
        //////console.log(args);
        },
        
        itemTemplate: search_result_template,
        
        });
        
        //render the component
        window['product_search{{$grid_id}}'].appendTo('#product_search{{$grid_id}}');
        
        
        customer_search_timeout = false;
        window['customer_search{{$grid_id}}'] = new ej.dropdowns.AutoComplete({
        dataSource: new ej.data.DataManager({
        url: '/global_search?search_type=customer',
        adaptor: new ej.data.UrlAdaptor(),
        crossDomain: true,
        }),
        cssClass: 'customer_search_input',
        minLength: 2,
        showClearButton:true,
        //width:150,
        //fields: { groupBy: 'type', value: 'name' },
        fields: {value: 'name' },
        placeholder:"Customer search ",
        //sortOrder: 'Ascending',
        popupWidth: 'auto',
        
        //add delay to search
        
        filtering: function (e) {
        
            e.preventDefaultAction = true;
            
            if (customer_search_timeout) { clearTimeout(customer_search_timeout); }
            customer_search_timeout = setTimeout(() => {
            const query = new ej.data.Query()
            .addParams('keyword', e.text)
            ;
            
            e.updateData(window['customer_search{{$grid_id}}'].dataSource, query);
            }, 600);
        },
        
        select: function(args){
        //////console.log('select',args);
        ////////console.log(args.itemData);
        
        if(args.isInteracted){
        
        if($(args.e.target).hasClass('external_link')){
        
        }else if($(args.e.target).hasAttr('data-target')){
        
        }else{
        if(args.itemData && args.itemData.link){
        
        window.open(args.itemData.link);
        }
        }
        
        setTimeout(function(){
        
        window['customer_search{{$grid_id}}'].clear();
        window['customer_search{{$grid_id}}'].focusIn();

        window['customer_search{{$grid_id}}'].filter();
       // window['customer_search{{$grid_id}}'].refresh();
        },500);
        
        
        }
        },
        actionBegin: function(args){
        //////console.log('actionBegin',args);
        },
        actionComplete: function(args){
            ///////console.log('actionComplete',args);
            if(args.name == 'actionComplete' && args.request == 'POST'){
                window['customer_search{{$grid_id}}'].first_result = false;
                if(args.result && args.result.length > 0){
                window['customer_search{{$grid_id}}'].first_result = args.result[0];
                }
            }
        },
        open: function(args){
        //////console.log('open',args);
        },
        change: function(args){
            //////console.log('change',args);
            //////console.log(window['customer_search{{$grid_id}}'].first_result);
            
            if (args.event && args.event.keyCode === 13) {
                if(window['customer_search{{$grid_id}}'].first_result && window['customer_search{{$grid_id}}'].first_result.link){
                    window.open(window['customer_search{{$grid_id}}'].first_result.link);
                }
            }
        },
        close: function(args){
        //////console.log('customer_search close');
        //////console.log(args.event.target);
        if(args.event && args.event.target && $(args.event.target).hasClass('e-clear-icon')){
        
        args.cancel=false;    
        }else if(args.event && args.event.target && $(args.event.target).hasClass('external_link')){
        args.cancel=true;    
        
        }else if(args.event && args.event.target && $(args.event.target).parents('.customer_search_row').length){
        
        args.cancel=true;    
        }else if(args.event && args.event.target && $(args.event.target).parents('.searchinputgroup').length){
        
        args.cancel=true;    
        }
        //////console.log(args);
        },
        
        itemTemplate: search_result_template,
        
        });
        
        //render the component
        window['customer_search{{$grid_id}}'].appendTo('#customer_search{{$grid_id}}');
    
        @endif
    })
    
</script>

<script>

  
/*TELECLOUD TAB*/ 
 @if($show_subscriptions_tab)
    $(document).ready(function() {
           
            // Initialize DropDownList component
            
            @if(session('role_level') != 'Customer')
          
            window['pbx_domain_switcher{{$grid_id}}'] = new ej.dropdowns.DropDownList({
                index: 0,
                placeholder: 'Select a customer',
                fields: {value: 'id', text: 'name'},
                dataSource: {!! json_encode(\Erp::services_accounts()) !!},
                allowFiltering: true,
                popupHeight: '200px',
                popupWidth: 'auto',
                showClearButton: false,
                
                filtering: function(e){
                    if(e.text == ''){
                        e.updateData(window['pbx_domain_switcher{{$grid_id}}'].dataSource);
                    }else{ 
                        var query = new ej.data.Query().select(['id','name','domain_uuid','domain_name']);
                        query = (e.text !== '') ? query.where('name', 'contains', e.text, true) : query;
                        e.updateData(window['pbx_domain_switcher{{$grid_id}}'].dataSource, query);
                    }
                },
                
                change: function (args) { 
                    //console.log('change',args);
                    if(args.isInteracted){
                       
                       
                        $.ajax({
                            type: 'get',
                            url: 'service_account_filter/'+args.itemData.id,
                            success: function (data){
                                  window['grid_{{ $grid_id }}'].gridOptions.refresh();
                            }
                        })
                    }
                   
                   // window.location.href = args.itemData.login_url+'?return_to={{$menu_route}}';
                },
                selectByAccountId: function(account_id){
                    if(account_id == 1){
                        account_id = 12;
                    }
                    //console.log('selectByAccountId');
                    var dataSource = window['pbx_domain_switcher{{$grid_id}}'].dataSource;
                    var item = dataSource.find(function(data) {
                    return data.id === account_id;
                    });
                    
                    //console.log(item);
                    if (item) {
                        window['pbx_domain_switcher{{$grid_id}}'].value = item.id;
                    }else{
                        window['pbx_domain_switcher{{$grid_id}}'].value = null;
                    }
                    
                },
                selectByDomainUuid: function(domain_uuid){
                    var dataSource = window['pbx_domain_switcher{{$grid_id}}'].dataSource;
                    var item = dataSource.find(function(data) {
                    return data.domain_uuid === domain_uuid;
                    });
                    
                    if (item) {
                        window['pbx_domain_switcher{{$grid_id}}'].value = item.id;
                    }else{
                        window['pbx_domain_switcher{{$grid_id}}'].value = null;
                    }
                },
            },'#panel_switcher{{$grid_id}}');
            //console.log( window['pbx_domain_switcher{{$grid_id}}']);
           @endif
        });
  
    @if(!empty($telecloud_menu) && count($telecloud_menu) > 0)   

    var telecloudMenuItems{{ $grid_id }} = @php echo json_encode($telecloud_menu); @endphp;
    
    //Initialize ListView component
    var telecloudMenu{{ $grid_id }} = new ej.lists.ListView({
        //set the data to datasource property
        dataSource: telecloudMenuItems{{ $grid_id }},
        // map the groupBy field with category column
        fields: { tooltip: 'text', child:'items' },
        headerTitle: 'Voice',
        showHeader: true,
        select: function(e) {
            var data = e.data;
          
            if (data.url && data.url > '' && data.url!='#') {
                var link_url = data.url;
                 
                if(window['pbx_domain_switcher{{$grid_id}}'] && window['pbx_domain_switcher{{$grid_id}}'].itemData){
                    if(window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_name!="156.0.96.60"){
                        if (link_url.includes('?')) {
                            link_url += '&telecloud_filter_domain=' + window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_uuid;
                        } else {
                            link_url += '?telecloud_filter_domain=' + window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_uuid;
                        }
                    }
                } 
                    //console.log(link_url);
                if (link_url.includes('voice_settings_form')) {
                    //console.log(11);
                    sidebarform('pbx_settings',link_url);
                }else{
                    //console.log(22);
                    window.open(link_url,'_blank');
                }
            }
        }
    });
    //Render initialized ListView
    telecloudMenu{{ $grid_id }}.appendTo("#telecloud_menu{{ $grid_id }}");
  
    // top_menu initialization
    /*
    telecloudMenu =new ej.navigations.Menu({
           items: telecloudMenuItems,
           orientation: 'Vertical',
            cssClass: 'telecloud_menu-wrapper',
            template: '#navMenuTemplate',
            width:'100%',
            @if(is_superadmin())
            created: function(args){
                $('body').append('<ul id="telecloud_menu_context" class="m-0"></ul>');
                var context_items = [
                    {
                        id: "context_menu_edit_1",
                        text: "Edit Menu",
                        iconCss: "fas fa-list",
                        url: 'sf_menu_manager/{{$module_id}}/telecloud_menu',
                        data_target: 'view_modal',
                    },
                    {
                        id: "context_menu_edit_1",
                        text: "Edit SMS Menu",
                        iconCss: "fas fa-list",
                        url: 'sf_menu_manager/{{$module_id}}/sms_menu',
                        data_target: 'view_modal',
                    },
               
                ];
                var menuOptions = {
                    target: '.telecloud_menubtn',
                    items: context_items,
                    beforeItemRender: contextmenurender,
                
                beforeOpen: function(args){
                    // toggle context items on header
                   
                    if( $(args.event.target).hasClass('telecloud_menubtn')){
                        data_menu_id = $(args.event.target).attr('data-menu-id');
                        data_button_function = $(args.event.target).attr('data-button-function');
                    }else{
                        data_menu_id = $(args.event.target).closest('li').attr('data-menu-id');
                        data_button_function = $(args.event.target).closest('li').attr('data-button-function');
                    }
                    if(data_button_function > ''){
                        telecloud_menu_context.enableItems(['Edit Function'], true);        
                    }else{
                        telecloud_menu_context.enableItems(['Edit Function'], false); 
                    }
                },
                select: function(args){
                    if(args.item.id === 'edit_menu_btn') {
                        sidebarform('editmenubtn','{{$menu_manager_url}}/edit/'+data_menu_id);
                    }
                    if(args.item.id === 'edit_menu_btn_function') {
                        sidebarform('editfunctionbtn','/code_edit/'+data_button_function);
                    }
                }
                };
                
                // Initialize ContextMenu control
                telecloud_menu_context = new ej.navigations.ContextMenu(menuOptions, '#telecloud_menu_context');
            },
            beforeOpen: function(args){
            
            telecloud_menu_context.refresh();
            },
            @endif
            beforeItemRender: function(args){
        
        
                
                var el = args.element;   
                $(el).find("a.main_link").attr("title",args.item.title);
                if(args.item.border_top){
                  
                   $(el).addClass("menu_border_top");
                }
                
                $(el).attr("data-menu-id",args.item.menu_id);
                $(el).attr("data-button-function",args.item.button_function);
                
                if(args.item.cssClass) {
                    $(el).addClass(args.item.cssClass);
                }
                 
                @if(!empty($menus_newtab) && $menus_newtab === true)
                if(args.item.data_target == '' && args.item.url > '' && args.item.url != "#"){
                    var el = args.element;
                    $(el).find("a.main_link").attr("target","_blank");
                }
                @endif
                if(args.item.new_tab == 1) {
                 
                   $(el).find("a.main_link").attr("target","_blank");
                }
                
              
               if(args.item.data_target == 'javascript') {
                   $(el).find("a.main_link").attr("data-target",args.item.data_target);
                   $(el).find("a.main_link").attr("js-target",args.item.url);
                   $(el).find("a.main_link").attr("id",args.item.url);
                   $(el).find("a.main_link").attr("href","javascript:void(0)");
               }else if(args.item.data_target == 'transaction' || args.item.data_target == 'transaction_modal') {
                   $(el).find("a.main_link").attr("data-target",args.item.data_target);
                   $(el).find("a.main_link").attr("href","javascript:void(0)");
                   $(el).find("a.main_link").attr("modal_url",args.item.url);
               }else if(args.item.data_target) {
                   $(el).find("a.main_link").attr("data-target",args.item.data_target);
                  
               }
               
                
                if(args.item.url > '' && args.item.url != "#"){
                    var link_url = args.item.url;
                  
                    if(window['pbx_domain_switcher{{$grid_id}}'] && window['pbx_domain_switcher{{$grid_id}}'].itemData){
                        if(window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_name!="156.0.96.60"){
                        if (link_url.includes('?')) {
                        link_url += '&telecloud_filter_domain=' + window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_uuid;
                        } else {
                        link_url += '?telecloud_filter_domain=' + window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_uuid;
                        }
                        }
                    }
                    $(el).find("a.main_link").attr("href",link_url);
                }
          
            },
       },'#telecloud_menu');
   */
    @endif 
    
    
    @if(!empty($sms_menu) && count($sms_menu) > 0)   

    var smsMenuItems{{ $grid_id }} = @php echo json_encode($sms_menu); @endphp;
  
    //Initialize ListView component
    var smsMenu{{$grid_id}} = new ej.lists.ListView({
        //set the data to datasource property
        dataSource: smsMenuItems{{ $grid_id }},
        // map the groupBy field with category column
        fields: { tooltip: 'text', child:'items' },
        headerTitle: 'SMS',
        showHeader: false,
        select: function(e) {
            var data = e.data;
            if (data.url && data.url > '' && data.url!='#') {
                var link_url = data.url;
                 
                if(window['pbx_domain_switcher{{$grid_id}}'] && window['pbx_domain_switcher{{$grid_id}}'].itemData){
                    if(window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_name!="156.0.96.60"){
                        if (link_url.includes('?')) {
                            link_url += '&telecloud_filter_domain=' + window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_uuid;
                        } else {
                            link_url += '?telecloud_filter_domain=' + window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_uuid;
                        }
                    }
                }
                
                window.open(link_url,'_blank');
            }
        }
    });
    //Render initialized ListView
    smsMenu{{$grid_id}}.appendTo("#sms_menu{{$grid_id}}");
    // top_menu initialization
    /*
    smsMenu =new ej.navigations.Menu({
           items: smsMenuItems,
           orientation: 'Vertical',
            cssClass: 'sms_menu-wrapper',
            template: '#navMenuTemplate',
           
           width:'100%',
            @if(is_superadmin())
            created: function(args){
                $('body').append('<ul id="sms_menu_context" class="m-0"></ul>');
                var context_items = [
                    {
                        id: "context_menu_edit_1",
                        text: "Edit Menu",
                        iconCss: "fas fa-list",
                        url: 'sf_menu_manager/{{$module_id}}/sms_menu',
                        data_target: 'view_modal',
                    },
               
                ];
                var menuOptions = {
                    target: '.sms_menubtn',
                    items: context_items,
                    beforeItemRender: contextmenurender,
                
                beforeOpen: function(args){
                    // toggle context items on header
                   
                    if( $(args.event.target).hasClass('sms_menubtn')){
                        data_menu_id = $(args.event.target).attr('data-menu-id');
                        data_button_function = $(args.event.target).attr('data-button-function');
                    }else{
                        data_menu_id = $(args.event.target).closest('li').attr('data-menu-id');
                        data_button_function = $(args.event.target).closest('li').attr('data-button-function');
                    }
                    if(data_button_function > ''){
                        sms_menu_context.enableItems(['Edit Function'], true);        
                    }else{
                        sms_menu_context.enableItems(['Edit Function'], false); 
                    }
                },
                select: function(args){
                    if(args.item.id === 'edit_menu_btn') {
                        sidebarform('editmenubtn','{{$menu_manager_url}}/edit/'+data_menu_id);
                    }
                    if(args.item.id === 'edit_menu_btn_function') {
                        sidebarform('editfunctionbtn','/code_edit/'+data_button_function);
                    }
                }
                };
                
                // Initialize ContextMenu control
                sms_menu_context = new ej.navigations.ContextMenu(menuOptions, '#sms_menu_context');
            },
            beforeOpen: function(args){
            
            sms_menu_context.refresh();
            },
            @endif
            beforeItemRender: function(args){
                
                var el = args.element;   
                $(el).find("a.main_link").attr("title",args.item.title);
                if(args.item.border_top){
                  
                   $(el).addClass("menu_border_top");
                }
                
                $(el).attr("data-menu-id",args.item.menu_id);
                $(el).attr("data-button-function",args.item.button_function);
                
                if(args.item.cssClass) {
                    $(el).addClass(args.item.cssClass);
                }
                 
                @if(!empty($menus_newtab) && $menus_newtab === true)
                if(args.item.data_target == '' && args.item.url > '' && args.item.url != "#"){
                    var el = args.element;
                    $(el).find("a.main_link").attr("target","_blank");
                }
                @endif
                if(args.item.new_tab == 1) {
                 
                   $(el).find("a.main_link").attr("target","_blank");
                }
                
              
               if(args.item.data_target == 'javascript') {
                   $(el).find("a.main_link").attr("data-target",args.item.data_target);
                   $(el).find("a.main_link").attr("js-target",args.item.url);
                   $(el).find("a.main_link").attr("id",args.item.url);
                   $(el).find("a.main_link").attr("href","javascript:void(0)");
               }else if(args.item.data_target == 'transaction' || args.item.data_target == 'transaction_modal') {
                   $(el).find("a.main_link").attr("data-target",args.item.data_target);
                   $(el).find("a.main_link").attr("href","javascript:void(0)");
                   $(el).find("a.main_link").attr("modal_url",args.item.url);
               }else if(args.item.data_target) {
                   $(el).find("a.main_link").attr("data-target",args.item.data_target);
                  
               }
               
                if(args.item.url > '' && args.item.url != "#"){
                    var link_url = args.item.url;
                  
                    if(window['pbx_domain_switcher{{$grid_id}}'] && window['pbx_domain_switcher{{$grid_id}}'].itemData){
                        if(window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_name!="156.0.96.60"){
                        if (link_url.includes('?')) {
                        link_url += '&telecloud_filter_domain=' + window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_uuid;
                        } else {
                        link_url += '?telecloud_filter_domain=' + window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_uuid;
                        }
                        }
                    }
                    $(el).find("a.main_link").attr("href",link_url);
                }
                
                
          
            },
       },'#sms_menu');
    */
    @endif 
    

    
    function pbx_domain_switcher_select(){
     
        if(window['pbx_domain_switcher{{$grid_id}}']){
            if($("#grid_{{ $grid_id }}").hasClass('detailgrid-focus')){
                var selected = window['selectedrow_detail{{ $grid_id }}'];
            }else{
                var selected = window['selectedrow_{{ $grid_id }}'];
            }
     
            @if($db_table == 'crm_accounts')
                if(selected && selected.id){
               
                window['pbx_domain_switcher{{$grid_id}}'].selectByAccountId(selected.id);
                }
            @else
        
                if(selected && selected.domain_uuid){
               
                window['pbx_domain_switcher{{$grid_id}}'].selectByDomainUuid(selected.domain_uuid);
                }else if(selected && selected.account_id){
               
                window['pbx_domain_switcher{{$grid_id}}'].selectByAccountId(selected.account_id);
                }
            @endif
        }
    }
             
  
    
    function setup_services_list_view{{$grid_id}}(){
            // Initialize DropDownList component
        //console.log('setup_services_list_view');
        var services_listview_datasource{{$grid_id}} = [];
    
        @if(!empty($telecloud_menu) && count($telecloud_menu) > 0)  
        var telecloudMenuItems{{ $grid_id }} = @php echo json_encode($telecloud_menu); @endphp;
        if(telecloudMenuItems{{ $grid_id }}.length > 0){
            services_listview_datasource{{$grid_id}}.push({id:'voicemenu',text:'Voice','items':telecloudMenuItems{{ $grid_id }}});
        }
        
        @endif
        
        var hosting_panels_ds{{ $grid_id }} = {!! json_encode(\Erp::hosting_panels(),true) !!};
        if(hosting_panels_ds{{ $grid_id }}.length > 0){
            services_listview_datasource{{$grid_id}}.push({id:'hostingmenu',text:'Hosting','items':hosting_panels_ds{{ $grid_id }}});
        }
        
        @if(!empty($sms_menu) && count($sms_menu) > 0)   
        var smsMenuItems{{ $grid_id }} = @php echo json_encode($sms_menu); @endphp;
        if(smsMenuItems{{ $grid_id }}.length > 0){
            services_listview_datasource{{$grid_id}}.push({id:'smsmenu',text:'SMS','items':smsMenuItems{{ $grid_id }}});
        }
        
        @endif
        
        
        //Initialize ListView component
        telecloud_listview{{ $grid_id }} = new ej.lists.ListView({
            //set the data to datasource property
            dataSource: services_listview_datasource{{ $grid_id }},
            // map the groupBy field with category column
            fields: { text:'text',id:'id', tooltip: 'text', child:'items' },
            headerTitle: 'Services',
            showHeader: true,
            select: function(e) {
                var data = e.data;
              //console.log(e);
              
                if (data && data.url && data.url > '' && data.url!='#') {
                    var link_url = data.url;
                    if(!data.id.includes('hosting')){
                        
                    
                    if(window['pbx_domain_switcher{{$grid_id}}'] && window['pbx_domain_switcher{{$grid_id}}'].itemData){
                        if(window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_name!="156.0.96.60"){
                            if (link_url.includes('?')) {
                                link_url += '&telecloud_filter_domain=' + window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_uuid;
                            } else {
                                link_url += '?telecloud_filter_domain=' + window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_uuid;
                            }
                        }
                    }
                    }
                    
                    //console.log(link_url);
                    if (link_url.includes('voice_settings_form')) {
                        //console.log(11);
                        sidebarform('pbx_settings',link_url);
                    }else{
                        //console.log(22);
                        window.open(link_url,'_blank');
                    }
                }
                // this.unselectItem();
            }
        });
        //Render initialized ListView
        telecloud_listview{{ $grid_id }}.appendTo("#telecloud_listview{{ $grid_id }}");
        
        
               
        @if($show_subscriptions_tab)
  
  
    $(document).ready(function() {
        // CONTEXT MENU FOR VISIBILITY
        $('body').append('<ul id="sidebar_subscription_info_context_el{{$grid_id}}" class="m-0"></ul>');
        // Initialize Syncfusion window['sidebar_nav_context']
        subscription_info_context_items = [
            {
                id: "ssi_setup",
                text: "Send Setup Email",
            },
            {
                id: "ssi_view",
                text: "View",
            },
            {
                id: "ssi_migrate",
                text: "Migrate",
            },
            @if(session('role_level') == 'Admin')
            {
                id: "ssi_annual",
                text: "Bill Annually",
            },
            @endif
            {
                id: "ssi_mailboxes",
                text: "Manage Mailboxes",
            },
            {
                id: "ssi_cancel",
                text: "Cancel",
            },
            {
                id: "ssi_undocancel",
                text: "Undo Cancel",
            },
            
        ];
        window['sidebar_subscription_info_context{{$grid_id}}'] = new ej.navigations.ContextMenu({
            target: '.sidebar_subscription_info',
            items: subscription_info_context_items,
            beforeItemRender: contextmenurender,
            beforeOpen: function(args){
                //console.log('beforeOpen');
                //console.log(args);
                //console.log($(args.event.target));
              
                
                
                context_accountid{{$grid_id}} = $(args.event.target).closest('.sidebar_subscription_info').attr('data-accountid');
                context_subid{{$grid_id}} = $(args.event.target).closest('.sidebar_subscription_info').attr('data-subid');
                context_tocancel{{$grid_id}} = $(args.event.target).closest('.sidebar_subscription_info').attr('data-tocancel');
                context_provision_type{{$grid_id}} = $(args.event.target).closest('.sidebar_subscription_info').attr('data-provisiontype');
                context_bill_frequency{{$grid_id}} = $(args.event.target).closest('.sidebar_subscription_info').attr('data-bill_frequency');
                
                //console.log(context_accountid{{$grid_id}});
                //console.log(context_subid{{$grid_id}});
                //console.log(context_tocancel{{$grid_id}});
                //console.log(context_provision_type{{$grid_id}});
                
                
                if(context_tocancel{{$grid_id}} == 1){
                    window['sidebar_subscription_info_context{{$grid_id}}'].enableItems(['Undo Cancel'], true);
                    window['sidebar_subscription_info_context{{$grid_id}}'].enableItems(['Cancel'], false);
                }else{
                    window['sidebar_subscription_info_context{{$grid_id}}'].enableItems(['Undo Cancel'], false);
                    window['sidebar_subscription_info_context{{$grid_id}}'].enableItems(['Cancel'], true);
                }
              
                if(context_provision_type{{$grid_id}} != 'hosting'){
                    
                    window['sidebar_subscription_info_context{{$grid_id}}'].enableItems(['Manage Mailboxes'], false);
                }else{
                   
                    window['sidebar_subscription_info_context{{$grid_id}}'].enableItems(['Manage Mailboxes'], true);
                }
                
                
                @if(session('role_level') == 'Admin')
                if(context_bill_frequency{{$grid_id}} == 1){
                 
                    window['sidebar_subscription_info_context{{$grid_id}}'].enableItems(['Bill Annually'], true);
                }else{
                  
                    window['sidebar_subscription_info_context{{$grid_id}}'].enableItems(['Bill Annually'], false);
                }
                @endif
              
            },
            select: function(args){
    
                if(args.item.pbx_url){
                    window.open(args.item.pbx_url,'_blank');
                }
                if(args.item.text === 'Send Setup Email'){
                    sidebarform(args.item.id,'service_setup_email/'+context_subid{{$grid_id}});
                }
                
                if(args.item.text === 'Manage Mailboxes'){
                    sidebarformleft(args.item.id,'manage_mailboxes/'+context_subid{{$grid_id}});
                }
                
                if(args.item.text === 'Bill Annually'){
                    gridAjaxConfirm('/subscription_bill_annually/'+context_subid{{$grid_id}}, 'Convert subscription to be billed annually? Starts at next billing period');
                    
                }
               
                if(args.item.text === 'View'){
                     window.open('{{$subscriptions_url}}?id='+context_subid{{$grid_id}});
                }
                if(args.item.text === 'Migrate'){
                    sidebarform(args.item.id,'subscription_migrate_form/'+context_subid{{$grid_id}});
                }
                if(args.item.text === 'Cancel'){
                    gridAjaxConfirm('/{{ $subscriptions_url }}/cancel?id='+context_subid{{$grid_id}}, 'Cancel Subscription?');
                }
                console.log(args.item);
                console.log('args.item.text');
                console.log(args.item.text);
                if(args.item.text === 'Undo Cancel'){
                    console.log('restore_subscription/'+context_subid{{$grid_id}});
                    gridAjaxConfirm('/restore_subscription/'+context_subid{{$grid_id}}, 'Cancel Subscription?');
                }
            }
        });
        window['sidebar_subscription_info_context{{$grid_id}}'].appendTo('#sidebar_subscription_info_context_el{{$grid_id}}');
    });
    
    
       

            content_subscriptions_listview{{$grid_id}} =  new ej.lists.ListView({
                // map the groupBy field with category column
                fields: { text:'text',id:'id', tooltip: 'text', child:'items' },
                headerTitle: 'Subscriptions',
                showHeader: true,
                template: '${if(items && items.length>0)}'+
                '<div class="e-text-content e-icon-wrapper ${cssClass}"><span class="e-list-text">${text}</span><div class="e-icons e-icon-collapsible"></div></div>'+
                '${else}'+
                '<div class="e-text-content ${cssClass}" data-bill_frequency="${bill_frequency}" data-provisiontype="${provisiontype}" data-tocancel="${tocancel}" data-subid="${subid}" data-accountid="${accountid}"><span class="e-list-text">${text}</span></div>'+
                '${/if}',
                 actionComplete: function(args) {
                  //console.log('actionComplete');
                  //console.log(args);
             
                    setTimeout(window['sidebar_subscription_info_context{{$grid_id}}'].refresh(),500);
               
            
                  
                },
                select: function(args){
                    //console.log('select');
                    //console.log(args);
                   
                }
            });
            
            //Render initialized Accordion component
            content_subscriptions_listview{{$grid_id}}.appendTo('#content_subscriptions_listview{{$grid_id}}'); 
        @endif
            //console.log(111);
    }
    
  
    
    /*
    kb articles - nested accordion
    
    */
   
    function setup_kb_accordion{{$grid_id}}(){
        $.get('get_sidebar_knowledge_base_list_view', function(data) {
           
            window['kb_items{{$grid_id}}'] = data.items;
           
           
            window['kb_listview{{$grid_id}}'] =  new ej.lists.ListView({
                // map the groupBy field with category column
                dataSource: window['kb_items{{$grid_id}}'],
                fields: { text:'text',id:'id', tooltip: 'text', child:'items',cssClass:'cssClass' },
                headerTitle: 'Knowledge Base',
                showHeader: true,
                template: '${if(items && items.length>0)}'+
                '<div class="e-text-content e-icon-wrapper ${cssClass}"><span class="e-list-text">${text}</span><div class="e-icons e-icon-collapsible"></div></div>'+
                '${else}'+
                '<div class="e-text-content ${cssClass}" data-attr-id="${faq_id}"><span class="e-list-text">${text}</span></div>'+
                '${/if}',
                
                
                actionComplete: function(args) {
                 // //console.log('actionComplete');
                 // //console.log(args);
              @if(session('role_level') == 'Admin')
                    setTimeout(contextkbitems{{$grid_id}}.refresh(),500);
               @endif
               
            
                  
                },
                select: function(args){
                   // //console.log('select');
                   // //console.log(args);
                   // setTimeout(contextkbitems{{$grid_id}}.refresh(),500);
                   if(args.data.cssClass == "kbitem_context"){
                       viewDialog('kbview','/kbview/'+args.data.faq_id);
                   }
                }
                
            });
            
            //Render initialized Accordion component
            window['kb_listview{{$grid_id}}'].appendTo('#kb_listview{{$grid_id}}'); 
           
        });
    }
    
  
    
    
    
    
@endif


    
    @if(session('role_level') == 'Admin')
    function create_kbitems_context{{$grid_id}}(){
        $('body').append('<ul id="contextkbitems{{$grid_id}}" class="m-0"></ul>');
        var items = [
            {
                id: "kbitem_email",
                text: "Email",
                iconCss: "fas fa-envelope",
            },
            {
                id: "kbitem_add",
                text: "Add",
                iconCss: "fa fa-plus",
            },
            {
                id: "kbitem_edit",
                text: "Edit",
                iconCss: "fas fa-pen",
            },
            {
                id: "kbitem_list",
                text: "List",
                iconCss: "fas fa-list",
            },
           
        ];
        var context_kbitem_id = false;
     
        var menuOptions = {
            target: '.kbitem_context',
            items: items,
            beforeItemRender: contextmenurender,
            beforeOpen: function(args){
                
                // toggle context items on header
                
                    context_kbitem_id = $(args.event.target).attr('data-attr-id');
                    if(!context_kbitem_id){
                       context_kbitem_id = $(args.event.target).closest('.kbitem_context').attr('data-attr-id');
                    }
            
                
            },
            select: function(args){
           
                if(args.item.id === 'kbitem_email') {
                    if(!context_kbitem_id){
                        alert('id not found');
                    }else{
                      
                        var kb_email_url = '/email_form/default/1?faq_id='+context_kbitem_id;
                        if($("#sidebar_accountid{{$grid_id}}").val() && $("#sidebar_accountid{{$grid_id}}").val() > 0){
                        var kb_email_url = '/email_form/default/'+$("#sidebar_accountid{{$grid_id}}").val()+'?faq_id='+context_kbitem_id;
                        }
                        console.log($("#sidebar_accountid{{$grid_id}}").val());
                        console.log(kb_email_url);
                        sidebarform('kbitem_edit',kb_email_url, 'Knowledge Base Edit');
                    }
                }
                if(args.item.id === 'kbitem_edit') {
                    if(!context_kbitem_id){
                        alert('id not found');
                    }else{
                     sidebarform('kbitem_edit','/{{$kbitems_url}}/edit/'+context_kbitem_id, 'Knowledge Base Edit');
                    }
                }
               
            
                if(args.item.id === 'kbitem_add') {
                     sidebarform('kbitem_edit','/{{$kbitems_url}}/edit', 'Knowledge Base Add');
                }
                if(args.item.id === 'kbitem_list') {
                     viewDialog('kbitem_list','/{{$kbitems_url}}', 'Knowledge Base');
                }
                    
            }
        };
      
        // Initialize ContextMenu control.
        contextkbitems{{$grid_id}} = new ej.navigations.ContextMenu(menuOptions, '#contextkbitems{{$grid_id}}');  
    }
    @endif

    @if(is_superadmin())
    @if(!empty($services_admin_menu) && count($services_admin_menu) > 0)   

    var servicesMenu{{$grid_id}}Items = @php echo json_encode($services_admin_menu); @endphp;


    //Initialize ListView component
    var servicesMenu{{$grid_id}} = new ej.lists.ListView({
        //set the data to datasource property
        dataSource: servicesMenu{{$grid_id}}Items,
        // map the groupBy field with category column
        fields: { tooltip: 'text', child:'items' },
        headerTitle: 'Services Admin',
        showHeader: true,
        select: function(e) {
            var data = e.data;
            if (data.url && data.url > '' && data.url!='#') {
                var link_url = data.url;
                 /*
                if(window['pbx_domain_switcher{{$grid_id}}'] && window['pbx_domain_switcher{{$grid_id}}'].itemData){
                    if(window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_name!="156.0.96.60"){
                        if (link_url.includes('?')) {
                            link_url += '&telecloud_filter_domain=' + window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_uuid;
                        } else {
                            link_url += '?telecloud_filter_domain=' + window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_uuid;
                        }
                    }
                }
                */
                window.open(link_url,'_blank');
            }
        }
    });
    //Render initialized ListView
    servicesMenu{{$grid_id}}.appendTo("#services_admin_menu{{$grid_id}}");
  /*
    // top_menu initialization
    var servicesMenu{{$grid_id}} = new ej.navigations.Menu({
           items: servicesMenu{{$grid_id}}Items,  
           orientation: 'Vertical',
            cssClass: 'telecloud_menu-wrapper',
            template: '#navMenuTemplate',
            width:'100%',
            @if(is_superadmin())
            created: function(args){
                $('body').append('<ul id="services_admin_menu{{$grid_id}}_context" class="m-0"></ul>');
                var context_items = [
                    {
                        id: "context_menu_edit_1",
                        text: "Edit Menu",
                        iconCss: "fas fa-list",
                        url: 'sf_menu_manager/{{$module_id}}/services_admin_menu',
                        data_target: 'view_modal',
                    },
               
                ];
                var menuOptions = {
                    target: '.services_admin_menubtn',
                    items: context_items,
                    beforeItemRender: contextmenurender,
                
                beforeOpen: function(args){
                    // toggle context items on header
                   
                    if( $(args.event.target).hasClass('services_admin_menu{{$grid_id}}btn')){
                        data_menu_id = $(args.event.target).attr('data-menu-id');
                        data_button_function = $(args.event.target).attr('data-button-function');
                    }else{
                        data_menu_id = $(args.event.target).closest('li').attr('data-menu-id');
                        data_button_function = $(args.event.target).closest('li').attr('data-button-function');
                    }
                    if(data_button_function > ''){
                        services_admin_menu{{$grid_id}}_context.enableItems(['Edit Function'], true);        
                    }else{
                        services_admin_menu{{$grid_id}}_context.enableItems(['Edit Function'], false); 
                    }
                },
                select: function(args){
                    if(args.item.id === 'edit_menu_btn') {
                        sidebarform('editmenubtn','{{$menu_manager_url}}/edit/'+data_menu_id);
                    }
                    if(args.item.id === 'edit_menu_btn_function') {
                        sidebarform('editfunctionbtn','/code_edit/'+data_button_function);
                    }
                }
                };
                
                // Initialize ContextMenu control
                services_admin_menu{{$grid_id}}_context = new ej.navigations.ContextMenu(menuOptions, '#services_admin_menu{{$grid_id}}_context');
            },
            beforeOpen: function(args){
            
            services_admin_menu{{$grid_id}}_context.refresh();
            },
            @endif
            beforeItemRender: function(args){
                
                var el = args.element;   
                $(el).find("a.main_link").attr("title",args.item.title);
                if(args.item.border_top){
                  
                   $(el).addClass("menu_border_top");
                }
                
                $(el).attr("data-menu-id",args.item.menu_id);
                $(el).attr("data-button-function",args.item.button_function);
                
                if(args.item.cssClass) {
                    $(el).addClass(args.item.cssClass);
                }
                 
                @if(!empty($menus_newtab) && $menus_newtab === true)
                if(args.item.data_target == '' && args.item.url > '' && args.item.url != "#"){
                    var el = args.element;
                    $(el).find("a.main_link").attr("target","_blank");
                }
                @endif
                if(args.item.new_tab == 1) {
                 
                   $(el).find("a.main_link").attr("target","_blank");
                }
                
              
           if(args.item.data_target == 'javascript') {
               $(el).find("a.main_link").attr("data-target",args.item.data_target);
               $(el).find("a.main_link").attr("js-target",args.item.url);
               $(el).find("a.main_link").attr("id",args.item.url);
               $(el).find("a.main_link").attr("href","javascript:void(0)");
           }else if(args.item.data_target == 'transaction' || args.item.data_target == 'transaction_modal') {
               $(el).find("a.main_link").attr("data-target",args.item.data_target);
               $(el).find("a.main_link").attr("href","javascript:void(0)");
               $(el).find("a.main_link").attr("modal_url",args.item.url);
           }else if(args.item.data_target) {
               $(el).find("a.main_link").attr("data-target",args.item.data_target);
              
           }
           
           
               
                if(args.item.url > '' && args.item.url != "#"){
                    var link_url = args.item.url;
                  
                    if(window['pbx_domain_switcher{{$grid_id}}'] && window['pbx_domain_switcher{{$grid_id}}'].itemData){
                        if(window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_name!="156.0.96.60"){
                        if (link_url.includes('?')) {
                        link_url += '&telecloud_filter_domain=' + window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_uuid;
                        } else {
                        link_url += '?telecloud_filter_domain=' + window['pbx_domain_switcher{{$grid_id}}'].itemData.domain_uuid;
                        }
                        }
                    }
                    $(el).find("a.main_link").attr("href",link_url);
                }
          
            },
       },'#services_admin_menu{{$grid_id}}');
    */
    
    @endif
    @endif
    
    @if(is_superadmin())
    function setup_kbinternal_accordion{{$grid_id}}(){
        $.get('get_sidebar_knowledge_base_list_view/1', function(data) {
           
            window['kbinternal_items{{$grid_id}}'] = data.items;
           
           
            window['kbinternal_listview{{$grid_id}}'] =  new ej.lists.ListView({
                // map the groupBy field with category column
                dataSource: window['kbinternal_items{{$grid_id}}'],
                fields: { text:'text',id:'id', tooltip: 'text', child:'items',cssClass:'cssClass' },
                headerTitle: 'Service Policies',
                showHeader: true,
                template: '${if(items && items.length>0)}'+
                '<div class="e-text-content e-icon-wrapper ${cssClass}"><span class="e-list-text">${text}</span><div class="e-icons e-icon-collapsible"></div></div>'+
                '${else}'+
                '<div class="e-text-content ${cssClass}" data-attr-id="${faq_id}"><span class="e-list-text">${text}</span></div>'+
                '${/if}',
                
                
                actionComplete: function(args) {
                 // //console.log('actionComplete');
                 // //console.log(args);
             @if(session('role_level') == 'Admin')
                    setTimeout(contextkbitems{{$grid_id}}.refresh(),500);
               @endif
            
                  
                },
                select: function(args){
                   // //console.log('select');
                   // //console.log(args);
                   // setTimeout(contextkbitems{{$grid_id}}.refresh(),500);
                   if(args.data.cssClass == "kbitem_context"){
                       viewDialog('kbview','/kbview/'+args.data.faq_id);
                   }
                }
                
            });
            
            //Render initialized Accordion component
            window['kbinternal_listview{{$grid_id}}'].appendTo('#kbinternal_listview{{$grid_id}}'); 
           
        });
    }
    @endif
    
    $(document).ready(function() {
        var r = '{{$show_subscriptions_tab}}';
        //console.log('show_subscriptions_tab',r);
      @if($show_subscriptions_tab)
            setup_services_list_view{{$grid_id}}();
        @endif
        
        @if(is_superadmin())
            setup_kbinternal_accordion{{$grid_id}}();
        @endif
        @if($show_subscriptions_tab)
            setup_kb_accordion{{$grid_id}}();
        @endif
        
            @if(session('role_level') == 'Admin')
                create_kbitems_context{{$grid_id}}();
            @endif
    });
  
</script>

@endpush

@push('page-styles')
<style>
.e-ripple-element{
    display:none !important;
}
.kbaccord .e-acrdn-header-content{
    text-transform: uppercase;
   
}

.kbitem .e-acrdn-header-content,.nested_kb .e-acrdn-header-content,.kbitem_context{
    text-transform: capitalize;
    padding-left: 15px;
}
#content_sidebar .e-list-item.e-selected{
    font-weight:bold !important;
}
#content_sidebar .e-acrdn-item.e-selected.e-active > .e-acrdn-header .e-acrdn-header-content{
    font-weight:bold !important;
}
#content_charts_accordion.e-accordion .e-acrdn-item .e-acrdn-panel .e-acrdn-content {
    padding: 0 !important;
}
.widget_type-Grid{
    min-height:500px;
    height: 500px;
}



#content_charts_accordion .e-acrdn-item.e-overlay{
    display:none !important;
}
#content_sidebar .tab-pane {
    height: calc(100vh - 210px) !important;
    max-height: calc(100vh - 210px) !important;
    overflow-y: auto !important;
}

#content_sidebar .e-acrdn-header{
    padding: 0 16px;
    min-height: 26px !important;
    max-height: 26px !important;
    height: 26px !important;
    line-height: 26px !important;
    margin: 0;
}

#content_sidebar .e-toggle-icon{
    
    min-height: 26px !important;
    max-height: 26px !important;
    height: 26px !important;
}

#content_sidebar{
font-size:12px;
}
#content_sidebar .e-acrdn-header-content{

    font-size:12px !important;
}

#content_sidebar .e-acrdn-content, #content_sidebar .e-acrdn-content strong, #content_sidebar .e-acrdn-content p, #content_sidebar .e-acrdn-content h1
, #content_sidebar .e-acrdn-content h2, #content_sidebar .e-acrdn-content h3, #content_sidebar .e-acrdn-content h4, #content_sidebar .e-acrdn-content h5
, #content_sidebar .e-acrdn-content h6{

    font-size:12px !important;
}

#content_sidebar .card-body, #content_sidebar .card-body strong, #content_sidebar .card-body p, #content_sidebar .card-body h1
, #content_sidebar .card-body h2, #content_sidebar .card-body h3, #content_sidebar .card-body h4, #content_sidebar .card-body h5
, #content_sidebar .card-body h6{

    font-size:12px !important;
}

#content_sidebar .e-list-item{
    
    padding: 0 16px;
    height: 26px !important;
    line-height: 26px !important;
    font-size:12px;
}

.sidebar-acc-header .e-acrdn-header .e-acrdn-header-content{
    text-transform: uppercase !important;
    font-weight:bold !important;
    color:#212529 !important;
}
.e-accordion .e-acrdn-item.sidebar-acc-header.e-overlay {
    background: #fff;
    opacity: 1;
}


.sidebar-acco-header{
    text-transform: uppercase !important;
    font-weight:bold !important;
    color:#212529 !important;
}
.e-acrdn-item.e-item-focus{
    border:none !important;
}
.telecloud_menu-wrapper:not(.e-menu-popup),#telecloud_menu,#services_admin_menu{{$grid_id}}{
    width:100% !important;
}
.sms_menu-wrapper:not(.e-menu-popup),#sms_menu{
    width:100% !important;
}
#content_sidebar_telecloud{{$grid_id}} .e-listview .e-list-header{
    background-color: #e6f5ff !important;
}

#content_sidebar_support{{$grid_id}} .e-listview .e-list-header{
    background-color: #e6f5ff !important;
}
</style>
@endpush