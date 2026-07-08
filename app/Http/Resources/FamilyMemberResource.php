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
            'invitation_status' => $this->invitationStatus(),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }

    /**
     * null: no email on file, nothing to invite.
     * accepted: linked to a real account.
     * pending: invite sent, not yet accepted, still within its window.
     * expired: invite sent but the 7-day window passed unaccepted.
     */
    protected function invitationStatus(): ?string
    {
        if (! $this->email) {
            return null;
        }

        if ($this->user_id) {
            return 'accepted';
        }

        if (! $this->invitation_token) {
            return null;
        }

        return $this->invitation_expires_at && $this->invitation_expires_at->isPast()
            ? 'expired'
            : 'pending';
    }
}
