<?php

namespace App\Listeners;

use App\Events\ZakatDueReminder;
use App\Notifications\ZakatDueNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendZakatDueNotification
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
    public function handle(ZakatDueReminder $event): void
    {
        $family = $event->calculation->family;

        // Notify family owner
        $family->owner->notify(new ZakatDueNotification($event->calculation));
    }
}
