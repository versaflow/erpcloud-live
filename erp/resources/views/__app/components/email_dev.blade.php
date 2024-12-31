 @if(empty($hide_form_tags))
{!! Form::open(array('url'=> 'email_send', 'class'=>'form-horizontal','id' => 'emailsendFormAjax' , 'parsley-validate'=>'','novalidate'=>' ')) !!}
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
	
	<div class="row mb-0">
		<div class="col">
			<div class="relative w-full text-left">
				<label class="flex text-sm not-italic items-center font-medium text-gray-800 whitespace-nowrap justify-between mb-1 mt-2">
					<div>To Email <span class="text-sm text-red-500"> * </span></div>
					</label>
					<div class="flex flex-col">
						<div class="relative rounded-md shadow-sm font-base">
							<input type="text" id="emailaddress" name="emailaddress" value="{{ $to_email }}" autocomplete="no" class="font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
						</div>
					</div>
			</div>
		</div>
	
	</div>
	
	<div class="row mb-0">
		<div class="col">
			<div class="relative w-full text-left">
				<label class="flex text-sm not-italic items-center font-medium text-gray-800 whitespace-nowrap justify-between mb-1 mt-2">
					<div>CC Email <span class="text-sm text-red-500"> * </span></div>
					</label>
					<div class="flex flex-col">
						<div class="relative rounded-md shadow-sm font-base">
							<input type="text" id="ccemailaddress" name="ccemailaddress" value="{{ $cc_email }}" autocomplete="no" class="font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
						</div>
					</div>
			</div>
		</div>
		<div class="col">
			<div class="relative w-full text-left">
				<label class="flex text-sm not-italic items-center font-medium text-gray-800 whitespace-nowrap justify-between mb-1 mt-2">
					<div>BCC Email</div>
					</label>
					<div class="flex flex-col">
						<div class="relative rounded-md shadow-sm font-base">
							<input type="text" id="bccemailaddress" name="bccemailaddress"  value="{{ $bcc_email }}" class="font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
						</div>
					</div>
			</div>
		</div>
	</div>
	
	<div class="row mb-0">
		<div class="col">
			<div class="relative w-full text-left">
				<label class="flex text-sm not-italic items-center font-medium text-gray-800 whitespace-nowrap justify-between mb-1 mt-2">
					<div>Subject <span class="text-sm text-red-500"> * </span></div>
					</label>
					<div class="flex flex-col">
						<div class="relative rounded-md shadow-sm font-base">
							<input type="text" id="subject" name="subject"  value="{{ $subject }}" autocomplete="no" class="font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
						</div>
					</div>
			</div>
		</div>
		<div class="col">
			<div class="relative w-full text-left">
				<label class="flex text-sm not-italic items-center font-medium text-gray-800 whitespace-nowrap justify-between mb-1 mt-2">
					<div>Templates</div>
					</label>
					<div class="flex flex-col">
						<div class="relative rounded-md shadow-sm font-base">
							<input type="text" id="template" name="template" class="font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
						</div>
					</div>
			</div>
		</div>
	</div>
	
	<div class="row mb-0 mt-2" id="attachments_div">
		<div class="col">
			<div class="relative w-full text-left">
				<label class="flex text-sm not-italic items-center font-medium text-gray-800 whitespace-nowrap justify-between mb-1 mt-2">
					<div>Attachments </div>
					</label>

    
 
    
	@if(!empty($attachments) && count($attachments) > 1 )
		<div class="row" id="attachments_content">
		<div class="col ">
		@foreach($attachments as $a)
			<a class="pl-0" href="{{ attachments_url().$a }}" target="_blank" >{{ $a }}</a><br>
		@endforeach
		</div>
		</div>
	@elseif(!empty($attachment))
	    <div class="row" id="attachments_content">
		    <div class="col ">
		     <a class="pl-0" href="{{ attachments_url().$attachment }}"  target="_blank" id="attachment_link">{{ $attachment }}</a>
		    </div>
	    </div>
    @else
	    <div class="row" id="attachments_content">
	    </div>
    @endif
    
			</div>
		</div>
	</div>
    
    @if(!empty($attachments) && (count($attachments) > 0 && empty($attachment) || count($attachments) > 1 ))
		<input type="hidden" name="attachment" value="{{ implode(',',$attachments) }}" id="attachment"/>
	@else
		<input type="hidden" name="attachment" value="{{ $attachment }}" id="attachment"/>
    @endif
    
    
    <input type="hidden" name="notification_id" value="{{$email_id}}"/>
	
    @if($use_accounts_email)
    <input type="hidden" name="use_accounts_email" value="1"/>
    @endif
    @if($activation_email)
    <input type="hidden" name="activation_email" value="1"/>
    @endif
   
   
   
	<div class="row mb-0 mt-2">
		<div class="col">
			<div class="relative w-full text-left">
				<label class="flex text-sm not-italic items-center font-medium text-gray-800 whitespace-nowrap justify-between mb-1 mt-2">
					<div>Message </div>
					</label>
					<div class="flex flex-col">
	    <textarea class='ckeditor' name='messagebox' id='messagebox' rows="10">{{ $message }}</textarea>
					</div>
			</div>
		</div>
	</div>
  
   
    @if(!empty($provision_id))
    	<input type="hidden" name="provision_id" value="{{ $provision_id }}" />
    @endif
    <input type="hidden" name="account_id" id="account_id" value="{{ $account_id }}" />
    <input type="hidden" name="partner_company" value="{{ $partner_company }}" />
    <input type="hidden" name="partner_email" value="{{ $partner_email }}" />
    <input type="hidden" name="customer_type" value="{{ $customer_type }}" />
    <input type="hidden" name="notification_id" id="notification_id" value="{{$notification_id}}" />
    <input type="hidden" name="notification_type" id="notification_type" value="{{$notification_type}}" />
    </div>

     @if(empty($hide_form_tags))
     </form>
     @endif

