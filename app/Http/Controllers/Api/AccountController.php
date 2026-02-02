<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\Family;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccountController extends Controller
{
    public function index(Family $family): AnonymousResourceCollection
    {
        $this->authorize('view', $family);

        $accounts = $family->accounts()
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        return AccountResource::collection($accounts);
    }

    public function store(StoreAccountRequest $request, Family $family): JsonResponse
    {
        $this->authorize('update', $family);

        $account = $family->accounts()->create([
            'name' => $request->name,
            'type' => $request->type,
            'currency' => $request->currency ?? $family->currency,
            'initial_balance' => $request->initial_balance,
            'balance' => $request->initial_balance,
            'account_number' => $request->account_number,
            'bank_name' => $request->bank_name,
            'include_in_zakat' => $request->include_in_zakat ?? true,
            'description' => $request->description,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Account created successfully',
            'data' => new AccountResource($account),
        ], 201);
    }

    public function show(Family $family, Account $account): AccountResource
    {
        $this->authorize('view', $family);

        if ($account->family_id !== $family->id) {
            abort(404);
        }

        return new AccountResource($account);
    }

    public function update(Request $request, Family $family, Account $account): JsonResponse
    {
        $this->authorize('update', $family);

        if ($account->family_id !== $family->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
            'include_in_zakat' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        $account->update($validated);

        return response()->json([
            'message' => 'Account updated successfully',
            'data' => new AccountResource($account),
        ]);
    }

    public function destroy(Family $family, Account $account): JsonResponse
    {
        $this->authorize('delete', $family);

        if ($account->family_id !== $family->id) {
            abort(404);
        }

        $account->delete();

        return response()->json([
            'message' => 'Account deleted successfully',
        ]);
    }
}