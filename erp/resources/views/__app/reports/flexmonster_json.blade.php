@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
    
	
@endif

@section('scripts')
    <script src="{{ '/assets/flexmonster/flexmonster.js' }}" ></script>
 
@endsection

@section('content')
@if($hide_header == 0)    
<div id="dashboard_toolbar" class="grid-toolbar dashboard-toolbar" ></div>
<div id="dashboard_toolbar_title" class="mr-2" >
<h5  class="mt-1 toolbar-title">{{$menu_name}}</h5>
</div>

@endif
    @if(!empty($workspace_menu) && count($workspace_menu) > 0)
    @if($role_id < 10 && $role_id != 9)
    <div id="workspace_toolbar_menu" >
        <div id="workspace_menu_div">
            <ul id="workspace_menu"></ul>
            </div>
    </div>
     <div id="report_refresh_time" style="font-size: 11px;">Refresh Time: {{ date("Y-m-d H:i",strtotime($last_refresh_time)) }} </div>
    @endif
    @endif
    
<div class="card">
	<div class="card-header">
		
        <div class="row chart-title">
			<div class="col-5">
				{{ $instance->name }} - {{ $report_name }} @if(!empty($goal)) <span style="font-size: 12px;">{{ $goal }} </span>  @endif @if(!empty($review_period)) <span style="font-size: 12px;">{{ $review_period }} </span>  @endif
			</div>
           <div class="col-7 text-right p-0">
               	<span style="font-size: 12px;width:100px;display:none;padding:5px;color:#000;font-weight: bold;background-color: #aecca2;" id="savedAlert" class="alert alert-success">Changes saved</span>
             
               <br>
              
               </div>
        </div>
	</div>
	<div class="card-body p-0">
		<div id="pivotContainer" style="min-height:100vh;"></div>
	</div>
</div>
    
@endsection

