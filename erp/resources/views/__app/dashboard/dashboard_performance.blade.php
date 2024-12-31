 @if(count($user_stats) > 0)
        
        <div class="container-fluid flex flex-col lg:flex-row items-start lg:items-center w-full grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-7 xl:grid-cols-7  gap-2">
        @foreach ($user_stats as $user_stat)
      
       
       
            <div class="m-2 p-3 border bg-white">
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