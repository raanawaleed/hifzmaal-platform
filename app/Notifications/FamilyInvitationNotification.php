<?php

namespace App\Notifications;

use App\Models\Family;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FamilyInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Sent via Notification::route('mail', ...) since the invitee may not
     * have an account yet — only the mail channel works "on demand".
     */
    public function __construct(
        public Family $family,
        public User $invitedBy,
        public string $role,
        public string $token,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim(config('app.frontend_url'), '/').'/invitations/accept?token='.$this->token;

        return (new MailMessage)
            ->subject($this->invitedBy->name.' invited you to join '.$this->family->name.' on HifzMaal')
            ->line($this->invitedBy->name." has invited you to join the \"{$this->family->name}\" family as a {$this->role} on HifzMaal.")
            ->line('HifzMaal helps families track spending, bills, savings goals, and Zakat together, the halal way.')
            ->action('Accept Invitation', $url)
            ->line('This invitation link expires in 7 days. If you weren\'t expecting this, you can safely ignore it.');
    }
}