@push('page-scripts')
    <script type="text/javascript">
    
    
     @if($hide_header == 0) 
    dashboard_toolbar= new ej.navigations.Toolbar({
        items: [
            { template: "#dashboard_toolbar_title" },
            @if(!empty($workspace_menu) && count($workspace_menu) > 0)
            { template: "#workspace_toolbar_menu", align: 'left' },
            @endif
            { template: "#report_refresh_time", align: 'left' },
        ]
    });
	dashboard_toolbar.appendTo('#dashboard_toolbar');
	@endif 
	
	
	 @if(!empty($workspace_menu) && count($workspace_menu) > 0)
        workspace_menu = new ej.navigations.Menu({
            items: {!! json_encode($workspace_menu) !!},
            enableScrolling: true,
            showItemOnClick: true,
            cssClass: 'builder_menu',
            title: 'Workspace',
            created: function(){
             //   alert($(window).width());
              if ($(window).width() < 700){
                  this.hamburgerMode = true;
              } 
              
              if (isMobile()){
                  this.hamburgerMode = true;
              }  
            },
            beforeItemRender: function(args){
                var el = args.element;   
                $(el).find("a").attr("title",args.item.title);
                if(args.item.border_top){
                   $(el).addClass("menu_border_top");
                }
              
                if(args.item.new_tab == 1) {
                   var el = args.element;
                   $(el).find("a").attr("target","_blank");
                }
                if(args.item.data_target == 'javascript') {
                
                    $(el).find("a").attr("data-target",args.item.data_target);
                    $(el).find("a").attr("js-target",args.item.url);
                    $(el).find("a").attr("id",args.item.url);
                    $(el).find("a").attr("href","javascript:void(0)");
                    
                }else if(args.item.data_target) {
                    $(el).find("a").attr("data-target",args.item.data_target);
                }
               if(args.item.data_target) {
                   $(el).find("a").attr("data-target",args.item.data_target);
               }
            if((!args.item.data_target) && args.item.url!='#'  && (args.item.iconCss=='' || args.item.iconCss==null)){
              
                var newtab_el = '<a href="'+$(el).find("a").attr("href")+'" target="_blank"><i class="fas fa-external-link-alt sf-menu-icon"></i></a>';
                $(el).find("a").after(newtab_el);
            }
            },
        }, '#workspace_menu');
    @endif

    
    //https://www.flexmonster.com/question/in-mobile-view-format-options-and-fields-menu-are-not-getting-displayed/
   
    
        grand_totals = {};
        
		initial_config = false;
		report_complete = false;
		@if(!empty(request()->file_type))
		var file_type = '{{request()->file_type}}';
		@endif
		
        var pivot = new Flexmonster({
                container: "#pivotContainer",
                componentFolder: "/assets/flexmonster/",
                toolbar: true, 
                width: "100%",
    			beforetoolbarcreated: customizeToolbar,
    			customizeContextMenu: customizeContextMenu,
			    global: {
			        localization: "locale_en.json"
			    },
				report: {
					dataSource: {
						data: {!! json_encode($rows) !!}
					},
					formats: [ 
					],
					options: {
						viewType: "grid",
						grid: {
							type: "classic",
							title: "{{ $report_name }}",
							showFilter: true,
							showHeaders: true,
							showTotals: "off",
							showGrandTotals: "on",
						},
						datePattern: "yyyy-MM-dd",
						dateTimePattern: "yyyy-MM-dd HH:mm",
					},
				},
				
                reportcomplete: function() {
                    $("#pivotContainer").addClass(('reportcomplete'));
                    pivot.off("reportcomplete");
                    setMouseWheel();
                    /*
                    flexmonster.js:387 Error in predefined filter or members have not been loaded yet: 17
                    cannot draw rows with filter set, requires refresh
                    
                    */
					pivot.updateData();
					pivot.refresh();
					report_complete = true;
			        disable_editing();
                },
                exportcomplete: function() {
                    pivot.off("exportcomplete");
                    pivot.exportcomplete = generatePDF;
                },
        		licenseKey: "{{ $license_key }}",
				customizeCell: function(cell, data) {
				
					if(data.escapedLabel == '1-01-01'){
						cell.text = '0000-00-00';
					}
					if (data.type == "value" && data.measure && data.measure.calculated === true && data.measure.formula.includes('max("today") -')) {
						cell.text = `${formatter(data.value)}`;
					}
				}
        });
       
        function disable_editing(){
            /*
			@if($role_id != 1)
            var options = pivot.getOptions();
            options.configuratorButton  = false;
            pivot.setOptions(options);
            pivot.refresh();
            @endif
            */
        }
        
        
		function pivotExportHandler(params) { 
			pivot.exportTo(params.type, {
				filename: params.filename
			})
		}

        function customizeContextMenu(items, data, viewType){
        
        	var header_field = false;
        	if((viewType == 'pivot' || viewType == 'flat') && data.type == 'header'){
        		if(data.escapedLabel > '' && data.hierarchy && data.hierarchy.uniqueName){
					var header_field = data.hierarchy.uniqueName;
        		}
        		if(data.escapedLabel > '' && data.measure && data.measure.uniqueName){
					var header_field = data.measure.uniqueName;
        		}
        	
        		if(header_field){
					items.push({
						label: "Set Caption",
						handler: function() {
                			sidebarform('report_config_alias', '/report_config_alias?report_connection={{$connection}}&id={{$id}}&alias_field='+header_field, 'Field Caption', '60%');
						}
					});
        		}
			
        	}
        	////console.log(data);
        	////console.log(items);
			items.push({
				label: "Expand Column",
				handler: function() {
				
        			pivot.expandAllData();
				}
			});
			
			items.push({
				label: "Collapse Column",
				handler: function() {
        			pivot.collapseAllData();
				}
			});
			
			items.push({
				label: "Export",
				handler: function() {
					pivot.exportTo('pdf');
				}
			});
			
			return items;
        }
            
		function customizeToolbar(toolbar) { 
			// get all tabs 
		
			var tabs = toolbar.getTabs(); 
			toolbar.getTabs = function () { 
				// delete the first tab 
				delete tabs[0]; 
				delete tabs[1]; 
				delete tabs[2]; 
				return tabs; 
			} 
			
			var tabs = toolbar.getTabs(); 
		
		    toolbar.getTabs = function () { 
		        // add new tab 
		        tabs.unshift({  
		            divider: true, 
		        }); 
		        return tabs; 
		    } 
		    
			var tabs = toolbar.getTabs(); 
		    toolbar.getTabs = function () { 
		    
		        // add new tab 
		        tabs.unshift({  
		            id: "fm-tab-reset", 
		            title: "Reset Config", 
		            handler: resetConfigHandler,  
		            icon: this.icons.connect_olap,
		            rightGroup: true,
		        }); 
		        return tabs; 
		    } 
		    var resetConfigHandler = function() {
		    	/*
		         // add new functionality 
		        var confirm_text = 'Reset report config completely? This cannot be undone.';
				var confirmation = confirm(confirm_text);
				if (confirmation) {
					$.ajax({
						url: '/report_config_reset',
						data: {report_connection: '{{$connection}}',id: '{{ $id }}'},
						type: 'post',
						success: function(data){
				           	location.reload();
			            	
						}
					});
				}
				*/
		    } 
		    
	    	var tabs = toolbar.getTabs(); 
		    toolbar.getTabs = function () { 
		        // add new tab 
		        tabs.unshift({  
		            id: "fm-tab-refresh", 
		            title: "Refresh Data", 
		            handler: refreshHandler,  
		            icon: this.icons.connect_elastic 
		        }); 
		        return tabs; 
		    } 
		    var refreshHandler = function() {
		    	/*
		        $.ajax({
		            url: '/report_server_restart',
		            data: {instance_id: '{{$instance_id}}',id: '{{ $id }}'},
		            beforeSend: function(){
		                showSpinner();  
		            },
		            type: 'post',
		            success: function(data){
		                hideSpinner();  
		                location.reload();
		            
		            }
		        });
		        */
		    } 
		    
			var tabs = toolbar.getTabs(); 
		
		    toolbar.getTabs = function () { 
		        // add new tab 
		        tabs.unshift({  
		            id: "fm-tab-query", 
		            title: "Query", 
		            handler: queryEditorHandler,  
		            icon: this.icons.connect 
		        }); 
		        return tabs; 
		    } 
		    var queryEditorHandler = function() { 
		         // add new functionality 
                sidebarform('querybuilder', '/report_query/{{ $id }}?report_connection={{$connection}}', 'Query Builder', '60%');
		    } 
			
			var tabs = toolbar.getTabs(); 
		
			
			toolbar.getTabs = function () { 
				var f_tab = tabs[14];
				var ff_tab = tabs[12];
				tabs[14] = ff_tab;
				tabs[12] = f_tab;
				
				return tabs; 
			} 
			
		
			var tabs = toolbar.getTabs(); 
			tabs = tabs.filter(function(){return true;});
			toolbar.getTabs = function () {
				tabs[4].menu[1].handler = pivotExportHandler; 
				tabs[4].menu[1].args = { 
					type: 'html',
					filename: '{{ $report_name }}'
				}
				tabs[4].menu[2].handler = pivotExportHandler; 
				tabs[4].menu[2].args = { 
					type: 'csv',
					filename: '{{ $report_name }}'
				}
				tabs[4].menu[3].handler = pivotExportHandler; 
				tabs[4].menu[3].args = { 
					type: 'excel',
					filename: '{{ $report_name }}'
				}
				tabs[4].menu[4].handler = pivotExportHandler; 
				tabs[4].menu[4].args = { 
					type: 'image',
					filename: '{{ $report_name }}'
				}
				tabs[4].menu[5].handler = pivotExportHandler; 
				tabs[4].menu[5].args = { 
					type: 'pdf',
					filename: '{{ $report_name }}'
				}
				return tabs; 
			} 
		
				
			var tabs = toolbar.getTabs(); 
		
			toolbar.getTabs = function () { 
				
				var f_tab = tabs[4];
			
				delete tabs[3]; 
				delete tabs[4];
				tabs[8] = f_tab;
				
				return tabs; 
			} 
			
			var tabs = toolbar.getTabs(); 
			////console.log(tabs);
		
			toolbar.getTabs = function () { 
				
				var f_tab = tabs[2];
			
				delete tabs[2]; 
				tabs[14] = f_tab;
				
				return tabs; 
			} 
			
			
			////console.log(tabs);
			
		} 
		
        function generateExcel() {
            var params = {
              filename : 'export.xlsx', 
              destinationType : 'server',
              url : '/report_export?file_ext=xlsx&report_connection={{ $connection }}',
              requestHeaders: {id: '{{ $id }}'},
            };
            pivot.exportTo('Excel', params);   
            return true;
        }

        function generateHTML() {
            var params = {
              filename : 'export.html', 
              destinationType : 'server',
              url : '/report_export?file_ext=html&report_connection={{ $connection }}',
              requestHeaders: {id: '{{ $id }}'},
            };
            pivot.exportTo('html', params);   
            return true;
        }

        function generatePDF() {
            var params = {
              filename : 'export.pdf', 
              destinationType : 'server',
              url : '/report_export?file_ext=pdf&report_connection={{ $connection }}',
              requestHeaders: {id: '{{ $id }}'},
            };
            pivot.exportTo('PDF', params);   
            return true;
        }
        
        function saveConfig(){
        	/*
        	if(report_complete){
    	
					pivot.save({
				        filename: 'report.json', 
					    destination: 'server', 
					    url: '/report_config_save?report_connection={{$connection}}&id={{$id}}',
		        		callbackHandler: reportSaved 
				    });
        	}
        	*/
        }
        
        function reportSaved(){	
          
			$("#savedAlert").fadeTo(2000, 500).css("display","inline").fadeOut(500, function(){
				$("#savedAlert").hide();
			});
        }
        
        function loadConfig(){
        	@if(!empty($report_config))
        	var json_config = {!! json_decode(json_encode($report_config),true) !!};
        	//console.log(json_config);
        	if(json_config && json_config.report){
        		pivot.load('/report_config_load?use_json=1&report_connection={{$connection}}&id={{$id}}');
        	}else{
	
    		if(json_config){
	    		var formats = false;
				if(json_config.formats == 'undefined' || json_config.formats == null){
					json_config.formats = [ 
						{ 
						name: "", 
						thousandsSeparator: " ", 
						decimalSeparator: ".", 
						decimalPlaces: 2, 
						maxDecimalPlaces: -1, 
						maxSymbols: 20,
						negativeNumberFormat: "-1",
						currencySymbol: "", 
						negativeCurrencyFormat: "-$1", 
						positiveCurrencyFormat: "$1", 
						isPercent: false, 
						nullValue: "", 
						infinityValue: "Infinity", 
						divideByZeroValue: "Infinity", 
						textAlign: "right", 
						beautifyFloatingPoint: true 
						} 
					];
				}else{
					formats = json_config.formats;
					delete json_config.formats;
				}
					
				//	if(typeof json_config.options.grid.title == 'undefined'){
				@if(!empty($target) && !empty($sql_where))
						json_config.options.grid.title = '{{ strtoupper($report_name) }} \n {!! $target !!} \n {!! str_replace("'",'"',$sql_where) !!}';
				@elseif(!empty($target))
						json_config.options.grid.title = '{{ strtoupper($report_name) }} \n {!! $target !!}';
				@elseif(!empty($sql_where))
						json_config.options.grid.title = '{{ strtoupper($report_name) }} \n {!! str_replace("'",'"',$sql_where) !!}';
				@else
					json_config.options.grid.title = '{{ strtoupper($report_name) }}';
				@endif
				//	}
				initial_config = json_config;
				pivot.setReport(json_config);
				
			
			
				if(formats ){
					if(formats){
						$(formats).each(function(j,obj) {
							pivot.setFormat(obj, obj.name);
						});
					}
					
					pivot.refresh();
				}
    		}
        	}
			@endif
       }
        
		pivot.on('ready', ($event) => {
		//	loadConfig(); 
		});
