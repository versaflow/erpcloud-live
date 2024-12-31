@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
@endif

@section('content')
@if(is_dev() || is_superadmin())
<ul id="formfields_context_ul{{$module_id}}" class="m-0"></ul>
<ul id="formtabs_context_ul{{$module_id}}" class="m-0"></ul>
@endif
@php $form_url = get_menu_url_from_table('erp_module_fields'); @endphp
@if(request()->ajax() && check_access('1,31'))
<button class="e-lib e-btn e-control e-outline e-small formconfigbtn" onclick="viewDialog('form{{$module_id}}','{{$form_url}}?module_id={{$module_id}}','Form Config - {{$title}}')"><span class="fas fa-list"></span> Fields </button>
@endif
@if($edit_type == 'view' && $allow_edit && $db_key == 'id')
<!--<div class="text-right">
	<a id="form_edit_btn" href="{{ url('/'.$menu_route.'/edit/'.$form_record_id) }}" class="e-btn mt-1 mr-2" data-target="form_edit_modal"> Edit</a>
</div>-->
@endif

<form id="{{ $form_name }}FormAjax" action="/{{ $menu_route }}/save" class="py-4 px-2 e-control form_type_{{$edit_type}}" autocomplete="autocomplete_off" >
<input type="hidden" name="syncfusion_form" value="1"/>
@if(isset(request()->insert_at_id))
<input type="hidden" name="insert_at_id" value="{{request()->insert_at_id}}"/>
@endif
@if(isset(request()->insert_at_db_field))
<input type="hidden" name="insert_at_db_field" value="{{request()->insert_at_db_field}}"/>
@endif
@if($module_id == 749 && isset(request()->layout_ids))
<input type="hidden" name="layout_id" value="{{request()->layout_ids}}"/>
@endif
<div id="{{ $form_name }}FormErrors"></div>
@if(!request()->ajax())
<div class="form-wrapper container">
@endif
@if(!empty(session('webform_module_id')) && !empty($webform_title))
@if($module_id == 390)
<div  class="p-2"><h2> Debit order mandate </h2></div>
@else
<div  class="p-2"><h2> {{ $webform_title }} </h2></div>
@endif
@endif
@if(!empty(session('webform_module_id')) && !empty($webform_text))
<div class="mt-0 py-4 px-2 @if($module_id == 390) d-none @endif" style="font-size:14px" id="webform_text">
{!! nl2br($webform_text) !!}
</div>
@endif
{!! $form_html !!}

@if(!request()->ajax())
	<div class="mt-4" style="background: none;border: none;">
		<div class="content-wrapper" >
		<div class="form-group" style="float: right;">
			@if($edit_type != 'view')
			<button type="submit" id="submitbtn" style="float:right" class="btn btn-default dialogSubmitBtn mb-0">Submit</button>
			@endif
			@if(empty(session('webform_module_id')))
			<a id="backbtn" style="float:right" href="{{ url()->previous() }}">Back</a>
			@endif
		</div>
		</div>
	</div>
@endif
</form>
@endsection

@push('page-scripts')

<script type="text/javascript">
$("#sidebarformtitle").text('{{$module_name}}');

@if(request()->ajax())
$("#{{ $form_name }}FormAjax").closest('.e-dialog').find('.e-dlg-header').find('.title').text('{{ $form_title }}');
@endif
	{!! $form_script !!}
	@if(!request()->ajax())
	$(document).ready(function() {
		var button = new ej.buttons.Button();
		button.appendTo('#backbtn');
		@if($edit_type != 'view')
		var button = new ej.buttons.Button({ cssClass: `e-success`});
		button.appendTo('#submitbtn');
		@endif
		
	});
	@endif
$(document).ready(function() {
$("form :input:not(.e-ddl-hidden):visible:enabled:first").focus();
});

