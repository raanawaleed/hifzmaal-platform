<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\StoreSavingsGoalRequest;
use App\Http\Requests\UpdateSavingsGoalRequest;
use App\Http\Resources\SavingsGoalResource;
use App\Models\Family;
use App\Models\SavingsGoal;
use App\Services\SavingsGoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SavingsGoalController extends Controller
{
    public function __construct(
        protected SavingsGoalService $savingsGoalService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/families/{family}/savings-goals",
     *     summary="Get all savings goals for a family",
     *     tags={"Savings Goals"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filter by active status",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by type",
     *         @OA\Schema(type="string", enum={"hajj", "umrah", "education", "marriage", "emergency", "business", "other"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/SavingsGoal")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request, Family $family): AnonymousResourceCollection
    {
        $this->authorize('view', $family);

        $query = $family->savingsGoals();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $goals = $query->orderBy('created_at', 'desc')->get();

        return SavingsGoalResource::collection($goals);
    }

    /**
     * @OA\Post(
     *     path="/api/families/{family}/savings-goals",
     *     summary="Create a new savings goal",
     *     tags={"Savings Goals"},
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
     *             required={"name", "type", "target_amount"},
     *             @OA\Property(property="name", type="string", example="Hajj Fund"),
     *             @OA\Property(property="type", type="string", enum={"hajj", "umrah", "education", "marriage", "emergency", "business", "other"}, example="hajj"),
     *             @OA\Property(property="target_amount", type="number", format="float", example=500000.00),
     *             @OA\Property(property="current_amount", type="number", format="float", example=0),
     *             @OA\Property(property="monthly_contribution", type="number", format="float", example=10000.00),
     *             @OA\Property(property="target_date", type="string", format="date", example="2026-06-01"),
     *             @OA\Property(property="account_id", type="integer", nullable=true),
     *             @OA\Property(property="description", type="string", example="Saving for Hajj pilgrimage"),
     *             @OA\Property(property="dua_reminder", type="string", example="O Allah, make it easy for me to perform Hajj"),
     *             @OA\Property(property="auto_contribute", type="boolean", example=true),
     *             @OA\Property(property="contribution_day", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Savings goal created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/SavingsGoal")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreSavingsGoalRequest $request, Family $family): JsonResponse
    {
        $this->authorize('update', $family);

        $goal = $this->savingsGoalService->createGoal($family, $request->validated());

        return response()->json([
            'message' => 'Savings goal created successfully',
            'data' => new SavingsGoalResource($goal),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/families/{family}/savings-goals/{savingsGoal}",
     *     summary="Get savings goal by ID",
     *     tags={"Savings Goals"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="savingsGoal",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/SavingsGoal")
     *     ),
     *     @OA\Response(response=404, description="Savings goal not found")
     * )
     */
    public function show(Family $family, SavingsGoal $savingsGoal): SavingsGoalResource
    {
        $this->authorize('view', $savingsGoal);

        if ($savingsGoal->family_id !== $family->id) {
            abort(404);
        }

        return new SavingsGoalResource($savingsGoal);
    }

    /**
     * @OA\Put(
     *     path="/api/families/{family}/savings-goals/{savingsGoal}",
     *     summary="Update savings goal",
     *     tags={"Savings Goals"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="savingsGoal",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="target_amount", type="number"),
     *             @OA\Property(property="monthly_contribution", type="number"),
     *             @OA\Property(property="target_date", type="string", format="date"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Savings goal updated successfully"
     *     )
     * )
     */
    public function update(UpdateSavingsGoalRequest $request, Family $family, SavingsGoal $savingsGoal): JsonResponse
    {
        $this->authorize('update', $savingsGoal);

        if ($savingsGoal->family_id !== $family->id) {
            abort(404);
        }

        $updated = $this->savingsGoalService->updateGoal($savingsGoal, $request->validated());

        return response()->json([
            'message' => 'Savings goal updated successfully',
            'data' => new SavingsGoalResource($updated),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/families/{family}/savings-goals/{savingsGoal}",
     *     summary="Delete savings goal",
     *     tags={"Savings Goals"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="savingsGoal",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Savings goal deleted successfully"
     *     )
     * )
     */
    public function destroy(Family $family, SavingsGoal $savingsGoal): JsonResponse
    {
        $this->authorize('delete', $savingsGoal);

        if ($savingsGoal->family_id !== $family->id) {
            abort(404);
        }

        $this->savingsGoalService->deleteGoal($savingsGoal);

        return response()->json([
            'message' => 'Savings goal deleted successfully',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/families/{family}/savings-goals/{savingsGoal}/contribute",
     *     summary="Add contribution to savings goal",
     *     tags={"Savings Goals"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="savingsGoal",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number", format="float", example=5000.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contribution added successfully"
     *     )
     * )
     */
    public function contribute(Request $request, Family $family, SavingsGoal $savingsGoal): JsonResponse
    {
        $this->authorize('contribute', $savingsGoal);

        if ($savingsGoal->family_id !== $family->id) {
            abort(404);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $this->savingsGoalService->contribute($savingsGoal, $validated['amount']);

        return response()->json([
            'message' => 'Contribution added successfully',
            'data' => new SavingsGoalResource($savingsGoal->fresh()),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/families/{family}/savings-goals/overview",
     *     summary="Get savings goals overview",
     *     tags={"Savings Goals"},
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
     *                 type="object",
     *                 @OA\Property(property="total_goals", type="integer"),
     *                 @OA\Property(property="completed_goals", type="integer"),
     *                 @OA\Property(property="in_progress_goals", type="integer"),
     *                 @OA\Property(property="total_target", type="number"),
     *                 @OA\Property(property="total_saved", type="number"),
     *                 @OA\Property(property="overall_progress", type="number")
     *             )
     *         )
     *     )
     * )
     */
    public function overview(Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $overview = $this->savingsGoalService->getFamilyGoalsOverview($family);

        return response()->json([
            'data' => $overview,
        ]);
    }
}