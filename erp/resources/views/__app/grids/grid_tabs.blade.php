@extends(( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' )

@if(!request()->ajax())
	
@endif


@section('content')


<div id="tabs_outer_container" class="m-0 p-0" height="100%">
    <div id="tabs_container" class="m-0 p-0"></div>
  
</div>

<div class="pinnedtab"></div>
@if(is_dev())
<!--<ul id="contexttabs" class="m-0"></ul>-->
@endif

@endsection
@push('page-scripts')

<script>


    @if(is_superadmin())
        function create_pinned_context(){
            $('body').append('<ul id="pinned_context" class="m-0"></ul>');
            var items = [
                {
                    id: "agc_refresh_single",
                    text: "Refresh",
                    iconCss: "fas fa-sync",
                },
              
                {
                    id: "agc_list",
                    text: "List",
                    iconCss: "fas fa-list",
                },
               
            ];
            context_agc_id = false;
            
            var menuOptions = {
                target: '.pinnedtab',
                items: items,
                beforeItemRender: contextmenurender,
                beforeOpen: function(args){
                    
                    // toggle context items on header
                   
               
                       
                     
                },
                select: function(args){
                  console.log(args);
                  console.log(args.item.id);
                    if(args.item.id === 'agc_refresh_single') {
                        refreshCurrentTab();
                    }
                   
                    if(args.item.id === 'agc_list') {
                       window.open('{{url($pinnned_tabs_url)}}','_blank');
                    }
                   
                }
            };
            
            // Initialize ContextMenu control.
            window['pinned_context'] = new ej.navigations.ContextMenu(menuOptions, '#pinned_context');  
            console.log('pinned_context');
            console.log(pinned_context);
        }
 
       create_pinned_context();
    
    @endif
    
    tab_refresh_ref = false;
    window['currently_loading_tabs'] = [];
    initialized = false;
    ej.base.enableRipple(true);
    last_active_tab = null;
    current_tab_index = null;
    tab_to_select = null;
    selecting_from_remove = null;
    appTabs = new ej.navigations.Tab({
        overflowMode: 'Popup',
        heightAdjustMode: 'Fill',
        showCloseButton: false,
        reorderActiveTab: false,
        animation: {previous:{effect:'FadeIn', duration:100},next:{effect:'FadeIn', duration:100}},
        selected: function(args){
            if (args.isSwiped) {
            args.cancel = true;
            }else{
            $(this.items).each(function(i, el){
                if(i != args.selectedIndex){
                    ////////////console.log(el);
                    ////////////console.log('add d-none');
                    $(el.content).addClass('d-none');
                }else{
                    ////////////console.log(el);
                    ////////////console.log('remove d-none');
                    $(el.content).removeClass('d-none');
                }
            })
            try{
           
       
            if(!initialized){
                
          
                 loadTabGrid(appTabs.items[args.selectedIndex], 1);
                 
            }else{
                
          
                 loadTabGrid(appTabs.items[args.selectedIndex]);
                 
            }
            
            document.title = appTabs.items[args.selectedIndex].header.title;
           
            }catch(e){}
            
           
            current_tab_index = args.selectedIndex;
            last_active_tab = args.previousIndex;
            last_active_tab_item = args.previousItem;
            tab_to_select = null;
            selecting_from_remove = null;
            
                  
            }
           
        @if(is_superadmin())
        window['pinned_context'].refresh();
        @endif
    
       
        },
        selecting: function(args){
            
            if (args.isSwiped) {
            args.cancel = true;
            }else{
       ////////////console.log('selecting');
                ////////////console.log(args);
            if(args.event == undefined && selecting_from_remove!=null){
                //////////////console.log('selecting');
                //////////////console.log(args);
                //////////////console.log(appTabs);
                //////////////console.log(last_active_tab);
                
                selecting_from_remove = null;
                args.cancel = true;
                appTabs.select(last_active_tab);
            }
            
            selecting_from_remove = null;
            }
        },
        removing: function(args){
         ////////console.log('removing tabs');
         ////////console.log(args);
         ////////console.log(appTabs);
            selecting_from_remove = true;
        },
        adding: function(args){
         
            selecting_from_remove = null;
        },
        removed: function(args){
               
            @if(session('role_level') == 'Admin')
            saveTabState();
            @endif
        },
        created: function(args){
         
            
            @if(session('role_level') == 'Admin')
            loadTabState();
            @endif
             @if(is_superadmin())
            window['pinned_context'].refresh();
            @endif
        },
    });
    
    //Render initialized Tab component
    appTabs.appendTo('#tabs_container');
    
    function refreshCurrentTab(){
       console.log('refreshCurrentTab');
        if($('.gridtabid:visible:first').length > 0 ){
        tab_refresh_ref  = "#"+$('.gridtabid:visible:first').attr('id');
        }
         
        loadTabGrid(appTabs.items[appTabs.selectedItem],0,1);
    }
    
    
    function loadTabGrid(tab_item, first_load = 0, refresh=0){
            console.log('loadTabGrid');
        
        
    ////////console.log('loadTabGrid');
     ////////console.log(tab_item);
        var grid_url = tab_item.url;
        var grid_div_id = tab_item.content;
        
        if(window['currently_loading_tabs'].includes(grid_url)){
            console.log('ret');
            return false;    
        }
        // modules
        
            //////console.log('loadTabGrid');
            //////console.log(grid_url);
            //////console.log(first_load);
        if(tab_item && tab_item.header && tab_item.header.text && tab_item.header.text > ''){
            document.title = tab_item.header.title;
        }
        
           
        if($(grid_div_id).html().trim() == '' || refresh==1){
           
            $.ajax({
                url: grid_url,
                crossDomain:true,
                crossOrigin:true,
                beforeSend: function(request) {
                    window['currently_loading_tabs'].push(grid_url);
                    if(refresh)
                    showSpinnerTab();
                    request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
                },
                success: function(popup_content) {
                    console.log('tab loaded');
                    console.log(grid_url);
                    
        
                    var index = window['currently_loading_tabs'].indexOf(grid_url);
                    if (index !== -1) {
                        window['currently_loading_tabs'].splice(index, 1);
                    }
                    //////////////console.log('loaded');////////////console.log(popup_content);
                    //hideSpinnerTab();
                    
                      
                    if(refresh)
                    hideSpinnerTab();
                    if (popup_content.status) {
                        
                        if (popup_content.status == 'reload') {
                            window.open(popup_content.message, "_self");
                        }else{
                            toastNotify(popup_content.message, popup_content.status);
                        }
                    }else {
                       
                        
                        $(grid_div_id).html(popup_content);
                        
                        // rename tab to layout name
                        if($(grid_div_id).find('.layout_name').length > 0){
                        
                            $.each(appTabs.items, function(i,el){
                                if(tab_item.id == el.id){
                                    var layout_name = $(grid_div_id).find('.layout_name')[0].textContent;
                                    
                                appTabs.items[i].header.text = layout_name;
                                }
                           
                            });
                        }
                        ////////console.log(appTabs);
                        //appTabs.dataBind();
                        appTabs.refresh();
                    }
                    @if(is_superadmin())
                    window['pinned_context'].refresh();
                    @endif
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    var index = window['currently_loading_tabs'].indexOf(grid_url);
                    if (index !== -1) {
                        window['currently_loading_tabs'].splice(index, 1);
                    }
                    
                    
                    console.log('tab load error');
                    console.log(index);
                    console.log(window['currently_loading_tabs']);
                    console.log(grid_url);
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
                    console.log(errorThrown);
                    //hideSpinnerTab();
                    toastNotify('Error loading tab', 'error');
                }
            });
            
            
     
        }
     
            @if(session('role_level') == 'Admin')
     
            if(!first_load){
                
     
            saveTabState();
            }
            @endif
    }
    
    @if(session('role_level') == 'Admin')
    function saveTabState(){
   
        var active_tabs = [];
        var edit_active = false;
        $.each(appTabs.items, function(i,el){
            if(el.url.includes('edit')){
                edit_active = true;
            }else{
                active_tabs.push({url: el.url,text: el.header.text});   
            }
       
        });
        
            
        if(!edit_active){  
            var post_data = {active_tabs: active_tabs, selected_item: appTabs.selectedItem};
            ////////console.log(post_data);
            $.ajax({
                url: 'save_tab_states',
                crossDomain:true,
                crossOrigin:true,
                data: post_data,
                type: 'post',
                beforeSend: function(request) {
                
                },
                success: function(data){
                     ////////console.log(data);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                }
            });
        }
        
    }
    
   function loadTabState(){
       return false;
        var tab_state = {!! json_encode($tab_state) !!}
     
        ////////console.log(tab_state);
        var current_tabs = appTabs.items;
        let active_urls = current_tabs.map(item => item.url);
        
        var iterate = $.each(tab_state.active_tabs, function(i, el) {
            
            if(!active_urls.includes(el.url)){
                if(tab_state.active_tabs[tab_state.selected_item] && tab_state.active_tabs[tab_state.selected_item].url == el.url){
                    addGridTab(el.text, el.url, el, tab_state.selected_item); 
                }else{
                    addGridTab(el.text, el.url, el);    
                }
            }
        }); 
    
        appTabs.select(tab_state.selected_item);
        initialized = true;
         
    }
    @endif
  
    function addGridTab(title, url, options = false, select_index = false){
        if(!title){
            title = 'New Tab';    
        }
        ////console.log('addGridTab');
        ////console.log(title);
        ////console.log(url);
        ////console.log(options);
        ////console.log(select_index);
        var existing_tab_index = false;
        // select existing tab
           
        var path = url.replace("https://{{session('instance')->domain_name}}/","");
        var path = path.replace("http://{{session('instance')->domain_name}}/","");
        if(path.includes('?')){
            path = path.split('?')[0];
        }
         
        ////console.log(path);
        $(appTabs.items).each(function(i,obj){
         
            var contains = obj.url.includes(path);
           
            
            if(contains){
                existing_tab_index = parseInt(i);  
                appTabs.items[i].url = url;
            }
        });
         
        ////console.log(existing_tab_index);
         
        if(existing_tab_index){
            //////console.log(1111);
             loadTabGrid(appTabs.items[existing_tab_index],0,1);
             appTabs.select(parseInt(existing_tab_index));
        }else{
            // or open new tab
            var uniqid = "id" + Math.random().toString(16).slice(2)
            var num_tabs = appTabs.items.length;
            var div_id = '#gridtab'+uniqid;
            $("#tabs_outer_container").append('<div id="gridtab'+uniqid+'" class="gridtabid"></div>');
            if(!url.includes('tab_load=1')){
            if(url.indexOf('?') !== -1) {
                url = url+'&tab_load=1';
            }else{
                url = url+'?tab_load=1';
            }
            }
            ////////console.log(options);
            var cssClass = 'pinnedtab';    
            if(options.cssClass){
                var cssClass = options.cssClass;  
            }
            var tabicon = false;
            if(options.tabicon){
                var tabicon = options.tabicon;
            }
            
            if(tabicon){
                cssClass += ' tabtext-bold';
                var items = [
                    {
                        header: {text:title ,iconCss:'',title: title},
                        cssClass: cssClass,
                        content: div_id,
                        url: url,
                    }
                ];
                
            }else{
                var items = [
                    {
                        header: {text: title, title: title},
                        cssClass: cssClass,
                        content: div_id,
                        url: url,
                    }
                ];
            }
            ////console.log('select tab');
           ////console.log(appTabs.items);
           ////console.log(items);
           ////console.log(num_tabs);
           ////console.log(select_index);
         
            
            appTabs.addTab(items,num_tabs);
            
            appTabs.refresh();
            if(select_index !== false){
                  //  //////////console.log('add select 2');
                appTabs.select(parseInt(select_index));
            }else{
                appTabs.select(num_tabs);    
            }
        }
        @if(is_superadmin())
        window['pinned_context'].refresh();
        @endif
    }
    
    function removeGridTab(div_id){
        //////////console.log('removeGridTab');
        //////////console.log(div_id);
        appTabs.refresh();
        var tabitem_id = false;
        $.each(appTabs.items, function(i, el) {
         
            if(el.content == "#"+div_id){
               
                tabitem_id = el.id;
            }
        });
        
        ////////console.log(tabitem_id);
    
        if(tabitem_id){
            var tab_index = appTabs.getItemIndex(tabitem_id);
          
            if(current_tab_index){
                var indexToRemove = parseInt(tab_index);
             
                var result = appTabs.removeTab(indexToRemove); 
            }
        }
       
    }
    
    
    // layouts contextmenu
    function contexttabsrender(args){
        
        var el = args.element;   
        $(el).find("a").attr("title",args.item.text);
        if(args.item.border_top){
        $(el).addClass("menu_border_top");
        }
        
        if(args.item.new_tab == 1) {
        var el = args.element;
        $(el).find("a").attr("target","_blank");
        }
        
        
        if(args.item.cssClass > '') {
        var el = args.element;
        $(el).addClass(args.item.cssClass);
        }
        
        
        if(args.item.data_target == 'javascript') {
        
        $(el).find("a").attr("data-target",args.item.data_target);
        $(el).find("a").attr("js-target",args.item.url);
        $(el).find("a").attr("id",args.item.url);
        $(el).find("a").attr("href","javascript:void(0)");
        
        }else if(args.item.data_target) {
        
        $(el).find("a").attr("data-target",args.item.data_target);
        }
    }
    
    function showSpinnerTab(){
        if(tab_refresh_ref){
            try {
               showSpinner(tab_refresh_ref);
            }
            catch (e) {}
        }else{
             try {
               
                $("#tabs_container .e-content").busyLoad("show", {
                    animation: "slide"
                });
            }
            catch (e) {}    
        }
    }
    
    function hideSpinnerTab(){
        if(tab_refresh_ref){
            try {
               hideSpinner(tab_refresh_ref);
            }
            catch (e) {}
        }else{
            try {
                $("#tabs_container .e-content").busyLoad("hide", {
                    animation: "slide"
                });
            }
            catch (e) {}
        }
    }
    $(document).ready(function(){
        @foreach($pinnned_tabs as $pinnned_tab)
        addGridTab("{{$pinnned_tab->title}}","{{$pinnned_tab->url}}",false,0);
        @endforeach
    });
    
</script>
@endpush
@push('page-styles')

<style>
#tabs_outer_container {
height: calc(100vh - 100px) !important;
}
#tabs_container .e-tab-header {
    background-color: #ebebeb;
}

#tabs_container .e-tab-header .e-toolbar-item.e-active .e-text-wrap::before {
    border-bottom: none !important;
}

#tabs_container .e-tab-wrap{
    border: none !important;
}

#tabs_container .default-tab .e-text-wrap .e-close-icon{
    display: none;
}
#tabs_container .e-tab-header .e-toolbar-item.e-active.e-ileft .e-tab-icon{
margin-top: 6px;    
}

#tabs_container .e-tab-header .e-toolbar-item.e-active {
    background-color: #e6f5ff;
}
#tabs_container .e-tab-header .e-toolbar-item.e-active .e-text-wrap::before {
    border: 1px solid #000000;
    display:none;
}


/* To disable indicator animation */

#tabs_container .e-tab-header:not(.e-vertical) .e-indicator,  .e-tab .e-tab-header.e-vertical .e-indicator {
    transition: none;
}

#tabs_container .tabs_container_tab{
display: flex;
flex-direction: column;
height: 100%;
}

#tabs_container .e-content{
display: flex;
flex-direction: column;
height: calc(100% - 21px) !important;
}
.e-tab .e-tab-header .e-toolbar-item.pinnedtab .e-tab-text{
    color: #4e4e4e;
   
}

.e-toolbar-item.pinnedtab{border: none !important;}
</style>
@endpush