function setTinyMce(){
setCkEditor();
/*
	$("textarea.tinymce").each(function(i){
		
		var textarea = $(this).val();
		tinymce.init({
			selector: "#"+$(this).attr('id'),  // change this value according to your HTML
			theme: 'silver',
			plugins: 'fontselect code paste print autoresize preview searchreplace autolink link media image template codesample hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern help',
			toolbar1: 'code | formatselect | bold italic underline forecolor backcolor | link image | alignleft aligncenter alignright alignjustify  | numlist bullist outdent indent  | removeformat | print',
			contextmenu: "",
			image_advtab: true,
			menubar: "",
			valid_children : '+body[style]',
			height : "400",
			relative_urls : false,
			visual : false,
			remove_script_host : false,
			convert_urls : true,
			force_br_newlines : false,
			force_p_newlines : false,
			entity_encoding : "raw",
			forced_root_block : "",
			convert_newlines_to_brs : false,
            remove_linebreaks : true,   
			cleanup : true,
			verify_html : true,
			content_style: "p {margin: 0}",
    		//paste_as_text: true,
    		// image options
    		block_unsupported_drop: false,
            images_upload_url: 'tinymce_images',
			automatic_uploads: false,
			file_picker_types: 'image',
			setup: function (ed) {
			ed.on('init', function(args) {
			});
			},
		});
	});
	*/
}
var editors = {};
function setCkEditor() {
    $("textarea.ckeditor").each(function(i) {
        var textareaId = $(this).attr('id');
        ClassicEditor
            .create(document.querySelector("#" + textareaId), {   
            	updateSourceElementOnDestroy: true,
				htmlSupport: {
				allow: [
				{
				name: /.*/,
				attributes: true,
				classes: true,
				styles: true
				}
				]
				}
            })
            .then(editor => {
editors[textareaId] = editor;
                //console.log(editor);
            })
            .catch(error => {
                console.error(error);
            });
            
            /* new ej.richtexteditor.RichTextEditor({},"#" + textareaId); */
 
    });
}

function setTinyMceTabs(e){
	setCkEditorTabs(e);
/*
	if(e == undefined){
		var div_id = parseInt(formtabObj.selectedID) ;
	}else{
		var div_id = parseInt(e.selectedIndex) ;
	}

	var tab_id = "#e-content";
	$(".e-content textarea.tinymce").each(function(i){
		var textarea = $(this).val();
		tinymce.init({
			selector: "#"+$(this).attr('id'),  // change this value according to your HTML
			theme: 'silver',
			plugins: 'code paste print autoresize preview searchreplace autolink link media image template codesample hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern help',
			toolbar1: 'code | formatselect | bold italic underline forecolor backcolor | link image | alignleft aligncenter alignright alignjustify  | numlist bullist outdent indent  | removeformat | print',
			contextmenu: "",
			image_advtab: true,
			menubar: "",
			valid_children : '+body[style]',
			height : "400",
			visual : false,
			relative_urls : false,
			remove_script_host : false,
			convert_urls : true,
			force_br_newlines : false,
			force_p_newlines : false,
			entity_encoding : "raw",
			forced_root_block : "",
			convert_newlines_to_brs : false,
            remove_linebreaks : true,  
			cleanup : true,
			verify_html : true,
			content_style: "p {margin: 0}",
    		//paste_as_text: true,
    		// image options
    		block_unsupported_drop: false,
            images_upload_url: 'tinymce_images',
			automatic_uploads: false,
			file_picker_types: 'image',
			setup: function (ed) {
			ed.on('init', function(args) {
			});
			},
		});
	});
*/
}

function setCkEditorTabs(e){
	
	if(e == undefined){
		var div_id = parseInt(formtabObj.selectedID) ;
	}else{
		var div_id = parseInt(e.selectedIndex) ;
	}

	var tab_id = "#e-content";

    $(".e-content textarea.ckeditor").each(function(i) {
        var textareaId = $(this).attr('id');
        ClassicEditor
            .create(document.querySelector("#" + textareaId), {
            	
				htmlSupport: {
				allow: [
				{
				name: /.*/,
				attributes: true,
				classes: true,
				styles: true
				}
				]
				}
            })
            .then(editor => {
editors[textareaId] = editor;
                //console.log(editor);
            })
            .catch(error => {
                console.error(error);
            });
    });
}

@if($module_id == 556)
$(document).off('click', '.insert-blade').on('click', '.insert-blade', function(e) {
	var blade_var = '';
	if($(this).attr('id') == 'blade-company'){
		insertBladeVariable("@{{$company}}")
	}
	if($(this).attr('id') == 'blade-contact'){
		insertBladeVariable("@{{$contact}}")
	}
	if($(this).attr('id') == 'blade-balance'){
		insertBladeVariable("@{{$customer->balance}}")
	}
});
	
