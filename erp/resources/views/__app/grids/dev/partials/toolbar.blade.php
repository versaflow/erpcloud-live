
@section('layouts_toolbar')
@parent
@if(!empty($module_contextbuilder_menu) && count($module_contextbuilder_menu) > 0)
<ul id="contextbuilder{{ $grid_id }}" class="m-0"></ul>
@endif



<div id="gridheadertoolbar{{ $grid_id }}" class="grid-toolbar" @if(str_contains($grid_id,'detail')) style="display:none" @endif></div>





 @if(!empty($moduleright_menu) && count($moduleright_menu) > 0)  
<div id="toolbar_template_moduleright_btns{{ $grid_id }}" class="d-flex align-items-center justify-content-end ">

  
    <ul class="k-widget k-button-group" id="moduleright_menu{{ $grid_id }}"></ul>
  
    
</div>
 @endif

<div id="toolbar_template_max_btn{{ $grid_id }}" class="d-flex align-items-center justify-content-end ">
        <div class="k-button-group searchinputgroup searchgroup{{ $grid_id }}" style="height:26px;">
<input  type="text"  id="searchtext{{ $grid_id }}" class="gridsearch k-widget k-textbox"/>
<button class="k-button" id="search{{ $grid_id }}" style="height:26px" title="Search"><i class="search-icon fas fa-search" ></i></button>
@if($master_detail && str_contains($grid_id,'detail'))
<button class="k-button" id="searchdetail{{ $grid_id }}" style="height:26px" title="Search Detail" onclick="search_detail()"><i class="fas fa-search-plus"></i></button>
@endif
</div>
     <button id="showrightsidebar{{$grid_id}}" class="k-button d-none mr-1">Maximize</button>
</div>
@if($show_policies)
<div id="toolbar_template_policies{{ $grid_id }}" class="">
    <div class="col p-0 flex-row flex-nowrap d-flex align-items-center">
        <ul id="policiesdropdown{{ $grid_id }}"> </ul>
    </div>
</div>

@endif


<div id="toolbar_template_layouts{{ $grid_id }}" class="@if(session('role_level') != 'Admin') d-none @endif">
<div class="layoutitem{{$master_grid_id}}"></div>
<div class="reportitem{{$master_grid_id}}"></div>
    <div class="col p-0 flex-row flex-nowrap d-flex align-items-center" >
      
        <ul class="k-widget k-button-group" id="gridlayouts_{{ $grid_id }}"></ul>
        
    </div>
</div>

@if(($layout_filter_user || $layout_filter_date))
<div id="toolbar_template_filters{{$grid_id}}">
    @if($layout_filter_user)
    <input type="text" id="layout_filter_user{{ $grid_id }}" class="mr-1"/>
    @endif
     @if($layout_filter_date)
    <input type="text" id="layout_filter_date{{ $grid_id }}" class="mr-1"/>
    @endif
</div>
@endif




