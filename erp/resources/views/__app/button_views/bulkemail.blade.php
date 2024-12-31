@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
    
	
@endif

@section('content')

<form id="sendNewsletter" action="/bulkemail_send" method="POST">
	<div id="page-wrapper" class="container-fluid mx-auto">
		<div class="form-group">
			<input name="from_email" id="from_email" />
		</div>
		
		<div class="form-group">
			<input name="from_company" id="from_company" />
		</div>
		
		<div class="form-group">
			<input name="subject" id="subject" />
		</div>
	   <div class="row">
		<div class="col form-group">
			<select name="groups" id="groups">
			@foreach($groups as $group)
			<option>{{$group}}</option>
			@endforeach 
			</select>
		</div> 
		
		<div class="col form-group" id="status_div">
			<select name="status" id="status">
			@foreach($statuses as $status)
			<option>{{$status}}</option>
			@endforeach 
			</select>
		</div>  
		
	
		</div> 
		
		<hr>
		
	
		<input type="hidden" value="{{ $notification_id }}" name="notification_id" /> 
		<div id="progressdiv" class="text-center">
		<button id="progressbtn"></button>
		</div>
	</div> 
</form>
<div id="preview" class="mx-4">{!! $preview !!}</div>
@endsection
@push('page-scripts')

<script type="text/javascript">
$(document).ready(function() { 
	$("#progressdiv").hide();

		// Initialize ProgressButton component
	progressBtn = new ej.splitbuttons.ProgressButton({
		content: 'Progress', 
		enableProgress: true, 
		isPrimary: true,
		duration: 0,
		begin: (args) => {
		    progressBtn.content = 'Progress ' + args.percent + '%';
		},
		end: (args) => {
		    progressBtn.content = 'Progress ' + args.percent + '%';
		},
	cssClass: 'e-hide-spinner'});
	
	// Render initialized ProgressButton.
	progressBtn.appendTo('#progressbtn');
	
	
	from_email = new ej.inputs.TextBox({
		placeholder: "From Email ",
		floatLabelType: 'Auto',
		value: '{!! $from_email !!}'
	});
	from_email.appendTo("#from_email");
	
	from_company = new ej.inputs.TextBox({
		placeholder: "From Company ",
		floatLabelType: 'Auto',
		value: '{!! $from_company !!}'
	});
	from_company.appendTo("#from_company");
	
	subject = new ej.inputs.TextBox({
		placeholder: "Subject ",
		floatLabelType: 'Auto',
		value: '{!! $newsletter->name !!}'
	});
	subject.appendTo("#subject");
	
	groups = new ej.dropdowns.DropDownList({
		placeholder: "Groups",
		ignoreAccent: true,
		allowFiltering: true,
		filterBarPlaceholder: 'Type Group Name',
		change: function(e){
			group_type_change();
		},
		created: function(e){
			group_type_change();
		}
	});
	groups.appendTo('#groups');
	
	statuses = new ej.dropdowns.DropDownList({
		placeholder: "Status",
		ignoreAccent: true,
		allowFiltering: true,
		filterBarPlaceholder: 'Type Status Name',
	});
	statuses.appendTo('#status');
	
	lead_status = new ej.dropdowns.DropDownList({
		placeholder: "Lead Status",
		filterBarPlaceholder: 'Select Lead status',
	});
	lead_status.appendTo('#lead_status');
	


});

function group_type_change(){
	var val = groups.value;
	if(val == "Admins"){
		$("#lead_status_div").hide();
		$("#status_div").hide();
	}else if(val == "Leads"){
		$("#lead_status_div").show();
		$("#status_div").hide();
	}else{
	
		$("#lead_status_div").hide();
		$("#status_div").show();
	}
}


function getProgress(){
	$.get('bulkemailprogress', function(data) {
		progressBtn.content = 'Progress ' + data + '%';
		if(parseInt(data) == 100){
			
		}
		
		progressBtn.dataBind();
	});
}

$('#sendNewsletter').on('submit', function(e) {
	e.preventDefault();

	$("#progressdiv").show();
	progressBtn.start();
	setInterval(getProgress, 1000);
	formSubmit('sendNewsletter');
});						
</script>
@endpush
@push('page-styles')

<style>
#page-wrapper{
	background-color: #fbfbfb;
	padding: 2%;
	margin-top:3%;
	margin-bottom:3%;
	box-shadow: 0 0 0.2cm rgba(0,0,0,0.3);
}
.main-body-cell{    margin: 0 auto;}
</style>
@endpush