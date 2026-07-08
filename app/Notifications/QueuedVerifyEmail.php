<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * The stock VerifyEmail notification sends synchronously — a slow or down
 * mail server would hang (or fail) the /api/register and resend-verification
 * requests themselves. Queue it like every other outbound mail in this app.
 */
class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;
}
