{!! Form::open(array("url"=> "bank_ofx_import", "class"=>"form-horizontal","files" => true , "parsley-validate"=>"","novalidate"=>" ","id"=> "ImportFormAjax")) !!}		
<div class="card mt-2" >
    <div class="card-header">Import OFX File</div>
    <div class="card-body">
    <input  type="hidden" name="cashbook_id" id="cashbook_id" value="{{$cashbook_id}}" />
    <input  name="import" id="import" type="file" aria-label="files" />
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
});
$('#ImportFormAjax').on('submit', function(e) {
 	e.preventDefault();
    formSubmit("ImportFormAjax");
});
</script>