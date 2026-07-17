<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emails an admin's reply back to the person who submitted the contact
 * form. Sent via Notification::route('mail', ...) — mail channel only,
 * since the submitter may not have an account.
 */
class ContactReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public ContactMessage $contactMessage,
        public ContactMessageReply $reply,
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Re: ' . $this->contactMessage->subject)
            ->greeting("Assalamu Alaikum, {$this->contactMessage->name}!")
            ->line('You have received a reply to your inquiry:')
            ->line($this->reply->body)
            ->line('If you have further questions, simply reply to this email or submit a new inquiry through our contact form.');
    }
}
