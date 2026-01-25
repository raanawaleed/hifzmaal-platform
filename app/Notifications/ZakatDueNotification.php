<?php

namespace App\Notifications;

use App\Models\ZakatCalculation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ZakatDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public ZakatCalculation $calculation)
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Zakat Payment Reminder')
            ->greeting('As-salamu alaykum!')
            ->line("This is a reminder about your pending Zakat payment for Hijri Year {$this->calculation->hijri_year}.")
            ->line("Total Zakat Due: {$this->calculation->zakat_due}")
            ->line("Amount Paid: {$this->calculation->zakat_paid}")
            ->line("Remaining: {$this->calculation->zakat_remaining}")
            ->line('May Allah accept your charity and multiply your rewards.')
            ->action('Pay Zakat', url('/zakat/calculations/' . $this->calculation->id));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'calculation_id' => $this->calculation->id,
            'hijri_year' => $this->calculation->hijri_year,
            'zakat_due' => $this->calculation->zakat_due,
            'zakat_remaining' => $this->calculation->zakat_remaining,
        ];
    }
}