<div id="toolbar_template_grid_btns{{ $grid_id }}" >
    <div class="toolbar_grid_buttons align-items-center d-flex" id="gridactions{{ $grid_id }}">  


       
      <div class="k-widget k-button-group">
      @if($communications_panel && !str_contains($grid_id,'detail'))

  
 
        @endif
       <button title="Refresh Data" id="{{ $grid_id }}Refresh" class="k-button {{ $grid_id }}Refresh"><span  class="e-btn-icon fa fa-sync-alt"></span></button> 

       
      
       
        @if($access['is_add'])
            <button title="Create Record" id="{{ $grid_id }}Add" class="k-button" ><span  class="e-btn-icon fa fa-plus"></span></button>
        @endif
        
        @if($access['is_view'] && (in_array($db_table,['crm_documents','crm_supplier_documents','crm_supplier_import_documents'])))
            <button title="View Record" id="{{ $grid_id }}View" class="k-button" ><span  class="e-btn-icon far fa-eye"></span></button>
        @endif
        
    
        @if($access['is_edit'])
            <button title="Edit Record" id="{{ $grid_id }}Edit" class="k-button" ><span  class="e-btn-icon fas fa-pen"></span></button>
        @endif
          
        
        @if($access['is_add'] && !in_array($db_table,['call_records_inbound','call_records_outbound','crm_documents','crm_supplier_documents']))
            <button title="Duplicate Record" id="{{ $grid_id }}Duplicate" class="k-button" ><span  class="e-btn-icon fa fa-copy"></span></button>
        @endif
        
        @if($access['is_delete'])
             @if(($db_table == 'crm_accounts' || $db_table == 'sub_services'))
            <button title="Cancel" id="{{ $grid_id }}Delete" class="k-button" ><span  class="e-btn-icon fa fa-times"></span></button>
            @else
            <button title="Delete Record" id="{{ $grid_id }}Delete" class="k-button" ><span  class="e-btn-icon fa fa-trash"></span></button>
            @endif
        @endif
       
        @if($db_table == 'crm_documents' || $db_table == 'crm_supplier_documents')
             <button title="Approve" id="{{ $grid_id }}Approve" class="k-button" ><span  class="e-btn-icon fa fa-check"></span></button>
        @endif
            
        
        <div class="dropdown">
        <button class="k-button dropdown-toggle" type="button" id="linkedrecords{{ $grid_id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
        <span class="e-btn-icon fa fa-caret-down"></span>
        </button>   
        <ul class="dropdown-menu" aria-labelledby="linkedrecords{{ $grid_id }}" id="linkedrecordsdropdown{{ $grid_id }}">
      
      
     
        @if($access['is_view'])
            <li><button  title="Print"  onclick="onBtnPrint()"  class="k-button" ><span  class="e-btn-icon fas fa-print"></span> Print</button></li>
             <li><button title="Export Data" id="{{ $grid_id }}Export" class="k-button" ><span  class="e-btn-icon fas fa-file-import"></span> Export</button></li>
        @endif
        @if($access['is_export'])
            <!--<button title="Export Data" id="{{ $grid_id }}Export" class="k-button" ><span  class="e-btn-icon fas fa-file-import"></span></button>-->
        @endif
        @if($access['is_import'])
             <li><button title="Import Data" id="{{ $grid_id }}Import" class="k-button" ><span  class="e-btn-icon fas fa-file-export"></span> Import</button></li>
        @endif
          
       
  
        
        @if(is_superadmin() && ($db_table == 'crm_accounts' || $db_table == 'sub_services'))    
         <li><button title="Manager Delete" id="{{ $grid_id }}ManagerDelete" class="k-button" ><span  class="e-btn-icon fa fa-trash"></span> Manager Delete</button></li>
        @endif
 
      
        
        @if(session('role_level')=='Admin')
         <li><button title="Clear Filters" id="filterclear{{ $grid_id }}" class="k-button"><span  class="e-btn-icon e-icons e-filter-clear"></span>Clear Filters</button></li>
       
         <li><button title="Show Deleted" id="showdeleted{{ $grid_id }}" class="k-button"><span  class="e-btn-icon fas fa-archive "></span>Show Deleted</button></li>
      
        @endif
          <li><button title="Copy Row" id="copyrow{{ $grid_id }}" class="k-button"><span  class="e-btn-icon fas fa-copy "></span>Copy Row</button></li>
        
        
        </ul> 
        </div>
        
        <!--<div class="dropdown">
        <button class="k-button dropdown-toggle" type="button" id="linkedrecords{{ $grid_id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
        <span class="e-btn-icon fa fa-caret-down"></span>
        </button>   
        <ul class="dropdown-menu" aria-labelledby="linkedrecords{{ $grid_id }}" id="linkedrecordsdropdown{{ $grid_id }}">
        
        </ul> </div>-->
      
    </div>
    @if(!empty($moduleleft_menu) && count($moduleleft_menu) > 0)  
    <ul class="k-button k-widget k-button-group" id="moduleleft_menu{{ $grid_id }}"></ul>
    @endif
  
    @if($master_detail && !empty($detail_grid['moduleleft_menu']) && count($detail_grid['moduleleft_menu']) > 0)  
    <ul class="k-button k-widget k-button-group d-none" id="moduleleft_menudetail{{ $grid_id }}"></ul>
    @endif
    
 
 
    </div>
</div>


<!--/ Cards with badge -->

@endsection

@section('page-scripts')

<script>
/** SYNCFUSION COMPONENTS **/

