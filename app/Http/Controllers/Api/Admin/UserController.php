<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->query('search'), function ($query, $search) {
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->query('status') === 'suspended', fn ($q) => $q->where('is_active', false))
            ->when($request->query('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->withCount(['ownedFamilies', 'familyMemberships'])
            ->latest()
            ->paginate(min((int) $request->query('per_page', 20), 100));

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['ownedFamilies', 'familyMemberships.family']);

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'suspended_at' => $user->suspended_at,
                'owned_families' => $user->ownedFamilies->map(fn ($f) => [
                    'id' => $f->id,
                    'name' => $f->name,
                    'members_count' => $f->getActiveMembers(),
                ]),
                'memberships' => $user->familyMemberships->map(fn ($m) => [
                    'family_id' => $m->family_id,
                    'family_name' => $m->family?->name,
                    'role' => $m->role,
                    'is_active' => $m->is_active,
                ]),
            ],
        ]);
    }

    public function suspend(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot suspend your own account.'], 422);
        }

        if ($user->isSuperAdmin()) {
            return response()->json(['message' => 'Superadmin accounts cannot be suspended.'], 422);
        }

        // forceFill: is_active/suspended_at are deliberately not mass-assignable.
        $user->forceFill(['is_active' => false, 'suspended_at' => now()])->save();
        $user->tokens()->delete();

        return response()->json(['message' => "{$user->email} has been suspended."]);
    }

    public function unsuspend(User $user): JsonResponse
    {
        $user->forceFill(['is_active' => true, 'suspended_at' => null])->save();

        return response()->json(['message' => "{$user->email} has been reactivated."]);
    }
}
