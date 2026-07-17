<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent via Notification::route('mail', ...) since the submitter may not
 * have an account — mail channel only.
 */
class ContactAcknowledgmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public ContactMessage $contactMessage)
    {
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
            ->subject('We received your message: ' . $this->contactMessage->subject)
            ->greeting("Assalamu Alaikum, {$this->contactMessage->name}!")
            ->line('Thank you for contacting HifzMaal. We have received your message and our team will get back to you as soon as possible.')
            ->line("Subject: {$this->contactMessage->subject}")
            ->line('Your message:')
            ->line($this->contactMessage->message)
            ->line('You will receive our reply at this email address.');
    }
}
