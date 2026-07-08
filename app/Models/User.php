<?php

namespace App\Models;

use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use Billable, HasApiTokens, HasFactory, MustVerifyEmail, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'timezone',
        'hijri_year_start_date',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'hijri_year_start_date' => 'date',
        'is_active' => 'boolean',
        'suspended_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('superadmin');
    }

    public function ownedFamilies(): HasMany
    {
        return $this->hasMany(Family::class, 'owner_id');
    }

    public function familyMemberships(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function createdTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    public function approvedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'approved_by');
    }

    public function currentFamily(): ?Family
    {
        return $this->ownedFamilies()->first() 
            ?? $this->familyMemberships()->with('family')->first()?->family;
    }

    public function hasAccessToFamily(Family $family): bool
    {
        return $this->ownedFamilies()->where('id', $family->id)->exists()
            || $this->familyMemberships()->where('family_id', $family->id)->where('is_active', true)->exists();
    }

    public function getFamilyMemberRole(Family $family): ?string
    {
        if ($this->ownedFamilies()->where('id', $family->id)->exists()) {
            return 'owner';
        }

        $membership = $this->familyMemberships()
            ->where('family_id', $family->id)
            ->where('is_active', true)
            ->first();

        return $membership?->role;
    }

    public function canApproveTransactions(Family $family): bool
    {
        return $this->getFamilyMemberRole($family) === 'owner';
    }

    public function canEditFamily(Family $family): bool
    {
        return in_array($this->getFamilyMemberRole($family), ['owner', 'member']);
    }

    /**
     * True if billing places no plan limits on this account: an active
     * Stripe subscription, or still inside the no-card-required trial.
     */
    public function hasProAccess(): bool
    {
        return $this->subscribed('default') || $this->onGenericTrial();
    }

    /**
     * Max number of families this user may own, or null when unlimited
     * (Pro access). Enforced in FamilyController::store().
     */
    public function familyLimit(): ?int
    {
        return $this->hasProAccess() ? null : (int) config('billing.free.max_families');
    }

    public function canCreateAnotherFamily(): bool
    {
        $limit = $this->familyLimit();

        return $limit === null || $this->ownedFamilies()->count() < $limit;
    }

    /**
     * Laravel's stock notifications send synchronously; queue both so a
     * slow/down mail server can't hang or fail /api/register,
     * /api/forgot-password, or the resend-verification endpoint.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new QueuedResetPassword($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new QueuedVerifyEmail());
    }
}