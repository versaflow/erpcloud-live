
<table class="table">
	<tr>
		<th></th>
		<th>Same day debit orders</th>
		<th>Two day debit orders</th>
	</tr>
	<tr>
		<td></td>
		<td>Sameday – Sameday batch upload (ideal for once off collection where the time the recipient is debited, is not important)</td>
		<td>TwoDay – Dated debit order batch upload (ideal for recurring billing)</td>
	</tr>
	<tr>
		<td><b>Must be loaded and authorized</b>
on Netcash before</td>
		<td>	10:59 on the action date (Mon-Fri) -or
23:59 one (1) business day prior
to the action date for Saturday service</td>
		<td>23:59 two (2) business days prior to action date</td>
	</tr>
</table>
@if(empty($provision_id))
{!! Form::open(array("url"=> "debit_order_create", "class"=>"form-horizontal","id"=> "debit_order_create")) !!}	
@endif
<input type="hidden" id="limit_id" name="limit_id" value="{{$limit_id}}">
<div class="row">
<div class="col-md-3">
<label style="font-weight:bold" for="action_date">Action Date</label> 
</div>
<div class="col">
<input id="action_date" />
</div>
</div>

<div class="row">
<div class="col-md-3">
<label style="font-weight:bold" for="instruction">Instruction</label> 
</div>
<div class="col">
<input id="sameday" type="radio" value="Sameday"/>
<input id="twoday" type="radio" value="Twoday"/>
@if(!empty($provision_id))
<input id="processed" type="radio" value="Processed"/>
@endif
</div>
</div>

@if(empty($provision_id))
{!! Form::close() !!}
@endif

<script>
	var radiobutton = new ej.buttons.RadioButton({ label: 'Sameday', name: 'instruction',checked: true});
	radiobutton.appendTo('#sameday');
	
	radiobutton = new ej.buttons.RadioButton({ label: 'Twoday', name: 'instruction'});
	radiobutton.appendTo('#twoday');
	
	@if(!empty($provision_id))
	radiobutton = new ej.buttons.RadioButton({ label: 'Debit Order already authorized.', name: 'instruction'});
	radiobutton.appendTo('#processed');
	@endif
	
	var action_date = new ej.calendars.DatePicker({
		format: 'yyyy-MM-dd',
		@if(!empty($limit_id))
		value: '{!! date("Y-m-d") !!}',
		@else
		value: '{!! date("Y-m-d",strtotime("+5 days")) !!}',
		@endif
	    placeholder: 'Action Date',
	});
	action_date.appendTo('#action_date');
	

$('#debit_order_create').on('submit', function(e) {
	e.preventDefault();
   formSubmit("debit_order_create");
   
});
</script>

<style>
	.stepbox{
		background-color: #fbfbfb;
		padding: 2%;
		margin-top:3%;
		margin-bottom:3%;
		box-shadow: 0 0 0.2cm rgba(0,0,0,0.3);
		min-height: 300px;
		min-width: 500px;
	}
	.e-tab .e-content > .e-item{
		width: 100%;
	}
	.e-tab.e-vertical-tab .e-content {
	border-left: 2px solid gray;
	}
	.e-tab.e-fill .e-tab-header.e-vertical.e-vertical-left {
    border-right: none;
}
.e-tab.e-fill .e-tab-header .e-toolbar-item.e-active .e-tab-wrap {
    background: gray;
}
.table td, .table th {
    padding-left: 0;
}
</style>