function insertBladeVariable(blade_var){
	////console.log(blade_var);
	tinymce.activeEditor.execCommand('mceInsertContent', false, blade_var);
}
@endif



$('#{{ $form_name }}FormAjax').on('submit', function(e) {
			////console.log('#{{ $form_name }}FormAjax submit');
	 if (!$(document.activeElement).hasClass('dialogSubmitBtn')){
			////console.log('#{{ $form_name }}FormAjax dialogSubmitBtn');
	
		
	 	
		return false;
	 }
  
	e.preventDefault();
	/*
	try{
		$("textarea.tinymce").each(function(i){
			field_id = $(this).attr('id');
			if(field_id){
				var content = tinyMCE.get(field_id).getContent();
				
				$('#'+field_id).val(content);
			}
		});
	}catch(e){
		
	}
	*/


	try{
		$("textarea.ckeditor").each(function(i){
			var field_id = $(this).attr('id');
	            console.log(editors);
	        if (field_id && editors[field_id]) {
	            console.log('saving' +field_id);
	             editors[field_id].updateSourceElement();
	        }
		});
	}catch(e){
    console.error(e);
	}



	try{
		$(".datefield").each(function(i, el){
			if($(this).val() == '0000-00-00'){
				$(this).val('');
				
			}
			if($(this).val() == '0000-00-00 00:00'){
				$(this).val('');
			}
		});
	
	}catch(e){
	
	}
	
	/// save grid config
	try{
		if ({{ $form_name }}FormValidator.validate()) {
		//	////console.log('validated');
			formSubmit('{{ $form_name }}FormAjax');
		}else{
			toastNotify('Please fill all required fields', 'warning');
		}
	}catch(e){
		////console.log(e);
		formSubmit('{{ $form_name }}FormAjax');
	}
});
$(document).ready(function() {
	
	$('input').blur(function() {
		$('.dialogSubmitBtn').each(function(e) {
			$(this).prop('disabled', false);
		});
	});

});

function setIconPicker(field_id,value){
	
	
	window['icon'+field_id] = $('#'+field_id).fontIconPicker();
	$.ajax( {
	url: 'https://gist.githubusercontent.com/swashata/c0db916b33700c91ab75f59d4aeba7d3/raw/366789b2d001a99f5f41f1ceab980d991de059c3/fontawesome-icons-with-categories.json',
	type: 'GET',
	dataType: 'json'
	} )
	.done( function( response ) {
	////console.log( response );
	window['icon'+field_id].setIcons( response );

	if(value > ''){
		window['icon'+field_id].setIcon(value);
	}
	} );
	
}

</script>
<script>
@if(is_dev() || is_superadmin())
/* FIELDS CONTEXT MENU */

