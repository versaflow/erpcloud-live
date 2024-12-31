<div id="notificationButton" class="dropdown" wire:poll.60s>
     <a href="javascript:;" class="btn btn-xs dropdown-toggle mb-0" data-bs-toggle="dropdown" id="ntd">
        <i class="fas fa-bell"></i>
        @if($notifications && $notifications->count()) <span class="badge badge-pill badge-danger">{{ $notifications->count() }}</span>@endif
    </a>
    <ul class="dropdown-menu mb-0 py-0" aria-labelledby="ntd" id="notificationDropdown" style="position:fixed; z-index: 10000 !important;">
   
            @if($notifications && $notifications->count())
            @foreach ($notifications as $notification)
            
             <li class="mt-2 mb-0 border-bottom">
             
                <div class="d-flex px-1">
                  <div class="d-flex flex-column justify-content-center">
                    <div class="row ">
                    <div class="col ">
                    <h6 class="text-sm font-weight-normal mb-1 py-1">
                 
                    <span class="font-weight-bold">{{ $notification->data['title'] }}</span>
                    
                    </h6>
                    </div>
                    @if(is_superadmin())
                    <div class="col-auto text-end">
                    @if(!empty($notification->data['approve_link']))
                    <a  href="{{ $notification->data['approve_link'] }}" data-target="ajax" title="Approve" class="btn btn-sm btn-icon btn-success"><i class="fas fa-check"></i></a>
                    @endif
                    @if(!empty($notification->data['reject_link']))
                    <a  href="{{ $notification->data['reject_link'] }}" data-target="ajax" title="Reject"  class="btn btn-sm btn-icon btn-danger"><i class="fas fa-times"></i> </a>
                    @endif
                    </div>
                    @endif
                 
                    <p class="text-xs text-secondary mb-1">
                      
                    @if(!empty($notification->data['link']))
                    <a  href="{{ $notification->data['link'] }}" target="_blank" class="font-weight-bold"> {!! $notification->data['message'] !!}</a>
                    @else
                    {!! $notification->data['message'] !!}
                    @endif
                    
                   
                        @if(empty($notification->data['approve_link']))
                        <br />
                        <a wire:click="markAsRead('{{ $notification->id }}')" class="mt-1 text-underline text-xs">
                        Mark as read
                        </a>
                        @endif
                    </p>
                    
                   
                  </div>
                </div>
            </li>
            @endforeach
            @endif
    </ul>
</div>