<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FamilyController;
use App\Http\Controllers\Api\FamilyMemberController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\SavingsGoalController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\ZakatController;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Controllers\WebhookController as StripeWebhookController;

// Authentication Routes (Public, tightly throttled)
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Stripe calls this directly — no session, no Sanctum token. Signature is
// verified inside Cashier's controller using STRIPE_WEBHOOK_SECRET.
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');

// Public invitation preview — lets someone without an account yet see
// what they're being invited to before logging in or registering.
Route::get('/invitations/{token}', [InvitationController::class, 'show'])
    ->middleware('throttle:20,1');

// Protected Routes
Route::middleware(['auth:sanctum', 'active'])->group(function () {

    // Auth
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
        ->middleware('throttle:6,1');
    Route::delete('/account', [AuthController::class, 'deleteAccount']);
    Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept']);

    // Billing (Stripe, USD — see config/billing.php)
    Route::prefix('billing')->group(function () {
        Route::get('status', [BillingController::class, 'status']);
        Route::post('checkout', [BillingController::class, 'checkout'])->middleware('verified');
        Route::get('portal', [BillingController::class, 'portal']);
    });

    // Family Routes
    Route::apiResource('families', FamilyController::class);

    // Family-scoped Routes
    Route::prefix('families/{family}')->middleware('family.access')->group(function () {
        
        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('insights', [DashboardController::class, 'insights']);
        
        // Family Members
        Route::post('members/{member}/resend-invitation', [FamilyMemberController::class, 'resendInvitation']);
        Route::apiResource('members', FamilyMemberController::class);
        
        // Accounts
        Route::apiResource('accounts', AccountController::class);
        
        // Transactions
        Route::get('transactions/pending', [TransactionController::class, 'pending']);
        Route::post('transactions/{transaction}/approve', [TransactionController::class, 'approve']);
        Route::post('transactions/{transaction}/reject', [TransactionController::class, 'reject']);
        Route::apiResource('transactions', TransactionController::class);
        
        // Categories
        Route::apiResource('categories', CategoryController::class);
        
        // Budgets
        Route::get('budgets/overview', [BudgetController::class, 'overview']);
        Route::apiResource('budgets', BudgetController::class);
        
        // Bills
        Route::get('bills/upcoming', [BillController::class, 'upcoming']);
        Route::get('bills/overdue', [BillController::class, 'overdue']);
        Route::get('bills/statistics', [BillController::class, 'statistics']);
        Route::post('bills/{bill}/mark-as-paid', [BillController::class, 'markAsPaid']);
        Route::apiResource('bills', BillController::class);
        
        // Savings Goals
        Route::get('savings-goals/overview', [SavingsGoalController::class, 'overview']);
        Route::post('savings-goals/{savingsGoal}/contribute', [SavingsGoalController::class, 'contribute']);
        Route::apiResource('savings-goals', SavingsGoalController::class);
        
        // Zakat
        Route::get('zakat/history', [ZakatController::class, 'history']);
        Route::post('zakat/auto-calculate', [ZakatController::class, 'autoCalculate']);
        Route::get('zakat/nisab-amount', [ZakatController::class, 'nisabAmount']);
        Route::get('zakat/recipients', [ZakatController::class, 'recipients']);
        Route::post('zakat/recipients', [ZakatController::class, 'storeRecipient']);
        Route::put('zakat/recipients/{recipient}', [ZakatController::class, 'updateRecipient']);
        Route::get('zakat/{calculation}/payments', [ZakatController::class, 'payments']);
        Route::post('zakat/{calculation}/payments', [ZakatController::class, 'recordPayment']);
        Route::apiResource('zakat', ZakatController::class)->parameters([
            'zakat' => 'calculation'
        ]);
    });

    // Superadmin Panel (platform staff only)
    Route::prefix('admin')->middleware('role:superadmin')->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);

        Route::get('users', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
        Route::get('users/{user}', [\App\Http\Controllers\Api\Admin\UserController::class, 'show']);
        Route::post('users/{user}/suspend', [\App\Http\Controllers\Api\Admin\UserController::class, 'suspend']);
        Route::post('users/{user}/unsuspend', [\App\Http\Controllers\Api\Admin\UserController::class, 'unsuspend']);

        Route::get('families', [\App\Http\Controllers\Api\Admin\FamilyController::class, 'index']);
        Route::get('families/{family}', [\App\Http\Controllers\Api\Admin\FamilyController::class, 'show']);
        Route::delete('families/{family}', [\App\Http\Controllers\Api\Admin\FamilyController::class, 'destroy']);

        Route::apiResource('categories', \App\Http\Controllers\Api\Admin\CategoryController::class)
            ->except(['show']);

        Route::get('settings', [\App\Http\Controllers\Api\Admin\SettingController::class, 'index']);
        Route::put('settings', [\App\Http\Controllers\Api\Admin\SettingController::class, 'update']);
    });
});