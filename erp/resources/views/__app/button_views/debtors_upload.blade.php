<div class="container p-4" >
{!! Form::open(array("url"=> "debtors_file_upload", "class"=>"form-horizontal","files" => true , "parsley-validate"=>"","novalidate"=>" ","id"=> "ImportFormAjax")) !!}		
<p>
Goto https://www.accountability.co.za/index.php?c=m_my_account/m_my_account_form_history
<br>
    Form A and B File = FORMS -> EXTRACT 
<br>
    Small and Large Collections File = SMALL COLLECTION REGISTRATION -> EXTRACT 
</p>
<div class="row" >
    <label for="Import" class=" control-label col-md-3 text-left"> Form A and B File </label>
    <div class="col-md-9">
        <input  name="form_ab" id="form_ab" type="file" aria-label="files" />
    </div>
</div>
<!--
<div class="row" >
    <label for="Import" class=" control-label col-md-3 text-left"> Small and Large Collections File </label>
    <div class="col-md-9">
        <input  name="handover" id="handover" type="file" aria-label="files" />
    </div>
</div>
-->

<div class="row text-right mt-2" >
<div class="col" >
<button lang="en" type="submit"  class="btn btn-primary float-right mr-2" ref="button">
Submit
</button>
</div>
</div>

{!! Form::close() !!}

<div id="debtor_import_result"></div>
</div>
<div class="container p-4" >
<div class="row"><p id="debtor_import_result"></p></div>
</div>

<script type="text/javascript">
function display_debtor_upload_result(data){
    //console.log('display_debtor_upload_result');
    //console.log(data);
    $("#debtor_import_result").html(data.update_result);
}
$(document).ready(function() {
 
    
    var uploadObj = new ej.inputs.Uploader({
        autoUpload: false,
        multiple: false,
    });
    uploadObj.appendTo("#form_ab");
    /*
    var uploadObj = new ej.inputs.Uploader({
        autoUpload: false,
        multiple: false,
    });
    uploadObj.appendTo("#handover");
    */
});
$('#ImportFormAjax').on('submit', function(e) {
	e.preventDefault();
    formSubmit("ImportFormAjax",display_debtor_upload_result);
});


</script>