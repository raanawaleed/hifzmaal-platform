<?php

namespace App\Policies;

use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SavingsGoalPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SavingsGoal $savingsGoal): bool
    {
        return $user->hasAccessToFamily($goal->family);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SavingsGoal $savingsGoal): bool
    {
        return in_array($user->getFamilyMemberRole($savingsGoal->family), ['owner', 'editor']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SavingsGoal $savingsGoal): bool
    {
        return $user->getFamilyMemberRole($savingsGoal->family) === 'owner';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SavingsGoal $savingsGoal): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SavingsGoal $savingsGoal): bool
    {
        return false;
    }

    public function contribute(User $user, SavingsGoal $savingsGoal): bool
    {
        return in_array($user->getFamilyMemberRole($savingsGoal->family), ['owner', 'editor', 'approver']);
    }

}
