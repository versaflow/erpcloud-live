@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
	
@endif

@section('content')
<div id="mapsTab"></div>
<div class="container-fluid">
<div class="row" id="domains">
	<div class="col-md-12 mt-3">
		<iframe style="width: 100vw;height: 550px;position: relative;" src="/domain_search" frameborder="0" allowfullscreen="" allow="geolocation"></iframe>
	</div>
</div>
<div class="row" id="fibremap" style="display:none;">
	<div class="col-md-12 mt-3">
		<iframe style="width: 100vw;height: 550px;position: relative;" src="/axxess_map" frameborder="0" allowfullscreen="" allow="geolocation"></iframe>
	</div>
</div>
<div class="row" id="ltemap" style="display:none;">
	<div class="col-md-12">
		<iframe style="width: 100vw;height: 550px;position: relative;" src="https://vccoverage.afrigis.co.za/#/" frameborder="0" allowfullscreen="" allow="geolocation"></iframe>
	</div>
</div>
</div>
@endsection
@push('page-scripts')
<script>
	$(document).ready(function() {
	var tabObj = new ej.navigations.Tab({
		items: [
			{header: { 'text': 'Domain Registrations' }, content: '#domains'},
			{header: { 'text': 'Fibre Coverage' }, content: '#fibremap'},
			{header: { 'text': 'LTE Coverage' }, content: '#ltemap'},
		]
	});
	tabObj.appendTo('#mapsTab');
});
</script>
@endpush
@push('page-styles')
<style>
.e-tab-header{
	 background-color: #d1ebff !important;
}
</style>
@endpush