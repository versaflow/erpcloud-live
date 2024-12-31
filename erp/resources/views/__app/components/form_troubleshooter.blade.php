

<form id="{{ $name }}FormAjax" action="/{{ $menu_route }}/save" class="py-4 px-2 e-control">


<div id="{{ $name }}FormErrors"></div>
@if(!request()->ajax())
<div class="form-wrapper container">
@endif
@if(!empty(session('webform_module_id')) && !empty($webform_title))
<div  class="p-2"><h2> {{ $webform_title }} </h2></div>
@endif
{!! $form_html !!}
@if(!empty(session('webform_module_id')) && !empty($webform_text))
<div  class="card mt-4 p-4">
{!! nl2br($webform_text) !!}
</div>
@endif
@if(!request()->ajax())
	<div class="card mt-4" style="background: none;border: none;">
		<div class="content-wrapper" >
		<div class="form-group" style="float: right;">
			@if($field_type != 'view')
			<button type="submit" id="submitbtn" style="float:right">Submit</button>
			@endif
			@if(empty(session('webform_module_id')))
			<a id="backbtn" style="float:right" href="{{ url()->previous() }}">Back</a>
			@endif
		</div>
		</div>
	</div>
@endif
</form>

@push('page-scripts')

<script type="text/javascript">

	{!! $form_script !!}
	@if(!request()->ajax())
	$(document).ready(function() {
		var button = new ej.buttons.Button();
		button.appendTo('#backbtn');
		@if($field_type != 'view')
		var button = new ej.buttons.Button({ cssClass: `e-info`});
		button.appendTo('#submitbtn');
		@endif
		
	});
	@endif
$(document).ready(function() {
$("form :input:not(.e-ddl-hidden):visible:enabled:first").focus();
});

function setTinyMce(){
	$("textarea.tinymce").each(function(i){
		
		var textarea = $(this).val();
		tinymce.init({
			selector: "#"+$(this).attr('id'),  // change this value according to your HTML
			theme: 'silver',
			plugins: 'print autoresize code image print preview searchreplace autolink directionality visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern help',
			toolbar1: 'code | image | formatselect | bold italic underline forecolor backcolor | link | alignleft aligncenter alignright alignjustify  | numlist bullist outdent indent  | removeformat | print',
			contextmenu: "",
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
			setup: function (ed) {
			ed.on('init', function(args) {
			});
			},
		});
	});
}

function setTinyMceTabs(e){
	
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
			plugins: 'print autoresize code image print preview searchreplace autolink directionality visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern help',
			toolbar1: 'code | image | formatselect | bold italic underline forecolor backcolor | link | alignleft aligncenter alignright alignjustify  | numlist bullist outdent indent  | removeformat | print',
			contextmenu: "",
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
			setup: function (ed) {
			ed.on('init', function(args) {
			});
			},
		});
	});
}

$('#{{ $name }}FormAjax').on('submit', function(e) {
	
	e.preventDefault();
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
	@if($module_id == 526 )
	
		var form = $('#{{ $name }}FormAjax');
	
		var formData = new FormData(form[0]);
	    @if(empty($id))
		var columns = window['grid_'+grid_module_id].columns;
		var state = JSON.parse(window['grid_'+grid_module_id].getPersistData());

		$(state.columns).each(function(i,el) {
			state.columns[i].headerText = columns[i].headerText;
			state.columns[i].type = columns[i].type;
		});
		
	    var state = JSON.stringify(state);
		var grid_state = JSON.stringify({ persistData: state });

		formData.append('settings', grid_state);
		@endif
		$.ajax({
			method: "post", 
			url: form.attr('action'),
			data: formData,
			contentType: false,
			processData: false,
			beforeSend: function(e){
			    try{
			    showSpinner();
	            $('.dialogSubmitBtn').each(function(e){
	                $(this).prop('disabled', true);
	            });
			    }catch(e){}
			},
			success: function(data) {
			    try{
			    hideSpinner();
			    }catch(e){}
			    toastNotify('Saved','success', true);
			      try{
                dialog.hide();
            }catch(e){
            }
			},
			error: function(jqXHR, textStatus, errorThrown) {
				 toastNotify('Error','error', true);
			    try{
			    hideSpinner();
			    }catch(e){}
			      try{
                dialog.hide();
            }catch(e){
            }
			},
		});
	@else
		if ({{ $name }}FormValidator.validate()) {
			formSubmit('{{ $name }}FormAjax');
		}else{
			toastNotify('Please fill all required fields', 'warning');
		}
	@endif
});
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
.form-wrapper{
		background-color: #fbfbfb;
		padding: 2%;
		padding-bottom: 4%;
		margin-bottom:3%;
		margin-top:1%;
		box-shadow: 0 0 0.2cm rgba(0,0,0,0.3);
}
.form_tooltip{
	float:right;
    background-color: #e0e0e0;
    padding: 4px 6px;
}
.e-file-select .e-error {
    display: table !important;
    width: 100%;
    min-width: 400px;
    margin-bottom: 20px;
}
@if(!request()->ajax())
label{
	font-size:13px;
}
@endif
</style>
@endpush