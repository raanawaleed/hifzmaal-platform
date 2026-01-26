<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'average_amount' => (float) ($this->average_amount ?? 0),
            'due_date' => $this->due_date->format('Y-m-d'),
            'frequency' => $this->frequency,
            'is_recurring' => $this->is_recurring,
            'auto_pay' => $this->auto_pay,
            'provider' => $this->provider,
            'account_number' => $this->account_number,
            'split_members' => $this->split_members,
            'reminder_days' => $this->reminder_days,
            'status' => $this->status,
            'last_paid_date' => $this->last_paid_date?->format('Y-m-d'),
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ],
            'account' => $this->when($this->account, [
                'id' => $this->account?->id,
                'name' => $this->account?->name,
            ]),
            'is_due' => $this->isDue(),
            'is_overdue' => $this->isOverdue(),
            'days_until_due' => $this->getDaysUntilDue(),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
