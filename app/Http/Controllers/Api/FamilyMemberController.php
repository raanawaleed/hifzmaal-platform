<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreFamilyMemberRequest;
use App\Http\Requests\UpdateFamilyMemberRequest;
use App\Http\Resources\FamilyMemberResource;
use App\Http\Controllers\Api\Controller;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Notifications\FamilyInvitationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class FamilyMemberController extends ApiController
{
    /**
     * @OA\Get(
     *     path="/api/families/{family}/members",
     *     summary="Get all family members",
     *     tags={"Family Members"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items()
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/families/{family}/members",
     *     summary="Add a new family member",
     *     tags={"Family Members"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "relationship", "role"},
     *             @OA\Property(property="name", type="string", example="Fatima Ahmed"),
     *             @OA\Property(property="email", type="string", example="fatima@example.com", nullable=true),
     *             @OA\Property(property="relationship", type="string", enum={"owner", "spouse", "son", "daughter", "father", "mother", "brother", "sister", "dependent"}, example="spouse"),
     *             @OA\Property(property="role", type="string", enum={"owner", "editor", "viewer", "approver"}, example="editor"),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="1990-05-15"),
     *             @OA\Property(property="spending_limit", type="number", format="float", example=5000.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Family member added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreFamilyMemberRequest $request, Family $family): JsonResponse
    {
        $this->authorize('manageMembers', $family);

        if (! $family->canAddAnotherMember()) {
            return response()->json([
                'message' => 'This family is at its plan limit of '.$family->memberLimit().' member(s). Upgrade to Pro to add more.',
                'error' => 'plan_limit_reached',
            ], 403);
        }

        $member = $family->members()->create($request->validated());

        // A member added with an email but no linked account gets an
        // invitation — they must accept it to actually log in and see
        // this family. Members without an email (e.g. a young dependent
        // tracked for budgeting only) never get one, by design.
        if ($member->email) {
            $member->forceFill([
                'invitation_token' => Str::random(48),
                'invitation_expires_at' => now()->addDays(7),
            ])->save();

            Notification::route('mail', $member->email)->notify(
                new FamilyInvitationNotification($family, $request->user(), $member->role, $member->invitation_token)
            );
        }

        return response()->json([
            'message' => 'Family member added successfully',
            'data' => new FamilyMemberResource($member),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/families/{family}/members/{member}",
     *     summary="Get family member by ID",
     *     tags={"Family Members"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="member",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#")
     *     ),
     *     @OA\Response(response=404, description="Family member not found")
     * )
     */
    public function show(Family $family, FamilyMember $member): FamilyMemberResource
    {
        $this->authorize('view', $family);

        if ($member->family_id !== $family->id) {
            abort(404);
        }

        return new FamilyMemberResource($member);
    }

    /**
     * @OA\Put(
     *     path="/api/families/{family}/members/{member}",
     *     summary="Update family member",
     *     tags={"Family Members"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="member",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="role", type="string", enum={"owner", "editor", "viewer", "approver"}),
     *             @OA\Property(property="spending_limit", type="number"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Family member updated successfully"
     *     )
     * )
     */
    public function update(UpdateFamilyMemberRequest $request, Family $family, FamilyMember $member): JsonResponse
    {
        $this->authorize('manageMembers', $family);

        if ($member->family_id !== $family->id) {
            abort(404);
        }

        $member->update($request->validated());

        return response()->json([
            'message' => 'Family member updated successfully',
            'data' => new FamilyMemberResource($member),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/families/{family}/members/{member}",
     *     summary="Remove family member",
     *     tags={"Family Members"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="member",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Family member removed successfully"
     *     ),
     *     @OA\Response(response=403, description="Cannot remove family owner")
     * )
     */
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

    /**
     * Re-send (and reset the expiry of) a pending or expired invitation.
     */
    public function resendInvitation(Family $family, FamilyMember $member): JsonResponse
    {
        $this->authorize('manageMembers', $family);

        if ($member->family_id !== $family->id) {
            abort(404);
        }

        if (! $member->email || $member->user_id) {
            return response()->json([
                'message' => 'This member has no pending invitation to resend.',
            ], 422);
        }

        $member->forceFill([
            'invitation_token' => Str::random(48),
            'invitation_expires_at' => now()->addDays(7),
        ])->save();

        Notification::route('mail', $member->email)->notify(
            new FamilyInvitationNotification($family, request()->user(), $member->role, $member->invitation_token)
        );

        return response()->json(['message' => 'Invitation re-sent.']);
    }
}