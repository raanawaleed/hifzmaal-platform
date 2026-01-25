<?php

namespace App\Notifications;

use App\Models\SavingsGoal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SavingsGoalAchievedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public SavingsGoal $goal)
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
            ->subject('Savings Goal Achieved!')
            ->greeting('Masha\'Allah!')
            ->line("Congratulations! You have successfully achieved your savings goal: {$this->goal->name}")
            ->line("Target Amount: {$this->goal->target_amount}")
            ->line("Current Amount: {$this->goal->current_amount}")
            ->line('May Allah grant you Barakah in your wealth.')
            ->action('View Goal', url('/savings-goals/' . $this->goal->id));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'goal_id' => $this->goal->id,
            'goal_name' => $this->goal->name,
            'target_amount' => $this->goal->target_amount,
            'achieved_at' => now()->toDateTimeString(),
        ];
    }
}
