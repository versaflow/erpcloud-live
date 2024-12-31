
<div class="container-fluid">



<div id="menuTabs"></div>
@foreach($locations as $i => $location)
@php $location = str_replace(' ','',$location); @endphp
<div class="row" id="{{$location}}" @if($i!=0) style="display:none" @endif>
	<div class="col-md-12">
		@include('__app.components.menu_sort',['location'=>$location,'module_id'=>$module_id])
	</div>
</div>
@endforeach

</div>

<script>
$(document).off('click', '#allMenus').on('click', '#allMenus', function() {
	$.ajax({
		url:'menu_sort_levels',
		success:function(data){
			@foreach($locations as $location)
			window['{{$location}}treeObj'].refresh();
			@endforeach
		}
	});
});
$(document).off('click', '#adminMenus').on('click', '#adminMenus', function() {
	$.ajax({
		url:'menu_sort_levels/admin',
		success:function(data){
			@foreach($locations as $location)
			window['{{$location}}treeObj'].refresh();
			@endforeach
		}
	});
});
$(document).off('click', '#resellerMenus').on('click', '#resellerMenus', function() {
	$.ajax({
		url:'menu_sort_levels/reseller',
		success:function(data){
			@foreach($locations as $location)
			window['{{$location}}treeObj'].refresh();;
			@endforeach
		}
	});
});
$(document).off('click', '#customer_menus').on('click', '#customer_menus', function() {
	$.ajax({
		url:'menu_sort_levels/customer',
		success:function(data){
			@foreach($locations as $location)
			window['{{$location}}treeObj'].refresh();
			@endforeach
		}
	});
});
$(document).off('click', '#createMenu').on('click', '#createMenu', function() {
  sidebarform('menu_create','/menu_manager/edit', 'Create Menu');
});
$(document).off('click', '#listMenu').on('click', '#listMenu', function() {
  viewDialog('menu_list','/menu_manager', 'Menu List', '95%', '95%'); 
});
	$(document).ready(function() {
	var menuTabs = new ej.navigations.Tab({
		items: [
			@foreach($locations as $location)
				{header: { 'text': '{{ ucfirst($location)." Menu" }}' }, content: '#{{ str_replace(' ','',$location) }}'},
			@endforeach
		]
	});
	menuTabs.appendTo('#menuTabs');
});

</script>
<style>
	#menuTabs .e-treeview .e-list-text{
	font-size:13px !important;
	}
	#menuTabs .e-treeview > .e-ul {
		margin: 0;
		padding: 0;
	}
	#menuTabs .e-treeview .e-list-item {
		padding: 0;
	}
</style>