searchtext{{ $grid_id }} = new ej.inputs.TextBox({
	showClearButton: true,
	width:180,
	change: function(e){
	  
        var search_val = searchtext{{ $grid_id }}.value;
        if(search_val > ''){
            search_val = search_val.trim();
        }
        if(window['grid_{{ $grid_id }}'].gridOptions && window['grid_{{ $grid_id }}'].gridOptions.api){
            var grid_api = window['grid_{{ $grid_id }}'].gridOptions.api;    
        }else if(window['grid_{{ $grid_id }}'].api){
            var grid_api = window['grid_{{ $grid_id }}'].api;   
        }
       
   
        if(search_val == 'Yes'){
            search_val = '1';    
        }
        if(search_val == 'No'){
            search_val = '0';    
        }
        if(search_val == '' || search_val == null){
            searchtext{{ $grid_id }}.value = ' ';
            grid_api.setQuickFilter(' ');
            @if($serverside_model)
            if(window['grid_{{ $grid_id }}'].gridOptions){
            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            }
            @endif
        }else{
            
            grid_api.setQuickFilter(search_val);
            @if($serverside_model)
            
            if(window['grid_{{ $grid_id }}'].gridOptions){
            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            }
            @endif
        }
	},
},'#searchtext{{ $grid_id }}');


$(document).off('click', '.searchgroup{{ $grid_id }} .e-clear-icon').on('click', '.searchgroup{{ $grid_id }} .e-clear-icon', function(e) {
   
   e.preventDefault();
   searchtext{{ $grid_id }}.value = '';
   searchtext{{ $grid_id }}.dataBind();
});

$(document).off('click', '.search{{ $grid_id }} .e-clear-icon').on('click', '.search{{ $grid_id }} .e-clear-icon', function(e) {
    
    if(window['grid_{{ $grid_id }}'].gridOptions && window['grid_{{ $grid_id }}'].gridOptions.api){
        var grid_api = window['grid_{{ $grid_id }}'].gridOptions.api;    
    }else if(window['grid_{{ $grid_id }}'].api){
        var grid_api = window['grid_{{ $grid_id }}'].api;   
    }
	 
    
    searching_detail = false;
    if(searchtext{{ $grid_id }}.value == '' || searchtext{{ $grid_id }}.value == null){
        grid_api.setQuickFilter(null);
        @if($serverside_model)
            if(window['grid_{{ $grid_id }}'].gridOptions){
            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            }
        @endif
    }else{
        
        var search_val = searchtext{{ $grid_id }}.value;
        if(search_val == 'Yes'){
            search_val = '1';    
        }
        if(search_val == 'No'){
            search_val = '0';    
        }
        grid_api.setQuickFilter(search_val);
        @if($serverside_model)  
            if(window['grid_{{ $grid_id }}'].gridOptions){
            window['grid_{{ $grid_id }}'].gridOptions.refresh();
            }
        @endif
    }
});



// grid contextmenu
@if(!empty($module_contextbuilder_menu) && count($module_contextbuilder_menu) > 0)
var menuOptions = {
    target: '#gridactions{{ $grid_id }}',
    items: {!! json_encode($module_contextbuilder_menu) !!},
    beforeItemRender: dropdowntargetrender
};

