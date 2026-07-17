<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Budget extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('financial')
            ->logOnly(['name', 'amount', 'period', 'start_date', 'end_date', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * getSpentAmount() runs a SUM query; every other accessor below calls it,
     * so BudgetResource alone would otherwise re-run that query 5x per row.
     * Safe to cache per-instance — every call site fetches a fresh Budget
     * right before reading these, so there's no mid-request staleness risk.
     */
    protected ?float $spentAmountCache = null;

    protected $fillable = [
        'family_id',
        'category_id',
        'name',
        'amount',
        'period',
        'start_date',
        'end_date',
        'alert_threshold',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'alert_threshold' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function getSpentAmount(): float
    {
        return $this->spentAmountCache ??= (float) $this->category->transactions()
            ->where('family_id', $this->family_id)
            ->where('type', 'expense')
            ->where('status', 'approved')
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->sum('amount');
    }

    public function getPercentageUsed(): float
    {
        if ($this->amount <= 0) {
            return 0;
        }

        $spent = $this->getSpentAmount();
        return ($spent / $this->amount) * 100;
    }

    public function getRemainingAmount(): float
    {
        return max(0, $this->amount - $this->getSpentAmount());
    }

    public function isOverBudget(): bool
    {
        return $this->getSpentAmount() > $this->amount;
    }

    public function shouldAlert(): bool
    {
        return $this->getPercentageUsed() >= $this->alert_threshold;
    }

    public function getDaysRemaining(): int
    {
        return now()->diffInDays($this->end_date, false);
    }
}