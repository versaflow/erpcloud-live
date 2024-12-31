<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CustomNotification
{
    public function send($notifiable, Notification $notification)
    {
        $data = $notification->toDatabase($notifiable);

        return $notifiable->routeNotificationFor('database')->create([
            'id' => $notification->id,
            'reference' => $notification->reference,
            'type' => get_class($notification),
            'data' => $data,
            'read_at' => null,
        ]);
    }
}
