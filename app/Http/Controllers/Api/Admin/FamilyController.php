<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Family;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamilyController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $families = Family::query()
            ->when($request->query('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->with('owner:id,name,email')
            ->withCount(['members', 'accounts', 'transactions'])
            ->latest()
            ->paginate(min((int) $request->query('per_page', 20), 100));

        return response()->json($families);
    }

    public function show(Family $family): JsonResponse
    {
        $family->load('owner:id,name,email')
            ->loadCount(['members', 'accounts', 'transactions', 'budgets', 'bills', 'savingsGoals']);

        return response()->json([
            'data' => [
                'id' => $family->id,
                'name' => $family->name,
                'currency' => $family->currency,
                'owner' => $family->owner,
                'created_at' => $family->created_at,
                'members_count' => $family->members_count,
                'accounts_count' => $family->accounts_count,
                'transactions_count' => $family->transactions_count,
                'budgets_count' => $family->budgets_count,
                'bills_count' => $family->bills_count,
                'savings_goals_count' => $family->savings_goals_count,
                'last_activity' => $family->transactions()->latest('created_at')->value('created_at'),
            ],
        ]);
    }

    public function destroy(Family $family): JsonResponse
    {
        $family->delete();

        return response()->json(['message' => "Family '{$family->name}' has been deleted."]);
    }
}
