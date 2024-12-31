       @if(is_superadmin())
@foreach($instances as $j => $instance)

        <div class="w-full pb-10 @if($j == 0) pt-10 @endif">
            <div class="container-fluid mx-auto px-6 flex items-start justify-center">
                    <!-- Card is full width. Use in 12 col grid for best view. -->
                    <!-- Card code block start -->
                    <div class="flex flex-col lg:flex-row mx-auto w-full bg-white dark:bg-gray-800 shadow rounded">
                        <div class="w-full p-6">
                            <div class="flex items-center">
                                <div class="h-12 rounded">
                                    <img class="w-full h-full overflow-hidden object-cover rounded" src="{{$instance->brand_logo}}" alt="logo" />
                                </div>
                                
                            </div>
                </div>     </div>
                </div>
            <div aria-label="group of cards" class="container mx-auto px-6 flex items-start justify-center">
        
                <div class="w-full">
                    <!-- Card is full width. Use in 12 col grid for best view. -->
                    <!-- Card code block start -->
                    <div class="flex flex-col lg:flex-row mx-auto w-full bg-white dark:bg-gray-800 shadow rounded">
                        <div class="w-full lg:w-1/3 p-6">
                            @if(count($instance->processes) > 0)
                                <h3 class="text-lg text-gray-800 dark:text-gray-100 font-bold mt-0 mb-4">Scheduled Tasks</h3>
                                @foreach($instance->processes as $p)
                                    <div class="mb-2">
                                        <p class="text-gray-600 dark:text-gray-400 text-sm font-normal leading-3 tracking-normal">{{$p->username}}</p>
                                         <p class="text-gray-600 dark:text-gray-400 text-xs font-normal leading-3 tracking-normal text-muted">Current task: {{$p->current_task}}</p>
                                     
                                    </div>
                               
                                
                                
    
                                <div id="accordion" class="mb-4">
  @if(!empty($p->process_list) && count($p->process_list) > 0)
  <div class="card">
    <div class="card-header p-0" id="headingOne">
      <div class="mb-0">
        <button class="text-xs text-left leading-3 dark:text-gray-100 text-gray-800 pl-2 btn w-full collapsed" data-toggle="collapse" data-target="#processes{{$p->uniq_id}}" aria-expanded="true" aria-controls="collapseOne">
          Processes <span class="text-xs float-right text-right dark:text-gray-100 text-gray-800">{{$p->process_done}}/{{$p->process_total}}</span>
        </button>
      </div>
    </div>

    <div id="processes{{$p->uniq_id}}" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
      <div class="card-body p-0">
     
        <div class="mt-0 mb-0 w-full rounded p-4 relative">
        <ul>
        @foreach($p->process_list as $pc)
        <li class="@if($pc->progress_status == 'Done') line-through @endif text-xs text-gray-600 dark:text-gray-400 font-normal tracking-normal my-4">- {{$pc->name}}</li>
        @endforeach
        </ul>
        </div>
      </div>
    </div>
  </div>
  @endif
  @if(!empty($p->project_list) && count($p->project_list) > 0)
  <div class="card">
    <div class="card-header  p-0" id="headingTwo">
      <div class="mb-0">
        <button class="text-xs text-left leading-3 dark:text-gray-100 text-gray-800 pl-2 btn w-full collapsed" data-toggle="collapse" data-target="#projects{{$p->uniq_id}}" aria-expanded="false" aria-controls="collapseTwo">
          Projects <span class="text-xs float-right text-right dark:text-gray-100 text-gray-800">{{$p->project_done}}/{{$p->project_total}}</span>
        </button>
        
      </div>
    </div>
    <div id="projects{{$p->uniq_id}}" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
      <div class="card-body p-0">
      
        <div class="mt-0 mb-0 w-full rounded px-4 relative">
        <ul>
        @foreach($p->project_list as $pc)
        <li class="@if($pc->completed) line-through @endif text-xs text-gray-600 dark:text-gray-400 font-normal tracking-normal my-4">- {{$pc->name}}</li>
        @endforeach
        </ul>
        </div>
     
      </div>
    </div>
  </div>
  @endif
