<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
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
            'currency' => $this->currency,
            'balance' => (float) $this->balance,
            'initial_balance' => (float) $this->initial_balance,
            'account_number' => $this->account_number,
            'bank_name' => $this->bank_name,
            'is_active' => $this->is_active,
            'include_in_zakat' => $this->include_in_zakat,
            'description' => $this->description,
            'total_income' => (float) $this->getTotalIncome(),
            'total_expense' => (float) $this->getTotalExpense(),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
