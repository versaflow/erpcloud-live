@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax() && empty($button_iframe))
	
	
@endif

@section('content')
@if(!empty($iframe_help))
<div class="container-fluid">
<div class="row pageheader" style="margin-bottom:-10px">
<div class="col">
<p style="font-size: 0.8rem;">{!! $iframe_help !!}</p>
</div>
</div>
</div>
@endif
<iframe src='{{ $iframe_url }}' width="100%" frameborder="0px" height="800px" onerror="alert('Failed')" style="margin-bottom:-5px;"><!-- //required for browser compatibility --></iframe> 
@endsection