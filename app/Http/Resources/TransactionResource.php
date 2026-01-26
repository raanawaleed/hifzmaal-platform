<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'date' => $this->date->format('Y-m-d'),
            'description' => $this->description,
            'notes' => $this->notes,
            'status' => $this->status,
            'needs_approval' => $this->needs_approval,
            'is_recurring' => $this->is_recurring,
            'recurring_frequency' => $this->recurring_frequency,
            'account' => [
                'id' => $this->account->id,
                'name' => $this->account->name,
                'type' => $this->account->type,
            ],
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'color' => $this->category->color,
                'icon' => $this->category->icon,
            ],
            'creator' => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ],
            'approver' => $this->when($this->approver, [
                'id' => $this->approver?->id,
                'name' => $this->approver?->name,
            ]),
            'transfer_to_account' => $this->when($this->transferToAccount, [
                'id' => $this->transferToAccount?->id,
                'name' => $this->transferToAccount?->name,
            ]),
            'receipts' => $this->getMedia('receipts')->map(fn($media) => [
                'id' => $media->id,
                'name' => $media->file_name,
                'url' => $media->getUrl(),
                'type' => $media->mime_type,
            ]),
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