/*
// https://www.flexmonster.com/question/bug-null-report-when-calling-getreport-after-reportchange-event/
// reportchange causes getReport to return null using update event instead
pivot.on('reportchange', ($event) => {
  //console.log("report change >> ", pivot.getReport());
});

pivot.on('reportcomplete', ($event) => {
  //console.log("report complete >> ", pivot.getReport());
});

pivot.on('update', ($event) => {
  //console.log("update >> ", pivot.getReport());
});
*/

		pivot.on('update', ($event) => {
		//	//console.log('reportchange');
			saveConfig();
		
		});
		pivot.on('reportchange', ($event) => {
			
			saveConfig();
		});
		pivot.on('filterclose', ($event) => {
			
			saveConfig();
		});

		
		pivot.on('fieldslistclose', ($event) => {
			
			saveConfig();
		});

function formatter(ms) {
	
if(ms >= '1600000000000'){
	return '';
}
  ms /= 1000;
  ms = Number(ms);
  let d = Math.floor(ms / (24 * 3600));
  let h = Math.floor(ms % (3600 * 24) / 3600);
  let m = Math.floor(ms % 3600 / 60);
  let s = Math.floor(ms % 60);
  //return `${d} days ${h < 10 ? `0${h}` : h} hours ${m < 10 ? `0${m}` : m} min ${s < 10 ? `0${s}` : s} sec`
  
  return `${d} days`
}
	
	
	@if(!empty($invalid_query))
	$(document).ready(function() {
		sidebarform('querybuilder', '/report_query/{{ $id }}?report_connection={{$connection}}', 'Query Builder', '60%');
	});
	@endif
	/* vertical scrolling */
	jQuery.fn.hasScrollBar = function(direction)
	{
		if (direction == 'vertical'){
			return this.get(0).scrollHeight > this.innerHeight();
		}else if (direction == 'horizontal'){
			return this.get(0).scrollWidth > this.innerWidth();
		}
		return false;
	}
	
	function setMouseWheel(){
		$('.fm-scroll-pane').mousewheel( function(e, delta){
			if ($('.fm-scroll-pane').hasScrollBar('horizontal')){
				this.scrollLeft -= (delta * 40);
				e.preventDefault();
			}
		});
	}

    </script>