</div>
                                
                                
                              
                                @endforeach
                            @endif
                            
                             <!--<div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-800 dark:text-gray-100 font-normal mb-1 tracking-normal">Earnings</p>
                                    <h2 class="text-sm xl:text-lg text-gray-600 dark:text-gray-400 font-bold tracking-normal">$357,655</h2>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-800 dark:text-gray-100 font-normal mb-1 tracking-normal">Expenses</p>
                                    <h2 class="text-sm xl:text-lg text-gray-600 dark:text-gray-400 font-bold tracking-normal">$189,955</h2>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-800 dark:text-gray-100 font-normal mb-1 tracking-normal">Net Cash</p>
                                    <h2 class="text-sm xl:text-lg text-gray-600 dark:text-gray-400 font-bold tracking-normal">$888,546</h2>
                                </div>
                            </div>-->
                        </div>
                        <div class="w-full lg:w-1/3 p-6 border-t border-b lg:border-t-0 lg:border-b-0 sm:border-l sm:border-r border-gray-300">
                           
                                     @if(count($instance->assets) > 0) 
                   
                    
                            <h3 class="text-lg text-gray-800 dark:text-gray-100 font-bold  mt-0 mb-4">Cashflow</h3>
                            <div class="w-full">
                                
       
                
                                    @foreach ($instance->assets as $asset)
                                        <div class="flex items-center justify-between pt-3">
                                            <div class="flex items-center">
                                                <div class="w-1 h-4 bg-blue-500 rounded-md"></div>
                    
                                                <p class="text-sm leading-3 dark:text-gray-100 text-gray-800 pl-2">{{$asset->name}}</p>
                                            </div>
                                            <div class="flex items-center">
                                                <p class="text-sm leading-3 text-right text-gray-600 dark:text-gray-100 pr-2"> {{$asset->total}}</p>
                                            </div>
                                        </div>
                                     @endforeach 
                                     
                                    <div class="flex items-center justify-between pt-3">
                                        <div class="flex items-center">
                                            <div class="w-1 h-4 bg-blue-500 rounded-md"></div>
                
                                            <p class="text-sm leading-3 dark:text-gray-100 text-gray-800 pl-2 font-bold">Total</p>
                                        </div>
                                        <div class="flex items-center">
                                            <p class="text-sm leading-3 text-right text-gray-600 dark:text-gray-100 pr-2 font-bold"> {{$instance->cashflow_total}}</p>
                                        </div>
                                    </div>
                            </div>
                            @endif
                        </div>
                        <div class="w-full lg:w-1/3 p-6">
                            
         
        
                     @if(count($instance->kpis) > 0) 
                   
                    
                            <h3 class="text-lg text-gray-800 dark:text-gray-100 font-bold  mt-0 mb-4">KPI</h3>
                            <div class="w-full">
                                
       
                
                                    @foreach ($instance->kpis as $kpi)
                                        <div class="flex items-center justify-between pt-3">
                                            <div class="flex items-center">
                                                <div class="w-1 h-4 bg-blue-500 rounded-md"></div>
                    
                                                <a href="{{$kpi->layout_link}}" target="_blank" class="text-sm leading-3 dark:text-gray-100 text-gray-800 pl-2">{{$kpi->name}}</a>
                                            </div>
                                            <div class="flex items-center">
                                                <p class="text-sm leading-3 text-right text-gray-600 dark:text-gray-100 pr-2"> {{$kpi->total}}</p>
                                            </div>
                                        </div>
                                     @endforeach 
                            </div>
                            @endif
    
                        </div>
                    </div>
                    <!-- Card code block end -->
                </div>
            </div>
        </div>
    
@endforeach
@endif