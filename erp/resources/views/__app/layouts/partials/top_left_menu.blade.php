@php
if($module_id == 1944){
    $new_tab = true;
}else{
    $new_tab = false;
}
@endphp



<div class="collapse navbar-collapse  w-auto h-auto" id="sidenav-collapse-main">

<ul class="navbar-nav">
    
    
    @if(session('role_level') == 'Admin' && !empty($company_logins) && count($company_logins) > 0)
       <!-- <li class="nav-item" id="company_logins_dropdown_container">
      
        <a data-bs-toggle="collapse" href="#company_logins_dropdown" class="nav-link fw-bold border" aria-controls="company_logins_dropdown" role="button" aria-expanded="false">
       
        <div class="shadow border-radius-md bg-white text-center d-flex align-items-center justify-content-center ">
        
        </div>
        <span class="nav-link-text ms-1">{{ session('instance')->name }}</span>
        </a>
      
        <div class="collapse " id="company_logins_dropdown">
        <ul class="nav ms-4 ps-3">
            @foreach($company_logins as $submenu)
            <li class="nav-item">
          
            <a class="nav-link " href="{{$submenu['url']}}"  target="_blank">
         
            <span class="sidenav-mini-icon"> {{$submenu['menu_name'][0]}} </span>
            <span class="sidenav-normal"> {{$submenu['menu_name']}} <b class="caret"></b></span>
            </a>
            </li>
            @endforeach
        </ul>
        </div>
        </li>-->
    @endif
  
    
    @if(!empty($customer_menu_menu) && count($customer_menu_menu) > 0)
    <!--<li class="nav-item mt-3 text-center-collapsed">
          <h6 class="ps-0  ms-0 text-uppercase text-xs font-weight-bolder opacity-6">My Account</h6>
    </li>-->
    @foreach($customer_menu_menu as $menu)
     
        <li class="nav-item top-level menu_context" data-menu-id="{{$menu->menu_id}}" data-button-function="{{$menu->button_function}}" data-menu-location="customer_menu">
        @if(isset($menu->items) && count($menu->items) > 0)    
        <a data-bs-toggle="collapse" href="#submenu{{$menu->menu_id}}" class="nav-link" aria-controls="submenu{{$menu->menu_id}}" role="button" aria-expanded="false">
        @else
        <a data-target="{{$menu->data_target ?? ''}}" href="{{$menu->url ?? ''}}" class="nav-link " aria-controls="submenu{{$menu->menu_id}}" role="button" aria-expanded="false" @if($new_tab || $menu->new_tab) target="_blank" @endif>
        @endif
        <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center d-flex align-items-center justify-content-center  me-2">
        <i class="{{$menu->iconCss ?? 'fas fa-list' }} text-lg text-dark opacity-10" aria-hidden="true"></i>
        </div>
        <span class="nav-link-text ms-1">{{$menu->text}}</span>
        </a>
        @if(isset($menu->items) && count($menu->items) > 0)
        <div class="collapse " id="submenu{{$menu->menu_id}}">
        <ul class="nav ms-4 ps-3">
        @each('__app.layouts.partials.main_menu_recursive', $menu->items, 'submenu')
        </ul>
        </div>
        @endif
        </li>
    @endforeach
    @endif
    
    @if(!empty($main_menu_menu) && count($main_menu_menu) > 0)
    <!--<li class="nav-item mt-3 text-center-collapsed">
          <h6 class="ps-0  ms-0 text-uppercase text-xs font-weight-bolder opacity-6">Admin</h6>
    </li>-->
    @foreach($main_menu_menu as $menu)
     
        <li class="nav-item top-level menu_context" data-menu-id="{{$menu->menu_id}}" data-button-function="{{$menu->button_function}}" data-menu-location="main_menu">
        @if(isset($menu->items) && count($menu->items) > 0)    
        <a data-bs-toggle="collapse" href="#submenu{{$menu->menu_id}}" class="nav-link" aria-controls="submenu{{$menu->menu_id}}" role="button" aria-expanded="false">
        @else
        <a data-target="{{$menu->data_target}}" href="{{$menu->url}}" class="nav-link " aria-controls="submenu{{$menu->menu_id}}" role="button" aria-expanded="false" @if($new_tab || $menu->new_tab) target="_blank" @endif>
        @endif
        <!--<div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center d-flex align-items-center justify-content-center  me-2">
        <i class="{{$menu->iconCss ?? 'fas fa-list' }} text-lg text-dark opacity-10" aria-hidden="true"></i>
        </div>-->
        <span class="nav-link-text ms-1">{{$menu->text}}</span>
        </a>
        @if(isset($menu->items) && count($menu->items) > 0)
        <div class="collapse " id="submenu{{$menu->menu_id}}">
        <ul class="nav ms-4 ps-3">
            @each('__app.layouts.partials.main_menu_recursive', $menu->items, 'submenu')
        </ul>
        </div>
        @endif
        </li>
    @endforeach
    @endif
    @if(session('role_level')!='Admin')
    @if(!empty($top_left_menu) && count($top_left_menu) > 0)
    @if(session('role_level') == 'Admin')
    <li class="nav-item mt-3 text-center-collapsed">
          <h6 class="ps-0  ms-0 text-uppercase text-xs font-weight-bolder opacity-6">Services</h6>
    </li>
    @endif
   
  
    @foreach($top_left_menu as $menu)
     
        <li class="nav-item top-level menu_context" data-menu-id="{{$menu->menu_id}}" data-button-function="{{$menu->button_function}}" data-menu-location="top_left_menu">
        @if(isset($menu->items) && count($menu->items) > 0)    
        <a data-bs-toggle="collapse" href="#submenu{{$menu->menu_id}}" class="nav-link" aria-controls="submenu{{$menu->menu_id}}" role="button" aria-expanded="false">
        @else
        <a data-target="{{$menu->data_target}}" href="{{$menu->url}}" class="nav-link " aria-controls="submenu{{$menu->menu_id}}" role="button" aria-expanded="false" @if($new_tab || $menu->new_tab) target="_blank" @endif>
        @endif
        <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center d-flex align-items-center justify-content-center  me-2">
        <i class="{{$menu->iconCss ?? 'fas fa-list' }} text-lg text-dark opacity-10" aria-hidden="true"></i>
        </div>
        <span class="nav-link-text ms-1">{{$menu->text}}</span>
        </a>
        @if(isset($menu->items) && count($menu->items) > 0)
        <div class="collapse " id="submenu{{$menu->menu_id}}">
        <ul class="nav ms-4 ps-3">
            @each('__app.layouts.partials.main_menu_recursive', $menu->items, 'submenu')
        </ul>
        </div>
        @endif
        </li>
    @endforeach
    @endif
    @endif
  
