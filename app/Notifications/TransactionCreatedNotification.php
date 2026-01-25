<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Transaction $transaction)
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
            ->subject('New Transaction Created')
            ->line("A new {$this->transaction->type} transaction has been created.")
            ->line("Amount: {$this->transaction->currency} {$this->transaction->amount}")
            ->line("Description: {$this->transaction->description}")
            ->action('View Transaction', url('/transactions/' . $this->transaction->id));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'type' => $this->transaction->type,
            'amount' => $this->transaction->amount,
            'currency' => $this->transaction->currency,
            'description' => $this->transaction->description,
        ];
    }
}
