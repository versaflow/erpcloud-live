{!! Form::open(array("url"=> "documents_invoice_upload/".$id, "class"=>"form-horizontal","files" => true , "parsley-validate"=>"","novalidate"=>" ","id"=> "ImportFormAjax")) !!}		

<div id="page-wrapper" class="container-fluid mx-auto ">
<div class="row" >
    <label for="Import" class=" control-label col-md-3 text-left"> Import File </label>
    <div class="col-md-9">
        <input  name="import" id="import" type="file" aria-label="files" />
    	<div class="form-group e-float-input e-control-wrapper">
			<label>Remove Current File</label><br>
			<input name="remove_file" id="remove_file" type="checkbox" value="1" >
		</div>
    </div>
</div>
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

<style>
#page-wrapper{
		background-color: #fbfbfb;
		padding: 2%;
		margin-top:3%;
		margin-bottom:3%;
		box-shadow: 0 0 0.2cm rgba(0,0,0,0.3);
}
</style>