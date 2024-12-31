   @include('__app.dashboard.dashboard_performance')
   <div class="w-full pb-10 ">
                
            <div class="container-fluid mx-auto px-6 flex items-start justify-center">
        
                <div class="w-full mt-4 bg-white p-2">
                      <div class="k-widget k-button-group">
                                    @foreach($links as $link)
                                    <a href="{{ $link['url'] }}" class="k-button" target="_blank">{{ $link['name'] }}</a>
                                    @endforeach
                                    @if(!empty($iframes) && count($iframes) > 0)
                                    @foreach($iframes as $module_iframe)
                                    <a href="{{$module_iframe->url}}" class="k-button iframe_btn" target="_blank">{{ $module_iframe->name }}</a>
                                    @endforeach
                                    @endif
                                    </div>
                </div>
            </div>
            <div aria-label="group of cards" class="container-fluid mx-auto px-6 flex items-start justify-center">
        
                <div class="w-full container-fluid mx-auto grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-2 pt-6 gap-8">
                     @foreach($iframes as $i => $module_iframe)
                   
                        <div class="border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 shadow rounded iframe_container">
                             <iframe src="{{ $module_iframe->iframe_url }}" width="100%" frameborder="0px" height="600px" onerror="alert('Failed')" style="margin-bottom:-5px;"><!-- //required for browser compatibility --></iframe> 
                        </div>
                
                       
                     @endforeach
                </div>
            </div>
        </div>
       

 {{-- @include('__app.dashboard.dashboard_instances_kpi') --}}
