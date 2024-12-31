@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
@endif
@section('styles')
@parent
<link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
@endsection
@section('content')

<div class="custom-container items-center justify-center">
  <div class="max-h-full overflow-y-auto" id="dasboard_container">


        <div class="w-full pb-10 ">
                
            <div class="container-fluid mx-auto px-6 flex items-start justify-center">
        
                <div class="w-full mt-4 bg-white p-2">
                      <div class="k-widget k-button-group">
                                    @foreach($links as $link)
                                    <a href="{{ $link['url'] }}" class="k-button" target="_blank">{{ $link['name'] }}</a>
                                    @endforeach
                                    </div>
                </div>
            </div>
            <div aria-label="group of cards" class="container-fluid mx-auto px-6 flex items-start justify-center">
        
                <div class="w-full">
                     @foreach($iframes as $module_iframe)
                        <div class="flex flex-col lg:flex-row mx-auto my-6 w-full bg-white dark:bg-gray-800 shadow rounded">
                         <iframe src="{{ $module_iframe['url'].'?from_iframe=1' }}" width="100%" frameborder="0px" height="600px" onerror="alert('Failed')" style="margin-bottom:-5px;"><!-- //required for browser compatibility --></iframe> 
                        </div>
                     @endforeach
                </div>
            </div>
        </div>
    
</div>
</div>



@endsection
@push('page-styles')

<style>
.custom-container {
  height: calc(100vh - 40px) !important;
}
.hide-link {
  opacity: 0;
  overflow: hidden;
  height: 0;
  width: 0;
  display: block;
}
</style>
@endpush

