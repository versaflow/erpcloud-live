
{!! Form::open(array('url'=> 'supportboard_send_reply', 'class'=>'form-horizontal','id' => 'supportBoardMessage' , 'parsley-validate'=>'','novalidate'=>' ')) !!}
   


<div class="container">

<input type="hidden" name="conversation_id" id="conversation_id" value="{{ $conversation_id }}" />
<div class="row mb-0">
	<div class="col">
		<div class="relative w-full text-left">
			<label class="flex text-sm not-italic items-center font-medium text-gray-800 whitespace-nowrap justify-between mb-1 mt-2">
				<div>Message <span class="text-sm text-red-500"> * </span></div>
				</label>
				<div class="flex flex-col">
					<div class="relative rounded-md shadow-sm font-base">
						<input type="text" id="message" name="message"   autocomplete="no" class="font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
					</div>
				</div>
		</div>
	</div>

</div>
<div class="row mb-0">
	<div class="col">
		<div class="relative w-full text-left">
			<label class="flex text-sm not-italic items-center font-medium text-gray-800 whitespace-nowrap justify-between mb-1 mt-2">
				<div>Attachments <span class="text-sm text-red-500"> * </span></div>
				</label>
				<div class="flex flex-col">
					
        			<input  name="attachments" id="attachments" type="file" aria-label="files" />
				</div>
		</div>
	</div>

</div>

</div>
</form>

<script>
var attachments = new ej.inputs.Uploader({
        autoUpload: false,
        multiple: true,
    });
    attachments.appendTo("#attachments");
    
    message = new ej.inputs.TextBox({
    value:'{{ $message }}',
    });
    message.appendTo("#message");
    
 
$('#supportBoardMessage').on('submit', function(e) {
	e.preventDefault();

	formSubmit('supportBoardMessage');
});


</script>
<style>



.control-label{font-weight:bold;}</style>