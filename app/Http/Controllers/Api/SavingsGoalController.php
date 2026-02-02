<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSavingsGoalRequest;
use App\Http\Resources\SavingsGoalResource;
use App\Models\Family;
use App\Models\SavingsGoal;
use App\Services\SavingsGoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SavingsGoalController extends Controller
{
    public function __construct(
        protected SavingsGoalService $savingsGoalService
    ) {}

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

    public function store(StoreSavingsGoalRequest $request, Family $family): JsonResponse
    {
        $this->authorize('update', $family);

        $goal = $this->savingsGoalService->createGoal($family, $request->validated());

        return response()->json([
            'message' => 'Savings goal created successfully',
            'data' => new SavingsGoalResource($goal),
        ], 201);
    }

    public function show(Family $family, SavingsGoal $savingsGoal): SavingsGoalResource
    {
        $this->authorize('view', $savingsGoal);

        if ($savingsGoal->family_id !== $family->id) {
            abort(404);
        }

        return new SavingsGoalResource($savingsGoal);
    }

    public function update(Request $request, Family $family, SavingsGoal $savingsGoal): JsonResponse
    {
        $this->authorize('update', $savingsGoal);

        if ($savingsGoal->family_id !== $family->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'target_amount' => 'sometimes|numeric|min:0',
            'monthly_contribution' => 'sometimes|numeric|min:0',
            'target_date' => 'sometimes|date',
            'is_active' => 'sometimes|boolean',
        ]);

        $updated = $this->savingsGoalService->updateGoal($savingsGoal, $validated);

        return response()->json([
            'message' => 'Savings goal updated successfully',
            'data' => new SavingsGoalResource($updated),
        ]);
    }

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

    public function contribute(Request $request, Family $family, SavingsGoal $savingsGoal): JsonResponse
    {
        $this->authorize('contribute', $savingsGoal);

        if ($savingsGoal->family_id !== $family->id) {
            abort(404);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $this->savingsGoalService->contribute($savingsGoal, $validated['amount']);

        return response()->json([
            'message' => 'Contribution added successfully',
            'data' => new SavingsGoalResource($savingsGoal->fresh()),
        ]);
    }

    public function overview(Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $overview = $this->savingsGoalService->getFamilyGoalsOverview($family);

        return response()->json([
            'data' => $overview,
        ]);
    }
}