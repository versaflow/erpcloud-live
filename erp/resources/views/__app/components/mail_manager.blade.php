@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
	
@endif
@php

$grid_buttons = ErpButtons::getHTML($grid_id,$menu_id);
if(empty(request()->all()) ||count(request()->all()) == 0){
    $request_get = '';
}else{
    $request_get = http_build_query(request()->all());
}
@endphp
@section('content')

@ensection