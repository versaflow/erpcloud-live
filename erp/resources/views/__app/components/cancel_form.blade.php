{!! Form::open(array("url"=> $menu_route."/cancelform", "class"=>"form-horizontal","files" => true , "parsley-validate"=>"","novalidate"=>" ","id"=> "cancelForm")) !!}		
<input type="hidden" name="id" value="{{$id}}" />

<div class="row " >
    <label for="delete_reason" class=" control-label col-md-3 text-left">Reason </label>
    <div class="col-md-9">
        <select name="delete_reason" id="delete_reason" class="form-control" required="required"/>
        <option value="Not Interested">Not Interested</option>
        <option value="Cannot contact">Cannot contact</option>
        <option value="Bad Debt">Bad Debt</option>
        <option value="Company Downsizing">Company Downsizing</option>
        <option value="Poor Support">Poor Support</option>
        <option value="Requested by Client">Requested by Client</option>
        </select>
    </div>
</div>

@if(is_manager())


<div class="row" >
    <label for="manager_delete" class=" control-label col-md-3 text-left">Delete Immediately </label>
    <div class="col-md-9">
        <input name="manager_delete" id="manager_delete" type="checkbox" value="1" >
    
    </div>
</div>
@endif
	@if(!empty(request()->tab_load))
<div ref="component" class="field form-group has-feedback formio-component formio-component-button formio-component-submit float-right mr-2 form-group" >
<button lang="en" type="submit"  class="btn btn-primary ui button primary float-right mr-2" ref="button">
Submit
</button>
</div>
@endif
{!! Form::close() !!}

<script type="text/javascript">
$(document).ready(function() {
    
    
   
    
    var uploadObj = new ej.inputs.Uploader({
        autoUpload: false,
        multiple: false,
    });
    uploadObj.appendTo("#cancellation_document");
    
    @if(is_manager())
    var checkbox = { label: 'Cancel without proof of cancellation.', change: function(e){
       if(e.checked){
           $('.files_row').hide();
       }else{
           $('.files_row').show();
       }
    }};
	var manager_override = new ej.buttons.Switch(checkbox);
	manager_override.appendTo("#manager_override");
	
    var checkbox = { label: '>Delete Immediately.' };
	var manager_delete = new ej.buttons.Switch(checkbox);
	manager_delete.appendTo("#manager_delete");
	@endif
    
});
$('#cancelForm').on('submit', function(e) {
	e.preventDefault();
    formSubmit("cancelForm");
});
</script>