// Initialize ContextMenu control.
new ej.navigations.ContextMenu(menuOptions, '#contextbuilder{{ $grid_id }}');
@endif

    
    @if($show_policies)
    
    //Initialize action items.
   
    var items = [ 
         {
        id: "policyheader",
        text: "Guides @if(!empty($policies)) ({{count($policies) }}) @else {{ '(0)' }} @endif",
        url: '#',
        view_id: 'header',
        items: [
            @if(!empty($policies))
            @foreach($policies as $policy)
            {
                    id: "policy{{$policy->id}}",
                    cssClass: "policyitem{{$grid_id}}",
                    text: "{{$policy->name}}",
                    url: '/kb_content/{{$policy->id}}',
                    data_target: 'view_modal',
                    view_id: '{{$policy->id}}',
            },
            @endforeach
            @else
            {
                    id: "policyplaceholder",
                    cssClass: "policyplaceholder",
                    text: "",
                    url: '#',
                    data_target: '',
                    view_id: '',
            },
            @endif
        ]
        },
    ];
    
    function refresh_poliicy_items{{$module_id}}(){
      
        
        $.ajax({
            url:'policies_datasource/{{$module_id}}/{{$grid_id}}',
            type: 'get', 
            dataType: 'json',
            success:function(data){
               
                var items = data.dropdown;
                
                if(items){
                   policiesdropdown{{ $grid_id }}.items = items;
                   policiesdropdown{{ $grid_id }}.dataBind();
                   
                }
                var contextitems = data.context;
              
                if(contextitems){
                   policies_context{{$grid_id}}.items = contextitems;
                   policies_context{{$grid_id}}.refresh();
                   
                }
            }
        });
    }
    
    // initialize DropDownButton control
    policiesdropdown{{ $grid_id }} = new ej.navigations.Menu({
        orientation: 'Horizontal',
        items: items,
        disabled: true,
        cssClass: 'top_menu k-widget k-button-group',
          @if(is_superadmin())
            created: function(args){
                $('body').append('<ul id="policies_context{{ $grid_id }}" class="m-0"></ul>');
                var context_items = [
                    {
                        id: "policies_context_list{{$grid_id}}",
                        text: "List",
                        iconCss: "fas fa-list",
                        url: '{{get_menu_url_from_module_id(1875)}}',
                        data_target: 'view_modal',
                    },
                    {
                        id: "policies_context_list{{$grid_id}}",
                        text: "Add",
                        iconCss: "fas fa-plus",
                        url: '{{get_menu_url_from_module_id(1875)}}/edit?module_id={{$module_id}}',
                        data_target: 'sidebarform',
                    },
                ];
                var menuOptions = {
                    target: '#policiesdropdown{{ $grid_id }}',
                    items: context_items,
                    beforeItemRender: contextmenurender
                };
                
                // Initialize ContextMenu control
                policies_context{{$grid_id}} = new ej.navigations.ContextMenu(menuOptions, '#policies_context{{ $grid_id }}');
            },
            @endif
           
        beforeItemRender: function (args){
            var el = args.element;  
          
            if(args.item.text) {
                $(el).find("a").attr("title",args.item.text);
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
            }else if(args.item.data_target == 'transaction' || args.item.data_target == 'transaction_modal') {
                $(el).find("a").attr("data-target",args.item.data_target);
                $(el).find("a").attr("href","javascript:void(0)");
                $(el).find("a").attr("modal_url",args.item.url);
            }else if(args.item.data_target) {
                $(el).find("a").attr("data-target",args.item.data_target);
            }
            if(args.item.view_id){
                $(el).attr("data-view-id",args.item.view_id);    
            }
           
            $(el).addClass('k-button');
            
        },
    });
    
    // Render initialized DropDownButton.
    policiesdropdown{{ $grid_id }}.appendTo('#policiesdropdown{{ $grid_id }}');
    @endif
    
    

