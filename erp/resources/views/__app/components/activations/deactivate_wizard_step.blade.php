@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
    
	
@endif

@section('content')
    <form action="/deactivate_service_post" id="provision_form" class="stepbox mx-auto">
    <input type="hidden" name="deactivate_plan_id"  value="{{$deactivate_plan_id}}">
    <input type="hidden" name="provision_id" value="{{$provision_id}}">
    <input type="hidden" name="current_step" value="{{$current_step}}">
    <input type="hidden" name="num_steps" value="{{$num_steps}}">
    <input type="hidden" name="service_table" value="{{$service_table}}">
    {!! $provision_form !!}
    </form>
    <div class="text-center" style="display:none" id="processing_div">
    	<h6>Processing ...</h6>
    </div>
@endsection
@push('scripts') 
 <script src="https://cdn.tiny.cloud/1/r393xiac7oc37ggv1pogvslt7pmbnzxivf5ee5mkkxj7dfu7/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
@endpush
@push('page-scripts')

<script>
@if(empty($exclude_form_script))
$(function(){
    
wizard_inputs = new Array();
setTabInputs(); 
});
if($("#prefix").length > 0 ){
	window['phone_number'] = new ej.dropdowns.DropDownList({
		dataSource: [],
		placeholder: "Phonenumber",
		ignoreAccent: true,
		allowFiltering: true,
		fields: {text: 'number', value: 'number'},
		filterBarPlaceholder: 'Select Phonenumber',
        cssClass: "e-small",
        filtering: function(e){
        if(e.text == ''){
        e.updateData(window['phone_number'].dataSource);
        }else{ 
        var query = new ej.data.Query().select(['number']);
        query = (e.text !== '') ? query.where('number', 'contains', e.text, true) : query;
        e.updateData(window['phone_number'].dataSource, query);
        }
        },
        change: function(args){
        	if(this.value > ''){
        		$('.testphonebtn').removeAttr('disabled');
        	}else{
				$('.testphonebtn').attr('disabled','disabled');
        	}
        }
	});
	window['phone_number'].appendTo("#phone_number");
	@if(session('role_level') == 'Admin')
		$(document).off('click', '#testfixedtelkom').on('click', '#testfixedtelkom', function() {
			testnum('test_number/fixedtelkom/'+window['phone_number'].value)
		});
		$(document).off('click', '#testmobiletelkom').on('click', '#testmobiletelkom', function() {
			testnum('test_number/mobiletelkom/'+window['phone_number'].value)
		});
		$(document).off('click', '#testvodacom').on('click', '#testvodacom', function() {
			testnum('test_number/vodacom/'+window['phone_number'].value)
		});
		$(document).off('click', '#testmtn').on('click', '#testmtn', function() {
			testnum('test_number/mtn/'+window['phone_number'].value)
		});
		$(document).off('click', '#testcellc').on('click', '#testcellc', function() {
			testnum('test_number/cellc/'+window['phone_number'].value)
		});
		
		function testnum(url){
		
			$.ajax({
				url: url,
				type: 'GET',
				beforeSend: function(e) {
				},
				success: function(data) {
				},
				error: function(jqXHR, textStatus, errorThrown) {
				},
			});
		}
		
	@endif
}



if($("#theme").length > 0 ){
	window['theme'] = new ej.dropdowns.DropDownList({
		dataSource: sitebuilder_themes,
		placeholder: "Theme",
		ignoreAccent: true,
		allowFiltering: true,
		fields: {text: 'name', value: 'id', thumb: 'thumb'},
		filterBarPlaceholder: 'Select Theme',
        cssClass: "e-small",
        filtering: function(e){
        if(e.text == ''){
        e.updateData(window['theme'].dataSource);
        }else{ 
        var query = new ej.data.Query().select(['name']);
        query = (e.text !== '') ? query.where('name', 'contains', e.text, true) : query;
        e.updateData(window['theme'].dataSource, query);
        }
        },
        change: function(e){
        	if(this.value > ''){
        		var theme_preview = '<img src="' + this.itemData.thumb + '" />';
        		$("#theme_preview").html(theme_preview);
        	}
        }
	});
	window['theme'].appendTo("#theme");

}

if($("#prefix").length > 0 ){

	window['prefix'] = new ej.dropdowns.DropDownList({
		placeholder: "Prefix",
		ignoreAccent: true,
		allowFiltering: true,
		filterBarPlaceholder: 'Select Prefix',
        cssClass: "e-small",
		change: function(e){
			change_number_ds();
		},
		created: function(e){
			change_number_ds();
		}
	});
	window['prefix'].appendTo("#prefix");
} 


