{!! Form::open(array("url"=> "cdr_archive_table", "class"=>"form-horizontal","files" => true , "parsley-validate"=>"","novalidate"=>" ","id"=> "cdr_archive_table")) !!}

<div class="card mt-2" >
    <div class="card-body">
    <div class="col">
        <label style="font-weight:bold" for="send_to">Set CDR Table</label> <br>
        <input name="cdr_table" id="cdr_table" />
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
    cdr_table = new ej.dropdowns.DropDownList({
		dataSource: {!! json_encode($cdr_tables) !!},
		fields: {text: 'table_label',value: 'table_name'},
		placeholder: "CDR Table",
	});
    cdr_table.appendTo("#cdr_table");
});
$('#cdr_archive_table').on('submit', function(e) {
	e.preventDefault();
    formSubmit("cdr_archive_table");
});
</script>