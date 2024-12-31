{!! Form::open(array("url"=> "supplier_cdr_import", "class"=>"form-horizontal","files" => true , "parsley-validate"=>"","novalidate"=>" ","id"=> "ImportFormAjax")) !!}		
<div class="card mt-2" >
  
        
    
    <div class="card-body">
    <div class="mb-3">
    <label for="gateway" class="form-label">Gateway</label>
    <select class="form-control" name="gateway" id="gateway" />
    <option>BVS</option>
    <option>VOX</option>
    <option>BITCO</option>
    </select>
    </div>
    <div class="mb-3">
    <label for="import" class="form-label">Excel file</label>
    <input  name="import" id="import" type="file" aria-label="files" />
    </div>

    <div class="form-check">
    <input class="form-check-input" type="checkbox" value="1" id="currentmonth" name="currentmonth">
    <label class="form-check-label" for="currentmonth">
       Current month CDR
    </label>
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
});
$('#ImportFormAjax').on('submit', function(e) {
 	e.preventDefault();
    formSubmit("ImportFormAjax");
});
</script>