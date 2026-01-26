<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZakatPaymentResource extends JsonResource
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
            'amount' => (float) $this->amount,
            'payment_date' => $this->payment_date->format('Y-m-d'),
            'type' => $this->type,
            'recipient_name' => $this->recipient_name,
            'notes' => $this->notes,
            'recipient' => $this->when($this->recipient, [
                'id' => $this->recipient?->id,
                'name' => $this->recipient?->name,
                'category' => $this->recipient?->category,
                'category_label' => $this->recipient?->getCategoryLabel(),
            ]),
            'zakat_calculation' => [
                'id' => $this->zakatCalculation->id,
                'hijri_year' => $this->zakatCalculation->hijri_year,
            ],
            'transaction' => $this->when($this->transaction, [
                'id' => $this->transaction?->id,
                'description' => $this->transaction?->description,
            ]),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
