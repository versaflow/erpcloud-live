@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
    
	
@endif

@section('content')

@php $conversations = $conversations->take(10); @endphp

@foreach($conversations as $c)
    <code>{{ print_r($c) }}</code><br><br>
@endforeach

@endsection
@push('page-scripts')

<script type="text/javascript">
/*
 supportboard_view_data
 endpoint for supportboard data - supportboard_view_data_ajax
*/

/* 
    1. copy the softui pages html 
    2. build the left data from an ajax request to supportboard_view_data_ajax
    3. build messages the same way - ajax to supportboard_view_data_ajax
*/
</script>
@endpush
@push('page-styles')

<style>

</style>
@endpush