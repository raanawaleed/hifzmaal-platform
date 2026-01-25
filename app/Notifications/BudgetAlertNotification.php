<?php

namespace App\Notifications;

use App\Models\Budget;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Budget $budget)
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
        $percentage = $this->budget->getPercentageUsed();
        
        return (new MailMessage)
            ->subject('Budget Alert: ' . $this->budget->name)
            ->line("Your budget '{$this->budget->name}' has reached {$percentage}% of the allocated amount.")
            ->line("Budget: {$this->budget->amount}")
            ->line("Spent: {$this->budget->getSpentAmount()}")
            ->line("Remaining: {$this->budget->getRemainingAmount()}")
            ->action('View Budget', url('/budgets/' . $this->budget->id));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'budget_id' => $this->budget->id,
            'budget_name' => $this->budget->name,
            'percentage_used' => $this->budget->getPercentageUsed(),
            'amount' => $this->budget->amount,
            'spent' => $this->budget->getSpentAmount(),
        ];
    }
}