function create_form_context{{$module_id}}(){
   
    field_labels{{$module_id}} = {!! json_encode($field_labels) !!};

    if(typeof formfields_context{{$module_id}} !== 'undefined'){
        formfields_context{{$module_id}}.destroy();
    }
    
    var context_items = [
        {id: "field_name", text: "",cssClass: "font-weight-bold"},
        {id: "sort_form", text: "List"},
        {id: "add_field", text: "Add"},
        {id: "edit_field", text: "Edit"},
        {id: "duplicate_field", text: "Duplicate"},
        {id: "delete_field", text: "Delete"},
        {id: "set_visible", text: "Visible", items:[
            {id: "set_visible_none", text: "None"},
            {id: "set_visible_both", text: "Add and Edit"},   
            {id: "set_visible_add", text: "Add"},   
            {id: "set_visible_edit", text: "Edit"}
        ]},
        {id: "set_tab", text: "Tabs", items:[
        	@foreach($context_tabs as $i => $context_tab)
            {id: "set_tab_option_{{$i}}", text: "{{$context_tab}}"},
            @endforeach
        ]},
        @if($module_id == 385)
        {id: "edit_code", text: "Edit Code"},
        @endif
    ];
    var menuOptions = {
        target: '.form-group',
        items: context_items,
        beforeItemRender:function(args){
            
            var el = args.element; 
                    if(args.item.cssClass > '') {
            var el = args.element;
            $(el).addClass(args.item.cssClass);
            }
        },
        beforeOpen: function(args,e){
            ////console.log('beforeOpen',args,e);
            try{
                if(args.parentItem === null){
                form_field_id{{$module_id}} = false;
                form_field_name{{$module_id}} = false;
                form_field_tab{{$module_id}} = false;
                if($(args.event.target).hasClass('form-group') == 1){
                    var el = $(args.event.target);
                }else{
                    var el = $(args.event.target).closest('.form-group');
                }
              
               
                form_field_id{{$module_id}} = $(el).attr('data-id');
            
                form_field_name{{$module_id}} = $(el).attr('data-field');
           
                form_field_tab{{$module_id}} = $(el).attr('data-tab');
                    
              
         
                if(form_field_id{{$module_id}} === false){
                    args.cancel = true;    
                }
                @if($module_id == 385)
                ////console.log(form_field_name{{$module_id}});
                if(form_field_name{{$module_id}} == 'function_name'){
                    formfields_context{{$module_id}}.enableItems(['Edit Code'], true);
                }else{
                    formfields_context{{$module_id}}.enableItems(['Edit Code'], false);    
                }
                @endif
                
                $.each(args.items, function(index, item) {
                    if(item.id == "field_name"){
                       item.text = field_labels{{$module_id}}[form_field_id{{$module_id}}];
                    }
                })
                }
                
            }catch (e) {
                ////console.log('error',e);
                args.cancel = true;
            }
        },
        select: function(args,e ){
            // context actions
            ////console.log('select',args);
            if(args.item.id == 'sort_form'){
                viewDialog('collist',"{{ url($module_fields_url.'?module_id='.$module_id) }}"); 
                if(form_field_tab{{$module_id}}){
                    
                    viewDialog('collist',"{{ url($module_fields_url.'?module_id='.$module_id) }}"+'&tab='+form_field_tab{{$module_id}});     
                }else{
                    viewDialog('collist',"{{ url($module_fields_url.'?module_id='.$module_id) }}"); 
                }
            }
            if(args.item.id == 'add_field'){
                var fields_url = "{{ url($module_fields_url.'/edit?module_id='.$module_id) }}";
                sidebarformleft('coladd',fields_url,"{{$fields_module_title}}","{{$fields_module_description}}");
            }
            if(args.item.id == 'edit_field'){   
                var url = "{{ url($module_fields_url.'/edit') }}"+'/'+form_field_id{{$module_id}};
               
                sidebarformleft('coladd',url,"{{$fields_module_title}}","{{$fields_module_description}}");   
            }
            if(args.item.id == 'duplicate_field'){
                gridAjaxConfirm('/{{ $module_fields_url }}/duplicate', 'Duplicate column?', {"id" : form_field_id{{$module_id}}}, 'post');
            }
            if(args.item.id == 'delete_field'){
                gridAjaxConfirm('/{{ $module_fields_url }}/delete', 'Delete Column?', {"id" : form_field_id{{$module_id}}}, 'post');
            }
            if(args.item.id == 'set_visible_none'){
                gridAjax('/field_visible_setting/{{$module_id}}/'+ form_field_id{{$module_id}} + '/none');
            }
            if(args.item.id == 'set_visible_both'){
                gridAjax('/field_visible_setting/{{$module_id}}/'+ form_field_id{{$module_id}} + '/both');
            }
            if(args.item.id == 'set_visible_add'){
                gridAjax('/field_visible_setting/{{$module_id}}/'+ form_field_id{{$module_id}} + '/add');
            }
            if(args.item.id == 'set_visible_edit'){
                gridAjax('/field_visible_setting/{{$module_id}}/'+ form_field_id{{$module_id}} + '/edit');
            }
            
			if(args.item.id.indexOf('set_tab_option') === 0){
				////console.log(args.item);
				gridAjax('/field_tab_update/{{$module_id}}/'+ form_field_id{{$module_id}} + '/'+args.item.text);
			}
            
            @if($module_id == 385)
            if(args.item.id == 'edit_code'){
                window.open('/{{get_menu_url_from_module_id(385) }}?id={{ $form_data["id"] }}','_blank');
            }
            @endif
            
        },
    };
    
    // Initialize ContextMenu control
    formfields_context{{$module_id}} = new ej.navigations.ContextMenu(menuOptions, '#formfields_context_ul{{$module_id}}');
}
function create_tab_context{{$module_id}}(){
   @if($num_tabs > 1)
    var context_items = [
        {id: "tab_left", text: "Move left"},
        {id: "tab_right", text: "Move right"},
    ];
    tab_context_name = '';
    var menuOptions = {
        target: '.form-dnd-zone',
        items: context_items,
        beforeOpen: function(args,e){
        //console.log(args);
        	tab_context_name = $(args.event.target).closest('.e-tab-wrap').find('.e-tab-text').text();
        },
        select: function(args,e ){
        	if(tab_context_name == ''){
        		alert('tab context could not identify tab name');
        	}else{
	            if(args.item.id == 'tab_left'){
	               move_tab('left',tab_context_name)
	            }
	            if(args.item.id == 'tab_right'){
	              move_tab('right',tab_context_name);
	            }
        	}
        },
    };
    
    // Initialize ContextMenu control
    formtabs_context{{$module_id}} = new ej.navigations.ContextMenu(menuOptions, '#formtabs_context_ul{{$module_id}}');
    @endif
    
    
}




