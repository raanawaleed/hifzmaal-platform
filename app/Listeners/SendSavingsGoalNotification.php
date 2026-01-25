<?php

namespace App\Listeners;

use App\Events\SavingsGoalCompleted;
use App\Notifications\SavingsGoalAchievedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendSavingsGoalNotification
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
    public function handle(SavingsGoalCompleted $event): void
    {
        $family = $event->goal->family;
        
        // Notify family owner
        $family->owner->notify(new SavingsGoalAchievedNotification($event->goal));
        
        // Notify all active family members
        $family->members()
            ->where('is_active', true)
            ->whereNotNull('user_id')
            ->each(function ($member) use ($event) {
                $member->user->notify(new SavingsGoalAchievedNotification($event->goal));
            });
    }
}