var contextviewid{{$grid_id}} = false;
var contextviewtype{{$grid_id}} = 'default';
function create_layouts_context{{$grid_id}}(){
 $('body').append('<ul id="contextlayouts{{ $grid_id }}" class="m-0"></ul>');
        var layout_items = [
            @if($module_id!=526)
            {
                id: "layoutsbtn_manage{{ $grid_id }}",
                text: "Sort",
                iconCss: "fas fa-list",
            },
            @endif
            {
                id: "layoutsbtn_create{{ $grid_id }}",
                text: "Save as new",
                iconCss: "fa fa-plus",
            },
            {
                id: "layoutsbtn_save{{ $grid_id }}",
                text: "Save current",
                iconCss: "fa fa-save",
            },
            {
                id: "layoutsbtn_edit{{ $grid_id }}",
                text: "Edit",
                iconCss: "fas fa-pen",
            },
            {
                id: "layoutsbtn_duplicate{{ $grid_id }}",
                text: "Copy",
                iconCss: "fa fa-copy",
            },
            {
                id: "layoutsbtn_delete{{ $grid_id }}",
                text: "Delete",
                iconCss: "fa fa-trash",
            },
            {
                id: "layoutsbtn_globaldefault{{ $grid_id }}",
                text: "Set as default",
                iconCss: "fa fa-star",
            },
            {
                id: "layoutsbtn_email{{ $grid_id }}",
                text: "Email",
                iconCss: "fa fa-file",
            },
            {
                id: "layout_tracking_enable{{ $grid_id }}",
                text: "Enable Layout Tracking",
                iconCss: "fas fa-toggle-on",
            },
            {
                id: "layout_tracking_disable{{ $grid_id }}",
                text: "Disable Layout Tracking",
                iconCss: "fas fa-toggle-off",
            },
        ];
        
        var menuOptions = {
            target: '.layoutitem{{$master_grid_id}}',
            items: layout_items,
            beforeItemRender: contextmenurender,
            beforeOpen: function(args){
                
                // toggle context items on header
                if($(args.event.target).hasClass('is_card') == 1){
                    contextviewtype{{$grid_id}} = 'card';
                   
                }else{
                    contextviewtype{{$grid_id}} = 'default';
                }
                if($(args.event.target).hasClass('layout-header') == 1){
                    console.log(11);
                    contextviewid{{$grid_id}} = false;
                    contextlayouts{{ $grid_id }}.enableItems(['Sort','Save as new','Save current'], true);
                    contextlayouts{{ $grid_id }}.enableItems(['Delete','Copy','Edit','Set as default','Email','Enable Layout Tracking','Disable Layout Tracking'], false);
                }else{
                    var item_list = ['Sort','Save as new','Save current','Delete','Copy','Edit','Set as default','Email'];
                    
                    contextlayouts{{ $grid_id }}.enableItems(item_list, true);
                 
                    console.log(14);
                    console.log($(args.event.target).attr('data-track_layout'));
                    if($(args.event.target).attr('data-track_layout') == "1"){
                      
                    console.log(12);
                        contextlayouts{{ $grid_id }}.enableItems(['Disable Layout Tracking'], true);
                        contextlayouts{{ $grid_id }}.enableItems(['Enable Layout Tracking'], false);
                    }else{
                       
                    console.log(13);
                        contextlayouts{{ $grid_id }}.enableItems(['Disable Layout Tracking'], false);
                        contextlayouts{{ $grid_id }}.enableItems(['Enable Layout Tracking'], true);
                    }
                    
                    contextlayouts{{ $grid_id }}.enableItems(item_list, true); 
                    
                    contextviewid{{$grid_id}} = $(args.event.target).attr('data-view-id'); 
                }
              
                
                
                
            },
            select: function(args){
                if(args.item.text === 'Set as default') {
                  
               
                    gridAjax('layout_set_default/'+contextviewid{{$grid_id}});
                }
               
            }
        };
      
        // Initialize ContextMenu control.
        contextlayouts{{ $grid_id }} = new ej.navigations.ContextMenu(menuOptions, '#contextlayouts{{ $grid_id }}');  
         
}
function create_reports_context{{$grid_id}}(){
 $('body').append('<ul id="contextreports{{ $grid_id }}" class="m-0"></ul>');
        var layout_items = [
          
            @if($module_id!=526)
            {
                id: "layoutsbtn_manage{{ $grid_id }}",
                text: "Sort",
                iconCss: "fas fa-list",
            },
            @endif
            {
                id: "layoutsbtn_create_report{{ $grid_id }}",
                text: "Save as new",
                iconCss: "fa fa-plus",
            },
            {
                id: "layoutsbtn_save{{ $grid_id }}",
                text: "Save current",
                iconCss: "fa fa-save",
            },
            {
                id: "layoutsbtn_edit{{ $grid_id }}",
                text: "Edit",
                iconCss: "fas fa-pen",
            },
            {
                id: "layoutsbtn_duplicate{{ $grid_id }}",
                text: "Copy",
                iconCss: "fa fa-copy",
            },
            {
                id: "layoutsbtn_delete{{ $grid_id }}",
                text: "Delete",
                iconCss: "fa fa-trash",
            },
        ];
        
        var menuOptions = {
            target: '.reportitem{{$master_grid_id}}',
            items: layout_items,
            beforeItemRender: contextmenurender,
            beforeOpen: function(args){
                
                // toggle context items on header
                if($(args.event.target).hasClass('layout-header') == 1){
                    contextviewid{{$grid_id}} = false;
                    contextreports{{ $grid_id }}.enableItems(['Sort','Save as new'], true);
                    contextreports{{ $grid_id }}.enableItems(['Delete','Copy','Edit'], false);
                }else{
                    var item_list = ['Sort','Save as new','Save current','Delete','Copy','Edit'];
                    
                    contextreports{{ $grid_id }}.enableItems(item_list, true);
                    contextviewid{{$grid_id}} = $(args.event.target).attr('data-view-id'); 
                }
              
            }
        };
        
        // Initialize ContextMenu control.
        contextreports{{ $grid_id }} = new ej.navigations.ContextMenu(menuOptions, '#contextreports{{ $grid_id }}');    
}
function refresh_layout_context_menus{{$grid_id}}(){
    @if(is_superadmin())
        contextlayouts{{ $grid_id }}.refresh();
        contextreports{{ $grid_id }}.refresh();
        
    @endif
}

        
window['gridlayouts_{{ $grid_id }}'] = new ej.navigations.Menu({
    items: {!! json_encode($sidebar_layouts) !!},
 
    enableScrolling: true,
    showItemOnClick: true,
    orientation: 'Horizontal',
    cssClass: 'top-menu k-widget k-button-group',
    
    @if(is_superadmin())
    created: function(){
       create_layouts_context{{$grid_id}}();
       create_reports_context{{$grid_id}}();

    },
    beforeOpen: function(args){
        refresh_layout_context_menus{{$grid_id}}();
    },
    @endif  
    beforeItemRender: function(args){
  
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
    
    $(el).attr("data-is-group",args.item.is_group);   
    if(args.item.view_id){
        $(el).attr("data-view-id",args.item.view_id);    
    }
    if (args.item.hasOwnProperty('track_layout')) {
        $(el).attr("data-track_layout",args.item.track_layout);    
    }
    
    if(args.item.data_target == 'javascript') {
    
    $(el).find("a").attr("data-target",args.item.data_target);
    $(el).find("a").attr("js-target",args.item.url);
    $(el).find("a").attr("id",args.item.url);
    $(el).find("a").attr("href","javascript:void(0)");
    
    }else if(args.item.data_target) {
    
    $(el).find("a").attr("data-target",args.item.data_target);
    }
    
    
    },
}, '#gridlayouts_{{ $grid_id }}');

    // layouts toolbar
    window['headertoolbar{{ $grid_id }}'] = new ej.navigations.Toolbar({
        items: [
           
         
          
            { template: "#toolbar_template_layouts{{ $grid_id }}", align: 'left' },
            
            { template: "#toolbar_template_grid_btns{{ $grid_id }}", align: 'left' },
           
           
           
          
            
          
            @if(($layout_filter_user || $layout_filter_date))
            { template: "#toolbar_template_filters{{ $grid_id }}", align: 'right' },
            @endif
            
            @if($show_policies)
            { template: "#toolbar_template_policies{{ $grid_id }}", align: 'right' },
            @endif
            @if(!empty($moduleright_menu) && count($moduleright_menu) > 0)  
            { template: "#toolbar_template_moduleright_btns{{ $grid_id }}", align: 'right' },
            @endif
            
            { template: "#toolbar_template_max_btn{{ $grid_id }}", align: 'right' },
            
        ]
    });
    window['headertoolbar{{ $grid_id }}'].appendTo('#gridheadertoolbar{{ $grid_id }}');
