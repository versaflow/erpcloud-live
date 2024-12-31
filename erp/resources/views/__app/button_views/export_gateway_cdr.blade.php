{!! Form::open(array("url"=> "exportcdrbygateway", "class"=>"form-horizontal","files" => true , "parsley-validate"=>"","novalidate"=>" ","id"=> "exportcdrbygateway")) !!}
<input type="hidden" name="gateway_uuid" value="{{$gateway_uuid}}"/>
<div class="card mt-2" >
    <div class="card-body">
    <div class="col">
        <label style="font-weight:bold" for="send_to">CDR Tables</label> <br>
        <input name="cdr_tables" id="cdr_tables" />
    </div>
</div>
</div>
{!! Form::close() !!}

<script type="text/javascript">
$(document).ready(function() {
    cdr_tables = new ej.dropdowns.MultiSelect({
		dataSource: {!! json_encode($cdr_tables) !!},
		htmlAttributes: {name: 'cdr_tables[]'}, 
		placeholder: "CDR Tables",
	});
    cdr_tables.appendTo("#cdr_tables");
});
$('#exportcdrbygateway').on('submit', function(e) {
	e.preventDefault();
    formSubmit("exportcdrbygateway");
});
</script>