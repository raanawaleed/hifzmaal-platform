<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\StoreFamilyRequest;
use App\Http\Requests\UpdateFamilyRequest;
use App\Http\Resources\FamilyResource;
use App\Models\Family;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FamilyController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Family::class, 'family');
    }

    /**
     * @OA\Get(
     *     path="/api/families",
     *     summary="Get list of families",
     *     tags={"Families"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Family")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(): AnonymousResourceCollection
    {
        $families = auth()->user()->ownedFamilies()
            ->with(['owner', 'members'])
            ->get();

        $memberFamilies = auth()->user()->familyMemberships()
            ->with('family.owner')
            ->get()
            ->pluck('family');

        $allFamilies = $families->merge($memberFamilies)->unique('id');

        return FamilyResource::collection($allFamilies);
    }

    /**
     * @OA\Post(
     *     path="/api/families",
     *     summary="Create a new family",
     *     tags={"Families"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","currency","locale"},
     *             @OA\Property(property="name", type="string", example="Ahmed Family"),
     *             @OA\Property(property="currency", type="string", example="PKR"),
     *             @OA\Property(property="locale", type="string", example="en")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Family created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/Family")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreFamilyRequest $request): JsonResponse
    {
        $family = Family::create([
            'name' => $request->name,
            'currency' => $request->currency,
            'locale' => $request->locale,
            'owner_id' => auth()->id(),
        ]);

        $family->members()->create([
            'user_id' => auth()->id(),
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'relationship' => 'owner',
            'role' => 'owner',
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Family created successfully',
            'data' => new FamilyResource($family),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/families/{id}",
     *     summary="Get family by ID",
     *     tags={"Families"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Family")
     *     ),
     *     @OA\Response(response=404, description="Family not found")
     * )
     */
    public function show(Family $family): FamilyResource
    {
        $family->load(['owner', 'members', 'accounts']);
        
        return new FamilyResource($family);
    }

    /**
     * @OA\Put(
     *     path="/api/families/{id}",
     *     summary="Update family",
     *     tags={"Families"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="currency", type="string"),
     *             @OA\Property(property="locale", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Family updated successfully"
     *     )
     * )
     */
    public function update(UpdateFamilyRequest $request, Family $family): JsonResponse
    {
        $family->update($request->validated());

        return response()->json([
            'message' => 'Family updated successfully',
            'data' => new FamilyResource($family),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/families/{id}",
     *     summary="Delete family",
     *     tags={"Families"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Family deleted successfully"
     *     )
     * )
     */
    public function destroy(Family $family): JsonResponse
    {
        $family->delete();

        return response()->json([
            'message' => 'Family deleted successfully',
        ]);
    }
}