<script>
	$("#form_toolbar_submit_approve_btn").addClass('d-none');
	
		$("#form_toolbar_submit_email_btn").addClass('d-none');
		$("#form_toolbar_submit_btn").text('Send');
		
	emailaddress_ds =  {!! json_encode($accounts) !!};
	//initiates the component
	emailaddress = new ej.dropdowns.ComboBox({
	    //bind the data manager instance to dataSource property
	    dataSource: emailaddress_ds,
    	value:'{{ $to_email }}',
	    //map the appropriate columns to fields property
	    fields: { value: 'email',text: 'company' },
	    //set the placeholder to ComboBox input
	    placeholder:"Select a company",
	    //sort the resulted items
	    sortOrder: 'Ascending',
        // enabled the ignoreAccent property for ignore the diacritics
        ignoreAccent: true,
        // set true for enable the filtering support.
        allowFiltering: true,
        //set the value to itemTemplate property
    	itemTemplate: "<span><span>${company}</span><span> <${email}> </span></span>",
		change: function(e){
		
			if(e.value > '' && e.itemData.id && e.itemData.email){
				$("#account_id").val(e.itemData.id);
				$("#emailaddress").val(e.itemData.email);
				@if(!empty($faq_id))
				$(document).ready(function() {
				load_newsletter({{$faq_id}}, 'faq');
				});
				@elseif(!empty($newsletter_id))
				$(document).ready(function() {
				load_newsletter({{$newsletter_id}}, 'newsletter');
				});
				@elseif(!empty($notification_id))
				$(document).ready(function() {
				load_newsletter({{$notification_id}}, 'notification');
				});
				@endif
			}
		
		},
		created: function(){
			e = this;
			if(e.value > '' && e.itemData.id && e.itemData.email){
				$("#account_id").val(e.itemData.id);
				$("#emailaddress").val(e.itemData.email);
				@if(!empty($faq_id))
				$(document).ready(function() {
				load_newsletter({{$faq_id}}, 'faq');
				});
				@elseif(!empty($newsletter_id))
				$(document).ready(function() {
				load_newsletter({{$newsletter_id}}, 'newsletter');
				});
				@elseif(!empty($notification_id))
				$(document).ready(function() {
				load_newsletter({{$notification_id}}, 'notification');
				});
				@endif
			}
		},
        filtering: function(e) {
		    if (e.text == '') {
		        e.updateData(emailaddress.dataSource);
		    } else {
		        var query = new ej.data.Query().select(['id', 'company', 'type', 'email']);
		        query = query.where(
		            new ej.data.Predicate('company', 'contains', e.text, true).or('email', 'contains', e.text, true)
		        );
		        e.updateData(emailaddress.dataSource, query);
		    }
		}
	});
	
	//render the component
	emailaddress.appendTo('#emailaddress');
		
		
		
	
    
    ccemailaddress = new ej.inputs.TextBox({
    value:'{{ $cc_email }}',
    });
    ccemailaddress.appendTo("#ccemailaddress");
    
    bccemailaddress = new ej.inputs.TextBox({
    value:'{{ $bcc_email }}',
    });
    bccemailaddress.appendTo("#bccemailaddress");
    
    subject = new ej.inputs.TextBox({
    value:'{{ $subject }}',
    });
    subject.appendTo("#subject");
    
  @if(!empty($attachment) )
  attbutton = new ej.buttons.Button({ cssClass: `e-link`}, '#attachment_link');
  @endif
    
    
	@if(empty($provision_id))
		@if(!empty($templates)) 
			template = new ej.dropdowns.DropDownList({
			dataSource: {!! json_encode($templates) !!},
			fields: { groupBy: 'category',text: 'name', value: 'id', type: 'type'},
	        showClearButton: true,
	        ignoreAccent: true,
	        allowFiltering: true,
	        filtering: function(e){
		        if(e.text == ''){
		        	e.updateData(template.dataSource);
		        }else{ 
			        var query = new ej.data.Query().select(['name','id','type']);
			        query = (e.text !== '') ? query.where('name', 'contains', e.text, true) : query;
			        e.updateData(template.dataSource, query);
		        }
	        },
			change: function(e){
				//console.log(e);
				//console.log('change');
				load_newsletter(template.value,template.itemData.type);
			}
			});
			template.appendTo("#template");
		@endif
	@endif
    

