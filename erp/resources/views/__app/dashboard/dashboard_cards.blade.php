@if(count($cards) > 0)


        <!-- Card is full width. Use in 12 col grid for best view. -->
        <!-- Card code block start -->
        <div class="container-fluid flex flex-col lg:flex-row items-start lg:items-center w-full grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-6 xl:grid-cols-6  gap-2">
        
        @foreach ($cards as $card)
        
             <a aria-label="card 1" href="{{$card->layout_url}}" target="_blank" class="m-2 kpicard bg-white dark:bg-gray-800 rounded  focus:ring-2 focus:ring-offset-2 focus:ring-indigo-700 focus:outline-none focus:bg-gray-100 hover:bg-gray-100">
        <div class="shadow px-8 py-6 flex items-center">
            <div class="p-4 bg-indigo-700 rounded">
                <img src="https://tuk-cdn.s3.amazonaws.com/can-uploader/medium_stat_cards_with_icon-svg1.svg" alt="icon"/>
               
            </div>
            
                <div class="ml-6">
                    <h3 class="mb-1 leading-5 text-gray-800 dark:text-gray-100 font-bold text-2xl">{{$card->result}}
                    </h3>
                    <p
                        class="text-gray-600 dark:text-gray-400 text-sm tracking-normal font-normal leading-5">
                        {{$card->title}}</p>
                </div>
          
        </div>
    </a>
        
        @endforeach
        </div>
        <!-- Card code block end -->
    
@endif