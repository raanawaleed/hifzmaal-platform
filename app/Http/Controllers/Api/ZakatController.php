<?php

namespace App\Http\Controllers\Api;

use App\Models\Family;
use App\Http\Controllers\Api\Controller;
use App\Models\ZakatCalculation;
use App\Models\ZakatRecipient;
use App\Services\ZakatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;
use App\Http\Requests\StoreZakatCalculationRequest;
use App\Http\Requests\UpdateZakatCalculationRequest;
use App\Http\Requests\StoreZakatPaymentRequest;
use App\Http\Requests\StoreZakatRecipientRequest;
use App\Http\Requests\UpdateZakatRecipientRequest;
use App\Http\Resources\ZakatCalculationResource;
use App\Http\Resources\ZakatPaymentResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ZakatController extends Controller
{
    public function __construct(
        protected ZakatService $zakatService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/families/{family}/zakat",
     *     summary="Get all zakat calculations for a family",
     *     tags={"Zakat"},
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
     *                 @OA\Items(ref="#/components/schemas/ZakatCalculation")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Family $family): AnonymousResourceCollection
    {
        $this->authorize('view', $family);

        $calculations = $family->zakatCalculations()
            ->orderBy('hijri_year', 'desc')
            ->get();

        return ZakatCalculationResource::collection($calculations);
    }

    /**
     * @OA\Post(
     *     path="/api/families/{family}/zakat",
     *     summary="Create new zakat calculation",
     *     tags={"Zakat"},
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
     *             required={"hijri_year", "cash_in_hand", "cash_in_bank", "nisab_type"},
     *             @OA\Property(property="hijri_year", type="integer", example=1445),
     *             @OA\Property(property="cash_in_hand", type="number", format="float", example=50000),
     *             @OA\Property(property="cash_in_bank", type="number", format="float", example=150000),
     *             @OA\Property(property="gold_value", type="number", format="float", example=100000),
     *             @OA\Property(property="silver_value", type="number", format="float", example=0),
     *             @OA\Property(property="business_inventory", type="number", format="float", example=0),
     *             @OA\Property(property="investments", type="number", format="float", example=0),
     *             @OA\Property(property="loans_receivable", type="number", format="float", example=0),
     *             @OA\Property(property="other_assets", type="number", format="float", example=0),
     *             @OA\Property(property="debts", type="number", format="float", example=20000),
     *             @OA\Property(property="nisab_type", type="string", enum={"gold", "silver"}, example="silver"),
     *             @OA\Property(property="notes", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Zakat calculated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/ZakatCalculation")
     *         )
     *     )
     * )
     */
    public function store(StoreZakatCalculationRequest $request, Family $family): JsonResponse
    {
        $this->authorize('update', $family);

        $calculation = $this->zakatService->calculateZakat(
            $family,
            $request->hijri_year,
            $request->validated()
        );

        return response()->json([
            'message' => 'Zakat calculated successfully',
            'data' => new ZakatCalculationResource($calculation),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/families/{family}/zakat/{calculation}",
     *     summary="Get zakat calculation by ID",
     *     tags={"Zakat"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="calculation",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/ZakatCalculation")
     *     )
     * )
     */
    public function show(Family $family, ZakatCalculation $calculation): ZakatCalculationResource
    {
        $this->authorize('view', $family);

        if ($calculation->family_id !== $family->id) {
            abort(404);
        }

        $calculation->load('payments');

        return new ZakatCalculationResource($calculation);
    }

    /**
     * @OA\Post(
     *     path="/api/families/{family}/zakat/auto-calculate",
     *     summary="Auto-calculate zakat from accounts",
     *     tags={"Zakat"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Zakat auto-calculated successfully"
     *     )
     * )
     */
    public function autoCalculate(Family $family): JsonResponse
    {
        $this->authorize('update', $family);

        $hijriYear = $this->zakatService->getCurrentHijriYear();
        $calculation = $this->zakatService->autoCalculateFromAccounts($family, $hijriYear);

        return response()->json([
            'message' => 'Zakat auto-calculated successfully',
            'data' => new ZakatCalculationResource($calculation),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/families/{family}/zakat/nisab-amount",
     *     summary="Get current nisab amount",
     *     tags={"Zakat"},
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
     *         description="Nisab type",
     *         @OA\Schema(type="string", enum={"gold", "silver"}, default="silver")
     *     ),
     *     @OA\Parameter(
     *         name="currency",
     *         in="query",
     *         description="Currency code",
     *         @OA\Schema(type="string", default="PKR")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function nisabAmount(Request $request, Family $family): JsonResponse
    {
        $type = $request->get('type', 'silver');
        $currency = $request->get('currency', $family->currency);

        $nisabAmount = $this->zakatService->getNisabAmount($type, $currency);

        return response()->json([
            'data' => [
                'nisab_amount' => $nisabAmount,
                'type' => $type,
                'currency' => $currency,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/families/{family}/zakat/{calculation}/payments",
     *     summary="Record zakat payment",
     *     tags={"Zakat"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="calculation",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount", "type"},
     *             @OA\Property(property="amount", type="number", format="float", example=2500),
     *             @OA\Property(property="payment_date", type="string", format="date"),
     *             @OA\Property(property="type", type="string", enum={"zakat", "sadaqah", "fitrah"}),
     *             @OA\Property(property="recipient_id", type="integer", nullable=true),
     *             @OA\Property(property="recipient_name", type="string"),
     *             @OA\Property(property="notes", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Zakat payment recorded successfully"
     *     )
     * )
     */
    public function recordPayment(StoreZakatPaymentRequest $request, Family $family, ZakatCalculation $calculation): JsonResponse
    {
        $this->authorize('update', $family);

        if ($calculation->family_id !== $family->id) {
            abort(404);
        }

        $payment = $this->zakatService->recordPayment($calculation, $request->validated());

        return response()->json([
            'message' => 'Zakat payment recorded successfully',
            'data' => new ZakatPaymentResource($payment),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/families/{family}/zakat/{calculation}/payments",
     *     summary="Get all payments for a zakat calculation",
     *     tags={"Zakat"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="calculation",
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
    public function payments(Family $family, ZakatCalculation $calculation): AnonymousResourceCollection
    {
        $this->authorize('view', $family);

        if ($calculation->family_id !== $family->id) {
            abort(404);
        }

        $payments = $calculation->payments()
            ->with('recipient')
            ->orderBy('payment_date', 'desc')
            ->get();

        return ZakatPaymentResource::collection($payments);
    }

    /**
     * @OA\Get(
     *     path="/api/families/{family}/zakat/history",
     *     summary="Get zakat history",
     *     tags={"Zakat"},
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
    public function history(Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $history = $this->zakatService->getZakatHistory($family);

        return response()->json([
            'data' => $history,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/families/{family}/zakat/recipients",
     *     summary="Get all zakat recipients",
     *     tags={"Zakat"},
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
    public function recipients(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $recipients = $family->zakatRecipients()
            ->where('is_active', true)
            ->get();

        return response()->json([
            'data' => $recipients,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/families/{family}/zakat/recipients",
     *     summary="Add new zakat recipient",
     *     tags={"Zakat"},
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
     *             required={"name", "category"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="contact", type="string", nullable=true),
     *             @OA\Property(property="category", type="string", enum={"fuqara", "masakin", "amilin", "muallaf", "riqab", "gharimin", "fisabilillah", "ibnus_sabil"}),
     *             @OA\Property(property="address", type="string", nullable=true),
     *             @OA\Property(property="notes", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Zakat recipient added successfully"
     *     )
     * )
     */
    public function storeRecipient(StoreZakatRecipientRequest $request, Family $family): JsonResponse
    {
        $this->authorize('update', $family);

        $recipient = $family->zakatRecipients()->create($request->validated());

        return response()->json([
            'message' => 'Zakat recipient added successfully',
            'data' => $recipient,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/families/{family}/zakat/recipients/{recipient}",
     *     summary="Update zakat recipient",
     *     tags={"Zakat"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="recipient",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="contact", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Zakat recipient updated successfully"
     *     )
     * )
     */
    public function updateRecipient(UpdateZakatRecipientRequest $request, Family $family, ZakatRecipient $recipient): JsonResponse
    {
        $this->authorize('update', $family);

        if ($recipient->family_id !== $family->id) {
            abort(404);
        }

        $recipient->update($request->validated());

        return response()->json([
            'message' => 'Zakat recipient updated successfully',
            'data' => $recipient,
        ]);
    }
}
