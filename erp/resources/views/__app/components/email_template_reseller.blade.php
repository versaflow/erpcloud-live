 @if(empty($hide_form_tags))
{!! Form::open(array('url'=> 'email_template_reseller_save', 'class'=>'form-horizontal','id' => 'emailHtmlForm' , 'parsley-validate'=>'','novalidate'=>' ')) !!}
    @endif
    <div class"container mt-2" style="padding:0 20px;">
	@if(!empty(request()->tab_load))
	<div class="row text-right m-0">
	<div ref="component" class="col p-0 mt-2" >
	<button lang="en" type="submit"  class="btn btn-primary ui button primary float-right mr-2" ref="button">
	Submit
	</button>
	</div>
	</div>
	@endif
  
    <div class="row mt-4">
	    <div class="col-sm-12 form-group">
	    <br>
	    <textarea class='ckeditor' name='email_html' id='email_html' rows="10">{{ $email_html }}</textarea>
	    </div>
    </div>
   

     @if(empty($hide_form_tags))
     </form>
     @endif

<script>
	
    
	var editors = {};	
$(document).ready(function() {

	var form = $('#emailHtmlForm'); 
	
	if( $('#email_html').length ){
		var textarea = $('#email_html').val();
			  var textareaId = 'email_html';
	 // console.log($("#email_html").val());
        ClassicEditor
            .create(document.querySelector("#" + textareaId), { 
            	
            	updateSourceElementOnDestroy: true,
				htmlSupport: {
				allow: [
				{
				name: /.*/,
				attributes: true,
				classes: true,
				styles: true
				}
				]
				}
            })
            .then(editor => {
editors[textareaId] = editor;
                console.log(editor);
            })
            .catch(error => {
                console.error(error);
            });
	}

});	



@if(empty($hide_form_tags))
$('#emailHtmlForm').on('submit', function(e) {
	e.preventDefault();
	try{
		$("textarea.ckeditor").each(function(i){
			var field_id = $(this).attr('id');
	            console.log(editors);
	        if (field_id && editors[field_id]) {
	            console.log('saving' +field_id);
	             editors[field_id].updateSourceElement();
	        }
		});
	}catch(e){
    console.error(e);
	}
	formSubmit('emailHtmlForm');
});
@endif

</script>
<style>



.control-label{font-weight:bold;}</style>