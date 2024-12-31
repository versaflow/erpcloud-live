<div id="notificationButton" class="relative" wire:poll.60s>
    <button class="text-gray-600 hover:text-gray-800">
        <i class="fas fa-bell"></i>
        @if($notifications && $notifications->count()) <span class="badge badge-pill badge-danger">{{ $notifications->count() }}</span>@endif
    </button>
    <div class="absolute block right-0 mt-2 w-auto bg-white border rounded-lg shadow-lg invisible" style="z-index: 10000 !important;min-width: 300px;" id="notificationDropdown">
     
            <div class="text-md block px-4 py-2 text-center text-gray-700 rounded-t-lg bg-gray-50 dark:bg-gray-800 dark:text-white">
                Notifications
            </div>
            @if($notifications && $notifications->count())
            @foreach ($notifications as $notification)
            <a href="{{ $notification->data['link'] }}" target="_blank" class="text-gray-700">
            <div class="p-2 border-b flex">
            
            <div class="w-full">
            
            
            <div class="text-xs font-bold" style="text-wrap: wrap;">{{ $notification->data['title'] }}</div>
            
            <div class="text-xs" style="text-wrap: wrap;">{!! $notification->data['message'] !!}</div>
            @if(str_contains( $notification->data['title'],'over max duration') || str_contains( $notification->data['title'],'Trial balance'))
            <a wire:click="markAsRead('{{ $notification->id }}')" class="mt-1 text-underline text-xs text-blue-500 dark:text-blue-500">
            Mark as read
            </a>
            @endif
            </div>
            
            </div>
            </a>
            @endforeach
            @endif
   
    </div>
</div>