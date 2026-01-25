<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use App\Notifications\TransactionCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTransactionCreatedNotification
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
    public function handle(TransactionCreated $event): void
    {
        $family = $event->transaction->family;
        
        // Notify family owner
        $family->owner->notify(new TransactionCreatedNotification($event->transaction));
        
        // Notify family members with appropriate role
        $family->members()
            ->where('is_active', true)
            ->whereIn('role', ['owner', 'editor', 'approver'])
            ->whereNotNull('user_id')
            ->each(function ($member) use ($event) {
                $member->user->notify(new TransactionCreatedNotification($event->transaction));
            });
    }
}
