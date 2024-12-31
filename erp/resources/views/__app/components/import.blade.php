{!! Form::open(array("url"=> "/".$menu_route."/postimport", "class"=>"form-horizontal","files" => true , "parsley-validate"=>"","novalidate"=>" ","id"=> "ImportFormAjax")) !!}		
<div class="card mt-2" >
    <div class="card-header">XLSX Import File<br><p>Field names needs to set as the header on the first line of the excel file.</p></div>
    <div class="card-body">
    {!! $form_html !!}
    <input  name="import" id="import" type="file" aria-label="files" />
</div>
</div>
@if(!empty(request()->tab_load))
<div ref="component" class="field form-group has-feedback formio-component formio-component-button formio-component-submit float-right mr-2 form-group" >
<button lang="en" type="submit"  class="btn btn-primary ui button primary float-right mr-2" ref="button">
Submit
</button>
</div>
@endif
{!! Form::close() !!}
<script type="text/javascript">

	{!! $form_script !!}
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