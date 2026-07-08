<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * The stock ResetPassword notification sends synchronously — a slow or
 * down mail server would hang (or fail) the /api/forgot-password request
 * itself. Queue it like every other outbound mail in this app.
 */
class QueuedResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;
}
