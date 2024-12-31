@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
    
	
@endif

@section('content')
<div id="provision_tabs"></div>
<?php

$tabs = '';
foreach ($provision_plans as $i => $step) {
    $hidden = ($step->step != $current_step) ? 'style="display:none"' : '';
    echo '<div id="step'.$i.'" '.$hidden.' class="stepbox mx-auto 11">';
    echo '<form action="/provision_service" id="stepForm'.$i.'">';
    echo '<input type="hidden" name="provision_plan_id"  value="'.$step->id.'">';
    echo '<input type="hidden" name="provision_id" value="'.$provision->id.'">';
    echo '<input type="hidden" name="current_step" value="'.$current_step.'">';
    echo '<input type="hidden" name="num_steps" value="'.$num_steps.'">';
    echo '<input type="hidden" name="topup" value="'.$topup.'">';
    echo $step->form;
    echo '</form>';
    echo '</div>';
    if ($current_step < $step->step) {
        $disabled = 'true';
    } elseif (!$step->repeatable && $step->step < $current_step) {
        $disabled = 'true';
    } else {
        $disabled = 'false';
    }
    $j = $i+1;
    $tabs .= '{ header: { "text": "Step '.$j.'" }, content: "#step'.$i.'", disabled: '.$disabled.', repeatable: '.$step->repeatable.' },';
}
?>

@endsection
@push('page-scripts')

<script>
if($("#prefix").length > 0 ){
	window['phone_number'] = new ej.dropdowns.DropDownList({
		dataSource: [],
		placeholder: "Phonenumber",
		ignoreAccent: true,
		allowFiltering: true,
		fields: {text: 'number', value: 'number'},
		filterBarPlaceholder: 'Select Phonenumber',
        filtering: function(e){
        if(e.text == ''){
        e.updateData(window['phone_number'].dataSource);
        }else{ 
        var query = new ej.data.Query().select(['number']);
        query = (e.text !== '') ? query.where('number', 'contains', e.text, true) : query;
        e.updateData(window['phone_number'].dataSource, query);
        }
        },
	});
	window['phone_number'].appendTo("#phone_number");

}
if($("#theme").length > 0 ){
	window['theme'] = new ej.dropdowns.DropDownList({
		dataSource: sitebuilder_themes,
		placeholder: "Theme",
		ignoreAccent: true,
		allowFiltering: true,
		fields: {text: 'name', value: 'id', thumb: 'thumb'},
		filterBarPlaceholder: 'Select Theme',
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
		change: function(e){
			change_number_ds();
		},
		created: function(e){
			change_number_ds();
		}
	});
	window['prefix'].appendTo("#prefix");
} 
$(document).ready(function() {


	wizard_inputs = new Array();
	wizardTab = new ej.navigations.Tab({
		items: [
		    {!! ($tabs) !!}
		],
		selectedItem: {{ $selected_tab }},
		selected: setTabInputs,
		created: setTabInputs,
		headerPlacement: 'Left',
		overflowMode: 'Popup',
		cssClass: 'e-fill',
	});
	wizardTab.appendTo('#provision_tabs');
});

function setTabInputs(e){

	if(e == undefined){
		var div_id = parseInt(wizardTab.selectedID) ;
	}else{
		var div_id = parseInt(e.selectedIndex) ;
	}

	var tab_id = "#step"+div_id;
	$(tab_id+" input[type='text']").each(function(i){
		var input_id = div_id+$(this).attr('id');

		if(typeof wizard_inputs[input_id] == undefined || wizard_inputs[input_id] == null){
			if($("#theme").length > 0  && $(this).attr('id') == 'theme'){
				return;
			}else if($("#prefix").length > 0 && $(this).attr('id') == 'phone_number'){
				return;
		
			}else{
			wizard_inputs[input_id] = new ej.inputs.TextBox({
				placeholder: $(this).attr('placeholder'),
				value: $(this).val(),
			});
			wizard_inputs[input_id].appendTo("#"+$(this).attr('id'));
			}
		}
	});
	
	$(tab_id+" input[type='checkbox']").each(function(i){
		var input_id = div_id+$(this).attr('id');
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
	
	$(tab_id+" input[type='email']").each(function(i){
		var input_id = div_id+$(this).attr('id');
		if(typeof wizard_inputs[input_id] == undefined || wizard_inputs[input_id] == null){
			wizard_inputs[input_id] = new ej.inputs.TextBox({
				placeholder: $(this).attr('placeholder'),
				value: $(this).val(),
			});
			wizard_inputs[input_id].appendTo("#"+$(this).attr('id'));
		}
	});
	
	$(tab_id+" select").each(function(i){
		var input_id = div_id+$(this).attr('id');
	
		if((typeof wizard_inputs[input_id] == undefined || wizard_inputs[input_id] == null) && input_id.indexOf("_hidden") == -1){
			if($("#prefix").length > 0 && $(this).attr('id') == 'prefix'){
				return;
			}else if($("#prefix").length > 0 && $(this).attr('id') == 'phone_number'){
				return;
		
			}else{
			
				wizard_inputs[input_id] = new ej.dropdowns.DropDownList({
					placeholder: $(this).attr('placeholder'),
					value: $(this).val(),
					allowFiltering: true,
				});
				wizard_inputs[input_id].appendTo("#"+$(this).attr('id'));
				
			}
		}
	});
	
	$(tab_id+" textarea").each(function(i){
	
		if($(this).hasClass('editor')){
				
			var textarea = $(this).val();
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
	    		paste_as_text: false,
				content_style: "p {margin: 0}",
				setup: function (ed) {
				ed.on('init', function(args) {
				});
				},
			});
		}else{
			var input_id = div_id+$(this).attr('id');
			if(typeof wizard_inputs[input_id] == undefined || wizard_inputs[input_id] == null){
				wizard_inputs[input_id] = new ej.inputs.TextBox({
			        placeholder: $(this).attr('placeholder'),
			    });
				wizard_inputs[input_id].appendTo("#"+$(this).attr('id'));
			}
		}
	});
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

$('form').on('submit', function(e) {
	
	
	e.preventDefault();
	
	try{
		tinyMCE.triggerSave();	
	}catch(e){
	}
	
	if(wizardTab.selectingID == undefined){
		var form_step = parseInt(wizardTab.selectedID) ;
	}else{
		var form_step = parseInt(wizardTab.selectingID) ;
	}

	if($(this).attr('id') == 'stepForm'+form_step){
		
		if($("#provision_iframe").length > 0){
			
	        iframeFormSubmit('provision_iframe','iframe_form');
			return false;
		}
		$('<input />').attr('type', 'hidden')
		.attr('name', "form_step")
		.attr('value', form_step)
		.appendTo('#stepForm'+form_step);
		formSubmit('stepForm'+form_step);
	}
	return false;
});

</script>
@endpush


@push('page-styles')

<style>
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