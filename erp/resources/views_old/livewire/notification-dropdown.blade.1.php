<div id="notificationButton" class="dropdown"  style="z-index: 10000 !important;">
     <a href="javascript:;" class="btn dropdown-toggle mb-0" data-bs-toggle="dropdown" id="ntd">
        <i class="fas fa-bell"></i>
        @if($notifications && $notifications->count()) <span class="badge badge-pill badge-danger">{{ $notifications->count() }}</span>@endif
    </a>
    <ul class="dropdown-menu mb-0" aria-labelledby="ntd" id="notificationDropdown" style="position:fixed; z-index: 10000 !important;min-width: 300px;">
   
            @if($notifications && $notifications->count())
            @foreach ($notifications as $notification)
            
             <li class="mb-2">
              <a class="dropdown-item border-radius-md" href="{{ $notification->data['link'] }}" target="_blank">
                <div class="d-flex py-1">
                  <div class="d-flex flex-column justify-content-center">
                    <h6 class="text-sm font-weight-normal mb-1">
                      <span class="font-weight-bold">{{ $notification->data['title'] }}</span> from Laur
                    </h6>
                    <p class="text-xs text-secondary mb-0">
                      {!! $notification->data['message'] !!}
                        @if(str_contains( $notification->data['title'],'over max duration') || str_contains( $notification->data['title'],'Trial balance'))
                        <a wire:click="markAsRead('{{ $notification->id }}')" class="mt-1 text-underline text-xs">
                        Mark as read
                        </a>
                        @endif
                    </p>
                  </div>
                </div>
              </a>
            </li>
            @endforeach
            @endif
    </ul>
</div>