function move_tab(position, tab_name) {
    var tabTextArray = $('.form-dnd-zone .e-tab-text').map(function() {
        return $(this).text();
    }).get();

    // Find the index of the tab_name in tabTextArray
    var tabIndex = tabTextArray.indexOf(tab_name);

    if (tabIndex !== -1) {
      ;
        var items = formtabObj.items;
        var tabsCount = items.length;

        if (position === 'left' && tabIndex > 0) {
            // Swap the tabs to move left
            var temp = items[tabIndex];
            items[tabIndex] = items[tabIndex - 1];
            items[tabIndex - 1] = temp;
        } else if (position === 'right' && tabIndex < tabsCount - 1) {
            // Swap the tabs to move right
            var temp = items[tabIndex];
            items[tabIndex] = items[tabIndex + 1];
            items[tabIndex + 1] = temp;
        }

        // Update the accordion items
        formtabObj.items = items;
        formtabObj.dataBind();
		
	    var tabTextArray = $('.form-dnd-zone .e-tab-text').map(function() {
	        return $(this).text();
	    }).get();
        $.ajax({
            url: '/form_tabs_sort/{{$module_id}}',
            type: 'post',
            data: {
                tabs: tabTextArray
            },
            beforeSend: function(args) {
                showSpinner();
            },
            success: function(data) {
                hideSpinner();
                reload_active_form{{$module_id}}();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                hideSpinner();
            },
        });
    }
}


$(document).ready(function() {
       $(document).off('dblclick', '.e-tab-text').on('dblclick', '.e-tab-text', function() {
        var element = $(this);
        ////console.log(element);
        var originalText = element.text();
        ////console.log(originalText);
        
        var inputField = $('<input type="text">').val(originalText);
        
        element.empty().append(inputField);
        
        inputField.focus();

        inputField.on('blur', function() {
            var newText = $(this).val();
            element.empty().text(newText);
            
            $.ajax({
                type: 'POST',
                url: 'tab_rename',
                data: { module_id: {{$module_id}}, before_name: originalText, after_name: newText },
                success: function(response) {
                    //console.log('Ajax response:', response);
                },
                error: function(xhr, status, error) {
                    console.error('Ajax error:', status, error);
                }
            });
        });
        
        
        // Allow pressing Enter to trigger blur event
        inputField.on('keydown', function(e) {
        	//console.log('keydown',e);
             if (e.which == 32){ // Space key
                // Space key handling
                e.stopPropagation();
            }
        });
        
    });
});


@endif

function reload_active_form{{$module_id}}(){
   
    if(sidebarformcontainer.isRendered && sidebarformcontainer.isOpen && sidebarformcontainer.formUrl){
      
    	sidebarform('reload_active_form',sidebarformcontainer.formUrl,$('#form_toolbar_title').text());
    }
    try{
    reload_grid_config{{$module_id}}();	
    }catch(e){}
}

@if(is_dev() || is_superadmin())
setTimeout(function(){
create_form_context{{$module_id}}();
create_tab_context{{$module_id}}();
},500);

@endif

$(document).off('click','.delete-form-image').on('click','.delete-form-image',function(){
	var delete_url = $(this).attr('data-attr-delete-url');
	var file_id = $(this).attr('data-attr-file');
	//////console.log(file_id);
	//////console.log(delete_url);
	//////console.log($("#"+file_id));
	$("#"+file_id).addClass('d-none');
	gridAjax(delete_url);
});
$(document).off('click','.delete-form-file').on('click','.delete-form-file',function(){
	var delete_url = $(this).attr('data-attr-delete-url');
	var file_id = $(this).attr('data-attr-file');
	//////console.log(file_id);
	//////console.log(delete_url);
	//////console.log($("#"+file_id));
	$("#"+file_id).addClass('d-none');
	gridAjax(delete_url);
});

