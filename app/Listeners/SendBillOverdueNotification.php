<?php

namespace App\Listeners;

use App\Events\BillOverdue;
use App\Notifications\BillDueNotification;

class SendBillOverdueNotification
{
    /**
     * Handle the event.
     */
    public function handle(BillOverdue $event): void
    {
        $event->bill->family->owner->notify(new BillDueNotification($event->bill));
    }
}
