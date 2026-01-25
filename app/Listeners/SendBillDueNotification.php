<?php

namespace App\Listeners;

use App\Events\BillDueReminder;
use App\Notifications\BillDueNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendBillDueNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(BillDueReminder $event): void
    {
        $family = $event->bill->family;
        
        // Notify family owner
        $family->owner->notify(new BillDueNotification($event->bill));
        
        // Notify members responsible for bill payment
        if ($event->bill->split_members && !empty($event->bill->split_members)) {
            $family->members()
                ->whereIn('id', $event->bill->split_members)
                ->where('is_active', true)
                ->whereNotNull('user_id')
                ->each(function ($member) use ($event) {
                    $member->user->notify(new BillDueNotification($event->bill));
                });
        }
    }
}
