<?php

function erp_notify_current_user($reference, $title, $message, $link = '', $extra_data = [])
{
    return false;
    $user_id = session('user_id');
    erp_notify($reference, $user_id, $title, $message, $link, $extra_data);
}

function erp_notify($reference, $user_id, $title, $message, $link = '', $extra_data = [])
{
    return false;

    $exists = \DB::connection('default')->table('notifications')->where('reference', $reference)->where('notifiable_id', $user_id)->count();
    if (! $exists) {
        $user = App\Models\User::find($user_id);
        if ($user) {
            $user->notify(new App\Notifications\ErpNotification($reference, $title, $message, $link, $extra_data));
            Livewire::dispatch('refreshNotificationDropdown');
        }
    }
}
