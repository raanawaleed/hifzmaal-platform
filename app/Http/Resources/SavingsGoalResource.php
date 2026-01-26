<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavingsGoalResource extends JsonResource
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
            'target_amount' => (float) $this->target_amount,
            'current_amount' => (float) $this->current_amount,
            'monthly_contribution' => (float) ($this->monthly_contribution ?? 0),
            'target_date' => $this->target_date?->format('Y-m-d'),
            'start_date' => $this->start_date->format('Y-m-d'),
            'description' => $this->description,
            'dua_reminder' => $this->dua_reminder,
            'auto_contribute' => $this->auto_contribute,
            'contribution_day' => $this->contribution_day,
            'is_active' => $this->is_active,
            'account' => $this->when($this->account, [
                'id' => $this->account?->id,
                'name' => $this->account?->name,
            ]),
            'progress_percentage' => round($this->getProgressPercentage(), 2),
            'remaining_amount' => (float) $this->getRemainingAmount(),
            'is_completed' => $this->isCompleted(),
            'is_overdue' => $this->isOverdue(),
            'estimated_completion_date' => $this->getEstimatedCompletionDate()?->format('Y-m-d'),
            'days_remaining' => $this->getDaysRemaining(),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
