@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
	
@endif

@section('content')
<div class="container">
@if($doc_id)
<div class="row mt-3 mb-3">
<div class="col">
<div class="k-widget k-button-group">
<a id="document_email_btn" href="{{ url('/email_form/documents/'.$doc_id) }}" class="btn btn-default" data-target="form_modal"> Email</a>
<a id="document_download_btn" class="btn btn-default" href="document_download/{{$file}}" target="_blank">Download</a>
</div>    
</div>    
</div>  
@endif
<div class="row mt-3 mb-3">
<div class="col">
    <object type="application/pdf" data="{{ url($pdf) }}" width="100%"  style="height: 100vh;">No Support</object>
</div>    
</div>    
</div>
@endsection