<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
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
            'amount' => (float) $this->amount,
            'period' => $this->period,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'alert_threshold' => (float) $this->alert_threshold,
            'is_active' => $this->is_active,
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'color' => $this->category->color,
            ],
            'spent_amount' => (float) $this->getSpentAmount(),
            'remaining_amount' => (float) $this->getRemainingAmount(),
            'percentage_used' => round($this->getPercentageUsed(), 2),
            'is_over_budget' => $this->isOverBudget(),
            'should_alert' => $this->shouldAlert(),
            'days_remaining' => $this->getDaysRemaining(),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
