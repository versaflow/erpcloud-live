
@if(!empty($formNote))
	<div class="row well"><p style="color:color: #333;font-size: 13px;"> {!! nl2br($formNote) !!} </p></div>
@endif

{!! $view_entry !!}

<script type="text/javascript">

$(document).ready(function() {
	var tabObj = new ej.navigations.Tab({
		items: [
			@foreach($tab_config as $tab)
				{header: { 'text': '{{ $tab["title"] }}' }, content: '#{{ $tab["id"] }}'},
			@endforeach
		]
	});
	tabObj.appendTo('#{{ $pageModule }}Tab');
});

</script>