</script>

<script>

// moduleright_menu
 @if(!empty($moduleright_menu) && count($moduleright_menu) > 0)   
    var modulerightMenuItems = @php echo json_encode($moduleright_menu); @endphp;
    // top_menu initialization
    var moduleright{{ $grid_id }} = new ej.navigations.Menu({
        items: modulerightMenuItems,
        orientation: 'Horizontal',
        cssClass: 'top-menu k-widget k-button-group',
        created: function(args){
            
      
            @if(is_superadmin())
            
            $('body').append('<ul id="moduleright_context{{$grid_id}}" class="m-0"></ul>');
            var context_items = [
                {
                    id: "context_gridtab_edit",
                    text: "Edit Menu",
                    iconCss: "fas fa-list",
                    url: 'sf_menu_manager/{{$module_id}}/moduleright',
                    data_target: 'view_modal',
                },
            ];
            var menuOptions = {
                target: '#moduleright_menu{{ $grid_id }}',
                items: context_items,
                beforeItemRender: dropdowntargetrender
            };
            
            // Initialize ContextMenu control
            new ej.navigations.ContextMenu(menuOptions, '#moduleright_context{{$grid_id}}');
            
            @endif
    
        },
        beforeOpen: function(args){
          
            var popup_items = [];
            $(args.items).each(function(i, el){
                popup_items.push(el.text);
            });
        
            var selected = window['selectedrow_{{ $grid_id }}'];
          
            {!! button_menu_selected($module_id, 'moduleright', $grid_id, 'selected', true) !!}
        },
        beforeItemRender: function(args){
            var el = args.element;   
            $(el).find("a").attr("title",args.item.title);
            if(args.item.border_top){
              
               $(el).addClass("menu_border_top");
            }
            
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
    },'#moduleright_menu{{ $grid_id }}');
    @endif
</script>
<script>
    
    /** layout filters **/
    @if($layout_filter_user)
   
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
    @if($layout_filter_date)
   
    window['layout_filter_date_{{ $grid_id }}'] = new ej.dropdowns.DropDownList({
    	dataSource: {!! json_encode($layout_filter_date_datasource) !!},
        placeholder: 'Filter {{$layout_filter_date_field}}',
        popupWidth: 'auto',
        //Set true to show header title
        select: function(args){
          
            var filterModel = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterModel();
          
            
            // Get a reference to the filter instance
            var filterInstance = window['grid_{{ $grid_id }}'].gridOptions.api.getFilterInstance('{{$layout_filter_date_field}}'); 
           
            // Set the filter model
            filterInstance.setModel({
                dateFrom: null,
                dateTo: null,
                filterType: 'date',
                type: args.itemData.value,
            });
            
            // Tell grid to run filter operation again
            window['grid_{{ $grid_id }}'].gridOptions.api.onFilterChanged();
            
        }
    }, '#layout_filter_date{{ $grid_id }}');
  
 
    @endif
    
    /** LAYOUT EVENTS **/    
	$(document).off('click', '#layoutsbtn_manage{{ $grid_id }}').on('click', '#layoutsbtn_manage{{ $grid_id }}', function() {
	    viewDialog('gridv{{ $grid_id }}','/{{$layouts_url}}?module_id={{ $module_id }}','Layouts Sort','90%','90%','coreDialog');
	});
	
	$(document).off('click', '#layoutsbtn_create{{ $grid_id }}').on('click', '#layoutsbtn_create{{ $grid_id }}', function() {
	   layout_save{{ $master_grid_id }}(true,contextviewtype{{$grid_id}});
	});
	$(document).off('click', '#layoutsbtn_create_report{{ $grid_id }}').on('click', '#layoutsbtn_create_report{{ $grid_id }}', function() {
	   layout_save{{ $master_grid_id }}(true, 'report');
	});
	$(document).off('click', '#layoutsbtn_create_card{{ $grid_id }}').on('click', '#layoutsbtn_create_card{{ $grid_id }}', function() {
	   layout_save{{ $master_grid_id }}(true, 'card');
	});
	
	$(document).off('click', '#layoutsbtn_save{{ $grid_id }}').on('click', '#layoutsbtn_save{{ $grid_id }}', function(e) {
	    layout_save{{ $master_grid_id }}();
	});
	
	$(document).off('click', '#layoutsbtn_email{{ $grid_id }}').on('click', '#layoutsbtn_email{{ $grid_id }}', function() {
		if(contextviewid{{$grid_id}}){
	    	gridAjax('/layout_email/'+contextviewid{{$grid_id}});
		}	
	});
	
	@if($layout_access['is_add'])
	$(document).off('click', '#layoutsbtn_duplicate{{ $grid_id }}').on('click', '#layoutsbtn_duplicate{{ $grid_id }}', function() {
		if(contextviewid{{$grid_id}}){
	    	gridAjaxConfirm('/{{ $layouts_url }}/duplicate', 'Duplicate layout?', {"id" : contextviewid{{$grid_id}}}, 'post');
		}	
	});
	@endif
	
	$(document).off('click', '#layoutsbtn_delete{{ $grid_id }}').on('click', '#layoutsbtn_delete{{ $grid_id }}', function() {
        var confirm_text = "Delete layout?"
        var confirmation = confirm(confirm_text);
        if (confirmation) {
	        layout_delete(contextviewid{{$grid_id}});
        }
	});

	$(document).off('click', '#layoutsbtn_showall{{ $grid_id }}').on('click', '#layoutsbtn_showall{{ $grid_id }}', function() {
	    gridview_show_all();
	});
	
	$(document).off('click', '[id^="layoutsbtnload{{ $grid_id }}_"]').on('click', '[id^="layoutsbtnload{{ $grid_id }}_"]', function() {
	  
	    var layout_id = $(this).attr('id').replace("layoutsbtnload{{ $grid_id }}_", "");
	    layout_load{{$grid_id}}(layout_id);
	});
	
	$(document).off('click', '#layoutsbtn_edit{{ $grid_id }}').on('click', '#layoutsbtn_edit{{ $grid_id }}', function() {
	    sidebarform('gridcv{{ $grid_id }}','/{{$layouts_url}}/edit/'+contextviewid{{$grid_id}},'Edit Grid View','','90%');
	});
	
	@if(is_superadmin() && !str_contains($db_table,'crm_task'))
        $(document).off('click','#layout_tracking_disable{{ $grid_id }}').on('click','#layout_tracking_disable{{ $grid_id }}', function(){
            if(contextviewid{{$grid_id}}){
                var layout_id =contextviewid{{$grid_id}};
             
                var url = '/layout_tracking_disable/'+layout_id;
                var confirmation = confirm('Disable layout tracking?');
                if (confirmation) {
                    $.ajax({
                        url: url,
                        type: 'get',
                        success: function(data) {
                           
                            get_sidebar_data{{$module_id}}();
                            toastNotify(data.message, data.status);
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            toastNotify('An error occured', 'error');
                            
                        },
                    });
                }
            }else{
                toastNotify('context id not set', 'error');
            }
        });
    @endif 
    
    @if(is_superadmin() && !str_contains($db_table,'crm_task'))
        $(document).off('click','#layout_tracking_enable{{ $grid_id }}').on('click','#layout_tracking_enable{{ $grid_id }}', function(){
            if(contextviewid{{$grid_id}}){
            var layout_id =contextviewid{{$grid_id}};
         
            var url = '/layout_tracking_enable/'+layout_id;
            var confirmation = confirm('Enable layout tracking?');
            if (confirmation) {
                $.ajax({
                    url: url,
                    type: 'get',
                    success: function(data) {
                        
                        get_sidebar_data{{$module_id}}();
                      
                        toastNotify(data.message, data.status);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        toastNotify('An error occured', 'error');
                        
                    },
                });
            }
            
            }else{
                toastNotify('context id not set', 'error');
            }
        });
    @endif 
    

    
    
    
</script>

@parent
@endsection


@section('page-styles')
@parent
<style>
#toolbar_template_filters{{ $grid_id }} .e-input-group {
    height: auto !important;
}
#layout_filter_user{{ $grid_id }}{
height:26px;
width:100px;
}
#layout_filter_date{{ $grid_id }}{
height:26px;
width:100px;
}
#toolbar_template_filters{{ $grid_id }} .e-input-group{
width:100px;
}
#gridheadertoolbar{{ $grid_id }} .e-input-group{height: 26px;}


