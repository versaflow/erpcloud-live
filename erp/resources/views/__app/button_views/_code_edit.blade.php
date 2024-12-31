
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.13.0/ace.js" integrity="sha512-btmS7t+mAyZXugYonCqUwCfOTw+8qUg9eO9AbFl5AT2zC1Q4we+KnCQAq2ZITQz1c9/axyUNYaNhGWqxfSpj7g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
{!! Form::open(array("url"=> "code_edit_save", "class"=>"form-horizontal","files" => true , "parsley-validate"=>"","novalidate"=>" ","id"=> "code_edit")) !!}	
<input type="hidden" name="function_name" value="{{ $function_name }}" />
<div class="card mt-2" >
    <div class="card-header">{{ $function_name }}</div>
    <div class="card-body">
		<textarea class='form-control input-sm editor' name='function_code' rows='25' id='function_code' style='display: none;'>{!! $function_code !!}</textarea>
		<div id='e_function_code'></div>
    </div>
</div>
<div ref="component" class="field form-group has-feedback formio-component formio-component-button formio-component-submit float-right mr-2 form-group" >
<button lang="en" type="submit"  class="btn btn-primary float-right mr-2" ref="button">
Submit
</button>
</div>
{!! Form::close() !!}
<script type="text/javascript">

$(document).ready(function() {
  
	var e_function_code = ace.edit('e_function_code');
	e_function_code.setTheme('ace/theme/twilight');
	e_function_code.session.setMode({path:"ace/mode/php", inline:true})
	e_function_code.setAutoScrollEditorIntoView(true);
	e_function_code.setOption('maxLines', 30);
	e_function_code.setOption('minLines', 10);
	e_function_code.setOption('minLines', 10);
	$('#function_code').hide();
	var t_function_code = $('#function_code');
	e_function_code.getSession().setValue(t_function_code.val());
	e_function_code.getSession().on('change', function(){
	    t_function_code.val(e_function_code.getSession().getValue());
	});
});
$('#code_edit').on('submit', function(e) {
 	e.preventDefault();
    formSubmit("code_edit");
});
</script>