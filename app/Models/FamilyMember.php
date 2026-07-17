<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class FamilyMember extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('financial')
            // Not 'email' — no need to duplicate PII into another table.
            ->logOnly(['name', 'role', 'is_active', 'spending_limit'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

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
        'invitation_expires_at' => 'datetime',
        'invitation_accepted_at' => 'datetime',
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
        return $this->role === 'owner';
    }

    public function canEdit(): bool
    {
        return in_array($this->role, ['owner', 'member']);
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
