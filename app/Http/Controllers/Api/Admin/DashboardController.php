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
        $signupsByMonth = User::query()
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->get()
            ->groupBy(fn ($user) => $user->created_at->format('Y-m'))
            ->map->count();

        $months = collect(range(11, 0))
            ->map(fn ($i) => now()->subMonths($i)->format('Y-m'));

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
                'signups' => $months->map(fn ($month) => [
                    'month' => $month,
                    'count' => $signupsByMonth[$month] ?? 0,
                ])->values(),
                'recent_users' => User::latest()->limit(5)->get(['id', 'name', 'email', 'created_at']),
            ],
        ]);
    }
}
