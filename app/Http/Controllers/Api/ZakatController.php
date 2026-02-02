<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreZakatCalculationRequest;
use App\Http\Resources\ZakatCalculationResource;
use App\Http\Resources\ZakatPaymentResource;
use App\Models\Family;
use App\Models\ZakatCalculation;
use App\Models\ZakatRecipient;
use App\Services\ZakatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ZakatController extends Controller
{
    public function __construct(
        protected ZakatService $zakatService
    ) {}

    public function index(Family $family): AnonymousResourceCollection
    {
        $this->authorize('view', $family);

        $calculations = $family->zakatCalculations()
            ->orderBy('hijri_year', 'desc')
            ->get();

        return ZakatCalculationResource::collection($calculations);
    }

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

    public function show(Family $family, ZakatCalculation $calculation): ZakatCalculationResource
    {
        $this->authorize('view', $family);

        if ($calculation->family_id !== $family->id) {
            abort(404);
        }

        $calculation->load('payments');

        return new ZakatCalculationResource($calculation);
    }

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

    public function nisabAmount(Request $request): JsonResponse
    {
        $type = $request->get('type', 'silver');
        $currency = $request->get('currency', 'PKR');

        $nisabAmount = $this->zakatService->getNisabAmount($type, $currency);

        return response()->json([
            'data' => [
                'nisab_amount' => $nisabAmount,
                'type' => $type,
                'currency' => $currency,
            ],
        ]);
    }

    public function recordPayment(Request $request, Family $family, ZakatCalculation $calculation): JsonResponse
    {
        $this->authorize('update', $family);

        if ($calculation->family_id !== $family->id) {
            abort(404);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'nullable|date',
            'type' => 'required|in:zakat,sadaqah,fitrah',
            'recipient_id' => 'nullable|exists:zakat_recipients,id',
            'recipient_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $payment = $this->zakatService->recordPayment($calculation, $validated);

        return response()->json([
            'message' => 'Zakat payment recorded successfully',
            'data' => new ZakatPaymentResource($payment),
        ], 201);
    }

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

    public function history(Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $history = $this->zakatService->getZakatHistory($family);

        return response()->json([
            'data' => $history,
        ]);
    }

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

    public function storeRecipient(Request $request, Family $family): JsonResponse
    {
        $this->authorize('update', $family);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact' => 'nullable|string|max:255',
            'category' => 'required|in:fuqara,masakin,amilin,muallaf,riqab,gharimin,fisabilillah,ibnus_sabil',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $recipient = $family->zakatRecipients()->create($validated);

        return response()->json([
            'message' => 'Zakat recipient added successfully',
            'data' => $recipient,
        ], 201);
    }

    public function updateRecipient(Request $request, Family $family, ZakatRecipient $recipient): JsonResponse
    {
        $this->authorize('update', $family);

        if ($recipient->family_id !== $family->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'contact' => 'nullable|string|max:255',
            'category' => 'sometimes|in:fuqara,masakin,amilin,muallaf,riqab,gharimin,fisabilillah,ibnus_sabil',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $recipient->update($validated);

        return response()->json([
            'message' => 'Zakat recipient updated successfully',
            'data' => $recipient,
        ]);
    }
}