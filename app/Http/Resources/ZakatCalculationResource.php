<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZakatCalculationResource extends JsonResource
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
            'hijri_year' => $this->hijri_year,
            'calculation_date' => $this->calculation_date->format('Y-m-d'),
            'assets' => [
                'cash_in_hand' => (float) $this->cash_in_hand,
                'cash_in_bank' => (float) $this->cash_in_bank,
                'gold_value' => (float) $this->gold_value,
                'silver_value' => (float) $this->silver_value,
                'business_inventory' => (float) $this->business_inventory,
                'investments' => (float) $this->investments,
                'loans_receivable' => (float) $this->loans_receivable,
                'other_assets' => (float) $this->other_assets,
            ],
            'debts' => (float) $this->debts,
            'total_assets' => (float) $this->total_assets,
            'nisab_amount' => (float) $this->nisab_amount,
            'nisab_type' => $this->nisab_type,
            'zakatable_amount' => (float) $this->zakatable_amount,
            'zakat_due' => (float) $this->zakat_due,
            'zakat_paid' => (float) $this->zakat_paid,
            'zakat_remaining' => (float) $this->zakat_remaining,
            'is_zakat_due' => $this->isZakatDue(),
            'is_fully_paid' => $this->isFullyPaid(),
            'completion_percentage' => round($this->getCompletionPercentage(), 2),
            'asset_details' => $this->asset_details,
            'notes' => $this->notes,
            'payments_count' => $this->payments()->count(),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