</ul>
</div>

@push('page-scripts')
<script>
      @if(is_superadmin())
     
            $('body').append('<ul id="main_menu_context" class="m-0"></ul>');
            var context_items = [
                {
                    id: "context_newtab",
                    text: "Open In New Tab",
                    iconCss: "fas fa-list",
                },
                {
                    id: "context_menu_edit_1",
                    text: "Edit Main Menu",
                    iconCss: "fas fa-list",
                    url: 'sf_menu_manager/{{$module_id}}/main_menu',
                    data_target: 'view_modal',
                },
                
                {
                    id: "context_menu_edit_2",
                    text: "Edit Top Left Menu",
                    iconCss: "fas fa-list",
                    url: 'sf_menu_manager/{{$module_id}}/top_left_menu',
                    data_target: 'view_modal',
                },
                
                {
                    id: "context_menu_edit_2",
                    text: "Edit Services Menu",
                    iconCss: "fas fa-list",
                    url: 'sf_menu_manager/{{$module_id}}/services_menu',
                    data_target: 'view_modal',
                },
                /*
                
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
                target: '.menu_context',
                items: context_items,
                beforeItemRender: contextmenurender,
                
                beforeOpen: function(args){
                    // toggle context items on header
                   
                    if( $(args.event.target).hasClass('main_menubtn')){
                        data_menu_id = $(args.event.target).attr('data-menu-id');
                       // data_button_function = $(args.event.target).attr('data-button-function');
                        data_menu_href = $(args.event.target).find('a').attr('href');
                    }else{
                        data_menu_id = $(args.event.target).closest('li').attr('data-menu-id');
                        //data_button_function = $(args.event.target).closest('li').attr('data-button-function');
                        data_menu_href = $(args.event.target).closest('li').find('a').attr('href');
                    }
                    /*
                    if(data_button_function > ''){
                        main_menu_context.enableItems(['Edit Function'], true);        
                    }else{
                        main_menu_context.enableItems(['Edit Function'], false); 
                    }
                    */
                },
                select: function(args){
                    if(args.item.id === 'context_newtab') {
                        window.open(data_menu_href,'_blank');
                    }
                    if(args.item.id === 'edit_menu_btn') {
                        sidebarform('editmenubtn','{{$menu_manager_url}}/edit/'+data_menu_id);
                    }
                    if(args.item.id === 'edit_menu_btn_function') {
                        sidebarform('editfunctionbtn','/code_edit/'+data_button_function);
                    }
                }
            };
            
            // Initialize ContextMenu control
            main_menu_context =new ej.navigations.ContextMenu(menuOptions, '#main_menu_context');
        
        @endif
</script>
@endpush