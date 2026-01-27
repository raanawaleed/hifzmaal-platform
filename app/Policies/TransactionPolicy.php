<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TransactionPolicy
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
    public function view(User $user, Transaction $transaction): bool
    {
        return $user->hasAccessToFamily($transaction->family);
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
    public function update(User $user, Transaction $transaction): bool
    {
         // Owner can update any transaction
         if ($user->getFamilyMemberRole($transaction->family) === 'owner') {
            return true;
        }

        // Creator can update their own pending transactions
        return $transaction->created_by === $user->id && $transaction->status === 'pending';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
         // Owner can delete any transaction
         if ($user->getFamilyMemberRole($transaction->family) === 'owner') {
            return true;
        }

        // Creator can delete their own pending transactions
        return $transaction->created_by === $user->id && $transaction->status === 'pending';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Transaction $transaction): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Transaction $transaction): bool
    {
        return false;
    }

    public function approve(User $user, Transaction $transaction): bool
    {
        return $user->canApproveTransactions($transaction->family) 
            && $transaction->status === 'pending';
    }

    public function reject(User $user, Transaction $transaction): bool
    {
        return $user->canApproveTransactions($transaction->family) 
            && $transaction->status === 'pending';
    }
}
