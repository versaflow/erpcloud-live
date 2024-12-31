@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
@endif

@section('content')
<div class="col-lg-12 control-section">
    <div class="content-wrapper mt-4">
    	@if($read_only)
        
      
                        <div class="row">
                            <div class="col-md-12" style="font-size:12px">
                                {!! $process !!}
                            </div>
                        </div>
                   
    	@else
        {!! Form::open(array("url"=> "reports_process", "class"=>"form-horizontal","id"=> "reports_process")) !!}	
        <input type="hidden" name="id" value="{{$id}}" />
        <input type="hidden" name="process_field" value="{{$process_field}}" />
        <input type="hidden" name="report_connection" value="{{$report_connection}}" />
     
                <div class="row">
                    <div class="col-md-12">
                        <textarea id='process' name='process' class='tinymce'>{{$process}}</textarea>
                    </div>
                </div>
                   
        @if(!request()->ajax())
        <div class="card mt-4" style="background: none;border: none;">
            <div class="content-wrapper" >
                <div class="form-group" style="float: right;">
                    <button type="submit" id="submitbtn" style="float:right" class="e-btn e-primary">Submit</button>
                </div>
            </div>
        </div>
        @endif
        {!! Form::close() !!}
        @endif
    </div>
</div>
@endsection

@push('page-scripts')


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
			@if($read_only)
            readonly : 1,
            @endif
			setup: function (editor) {
    			editor.on('init', function(args) {
                    this.execCommand("fontName", false, "tahoma");
                    this.execCommand("fontSize", false, "12px");
    			})
    			/*
                ed.ui.registry.addButton('mybutton', {
                    text: '{{$title}}',
                    onAction: function () {
                   
                    }
                });
                */
			},
		});
	});

$('#reports_process').on('submit', function(e) {
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
    formSubmit("reports_process");
});

</script>

@endspush

@push('page-styles')
	      
<style>
</style>
@endpush