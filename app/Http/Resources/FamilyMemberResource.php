<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyMemberResource extends JsonResource
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
            'email' => $this->email,
            'relationship' => $this->relationship,
            'role' => $this->role,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'age' => $this->getAge(),
            'is_active' => $this->is_active,
            'spending_limit' => (float) ($this->spending_limit ?? 0),
            'user' => $this->when($this->user, [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
