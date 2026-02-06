<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreFamilyMemberRequest;
use App\Http\Requests\UpdateFamilyMemberRequest;
use App\Http\Resources\FamilyMemberResource;
use App\Http\Controllers\Api\Controller;
use App\Models\Family;
use App\Models\FamilyMember;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FamilyMemberController extends Controller
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
     *                 @OA\Items(ref="#/components/schemas/FamilyMember")
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
     *             @OA\Property(property="data", ref="#/components/schemas/FamilyMember")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreFamilyMemberRequest $request, Family $family): JsonResponse
    {
        $this->authorize('manageMembers', $family);

        $member = $family->members()->create($request->validated());

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
     *         @OA\JsonContent(ref="#/components/schemas/FamilyMember")
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
}