{!! Form::open(array("url"=> "pricelist_send", "class"=>"form-horizontal","files" => true , "parsley-validate"=>"","novalidate"=>" ","id"=> "SendFormAjax")) !!}		
<div class="card mt-2" >
    <div class="card-body">
    <div class="col">
        <label style="font-weight:bold" for="send_to">Send Pricelist To</label> <br>
        <input name="send_to" id="send_to" />
    </div>
</div>
</div>
<input type="hidden" name="pricelist_id" value="{{ $pricelist_id }}" />
{!! Form::close() !!}

<script type="text/javascript">
$(document).ready(function() {
    send_to = new ej.dropdowns.MultiSelect({
		dataSource: {!! json_encode($send_to) !!},
		htmlAttributes: {name: 'send_to[]'}, 
		placeholder: "Recipients",
	 	allowCustomValue: true,
	});
    send_to.appendTo("#send_to");
});
$('#SendFormAjax').on('submit', function(e) {
	e.preventDefault();
    formSubmit("SendFormAjax");
});
</script>