var editors = {};	
$(document).ready(function() {
	@if( empty($attachment) )
		$("#attachments_div").hide(); 
	@endif
	var form = $('#emailsendFormAjax'); 
	  var textareaId = 'messagebox';
//	  console.log($("#messagebox").val());
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
	/*
	if( $('#messagebox').length ){
		var textarea = $('#messagebox').val();
		tinymce.init({
			selector: "#messagebox",  // change this value according to your HTML
			theme: 'silver',
			plugins: 'fontselect print autoresize fullpage code image print preview searchreplace autolink directionality visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern help',
			toolbar1: 'code | image | formatselect | bold italic underline forecolor backcolor | link | alignleft aligncenter alignright alignjustify  | numlist bullist outdent indent  | removeformat | print',
			image_advtab: true,
			menubar: "",
			valid_children : '+body[style]',
			auto_focus : "messagebox",
			height : "400",
			relative_urls : false,
			visual : false,
			remove_script_host : false,
			convert_urls : true,
			force_br_newlines : false,
			force_p_newlines : false,
			entity_encoding : "raw",
			forced_root_block : "",
			convert_newlines_to_brs : false,
            remove_linebreaks : true,
			cleanup : true,
			verify_html : true,
			plugins: "paste code",
			content_style: "p {margin: 0}",
    		paste_as_text: false,  
			setup: function (ed) {
		        ed.on('init', function(args) {
		        });
			},
		});
		
		
	}
	*/
});	



@if(!empty($templates))	
	function load_newsletter(template, type) {
	
		$("#attachments_div").hide();
		var val = template;
		if(type != 'Helpdesk'){
		$("#notification_id").val(val);
		}
		var account_id = $("#account_id").val();
		//console.log(account_id);
		
				//console.log('/getemailtemplate/'+account_id+'/'+type+'/'+val);
		$.ajax({
			url: '/getemailtemplate/'+account_id+'/'+type+'/'+val,
			beforeSend: function(){$('.ajaxLoading').show();},
			dataType: 'json',
			success: function(data) {
				//console.log('getemailtemplate result');
				//console.log(data);
				$('.ajaxLoading').hide();
				$("#subject").val(data.subject);
				if (data.attachment){
					$("#attachment").val(data.attachment);
					var attachements_html = '';
					$.each(data.attachments,function(i,el){
					attachements_html += '<a class="pl-0" href="{{ attachments_url() }}'+el+'" id="attachment_link" target="_blank">'+el+'</a>'
					})
				
					$("#attachments_content").html(attachements_html);
					$("#attachments_div").show();
				}else{
					
					$("#attachments_div").hide();
				}
				
				//tinyMCE.activeEditor.setContent(data.msg);
				var textareaId = 'messagebox';
				
				editors[textareaId].setData(data.msg);
			}
		});
	}
	$('#template').on('change', function(e){
		//console.log(e);
		var template = $(this).val();
		load_newsletter(template);
	});
@endif
@if(empty($hide_form_tags))
$('#emailsendFormAjax').on('submit', function(e) {
	e.preventDefault();
	//tinyMCE.triggerSave();
	
	try{
		$("textarea.ckeditor").each(function(i){
			var field_id = $(this).attr('id');
	           // console.log(editors);
	        if (field_id && editors[field_id]) {
	            //console.log('saving' +field_id);
	             editors[field_id].updateSourceElement();
	        }
		});
	}catch(e){
    console.error(e);
	}

	formSubmit('emailsendFormAjax');
});
@endif

@if(!empty($faq_id))
$(document).ready(function() {
    load_newsletter({{$faq_id}}, 'faq');
});
@elseif(!empty($newsletter_id))
$(document).ready(function() {
    load_newsletter({{$newsletter_id}}, 'newsletter');
});
@elseif(!empty($load_notification_id))
//$(document).ready(function() {
  //  load_newsletter({{$load_notification_id}}, 'notification');
//});
@endif
</script>
<style>

.ckeditor a, .ck a, .ck-editor__editable a{
text-decoration: underline !important;
}

.control-label{font-weight:bold;}</style>