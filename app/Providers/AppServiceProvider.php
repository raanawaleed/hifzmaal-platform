<?php

namespace App\Providers;

use App\Models\Family;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // We register our own /api/stripe/webhook route (routes/api.php) to
        // keep it consistent with the rest of this API-only app; Cashier's
        // default web-group routes would otherwise duplicate it.
        Cashier::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Laravel's default ResetPassword notification links to a
        // `password.reset` named route, which doesn't exist in this
        // API-only app (previously threw RouteNotFoundException the moment
        // anyone requested a reset — masked in tests by Notification::fake()).
        // Point it at the SPA's own reset page instead.
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            return config('app.frontend_url').'/reset-password?token='.$token.'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });

        // Centralizes family_id resolution for every LogsActivity model in
        // one place instead of repeating it per model — activity_log.family_id
        // is what makes GET /families/{family}/activity a plain indexed
        // WHERE instead of a join through N different subject types.
        Activity::creating(function (Activity $activity) {
            $subject = $activity->subject;

            if ($subject instanceof Family) {
                $activity->family_id = $subject->id;
            } elseif ($subject && isset($subject->family_id)) {
                $activity->family_id = $subject->family_id;
            }
        });
    }
}
