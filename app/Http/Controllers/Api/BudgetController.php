<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Models\Family;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BudgetController extends ApiController
{
    public function __construct(
        protected BudgetService $budgetService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/families/{family}/budgets",
     *     summary="Get all budgets for a family",
     *     tags={"Budgets"},
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
     *         name="current",
     *         in="query",
     *         description="Get only current budgets",
     *         @OA\Schema(type="boolean")
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
    public function index(Request $request, Family $family): AnonymousResourceCollection
    {
        $this->authorize('view', $family);

        $query = $family->budgets()->with('category');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('current')) {
            $query->current();
        }

        $budgets = $query->orderBy('start_date', 'desc')->get();

        return BudgetResource::collection($budgets);
    }

    /**
     * @OA\Post(
     *     path="/api/families/{family}/budgets",
     *     summary="Create a new budget",
     *     tags={"Budgets"},
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
     *             required={"category_id", "name", "amount", "period", "start_date", "end_date"},
     *             @OA\Property(property="category_id", type="integer", example=5),
     *             @OA\Property(property="name", type="string", example="Monthly Groceries Budget"),
     *             @OA\Property(property="amount", type="number", format="float", example=15000.00),
     *             @OA\Property(property="period", type="string", enum={"weekly", "monthly", "yearly"}, example="monthly"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-02-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2024-02-29"),
     *             @OA\Property(property="alert_threshold", type="number", format="float", example=80.00),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Budget created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreBudgetRequest $request, Family $family): JsonResponse
    {
        $this->authorize('update', $family);

        $budget = $this->budgetService->createBudget($family, $request->validated());

        return response()->json([
            'message' => 'Budget created successfully',
            'data' => new BudgetResource($budget),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/families/{family}/budgets/{budget}",
     *     summary="Get budget by ID",
     *     tags={"Budgets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="budget",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#")
     *     ),
     *     @OA\Response(response=404, description="Budget not found")
     * )
     */
    public function show(Family $family, Budget $budget): BudgetResource
    {
        $this->authorize('view', $budget);

        if ($budget->family_id !== $family->id) {
            abort(404);
        }

        return new BudgetResource($budget);
    }

    /**
     * @OA\Put(
     *     path="/api/families/{family}/budgets/{budget}",
     *     summary="Update budget",
     *     tags={"Budgets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="budget",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="alert_threshold", type="number"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Budget updated successfully"
     *     )
     * )
     */
    public function update(UpdateBudgetRequest $request, Family $family, Budget $budget): JsonResponse
    {
        $this->authorize('update', $budget);

        if ($budget->family_id !== $family->id) {
            abort(404);
        }

        $updated = $this->budgetService->updateBudget($budget, $request->validated());

        return response()->json([
            'message' => 'Budget updated successfully',
            'data' => new BudgetResource($updated),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/families/{family}/budgets/{budget}",
     *     summary="Delete budget",
     *     tags={"Budgets"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="budget",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Budget deleted successfully"
     *     )
     * )
     */
    public function destroy(Family $family, Budget $budget): JsonResponse
    {
        $this->authorize('delete', $budget);

        if ($budget->family_id !== $family->id) {
            abort(404);
        }

        $this->budgetService->deleteBudget($budget);

        return response()->json([
            'message' => 'Budget deleted successfully',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/families/{family}/budgets/overview",
     *     summary="Get budget overview for family",
     *     tags={"Budgets"},
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
     *                 @OA\Property(property="total_budget", type="number"),
     *                 @OA\Property(property="total_spent", type="number"),
     *                 @OA\Property(property="total_remaining", type="number"),
     *                 @OA\Property(property="overall_percentage", type="number"),
     *                 @OA\Property(property="budgets_count", type="integer"),
     *                 @OA\Property(property="over_budget_count", type="integer"),
     *                 @OA\Property(
     *                     property="budgets",
     *                     type="array",
     *                     @OA\Items()
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function overview(Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $overview = $this->budgetService->getFamilyBudgetOverview($family);

        return response()->json([
            'data' => $overview,
        ]);
    }
}