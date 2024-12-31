<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ErpNotification extends Notification
{
    use Queueable;

    protected $table = 'notifications';

    public $reference;

    public $title;

    public $message;

    public $link;

    public $type;

    public $extra_data;

    public function __construct($reference, $title, $message, $link = '', $extra_data = [])
    {
        $this->reference = $reference;
        $this->title = $title;
        $this->message = $message;
        $this->link = $link;
        if (count($extra_data) > 0) {
            $this->extra_data = $extra_data;
        }

    }

    public function toDatabase($notifiable)
    {
        $data = [
            'title' => $this->title,
            'reference' => $this->reference,
            'message' => $this->message,
            'link' => $this->link,
        ];
        if (! empty($this->extra_data) && is_array($this->extra_data) && count($this->extra_data) > 0) {
            foreach ($this->extra_data as $k => $v) {
                $data[$k] = $v;
            }
        }

        return $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [CustomNotification::class];
    }
}