@endpush

@push('page-styles')
<style>
	#fm-pivot-view #fm-fields-view.fm-pivot-fields {
    width: 80%;
    left:10%;
}
#fm-pivot-view span.fm-ui-label.fm-pivot-title {
    white-space: pre;
    padding: 9px 10px 10px;
}
#fm-tab-refresh{
	width:97px !important;
}
#fm-tab-reset{
	width:87px !important;
}
#customInfo{
	position:absolute;
	left:500px;
}
.fm-pivot-title{
	display: none !important;
}
.card-header{
	height: 65px !important;
}


#pivotContainer{
min-height: calc(100vh - 70px) !important;
height: 100% !important;
}
#savedAlert(position:absolute !important;)
</style>
<style>
#fm-pivot-view #fm-fields-view.fm-pivot-fields {
    width: 80%;
    left:10%;
}

.chart-box {
  background-color: #fafafa;
  position: relative;
}
.sortable-placeholder{
    border: 1px dashed #000;
}
.process-title {
  font-size: 17px;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
  font-weight:500;
}
.chart-title {
  font-size: 15px;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
}
.builder_menu.e-menu-wrapper{
    border: 1px solid #adadad;
    background-color:transparent;
    margin-right:20px;
}
.builder_menu.e-menu-wrapper .e-menu-item .e-menu-text,.builder_menu.e-menu-wrapper .e-menu-item .e-caret{
}