function setTabInputs(){
@if(empty($exclude_input_script))
	$("#provision_form input[type='text']").each(function(i){
		var input_id = $(this).attr('id');

		if(typeof wizard_inputs[input_id] == undefined || wizard_inputs[input_id] == null){
			if($("#theme").length > 0  && $(this).attr('id') == 'theme'){
				return;
			}else if($("#prefix").length > 0 && $(this).attr('id') == 'phone_number'){
				return;
		
			}else{
				if($(this).hasClass(('text-date'))){
					wizard_inputs[input_id] = new ej.calendars.DatePicker({
						placeholder: $(this).attr('placeholder'),
						format: 'yyyy-MM-dd',
						value: $(this).val(),
			            cssClass: "e-small",
					});
					wizard_inputs[input_id].appendTo("#"+$(this).attr('id'));
				
				}else if(input_id == 'domain_name'){
					wizard_inputs[input_id] = new ej.inputs.TextBox({
						placeholder: $(this).attr('placeholder'),
						value: $(this).val(),
			            cssClass: "e-small",
						change: function(){
							show_epp_div();
						},
					});
					wizard_inputs[input_id].appendTo("#"+$(this).attr('id'));
				}else{
					wizard_inputs[input_id] = new ej.inputs.TextBox({
						placeholder: $(this).attr('placeholder'),
			            cssClass: "e-small",
						value: $(this).val(),
					});
					wizard_inputs[input_id].appendTo("#"+$(this).attr('id'));
				}
			}
		}
	});
	
	$("#provision_form input[type='checkbox']").each(function(i){
		var input_id = $(this).attr('id');
		if(typeof wizard_inputs[input_id] == undefined || wizard_inputs[input_id] == null){
	
			if($(this).is(":checked")){
				var is_checked = true;
			}else{
				var is_checked = false;
			}
			wizard_inputs[input_id] = new ej.buttons.Switch({ onLabel: 'ON', offLabel: 'OFF', checked: is_checked });
			wizard_inputs[input_id].appendTo("#"+$(this).attr('id'));
		}
		
		
	});
	
	$("#provision_form input[type='email']").each(function(i){
		var input_id = $(this).attr('id');
		if(typeof wizard_inputs[input_id] == undefined || wizard_inputs[input_id] == null){
			wizard_inputs[input_id] = new ej.inputs.TextBox({
				placeholder: $(this).attr('placeholder'),
				value: $(this).val(),
	            cssClass: "e-small",
			});
			wizard_inputs[input_id].appendTo("#"+$(this).attr('id'));
		}
	});
	
	$("#provision_form select").each(function(i){
		var input_id = $(this).attr('id');
	
		if((typeof wizard_inputs[input_id] == undefined || wizard_inputs[input_id] == null) && input_id.indexOf("_hidden") == -1){
			if($("#prefix").length > 0 && $(this).attr('id') == 'prefix'){
				return;
			}else if($("#prefix").length > 0 && $(this).attr('id') == 'phone_number'){
				return;
		
			}else if($("#gateway").length > 0 && $("#type").length > 0 && $(this).attr('id') == 'type'){
			
				wizard_inputs[input_id] = new ej.dropdowns.DropDownList({
					placeholder: $(this).attr('placeholder'),
					value: $(this).val(),
					allowFiltering: true,
					change: function(args){
						if(this.value == 'Object'){
							$('.gatewayrow').hide();
							$('.asnumberrow').show();
						}else{
							$('.gatewayrow').show();
							$('.asnumberrow').hide();
						}
					},
					created: function(args){
						if(this.value == 'Object'){
							$('.gatewayrow').hide();
							$('.asnumberrow').show();
						}else{
							$('.gatewayrow').show();
							$('.asnumberrow').hide();
						}
						
					},
				});
				wizard_inputs[input_id].appendTo("#"+$(this).attr('id'));
		
			}else{
				if(input_id == 'domain_action'){
					wizard_inputs[input_id] = new ej.dropdowns.DropDownList({
						placeholder: $(this).attr('placeholder'),
						value: $(this).val(),
						allowFiltering: true,
						change: function(){
							show_epp_div();
						},
					});
					wizard_inputs[input_id].appendTo("#"+$(this).attr('id'));
				}else{
					wizard_inputs[input_id] = new ej.dropdowns.DropDownList({
						placeholder: $(this).attr('placeholder'),
						value: $(this).val(),
						allowFiltering: true,
					});
					wizard_inputs[input_id].appendTo("#"+$(this).attr('id'));
				}
				
			}
		}
	});
	
	$("#provision_form textarea").each(function(i){
	
		if($(this).hasClass('tinymce')){
			var textarea = $(this).val();
			for (var i = tinyMCE.editors.length - 1 ; i > -1 ; i--) {
			var ed_id = tinymce.editors[i].id;
			tinyMCE.execCommand("mceRemoveEditor", true, ed_id);
			}
			tinymce.init({
				selector: "#"+$(this).attr('id'),  // change this value according to your HTML
				theme: 'silver',
				plugins: 'autoresize fullpage code image print preview searchreplace autolink directionality visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern help',
				toolbar1: 'code | image | formatselect | bold italic underline forecolor backcolor | link | alignleft aligncenter alignright alignjustify  | numlist bullist outdent indent  | removeformat',
				image_advtab: true,
				menubar: "",
				valid_children : '+body[style]',
				height : "200",
				visual : false,
				relative_urls : false,
				remove_script_host : false,
				convert_urls : true,
				force_br_newlines : true,
				force_p_newlines : false,
				entity_encoding : "raw",
				forced_root_block : "",
				convert_newlines_to_brs : false,
	            remove_linebreaks : true,  
				cleanup : true,
				verify_html : true,
				plugins: "paste",
	    		paste_as_text: true, 
	    		@if(session('role_level') == 'reseller' || session('role_level') == 'customer')
	    		readonly:1,
	    		@endif
				setup: function (ed) {
				ed.on('init', function(args) {
				});
				},
			});
			
		}else{
			
			var input_id = $(this).attr('id');
			if(typeof wizard_inputs[input_id] == undefined || wizard_inputs[input_id] == null){
				wizard_inputs[input_id] = new ej.inputs.TextBox({
			        placeholder: $(this).attr('placeholder'),
			    });
				wizard_inputs[input_id].appendTo("#"+$(this).attr('id'));
			}
		}
	});
	@endif
}

