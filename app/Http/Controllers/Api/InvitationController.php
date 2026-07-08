<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\FamilyResource;
use App\Models\FamilyMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvitationController extends ApiController
{
    /**
     * Public preview of an invitation — no auth required, so someone who
     * doesn't have an account yet can see what they're being invited to
     * before deciding to log in or register.
     */
    public function show(string $token): JsonResponse
    {
        $member = $this->findPending($token);

        if (! $member) {
            return $this->notFound();
        }

        return response()->json([
            'data' => [
                'family_name' => $member->family->name,
                'invited_by' => $member->family->owner->name,
                'role' => $member->role,
                'relationship' => $member->relationship,
                'email' => $member->email,
            ],
        ]);
    }

    /**
     * Link the authenticated user's account to the invited roster slot.
     * The invite's email must match the logged-in user's — the token
     * alone isn't treated as sufficient proof of identity.
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $member = $this->findPending($token);

        if (! $member) {
            return $this->notFound();
        }

        $user = $request->user();

        if (strcasecmp($member->email, $user->email) !== 0) {
            return response()->json([
                'message' => "This invitation was sent to {$member->email}. Log in with that address to accept it.",
                'error' => 'email_mismatch',
            ], 403);
        }

        if ($member->family->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'You are already a member of this family.',
            ], 422);
        }

        $member->forceFill([
            'user_id' => $user->id,
            'invitation_accepted_at' => now(),
            'invitation_token' => null,
        ])->save();

        return response()->json([
            'message' => 'You have joined the family.',
            'data' => ['family' => new FamilyResource($member->family->fresh(['owner', 'members']))],
        ]);
    }

    protected function findPending(string $token): ?FamilyMember
    {
        $member = FamilyMember::where('invitation_token', $token)
            ->whereNull('invitation_accepted_at')
            ->with(['family', 'family.owner'])
            ->first();

        if (! $member) {
            return null;
        }

        if ($member->invitation_expires_at && $member->invitation_expires_at->isPast()) {
            return null;
        }

        return $member;
    }

    protected function notFound(): JsonResponse
    {
        return response()->json([
            'message' => 'This invitation could not be found or has expired.',
        ], 404);
    }
}
