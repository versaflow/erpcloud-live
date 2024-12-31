{!! Form::open(array("url"=> "supplier_invoice_upload/".$id, "class"=>"form-horizontal","files" => true , "parsley-validate"=>"","novalidate"=>" ","id"=> "ImportFormAjax")) !!}		
<div class="row" >
    <label for="Import" class=" control-label col-md-3 text-left"> Invoice File </label>
    <div class="col-md-9">
        <input  name="import" id="import" type="file" aria-label="files" />
    	<div class="form-group e-float-input e-control-wrapper">
			<label>Remove Current File</label><br>
			<input name="remove_file" id="remove_file" type="checkbox" value="1" >
		</div>
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
    var uploadObj = new ej.inputs.Uploader({
        autoUpload: false,
        multiple: false,
    });
    uploadObj.appendTo("#import");
    var checkbox = { label: 'Remove Current File' };
	var remove_file = new ej.buttons.Switch(checkbox);
	remove_file.appendTo("#remove_file");
});
$('#ImportFormAjax').on('submit', function(e) {
	e.preventDefault();
    formSubmit("ImportFormAjax");
});
</script>