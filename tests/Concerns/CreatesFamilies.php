<?php

namespace Tests\Concerns;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;

trait CreatesFamilies
{
    /**
     * Create a family owned by the given user, including the owner
     * membership row (mirrors FamilyController::store).
     */
    protected function createFamilyFor(User $user, array $attributes = []): Family
    {
        $family = Family::factory()->create(['owner_id' => $user->id] + $attributes);

        $family->members()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'relationship' => 'owner',
            'role' => 'owner',
            'is_active' => true,
        ]);

        return $family;
    }

    /**
     * Add a user to a family with the given role and return that user.
     */
    protected function addMember(Family $family, string $role = 'member', array $attributes = []): User
    {
        $user = User::factory()->create();

        FamilyMember::factory()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
        ] + $attributes);

        return $user;
    }
}
