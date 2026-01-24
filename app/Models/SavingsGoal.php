<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class SavingsGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'account_id',
        'name',
        'type',
        'target_amount',
        'current_amount',
        'monthly_contribution',
        'target_date',
        'start_date',
        'description',
        'dua_reminder',
        'auto_contribute',
        'contribution_day',
        'is_active',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'monthly_contribution' => 'decimal:2',
        'target_date' => 'date',
        'start_date' => 'date',
        'auto_contribute' => 'boolean',
        'contribution_day' => 'integer',
        'is_active' => 'boolean',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereColumn('current_amount', '>=', 'target_amount');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereColumn('current_amount', '<', 'target_amount');
    }

    public function getProgressPercentage(): float
    {
        if ($this->target_amount <= 0) {
            return 0;
        }

        return min(100, ($this->current_amount / $this->target_amount) * 100);
    }

    public function getRemainingAmount(): float
    {
        return max(0, $this->target_amount - $this->current_amount);
    }

    public function getEstimatedCompletionDate(): ?Carbon
    {
        if (!$this->monthly_contribution || $this->monthly_contribution <= 0) {
            return null;
        }

        $remaining = $this->getRemainingAmount();
        if ($remaining <= 0) {
            return now();
        }

        $monthsNeeded = ceil($remaining / $this->monthly_contribution);
        return now()->addMonths($monthsNeeded);
    }

    public function isCompleted(): bool
    {
        return $this->current_amount >= $this->target_amount;
    }

    public function isOverdue(): bool
    {
        if (!$this->target_date) {
            return false;
        }

        return $this->target_date->isPast() && !$this->isCompleted();
    }

    public function contribute(float $amount): void
    {
        $this->increment('current_amount', $amount);
    }

    public function getDaysRemaining(): ?int
    {
        if (!$this->target_date) {
            return null;
        }

        return now()->diffInDays($this->target_date, false);
    }
}