function show_epp_div(){
	var action = wizard_inputs['domain_action'].value;
	var domain_name = wizard_inputs['domain_name'].value;
	if(action == 'Transfer' && !domain_name.endsWith('.co.za')){
		$("#epp_div").show();
	}else{
		$("#epp_div").hide();
	}
}

function change_number_ds(e){
	var prefix = window['prefix'].value;

	if(prefix){
		window['phone_number'].dataSource = window["prefixds"+prefix];
		if(window["selected_number"] !== undefined && window["selected_number"].length > 0 && window["selected_number"].indexOf(prefix) >= 0 && !window['phone_number'].value){
		
			window['phone_number'].value = window["selected_number"];
		}else{
			window['phone_number'].value = '';
		}
		window['phone_number'].dataBind();
		$("#numberdiv").show();
	}else{
		 window['phone_number'].value = '';
		 window['phone_number'].dataBind();
		$("#numberdiv").hide();
	}
}
@endif



$(document).off('submit','#provision_form').on('submit','#provision_form', function(e) {
	$(".btn-toolbar").hide();
	e.preventDefault();
	 
    $("#activate_submit_btn").attr('disabled','disabled');
        
	try{
		tinyMCE.triggerSave();	
	}catch(e){
	}
	
		
		if($("#provision_iframe").length > 0){
			
	        iframeFormSubmit('provision_iframe','iframe_form');
			return false;
		}
		
		formSubmit('provision_form');
		if($("#messagebox").length > 0){
			$("#messagebox").hide();
		}
		for (var i = tinyMCE.editors.length - 1; i > -1; i--) {
		var ed_id = tinymce.editors[i].id;
		tinyMCE.execCommand("mceRemoveEditor", true, ed_id);
		}
	return false;
});
</script>
@endpush


@push('page-styles')

<style>
label{
margin-bottom: 0px;
margin-top: 5px;
}
	.stepbox{
		background-color: #fbfbfb;
		padding: 2%;
		margin-top:3%;
		margin-bottom:3%;
		box-shadow: 0 0 0.2cm rgba(0,0,0,0.3);
		min-height: 300px;
		width:94%;
	}
	.e-tab .e-content > .e-item{
		width: 100%;
	}
	.e-tab.e-vertical-tab .e-content {
	border-left: 2px solid gray;
	}
	.e-tab.e-fill .e-tab-header.e-vertical.e-vertical-left {
    border-right: none;
}
.e-tab.e-fill .e-tab-header .e-toolbar-item.e-active .e-tab-wrap {
    background: gray;
}
</style>
@endpush