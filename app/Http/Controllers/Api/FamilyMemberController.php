<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFamilyMemberRequest;
use App\Http\Resources\FamilyMemberResource;
use App\Models\Family;
use App\Models\FamilyMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FamilyMemberController extends Controller
{
    public function index(Family $family): AnonymousResourceCollection
    {
        $this->authorize('view', $family);

        $members = $family->members()
            ->with('user')
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return FamilyMemberResource::collection($members);
    }

    public function store(StoreFamilyMemberRequest $request, Family $family): JsonResponse
    {
        $this->authorize('manageMembers', $family);

        $member = $family->members()->create($request->validated());

        return response()->json([
            'message' => 'Family member added successfully',
            'data' => new FamilyMemberResource($member),
        ], 201);
    }

    public function show(Family $family, FamilyMember $member): FamilyMemberResource
    {
        $this->authorize('view', $family);

        if ($member->family_id !== $family->id) {
            abort(404);
        }

        return new FamilyMemberResource($member);
    }

    public function update(Request $request, Family $family, FamilyMember $member): JsonResponse
    {
        $this->authorize('manageMembers', $family);

        if ($member->family_id !== $family->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'role' => 'sometimes|in:owner,editor,viewer,approver',
            'spending_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $member->update($validated);

        return response()->json([
            'message' => 'Family member updated successfully',
            'data' => new FamilyMemberResource($member),
        ]);
    }

    public function destroy(Family $family, FamilyMember $member): JsonResponse
    {
        $this->authorize('manageMembers', $family);

        if ($member->family_id !== $family->id) {
            abort(404);
        }

        if ($member->role === 'owner') {
            return response()->json([
                'message' => 'Cannot remove family owner',
            ], 403);
        }

        $member->delete();

        return response()->json([
            'message' => 'Family member removed successfully',
        ]);
    }
}