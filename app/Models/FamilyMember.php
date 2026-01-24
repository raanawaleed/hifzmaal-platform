<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'family_id',
        'user_id',
        'name',
        'email',
        'relationship',
        'role',
        'date_of_birth',
        'is_active',
        'spending_limit',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
        'spending_limit' => 'decimal:2',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canApprove(): bool
    {
        return in_array($this->role, ['owner', 'approver']);
    }

    public function canEdit(): bool
    {
        return in_array($this->role, ['owner', 'editor', 'approver']);
    }

    public function canView(): bool
    {
        return $this->is_active;
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function getAge(): ?int
    {
        return $this->date_of_birth?->age;
    }
}
