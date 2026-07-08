<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Family;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends ApiController
{
    public function index(): JsonResponse
    {
        // 12 small COUNT queries, not a full row fetch of every user signed
        // up in the last year — that scan gets more expensive every month as
        // the user base grows, and it was pulling entire User rows (hashed
        // passwords included) into PHP just to tally them.
        $signups = collect(range(11, 0))->map(function (int $i) {
            $month = now()->subMonths($i);

            return [
                'month' => $month->format('Y-m'),
                'count' => User::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count(),
            ];
        });

        return response()->json([
            'data' => [
                'totals' => [
                    'users' => User::count(),
                    'active_users' => User::where('is_active', true)->count(),
                    'suspended_users' => User::where('is_active', false)->count(),
                    'families' => Family::count(),
                    'transactions' => Transaction::count(),
                    'transaction_volume' => (float) Transaction::where('status', 'approved')->sum('amount'),
                ],
                'signups' => $signups->values(),
                'recent_users' => User::latest()->limit(5)->get(['id', 'name', 'email', 'created_at']),
            ],
        ]);
    }
}
