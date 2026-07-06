<?php

use App\Services\BillService;
use App\Services\SavingsGoalService;
use App\Services\ZakatService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily housekeeping — requires a system cron entry running
// `php artisan schedule:run` every minute (see INSTALLATION.md).
Schedule::call(fn () => app(BillService::class)->sendBillReminders())
    ->dailyAt('08:00')
    ->name('bills:send-reminders')
    ->onOneServer();

Schedule::call(fn () => app(BillService::class)->checkOverdueBills())
    ->dailyAt('00:30')
    ->name('bills:check-overdue')
    ->onOneServer();

Schedule::call(fn () => app(ZakatService::class)->sendZakatReminders())
    ->weeklyOn(5, '08:00') // Friday morning
    ->name('zakat:send-reminders')
    ->onOneServer();

Schedule::call(fn () => app(SavingsGoalService::class)->processAutoContributions())
    ->dailyAt('01:00') // service filters goals by their contribution_day
    ->name('savings:auto-contribute')
    ->onOneServer();