@if(is_superadmin())
$(document).ready(function(){
	if(tab_count  > 1){
		$(".current-tab").sortable({
	        containment: "parent",
	        handle: '.form-dnd-label',
	        start: function(e) {
	        //console.log('start',e);
	        },
	        stop: function(e) {
				//console.log(e);
				
				var dataArray = $(e.target).find('.form-group').map(function() {
					return $(this).attr('data-id');
				}).get();
				//console.log(dataArray);
				
				$.ajax({
					url: '/update_fields_sort/{{$module_id}}',
					type:'post',
					data: {fields: dataArray},
					success: function(data) { 
					
					
					}
				});
	        }
	    });
	}else{
		$(".content-wrapper").sortable({
	        containment: "parent",
	        handle: '.form-dnd-label',
	        start: function(e) {
	        //console.log('start',e);
	        },
	        stop: function(e) {
				//console.log(e);
				
				var dataArray = $(e.target).find('.form-group').map(function() {
					return $(this).attr('data-id');
				}).get();
				//console.log(dataArray);
				
				$.ajax({
					url: '/update_fields_sort/{{$module_id}}',
					type:'post',
					data: {fields: dataArray},
					success: function(data) { 
					
					
					}
				});
	        }
	    });
	}
	
	/*
	$(".form-dnd-label").draggable({
		helper: 'clone',
		cursor: 'move',
		tolerance: 'fit',
		revert: true
	});

	$(".form-dnd-zone").droppable({
		accept: ".form-dnd-label",
		drop: function (event, ui) {
			//////console.log(event);
			//////console.log(ui);
			const tab = $(event.target).find('.e-tab-text').text();
			const current_tab = ui.draggable.closest('.current-tab').attr('id');
			const field_id = ui.draggable.closest('.form-group').attr('data-id');
			if(tab != current_tab){
                gridAjax('/field_tab_update/{{$module_id}}/'+ field_id + '/'+tab);
				//////console.log(tab);
				//////console.log(field_id);
			}
		}
	});
    */
});
@endif


</script>
@endpush

@push('page-styles')
	      
<style>
@if(!empty(session('webform_module_id')) && !request()->ajax())
body{
background-color: #f7f7f7;
}
@endif
.tox-tinymce {
height : 400px;
}
#{{ $form_name }}FormAjax .boldlabel{
	text-decoration: underline;
	font-size: 14px;
}

.form-wrapper{
		background-color: #fbfbfb;
		padding: 2%;
		padding-bottom: 4%;
		margin-bottom:3%;
		margin-top:1%;
		box-shadow: 0 0 0.2cm rgba(0,0,0,0.3);
}
.form_tooltip{
	font-size:14px;
}
.e-file-select .e-error {
    display: table !important;
    width: 100%;
    min-width: 400px;
    margin-bottom: 20px;
}
#{{ $form_name }}FormAjax .e-tab .e-content{
padding-left:30px;	
padding-right:30px;	
}
.form-group .e-disabled{
	color:#ccc !important;
}
@if(!request()->ajax())
label{
	font-size:13px;
}

@endif
.formio-editor-read-only-content p {
margin: 0;
}

.e-multi-line-input.form-control{
	padding: 0 !important;
	border:none !important;
}
.form-check {
    padding-left: 0;
}
.form-control-sm {
    padding: 0;
}
.tox-dialog-wrap{
z-index:3000 !important;	
}
.tox-dialog-wrap{
z-index:3000 !important;	
}
.tox .tox-dialog {
z-index:3001 !important;
}
.tox-tinymce-aux{z-index:99999999999 !important;}
.ck-editor__main, .ck-editor__editable{z-index:99999999999 !important;}



.e-tab .e-tab-header .e-toolbar-item .e-tab-wrap {
  
    padding: 0 24px;
}
#mod{{$module_id}}Tab .e-toolbar-item.e-disable{
	display:none;
}
textarea.e-input{
border: 1px solid #ced4da !important;
}
</style>
@endpush