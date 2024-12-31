 <!-- Navbar -->

 <div id="nav-container" class="d-none">
    <div class="mt-2 mb-2 top-1 p-0 mx-2 shadow-none border-radius-xl z-index-sticky bg-white" >
    
          <div id="navbar_header" class="navbar p-0 bg-white border-radius-xl" style="border-radius:1rem"></div>
    </div>
    <!-- End Navbar -->
    
    <div id="toolbar_template_branding" class="d-flex">
      
        <a class="navbar-brand m-0 p-0"  target="_blank">
        @if(!empty($panel_logo))
        <img src="{{ url($panel_logo) }}" class="img-fluid" alt="main_logo" style="max-height:40px">
        @elseif(!empty($branding_logo))
        <img src="{{ url($branding_logo) }}" class="img-fluid" alt="main_logo" style="max-height:40px">
        @endif
        </a>
       
       
        @if(empty($webform))
        <!--<div class="sidenav-toggler sidenav-toggler-inner me-2 d-flex align-items-center">
          <a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav">
            <div class="sidenav-toggler-inner">
              <i class="sidenav-toggler-line"></i>
              <i class="sidenav-toggler-line"></i>
              <i class="sidenav-toggler-line"></i>
            </div>
          </a>
        </div>
        
        <nav aria-label="breadcrumb">
        
        </nav>
        <button id="showleftsidebar" class="e-btn btn btn-xs ms-1 mb-0 d-none">Maximize</button>-->
        @endif
      
    </div>
    
        
  
   
    
    
    
  
    @if(session('role_level') && session('role_level')!='Admin')     
    <div id="toolbar_template_customer_menu">
        <ul id="customer_menu" class=" navbar-nav justify-content-end "></ul>
    </div>
    @endif
    @if(session('role_level') && session('role_level')=='Admin')     
    <div id="toolbar_template_module_menu">
        <ul id="module_menu" class=" navbar-nav justify-content-end "></ul>
    </div>
    @endif
    @if(!empty($top_left_menu) && count($top_left_menu) > 0)    
    <div id="toolbar_template_top_left_menu">
        <ul id="top_left_menu" class=" navbar-nav justify-content-end "></ul>
    </div>
    @endif
   
    
    <div id="toolbar_template_right_menu">
        <ul id="top_right_menu" class=" navbar-nav justify-content-end "></ul>
        <button id="showrightsidebar" class="e-btn btn btn-xs ms-1 mb-0 d-none">Maximize</button>
        <button id="showrightsidebar{{$grid_id}}" class="e-btn btn btn-xs ms-1 mb-0 d-none">Maximize</button>
    </div>
    
    

    
    @if(!empty($main_menu) && count($main_menu) > 0)  
    <div id="toolbar_template_main_menu">  
    <ul id="main_menu"></ul>
    </div>
    @endif
    
    @if(!empty($services_menu) && count($services_menu) > 0)  
    <div id="toolbar_template_services_menu">  
    <ul id="services_menu"></ul>
    </div>
    @endif
    
   
    
    
    </div>
    <div class="top_left_menubtn"></div>
  
   <div class="main_menubtn"></div>
   <div class="services_menubtn"></div>
   <div class="module_menubtn"></div>

    @push('page-scripts')
      <!-- Template to render Menu -->
    <script id="navMenuTemplate" type="text/x-template">
        ${if(parent_id == 0)}
            ${if(iconCss)}
                ${if(url)}
                <div class="e-anchor-wrap"><span class="e-menu-icon ${iconCss}"></span> <a href="${url}"  class="main_link stretched-link"><span style="width:100%;">${menu_title}</span></a></div>  
                ${else}
                   <div class="e-anchor-wrap"><span class="e-menu-icon ${iconCss}"></span>${title}</div> 
                ${/if}
            ${else}
                ${if(url)}
                <a href="${url}" class="main_link stretched-link"><span style="width:100%;">${if(iconCss)} <i class="${iconCss}"></i>${/if}${menu_title}</span></a>  
                ${else}
                    ${title}
                ${/if}
            ${/if} 
        ${else if (value)}
            <div style="width:100%;display:flex;justify-content: space-between;" class="${cssClass}">
            ${if(url)}
            <a href="${url}"  class="main_link stretched-link"><span style="width:100%;">${if(iconCss)} <i class="${iconCss}"></i>${/if}${menu_title}</span></a>  
            ${/if}
            ${if(add_url)}
                <a href="${add_url}" data-target="sidebarform" class="ms-3"><span class="e-badge e-badge-success"><i class="fas fa-plus"></i></span></a>
            ${/if}
            </div>
        ${/if}    
    </script>

    <script>
    $(document).on('click','#showrightsidebar', function(e){
        $("#showrightsidebar").addClass('d-none');
        sidebarformcontainer.show();
    });
    $(document).on('click','#showleftsidebar', function(e){
        $("#showleftsidebar").addClass('d-none');
        sidebar_leftformcontainer.show();
    });
      // module contextmenu
   
      
   
    @if(!empty($top_right_menu) && count($top_right_menu) > 0)   

    var adminMenuItems = @php echo json_encode($top_right_menu); @endphp;

  
    // top_menu initialization
    var adminRightMenu =new ej.navigations.Menu({
           items: adminMenuItems,
           orientation: 'Horizontal',
            cssClass: 'top-menu btn-group',
        template: '#navMenuTemplate',
            @if(is_superadmin())
            created: function(args){
                $('body').append('<ul id="allaccess_context" class="m-0"></ul>');
                var context_items = [
                    {
                        id: "context_allaccess_edit",
                        text: "Edit Menu",
                        iconCss: "fas fa-list",
                        url: 'sf_menu_manager/{{$module_id}}/top_right_menu',
                        data_target: 'view_modal',
                    },
                   /* {
                        id: "edit_menu_btn",
                        text: "Edit",
                        iconCss: "fas fa-list",
                    },
                    {
                        id: "edit_menu_btn_function",
                        text: "Edit Function",
                        iconCss: "fas fa-list",
                    },*/
                ];
                var menuOptions = {
                    target: '.top_right_menubtn',
                    items: context_items,
                    beforeItemRender: contextmenurender,
                
                beforeOpen: function(args){
                    // toggle context items on header
                   
                    if( $(args.event.target).hasClass('top_right_menubtn')){
                        data_menu_id = $(args.event.target).attr('data-menu-id');
                        data_button_function = $(args.event.target).attr('data-button-function');
                    }else{
                        data_menu_id = $(args.event.target).closest('li').attr('data-menu-id');
                        data_button_function = $(args.event.target).closest('li').attr('data-button-function');
                    }
                    if(data_button_function > ''){
                        top_right_menu_context.enableItems(['Edit Function'], true);        
                    }else{
                        top_right_menu_context.enableItems(['Edit Function'], false); 
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
                top_right_menu_context = new ej.navigations.ContextMenu(menuOptions, '#allaccess_context');
            },
            beforeOpen: function(args){
            
            top_right_menu_context.refresh();
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
          
            },
       },'#top_right_menu');
    @endif
    
    
    @if(!empty($main_menu) && count($main_menu) > 0)   

    var adminMenuItems = @php echo json_encode($main_menu); @endphp;

  
    // top_menu initialization
    var adminLeftMenu =new ej.navigations.Menu({
           items: adminMenuItems,
           orientation: 'Horizontal',
            cssClass: 'top-menu btn-group',
            template: '#navMenuTemplate',
            @if(is_superadmin())
            created: function(args){
                $('body').append('<ul id="main_menu_context" class="m-0"></ul>');
                var context_items = [
                {
                    id: "context_menu_edit_1",
                    text: "Edit Menu",
                    iconCss: "fas fa-list",
                    url: 'sf_menu_manager/{{$module_id}}/main_menu',
                    data_target: 'view_modal',
                },{
                    id: "context_menu_mvr",
                    text: "Move to root",
                    iconCss: "fas fa-list",
                },/*
                {
                    id: "context_menu_edit_5",
                    text: "Edit Customer Menu",
                    iconCss: "fas fa-list",
                    url: 'sf_menu_manager/{{$module_id}}/customer_menu',
                    data_target: 'view_modal',
                },
                */
                ];
                var menuOptions = {
                    target: '.main_menubtn',
                    items: context_items,
                    beforeItemRender: contextmenurender,
                
                beforeOpen: function(args){
                    // toggle context items on header
                   
                    if( $(args.event.target).hasClass('main_menubtn')){
                        data_menu_id = $(args.event.target).attr('data-menu-id');
                        data_button_function = $(args.event.target).attr('data-button-function');
                    }else{
                        data_menu_id = $(args.event.target).closest('li').attr('data-menu-id');
                        data_button_function = $(args.event.target).closest('li').attr('data-button-function');
                    }
                    if(data_button_function > ''){
                        main_menu_context.enableItems(['Edit Function'], true);        
                    }else{
                        main_menu_context.enableItems(['Edit Function'], false); 
                    }
                },
                select: function(args){
                    if(args.item.id === 'edit_menu_btn') {
                        sidebarform('editmenubtn','{{$menu_manager_url}}/edit/'+data_menu_id);
                    }
                    if(args.item.id === 'edit_menu_btn_function') {
                        sidebarform('editfunctionbtn','/code_edit/'+data_button_function);
                    }
                    if(args.item.id === 'context_menu_mvr') {
                        gridAjax('/menu_mvr/'+data_menu_id);
                        viewDialog('editmenubtn','sf_menu_manager/{{$module_id}}/main_menu');
                    }
                }
                };
                
                // Initialize ContextMenu control
                main_menu_context = new ej.navigations.ContextMenu(menuOptions, '#main_menu_context');
            },
            beforeOpen: function(args){
            
            main_menu_context.refresh();
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
                 /*
                @if(!empty($menus_newtab) && $menus_newtab === true)
                if(args.item.data_target == '' && args.item.url > '' && args.item.url != "#"){
                    var el = args.element;
                    $(el).find("a.main_link").attr("target","_blank");
                }
                @endif
                
                */
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
          
            },
       },'#main_menu');
    @endif
    
    @if(!empty($services_menu) && count($services_menu) > 0)   

    var servicesMenuItems = @php echo json_encode($services_menu); @endphp;

  
    // top_menu initialization
    var servicesMenu =new ej.navigations.Menu({
           items: servicesMenuItems,
           orientation: 'Horizontal',
            cssClass: 'top-menu btn-group',
            template: '#navMenuTemplate',
            @if(is_superadmin())
            created: function(args){
                $('body').append('<ul id="services_menu_context" class="m-0"></ul>');
                var context_items = [
                    {
                        id: "context_menu_edit_1",
                        text: "Edit Menu",
                        iconCss: "fas fa-list",
                        url: 'sf_menu_manager/{{$module_id}}/services_menu',
                        data_target: 'view_modal',
                    },
               
                ];
                var menuOptions = {
                    target: '.services_menubtn',
                    items: context_items,
                    beforeItemRender: contextmenurender,
                
                beforeOpen: function(args){
                    // toggle context items on header
                   
                    if( $(args.event.target).hasClass('services_menubtn')){
                        data_menu_id = $(args.event.target).attr('data-menu-id');
                        data_button_function = $(args.event.target).attr('data-button-function');
                    }else{
                        data_menu_id = $(args.event.target).closest('li').attr('data-menu-id');
                        data_button_function = $(args.event.target).closest('li').attr('data-button-function');
                    }
                    if(data_button_function > ''){
                        services_menu_context.enableItems(['Edit Function'], true);        
                    }else{
                        services_menu_context.enableItems(['Edit Function'], false); 
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
                services_menu_context = new ej.navigations.ContextMenu(menuOptions, '#services_menu_context');
            },
            beforeOpen: function(args){
            
            services_menu_context.refresh();
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
          
            },
       },'#services_menu');
    @endif
    
    function refresh_main_menu_datasource(role_id){
         $.get('main_menu_datasource/{{$module_id}}/'+role_id, function(data) {
            adminLeftMenu.items = data;
            adminLeftMenu.refresh();
        });
    }
    
     
    @if(!empty($top_left_menu) && count($top_left_menu) > 0)   
    var adminMenuItems = @php echo json_encode($top_left_menu); @endphp;
    // top_menu initialization
    var top_left_menu = new ej.navigations.Menu({
        items: adminMenuItems,
        orientation: 'Horizontal',
        cssClass: 'top-menu btn-group',
        template: '#navMenuTemplate',
      
        @if(is_superadmin())
        created: function(args){
            $('body').append('<ul id="top_left_menu_context" class="m-0"></ul>');
            var context_items = [
                {
                    id: "context_top_left_menu_edit",
                    text: "Edit Menu",
                    iconCss: "fas fa-list",
                    url: 'sf_menu_manager/{{$module_id}}/top_left_menu',
                    data_target: 'view_modal',
                },
                @if(is_superadmin())
                /*
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
                */
                @endif
            ];
            var menuOptions = {
                target: '.top_left_menubtn',
                items: context_items,
                beforeItemRender: contextmenurender,
                
                beforeOpen: function(args){
                    // toggle context items on header
                   
                    if( $(args.event.target).hasClass('top_left_menubtn')){
                        data_menu_id = $(args.event.target).attr('data-menu-id');
                        data_button_function = $(args.event.target).attr('data-button-function');
                    }else{
                        data_menu_id = $(args.event.target).closest('li').attr('data-menu-id');
                        data_button_function = $(args.event.target).closest('li').attr('data-button-function');
                    }
                    if(data_button_function > ''){
                        top_left_menu_context.enableItems(['Edit Function'], true);        
                    }else{
                        top_left_menu_context.enableItems(['Edit Function'], false); 
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
            top_left_menu_context = new ej.navigations.ContextMenu(menuOptions, '#top_left_menu_context');
        },
        beforeOpen: function(args){
            //console.log(args);
        top_left_menu_context.refresh();
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
            
            if(args.item.new_tab == 1) {
            var el = args.element;
            $(el).find("a.main_link").attr("target","_blank");
            }
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
            var el = args.element;
            $(el).find("a.main_link").attr("target","_blank");
            }
            //////console.log(args.item);
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
        },      
        },'#top_left_menu');
    @endif
   
    
    @if(session('role_level') && session('role_level')!='Admin')     
    @if(!empty($customer_menu_menu) && count($customer_menu_menu) > 0)   
    var customer_menuItems = @php echo json_encode($customer_menu_menu); @endphp;
    // top_menu initialization
    var customer_menu = new ej.navigations.Menu({
        items: customer_menuItems,
        orientation: 'Horizontal',
        cssClass: 'top-menu btn-group',
        beforeItemRender: function(args){
           
            var el = args.element;   
            $(el).find("a").attr("title",args.item.title);
            if(args.item.border_top){
            
                $(el).addClass("menu_border_top");
            }
            
            $(el).attr("data-menu-id",args.item.menu_id);
            $(el).attr("data-button-function",args.item.button_function);
            
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
            //////console.log(args.item);
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
        },      
        },'#customer_menu');
    @endif
    @endif
    
    @if(session('role_level') && session('role_level')=='Admin')     
    @if(!empty($module_menu_menu) && count($module_menu_menu) > 0)   
    var module_menuItems = @php echo json_encode($module_menu_menu); @endphp;
    // top_menu initialization
    var module_menu = new ej.navigations.Menu({
        items: module_menuItems,
        orientation: 'Horizontal',
        cssClass: 'top-menu btn-group',
        template: '#navMenuTemplate',
         @if(is_superadmin())
         
        created: function(args){
            $('body').append('<ul id="module_menu_context" class="m-0"></ul>');
            var context_items = [
            
                {
                    id: "context_module_menu_edit",
                    text: "Edit Menu",
                    iconCss: "fas fa-list",
                    url: 'sf_menu_manager/{{$module_id}}/module_menu',
                    data_target: 'view_modal',
                },
            ];
            var menuOptions = {
              
                target: '.module_menubtn',
              
                items: context_items,
                beforeItemRender: contextmenurender,
                
                beforeOpen: function(args){
                    // toggle context items on header
                   
                    if( $(args.event.target).hasClass('module_menubtn')){
                        data_menu_id = $(args.event.target).attr('data-menu-id');
                        data_button_function = $(args.event.target).attr('data-button-function');
                    }else{
                        data_menu_id = $(args.event.target).closest('li').attr('data-menu-id');
                        data_button_function = $(args.event.target).closest('li').attr('data-button-function');
                    }
                    if(data_button_function > ''){
                        module_menu_context.enableItems(['Edit Function'], true);        
                    }else{
                        module_menu_context.enableItems(['Edit Function'], false); 
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
            module_menu_context = new ej.navigations.ContextMenu(menuOptions, '#module_menu_context');
        },
        beforeOpen: function(args){
            //console.log(args);
            module_menu_context.refresh();
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
            
            if(args.item.new_tab == 1) {
            var el = args.element;
            $(el).find("a.main_link").attr("target","_blank");
            }
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
            var el = args.element;
            $(el).find("a.main_link").attr("target","_blank");
            }
            //////console.log(args.item);
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
        },      
        },'#module_menu');
    @endif
    @endif
    
  
    
    

    
    window['navbar_header'] = new ej.navigations.Toolbar({
        overflowMode: 'Popup',
        height: '50px',
        items: [
           
            { template:'#toolbar_template_branding', align: 'left'},
          
          
           
       
           
            @if(session('role_level') && session('role_level')!='Admin')
            { template:'#toolbar_template_customer_menu', align: 'left'},
            @endif
            @if(session('role_level') && session('role_level')=='Admin')
            { template:'#toolbar_template_module_menu', align: 'left'},
            @endif
           
            @if(!empty($main_menu) && count($main_menu) > 0) 
            { template:'#toolbar_template_main_menu', align: 'left'},
            @endif
            @if(!empty($services_menu) && count($services_menu) > 0) 
            { template:'#toolbar_template_services_menu', align: 'left'},
            @endif
            @if(!empty($top_left_menu) && count($top_left_menu) > 0) 
            { template:'#toolbar_template_top_left_menu', align: 'left'},
            @endif
        
            
         
            
            
               

            
            { template:'#toolbar_template_right_menu', align: 'right'},
           
            
        ]
    });
    window['navbar_header'].appendTo('#navbar_header');
    
 //console.log('mainmenu',{!!$main_menu !!});
    </script>
    @endpush