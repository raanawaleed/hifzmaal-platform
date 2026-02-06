<?php

namespace App\Http\Controllers\Api;

use OpenApi\Annotations as OA;
use App\Http\Controllers\Api\Controller;
use App\Http\Requests\StoreBillRequest;
use App\Http\Requests\UpdateBillRequest;
use App\Http\Resources\BillResource;
use App\Models\Bill;
use App\Models\Family;
use App\Services\BillService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BillController extends ApiController
{
    public function __construct(
        protected BillService $billService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/families/{family}/bills",
     *     summary="Get all bills for a family",
     *     tags={"Bills"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"pending", "paid", "overdue"})
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by type",
     *         @OA\Schema(type="string", enum={"electricity", "gas", "water", "internet", "mobile", "rent", "school_fees", "other"})
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
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request, Family $family): AnonymousResourceCollection
    {
        $this->authorize('view', $family);

        $query = $family->bills()->with('category');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $bills = $query->orderBy('due_date')->get();

        return BillResource::collection($bills);
    }

    /**
     * @OA\Post(
     *     path="/api/families/{family}/bills",
     *     summary="Create a new bill",
     *     tags={"Bills"},
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
     *             required={"category_id", "name", "type", "amount", "due_date", "frequency"},
     *             @OA\Property(property="category_id", type="integer", example=10),
     *             @OA\Property(property="name", type="string", example="Electricity Bill"),
     *             @OA\Property(property="type", type="string", enum={"electricity", "gas", "water", "internet", "mobile", "rent", "school_fees", "other"}, example="electricity"),
     *             @OA\Property(property="amount", type="number", format="float", example=3500.00),
     *             @OA\Property(property="due_date", type="string", format="date", example="2024-02-15"),
     *             @OA\Property(property="frequency", type="string", enum={"monthly", "quarterly", "yearly"}, example="monthly"),
     *             @OA\Property(property="is_recurring", type="boolean", example=true),
     *             @OA\Property(property="auto_pay", type="boolean", example=false),
     *             @OA\Property(property="account_id", type="integer", nullable=true),
     *             @OA\Property(property="provider", type="string", example="K-Electric"),
     *             @OA\Property(property="account_number", type="string", example="12345678"),
     *             @OA\Property(property="split_members", type="array", @OA\Items(type="integer",example=5)),
     *             @OA\Property(property="reminder_days", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Bill created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreBillRequest $request, Family $family): JsonResponse
    {
        $this->authorize('update', $family);
        $bill = $this->billService->createBill($family, $request->validated());

        return response()->json([
            'message' => 'Bill created successfully',
            'data' => new BillResource($bill),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/families/{family}/bills/{bill}",
     *     summary="Get bill by ID",
     *     tags={"Bills"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="bill",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#")
     *     ),
     *     @OA\Response(response=404, description="Bill not found")
     * )
     */
    public function show(Family $family, Bill $bill): BillResource
    {
        $this->authorize('view', $bill);

        if ($bill->family_id !== $family->id) {
            abort(404);
        }

        return new BillResource($bill);
    }

    /**
     * @OA\Put(
     *     path="/api/families/{family}/bills/{bill}",
     *     summary="Update bill",
     *     tags={"Bills"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="bill",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="due_date", type="string", format="date"),
     *             @OA\Property(property="reminder_days", type="integer"),
     *             @OA\Property(property="auto_pay", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bill updated successfully"
     *     )
     * )
     */
    public function update(UpdateBillRequest $request, Family $family, Bill $bill): JsonResponse
    {
        $this->authorize('update', $bill);

        if ($bill->family_id !== $family->id) {
            abort(404);
        }

        $updated = $this->billService->updateBill($bill, $request->validated());

        return response()->json([
            'message' => 'Bill updated successfully',
            'data' => new BillResource($updated),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/families/{family}/bills/{bill}",
     *     summary="Delete bill",
     *     tags={"Bills"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="bill",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bill deleted successfully"
     *     )
     * )
     */
    public function destroy(Family $family, Bill $bill): JsonResponse
    {
        $this->authorize('delete', $bill);

        if ($bill->family_id !== $family->id) {
            abort(404);
        }

        $this->billService->deleteBill($bill);

        return response()->json([
            'message' => 'Bill deleted successfully',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/families/{family}/bills/{bill}/mark-as-paid",
     *     summary="Mark bill as paid",
     *     tags={"Bills"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="bill",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bill marked as paid successfully"
     *     )
     * )
     */
    public function markAsPaid(Family $family, Bill $bill): JsonResponse
    {
        $this->authorize('markAsPaid', $bill);

        if ($bill->family_id !== $family->id) {
            abort(404);
        }

        $this->billService->markAsPaid($bill);

        return response()->json([
            'message' => 'Bill marked as paid successfully',
            'data' => new BillResource($bill->fresh()),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/families/{family}/bills/upcoming",
     *     summary="Get upcoming bills",
     *     tags={"Bills"},
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
    public function upcoming(Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $bills = $this->billService->getUpcomingBills($family);

        return response()->json([
            'data' => $bills,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/families/{family}/bills/overdue",
     *     summary="Get overdue bills",
     *     tags={"Bills"},
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
    public function overdue(Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $bills = $this->billService->getOverdueBills($family);

        return response()->json([
            'data' => $bills,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/families/{family}/bills/statistics",
     *     summary="Get bill statistics",
     *     tags={"Bills"},
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
     *                 @OA\Property(property="total_bills", type="integer"),
     *                 @OA\Property(property="pending_bills", type="integer"),
     *                 @OA\Property(property="overdue_bills", type="integer"),
     *                 @OA\Property(property="paid_this_month", type="integer"),
     *                 @OA\Property(property="total_due_amount", type="number"),
     *                 @OA\Property(property="average_bill_amount", type="number")
     *             )
     *         )
     *     )
     * )
     */
    public function statistics(Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $stats = $this->billService->getBillStatistics($family);

        return response()->json([
            'data' => $stats,
        ]);
    }
}
