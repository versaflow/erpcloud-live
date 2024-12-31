{!! Form::open(array("url"=> "domains_import", "class"=>"form-horizontal","files" => true , "parsley-validate"=>"","novalidate"=>" ","id"=> "ImportFormAjax")) !!}		
<h6>
ZACR  
</h6>
<p>Login to <a href="https://portal.registry.net.za/">https://portal.registry.net.za/</a> Go to Integration -> co.za, enter the registrar password for the associated tld and click on download.</p>
<p>
<code>'co.za' - '79f9a51292'</code><br>
<code>'org.za' - '579feace28'</code><br>
</p>
<div class="row" >
    <label for="Import" class=" control-label col-md-3 text-left"> Domain CSV File </label>
    <div class="col-md-9">
        <input  name="import" id="import" type="file" aria-label="files" />
    </div>
    
</div>
 <input id="provider" name="provider"  type="hidden" value="{{$provider}}"/>
 <input id="id" name="id"  type="hidden" value="{{$id}}"/>
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
    
	
});
$('#ImportFormAjax').on('submit', function(e) {
	e.preventDefault();
    formSubmit("ImportFormAjax");
});
</script>