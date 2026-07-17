<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Maps a notification's short class name to where it should send the
     * user when clicked. None of the underlying notifications carry a
     * family_id today, so this links to the relevant list screen rather
     * than a specific record.
     */
    protected const META = [
        'BillDueNotification' => ['title' => 'Bill due soon', 'route' => '/bills'],
        'BudgetAlertNotification' => ['title' => 'Budget alert', 'route' => '/budgets'],
        'SavingsGoalAchievedNotification' => ['title' => 'Savings goal achieved', 'route' => '/savings-goals'],
        'TransactionApprovalNeededNotification' => ['title' => 'Approval needed', 'route' => '/transactions/pending'],
        'TransactionCreatedNotification' => ['title' => 'New transaction', 'route' => '/transactions'],
        'ZakatDueNotification' => ['title' => 'Zakat due', 'route' => '/zakat'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $type = class_basename($this->type);
        $meta = self::META[$type] ?? ['title' => 'Notification', 'route' => '/dashboard'];

        return [
            'id' => $this->id,
            'type' => $type,
            'title' => $meta['title'],
            'message' => $this->buildMessage($type, $this->data),
            'route' => $meta['route'],
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function buildMessage(string $type, array $data): string
    {
        return match ($type) {
            'BillDueNotification' => "{$data['bill_name']} is due in {$data['days_until_due']} day(s).",
            'BudgetAlertNotification' => "\"{$data['budget_name']}\" has used ".round($data['percentage_used']).'% of its budget.',
            'SavingsGoalAchievedNotification' => "You reached your \"{$data['goal_name']}\" savings goal!",
            'TransactionApprovalNeededNotification' => "{$data['created_by']} submitted a {$data['type']} of {$data['amount']} that needs your approval.",
            'TransactionCreatedNotification' => ucfirst((string) $data['type'])." of {$data['amount']} {$data['currency']} recorded.",
            'ZakatDueNotification' => "{$data['zakat_remaining']} in Zakat remains unpaid for {$data['hijri_year']} AH.",
            default => 'You have a new notification.',
        };
    }
}
