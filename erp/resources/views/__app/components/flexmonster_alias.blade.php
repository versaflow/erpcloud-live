{!! Form::open(array("url"=> "report_config_alias_save", "class"=>"form-horizontal","id"=> "report_config_alias_save")) !!}	
<input type="hidden" name="id" value="{{ $id }}">
<input type="hidden" name="report_connection" value="{{ $connection }}">

<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
                 Set Field Caption
            </div>
            <div class="card-body">
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="alias_field"> Field </label>
                    </div>
                    <div class="col-md-9">
                        <input id="alias_field" />
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="alias_field_caption"> Caption </label>
                    </div>
                    <div class="col-md-9">
                        <input id="alias_field_caption" />
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>


{!! Form::close() !!}

<script type="text/javascript">
$(document).ready(function() {
   
	alias_field = new ej.inputs.TextBox({
		placeholder: "Report Field",
		value: '{!! $alias_field !!}',
		readonly: true,
	});
	alias_field.appendTo("#alias_field");
	
	alias_field_caption = new ej.inputs.TextBox({
		placeholder: "Caption",
		value: '{!! $alias_field_caption !!}',
	});
	alias_field_caption.appendTo("#alias_field_caption");
});


$('#report_config_alias_save').on('submit', function(e) {
    e.preventDefault();
    formSubmit("report_config_alias_save");
});
</script>