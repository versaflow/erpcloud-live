
@if($module_id == 1944)

            
        @if(count($user_stats) > 0)
        
        <div class="flex flex-col lg:flex-row items-start lg:items-center w-full grid grid-cols-1 gap-2">
        @foreach ($user_stats as $user_stat)
      
       
       
            <div class="m-2 p-3 border">
                <div class="flex items-center justify-between">
                    <div>
                        <p tabindex="0" class="focus:outline-none text-lg font-semibold leading-6 text-gray-800 dark:text-gray-100">{{$user_stat->username}}</p>
                        <p tabindex="0" class="focus:outline-none text-xs leading-3 text-gray-500 mt-1">{{$user_stat->role_name}}</p>
                    </div>
   
                </div>
                <div class="">
                    <div>
                        <div class="flex items-center justify-between">
                            <p tabindex="0" class="focus:outline-none text-sm leading-3 text-gray-500 dark:text-gray-400">Completed Tasks</p>
                            <p tabindex="0" class="focus:outline-none text-base leading-3 text-right text-gray-800 dark:text-gray-100">{{$user_stat->completed}}</p>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="flex items-center justify-between">
                            <p tabindex="0" class="focus:outline-none text-sm leading-3 text-gray-500 dark:text-gray-400">Hours Tracked</p>
                            <p tabindex="0" class="focus:outline-none text-base leading-3 text-right text-gray-800 dark:text-gray-100">{{$user_stat->hours_spent}}</p>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="flex items-center justify-between">
                            <p tabindex="0" class="focus:outline-none text-sm leading-3 text-gray-500 dark:text-gray-400">Start Time</p>
                            <p tabindex="0" class="focus:outline-none text-base leading-3 text-right text-gray-800 dark:text-gray-100">{{$user_stat->start_time}}</p>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="flex items-center justify-between">
                            <p tabindex="0" class="focus:outline-none text-sm leading-3 text-gray-500 dark:text-gray-400">Project tasks</p>
                            <p tabindex="0" class="focus:outline-none text-base leading-3 text-right text-gray-800 dark:text-gray-100">{{$user_stat->project_tasks_completed}}/{{$user_stat->project_tasks_total}}</p>
                        </div>
                        <div class="w-full h-1 bg-gray-200 rounded-full mt-2">
                            <div style="width:{{$user_stat->complete_percentage}}%;" class="h-1 bg-green-500 rounded-full"></div>
                        </div>
                    </div>
                 
                </div>
            </div>
        @endforeach
        
        </div>
        @endif
@endif

@if(count($cards) > 0)

<div
class="flex flex-col lg:flex-row items-start lg:items-center"
>

        <!-- Card is full width. Use in 12 col grid for best view. -->
        <!-- Card code block start -->
        <div class="w-full grid grid-cols-1 gap-2">
        
        @foreach ($cards as $card)
        
             <a aria-label="card 1" href="{{$card->layout_url}}" target="_blank" class="kpicard bg-white dark:bg-gray-800 rounded  focus:ring-2 focus:ring-offset-2 focus:ring-indigo-700 focus:outline-none focus:bg-gray-100 hover:bg-gray-100">
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
    
</div>
@endif