#gridheadertoolbar{{ $grid_id }} .e-input-group.e-ddl{
    border-radius: 4px;
}
#linkedrecordsdropdown{{ $grid_id }}{
margin-top: 0 !important;
padding: 0px !important;
}
#linkedrecordsdropdown{{ $grid_id }} button{
width:100%;   
justify-content: left;
border-radius:0px !important;
}
#linkedrecordsdropdown{{ $grid_id }} a{
width:100%;   
justify-content: left;
border-radius:0px !important;
}
#linkedrecordsdropdown{{ $grid_id }} button span{
margin-right: 5px;
}
#linkedrecordsdropdown{{ $grid_id }} a span{
margin-right: 5px;
}
#linkedrecords{{ $grid_id }} {
    border-top-left-radius: 0px !important;
    border-bottom-left-radius: 0px !important;
    border-top-right-radius: 4px !important;
    border-bottom-right-radius: 4px !important;
    height: 26px !important;
    border-left: 0 !important;
}

.e-menu-wrapper.k-button-group .e-menu-item.k-button.policyplaceholder {
    height: 0px !important;
    line-height: 0px;
    min-height: 0px !important;
}
#gridheadertoolbar{{ $grid_id }} .e-toolbar-left  .e-toolbar-item{
   padding-left: 12px !important;
}
#gridheadertoolbar{{ $grid_id }} .e-toolbar-right  .e-toolbar-item{
   padding-left: 0px !important;
}
#gridheadertoolbar{{ $grid_id }} .e-toolbar-right  .e-toolbar-item .e-menu-wrapper {
   padding-right: 0px !important;
}
</style>

@endsection