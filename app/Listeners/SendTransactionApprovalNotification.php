<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use App\Notifications\TransactionApprovalNeededNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTransactionApprovalNotification
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
        if (!$event->transaction->needs_approval) {
            return;
        }

        $family = $event->transaction->family;
        
        // Notify approvers
        $family->members()
            ->where('is_active', true)
            ->whereIn('role', ['owner', 'approver'])
            ->whereNotNull('user_id')
            ->each(function ($member) use ($event) {
                $member->user->notify(new TransactionApprovalNeededNotification($event->transaction));
            });
    }
}
