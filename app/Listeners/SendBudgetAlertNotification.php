<?php

namespace App\Listeners;

use App\Events\BudgetThresholdReached;
use App\Notifications\BudgetAlertNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendBudgetAlertNotification
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
    public function handle(BudgetThresholdReached $event): void
    {
        $family = $event->budget->family;
        
        // Notify family owner
        $family->owner->notify(new BudgetAlertNotification($event->budget));
        
        // Notify active family members
        $family->members()
            ->where('is_active', true)
            ->whereNotNull('user_id')
            ->each(function ($member) use ($event) {
                $member->user->notify(new BudgetAlertNotification($event->budget));
            });
    }
}
