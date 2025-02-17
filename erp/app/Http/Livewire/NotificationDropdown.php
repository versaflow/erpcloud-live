<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NotificationDropdown extends Component
{
    public $notifications;
    public $isDropdownOpen = false; // Default to closed
    
    public function mount()
    {
        $this->notifications = $this->getNotifications();
    }

    public function markAsRead($notificationId)
    {
        $notification = Auth::user()->notifications->find($notificationId);

        if ($notification) {
            $notification->markAsRead();
            $notifications = $this->getNotifications();
            if(count($notifications) == 0){
                $this->toggleDropdown();
            }
            $this->notifications = $notifications;
        }
    }

    public function toggleDropdown()
    {
       
        $this->isDropdownOpen = !$this->isDropdownOpen;
    }
    
    public function render()
    {
        return view('livewire.notification-dropdown', [
            'dropdownClasses' => $this->isDropdownOpen ? 'block' : 'hidden',
        ]);
    }


    public function getNotifications()
    {   
        //rebuild_approval_notifications();
        return Auth::user()->unreadNotifications;
    }

    public function poll()
    {
        // Livewire will automatically call this method on a regular interval
        // Use it to refresh the notifications
        $this->notifications = $this->getNotifications();
        
    }

    public function hydrate()
    {
        $this->poll();
    }
}