.builder_menu.e-menu-wrapper .e-menu-item.e-focused .e-menu-text,.builder_menu.e-menu-wrapper .e-menu-item.e-focused .e-caret{
    color: rgba(0, 0, 0, 0.87);
}
.builder_menu.e-menu-wrapper .e-menu-item.e-selected .e-menu-text,.builder_menu.e-menu-wrapper .e-menu-item.e-selected .e-caret{
    color: rgba(0, 0, 0, 0.87);
}
.builder_menu.e-menu-wrapper .e-ul{
    background-color:#fff;
}

.builder_menu.e-menu-wrapper .e-ul .e-menu-item .e-menu-text,.builder_menu.e-menu-wrapper .e-ul .e-menu-item .e-caret{
    color: rgba(0, 0, 0, 0.87);
}
.builder_menu .e-menu-text{
    font-size:15px !important;
}

.builder_menu.e-menu-wrapper ul .e-menu-item .e-caret, .builder_menu.e-menu-wrapper ul .e-menu-item {
    height: 28px;
    line-height: 28px;
}
.fm-pivot-title{
	display: none !important;
}
#content .grid-toolbar.dashboard-toolbar{
    background-color: #0b4c80;
}

#content .grid-toolbar.dashboard-toolbar .e-toolbar-items {
    background-color: rgba(0,0,0,0.2);
}
.e-acrdn-content{
    padding: 0px !important;
}
.e-acrdn-header-content{
    width:100%;
    padding-left:40px;
}
.e-toolbar .e-toolbar-items .e-toolbar-item .toolbar-title {
    cursor: text;
    user-select: text;
}
.e-toolbar .e-btn-group.e-outline{
    height:28px;
}
.process-uncompleted{
    background-color: #C04841 !important;
}
.process-completed{
    background-color: #1CB99B !important;
}
.tasks-expanded{
   /* height:600px;*/
}
.tasks-default{
 /*   height:200px;*/
}
#category_btn .e-caret{display:none !important;}
#accordion_process_categories .e-acrdn-header{
    background-color: #e2e2e2;
}
#accordion_process_categories .e-acrdn-header-content{
    width:100%;
    padding-left:10px;
    font-weight:bold;
}
.e-active .e-acrdn-header-content, .e-selected .e-acrdn-header-content{
    color: #000 !important;
}
#dashboard_toolbar .e-menu-wrapper ul .e-menu-item .e-menu-icon  {
   
    line-height: 28px !important;
}
#dashboard_toolbar .e-menu-wrapper ul .e-menu-item .e-menu-icon:before {
   
}

</style>
@endpush