<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends ApiController
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'metal_rates' => PlatformSetting::get('zakat.metal_rates'),
                'nisab' => PlatformSetting::get('zakat.nisab'),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'metal_rates.currency' => ['required_with:metal_rates', 'string', 'size:3'],
            'metal_rates.gold_per_gram' => ['required_with:metal_rates', 'numeric', 'min:0.01'],
            'metal_rates.silver_per_gram' => ['required_with:metal_rates', 'numeric', 'min:0.01'],
            'nisab.gold_grams' => ['required_with:nisab', 'numeric', 'min:0.01'],
            'nisab.silver_grams' => ['required_with:nisab', 'numeric', 'min:0.01'],
        ]);

        if (isset($validated['metal_rates'])) {
            PlatformSetting::set('zakat.metal_rates', $validated['metal_rates'] + [
                'updated_by' => $request->user()->id,
            ]);
        }

        if (isset($validated['nisab'])) {
            PlatformSetting::set('zakat.nisab', $validated['nisab']);
        }

        return response()->json([
            'message' => 'Platform settings updated.',
            'data' => [
                'metal_rates' => PlatformSetting::get('zakat.metal_rates'),
                'nisab' => PlatformSetting::get('zakat.nisab'),
            ],
        ]);
    }
}
