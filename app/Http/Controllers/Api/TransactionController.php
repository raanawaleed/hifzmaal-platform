<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Controllers\Api\Controller;
use App\Models\Family;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends ApiController
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/families/{family}/transactions",
     *     summary="Get all transactions for a family",
     *     tags={"Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by transaction type",
     *         @OA\Schema(type="string", enum={"income", "expense", "transfer"})
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"pending", "approved", "rejected"})
     *     ),
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by category",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="account_id",
     *         in="query",
     *         description="Filter by account",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Filter from date",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Filter to date",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=15)
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
     *             ),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request, Family $family): AnonymousResourceCollection
    {
        $this->authorize('view', $family);

        $query = $family->transactions()
            ->with(['account', 'category', 'creator', 'approver']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        $sortBy = $request->get('sort_by', 'date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $transactions = $query->paginate($request->get('per_page', 15));

        return TransactionResource::collection($transactions);
    }

    /**
     * @OA\Post(
     *     path="/api/families/{family}/transactions",
     *     summary="Create a new transaction",
     *     tags={"Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"account_id", "category_id", "type", "amount", "date"},
     *                 @OA\Property(property="account_id", type="integer", example=1),
     *                 @OA\Property(property="category_id", type="integer", example=5),
     *                 @OA\Property(property="type", type="string", enum={"income", "expense", "transfer"}, example="expense"),
     *                 @OA\Property(property="amount", type="number", format="float", example=1500.50),
     *                 @OA\Property(property="date", type="string", format="date", example="2024-01-28"),
     *                 @OA\Property(property="description", type="string", example="Grocery shopping"),
     *                 @OA\Property(property="notes", type="string", example="Monthly groceries from supermarket"),
     *                 @OA\Property(property="transfer_to_account_id", type="integer", example=2),
     *                 @OA\Property(property="is_recurring", type="boolean", example=false),
     *                 @OA\Property(property="recurring_frequency", type="string", enum={"daily", "weekly", "monthly", "yearly"}),
     *                 @OA\Property(property="recurring_end_date", type="string", format="date"),
     *                 @OA\Property(
     *                     property="receipts[]",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transaction created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreTransactionRequest $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $transaction = $this->transactionService->createTransaction($family, $request->validated());

        return response()->json([
            'message' => 'Transaction created successfully',
            'data' => new TransactionResource($transaction),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/families/{family}/transactions/{transaction}",
     *     summary="Get transaction by ID",
     *     tags={"Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="transaction",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#")
     *     ),
     *     @OA\Response(response=404, description="Transaction not found")
     * )
     */
    public function show(Family $family, Transaction $transaction): TransactionResource
    {
        $this->authorize('view', $transaction);

        if ($transaction->family_id !== $family->id) {
            abort(404);
        }

        $transaction->load(['account', 'category', 'creator', 'approver', 'transferToAccount']);

        return new TransactionResource($transaction);
    }

    /**
     * @OA\Put(
     *     path="/api/families/{family}/transactions/{transaction}",
     *     summary="Update transaction",
     *     tags={"Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="transaction",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction updated successfully"
     *     )
     * )
     */
    public function update(UpdateTransactionRequest $request, Family $family, Transaction $transaction): JsonResponse
    {
        $this->authorize('update', $transaction);

        if ($transaction->family_id !== $family->id) {
            abort(404);
        }

        $updated = $this->transactionService->updateTransaction($transaction, $request->validated());

        return response()->json([
            'message' => 'Transaction updated successfully',
            'data' => new TransactionResource($updated),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/families/{family}/transactions/{transaction}",
     *     summary="Delete transaction",
     *     tags={"Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="transaction",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction deleted successfully"
     *     )
     * )
     */
    public function destroy(Family $family, Transaction $transaction): JsonResponse
    {
        $this->authorize('delete', $transaction);

        if ($transaction->family_id !== $family->id) {
            abort(404);
        }

        $this->transactionService->deleteTransaction($transaction);

        return response()->json([
            'message' => 'Transaction deleted successfully',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/families/{family}/transactions/{transaction}/approve",
     *     summary="Approve pending transaction",
     *     tags={"Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="transaction",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction approved successfully"
     *     )
     * )
     */
    public function approve(Family $family, Transaction $transaction): JsonResponse
    {
        $this->authorize('approve', $transaction);

        if ($transaction->family_id !== $family->id) {
            abort(404);
        }

        $this->transactionService->approveTransaction($transaction);

        return response()->json([
            'message' => 'Transaction approved successfully',
            'data' => new TransactionResource($transaction->fresh()),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/families/{family}/transactions/{transaction}/reject",
     *     summary="Reject pending transaction",
     *     tags={"Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="transaction",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction rejected successfully"
     *     )
     * )
     */
    public function reject(Family $family, Transaction $transaction): JsonResponse
    {
        $this->authorize('reject', $transaction);

        if ($transaction->family_id !== $family->id) {
            abort(404);
        }

        $this->transactionService->rejectTransaction($transaction);

        return response()->json([
            'message' => 'Transaction rejected successfully',
            'data' => new TransactionResource($transaction->fresh()),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/families/{family}/transactions/pending",
     *     summary="Get pending transactions",
     *     tags={"Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function pending(Family $family): AnonymousResourceCollection
    {
        $this->authorize('view', $family);

        $transactions = $family->transactions()
            ->with(['account', 'category', 'creator'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return TransactionResource::collection($transactions);
    }
}