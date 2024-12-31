{!! Form::open(array("url"=> "pdf_edit", "class"=>"form-horizontal","id"=> "pdfEdit")) !!}	
<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
               Letter of Demand
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <textarea id='letter' name='letter' class='tinymce'>{{$letter}}</textarea>
                		
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
               Cancellation Letter
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <textarea id='cancellation' name='cancellation' class='tinymce'>{{$cancellation}}</textarea>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

{!! Form::close() !!}

<script type="text/javascript">

	$("textarea.tinymce").each(function(i){
		var textarea = $(this).val();
		tinymce.init({
			selector: "#"+$(this).attr('id'),  // change this value according to your HTML
			theme: 'silver',
			plugins: 'print autoresize code image print preview searchreplace autolink directionality visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern help',
			toolbar1: 'code | image | formatselect | bold italic underline forecolor backcolor | link | alignleft aligncenter alignright alignjustify  | numlist bullist outdent indent  | removeformat | print',
			contextmenu: "",
			image_advtab: true,
			menubar: "",
			valid_children : '+body[style]',
			height : "200",
			visual : false,
			relative_urls : false,
			remove_script_host : false,
			convert_urls : true,
			force_br_newlines : true,
			force_p_newlines : false,
			entity_encoding : "raw",
			forced_root_block : "",
			convert_newlines_to_brs : false,
            remove_linebreaks : true,  
				cleanup : true,
				verify_html : true,
				plugins: "paste",
	    		paste_as_text: true, 
			setup: function (ed) {
			ed.on('init', function(args) {
			});
			},
		});
	});

$('#pdfEdit').on('submit', function(e) {
    e.preventDefault();
    	try{
		$("textarea.tinymce").each(function(i){
			field_id = $(this).attr('id');
			if(field_id){
				var content = tinyMCE.get(field_id).getContent();
				
				$('#'+field_id).val(content);
			}
		});
	}catch(e){
		
	}
    formSubmit("pdfEdit");
});
</script>