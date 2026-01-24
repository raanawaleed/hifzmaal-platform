<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

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
    ];

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
        $role = $this->getFamilyMemberRole($family);
        return in_array($role, ['owner', 'approver']);
    }

    public function canEditFamily(Family $family): bool
    {
        $role = $this->getFamilyMemberRole($family);
        return in_array($role, ['owner', 'editor', 'approver']);
    }
}