@extends(( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' )

@if(!request()->ajax())
	
	
@endif

@section('content')
<div class="container-fluid p-4">
<div class="btn-group">

<button id="email_statement_btn" class="btn btn-sm">Email Statement</button>
<button id="email_full_statement_btn" class="btn btn-sm">Email Complete Statement</button>
<button id="email_reversal_statement_btn" class="btn btn-sm">Email Statement with reversals</button>
<button id="email_reversal_full_statement_btn" class="btn btn-sm">Email Complete Statement with reversals</button>
@if($show_rand_statement)
<button id="zar_statement_btn" class="btn btn-sm">Rand Statement</button>
@endif
</div>
<div>
<br><div style="background: transparent url('. {{ public_path().'/assets/loading.gif' }} .');background-position: center;background-repeat: no-repeat;">
<object height="1250px" width="100%" type="application/pdf" data="{{ $file_url }}">
<param value="aaa.pdf" name="src"/>
<param value="transparent" name="wmode"/>
</object>
</div>
</div>
</div>
@endsection

@push('page-scripts')

<script>
	$(document).off('click', '#email_statement_btn').on('click', '#email_statement_btn', function() {
	    gridAjax("/statement_email/{{$account_id}}");
	});
	$(document).off('click', '#email_full_statement_btn').on('click', '#email_full_statement_btn', function() {
	    gridAjax("/statement_email/{{$account_id}}/1");
	});
	$(document).off('click', '#email_reversal_statement_btn').on('click', '#email_reversal_statement_btn', function() {
	    gridAjax("/reversal_statement_email/{{$account_id}}");
	});
	$(document).off('click', '#email_reversal_full_statement_btn').on('click', '#email_reversal_full_statement_btn', function() {
	    gridAjax("/reversal_statement_email/{{$account_id}}}/1");
	});
	@if($show_rand_statement)
	$(document).off('click', '#zar_statement_btn').on('click', '#zar_statement_btn', function() {
		//console.log("statement_zar_pdf/{{$account_id}}");
	    viewDialog("statement_zar_pdf","/statement_zar_pdf/{{$account_id}}");
	});
	@endif
</script>
@endpush