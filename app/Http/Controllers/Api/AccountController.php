<?php

namespace App\Http\Controllers\Api;

use OpenApi\Annotations as OA;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\Family;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccountController extends ApiController
{
    /**
     * @OA\Get(
     *     path="/api/families/{family}/accounts",
     *     summary="Get all accounts for a family",
     *     tags={"Accounts"},
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

        $accounts = $family->accounts()
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        return AccountResource::collection($accounts);
    }

    /**
     * @OA\Post(
     *     path="/api/families/{family}/accounts",
     *     summary="Create a new account",
     *     tags={"Accounts"},
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
     *             required={"name", "type", "initial_balance"},
     *             @OA\Property(property="name", type="string", example="Main Wallet"),
     *             @OA\Property(property="type", type="string", enum={"cash", "bank", "wallet", "savings", "investment"}, example="cash"),
     *             @OA\Property(property="currency", type="string", example="PKR"),
     *             @OA\Property(property="initial_balance", type="number", format="float", example=10000.00),
     *             @OA\Property(property="account_number", type="string", example="12345678"),
     *             @OA\Property(property="bank_name", type="string", example="HBL"),
     *             @OA\Property(property="include_in_zakat", type="boolean", example=true),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Account created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/families/{family}/accounts/{account}",
     *     summary="Get account by ID",
     *     tags={"Accounts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="account",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#")
     *     ),
     *     @OA\Response(response=404, description="Account not found")
     * )
     */
    public function show(Family $family, Account $account): AccountResource
    {
        $this->authorize('view', $family);

        if ($account->family_id !== $family->id) {
            abort(404);
        }

        return new AccountResource($account);
    }

    /**
     * @OA\Put(
     *     path="/api/families/{family}/accounts/{account}",
     *     summary="Update account",
     *     tags={"Accounts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="account",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="include_in_zakat", type="boolean"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account updated successfully"
     *     )
     * )
     */
    public function update(UpdateAccountRequest $request, Family $family, Account $account): JsonResponse
    {
        $this->authorize('update', $family);

        if ($account->family_id !== $family->id) {
            abort(404);
        }

        $account->update($request->validated());

        return response()->json([
            'message' => 'Account updated successfully',
            'data' => new AccountResource($account),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/families/{family}/accounts/{account}",
     *     summary="Delete account",
     *     tags={"Accounts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="account",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account deleted successfully"
     *     )
     * )
     */
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