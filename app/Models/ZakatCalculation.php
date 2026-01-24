<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ZakatCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'hijri_year',
        'calculation_date',
        'cash_in_hand',
        'cash_in_bank',
        'gold_value',
        'silver_value',
        'business_inventory',
        'investments',
        'loans_receivable',
        'other_assets',
        'debts',
        'total_assets',
        'nisab_amount',
        'nisab_type',
        'zakatable_amount',
        'zakat_due',
        'zakat_paid',
        'zakat_remaining',
        'asset_details',
        'notes',
    ];

    protected $casts = [
        'hijri_year' => 'integer',
        'calculation_date' => 'date',
        'cash_in_hand' => 'decimal:2',
        'cash_in_bank' => 'decimal:2',
        'gold_value' => 'decimal:2',
        'silver_value' => 'decimal:2',
        'business_inventory' => 'decimal:2',
        'investments' => 'decimal:2',
        'loans_receivable' => 'decimal:2',
        'other_assets' => 'decimal:2',
        'debts' => 'decimal:2',
        'total_assets' => 'decimal:2',
        'nisab_amount' => 'decimal:2',
        'zakatable_amount' => 'decimal:2',
        'zakat_due' => 'decimal:2',
        'zakat_paid' => 'decimal:2',
        'zakat_remaining' => 'decimal:2',
        'asset_details' => 'array',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ZakatPayment::class);
    }

    public function scopeForYear(Builder $query, int $hijriYear): Builder
    {
        return $query->where('hijri_year', $hijriYear);
    }

    public function scopeWithPendingZakat(Builder $query): Builder
    {
        return $query->where('zakat_remaining', '>', 0);
    }

    public function calculateZakat(): void
    {
        // Calculate total assets
        $this->total_assets = $this->cash_in_hand 
            + $this->cash_in_bank 
            + $this->gold_value 
            + $this->silver_value 
            + $this->business_inventory 
            + $this->investments 
            + $this->loans_receivable 
            + $this->other_assets;

        // Calculate zakatable amount (assets - debts)
        $this->zakatable_amount = max(0, $this->total_assets - $this->debts);

        // Calculate zakat if above nisab
        if ($this->zakatable_amount >= $this->nisab_amount) {
            $this->zakat_due = $this->zakatable_amount * 0.025; // 2.5%
        } else {
            $this->zakat_due = 0;
        }

        // Calculate remaining zakat
        $this->zakat_remaining = max(0, $this->zakat_due - $this->zakat_paid);
        
        $this->save();
    }

    public function isZakatDue(): bool
    {
        return $this->zakatable_amount >= $this->nisab_amount && $this->zakat_due > 0;
    }

    public function getCompletionPercentage(): float
    {
        if ($this->zakat_due <= 0) {
            return 100;
        }

        return min(100, ($this->zakat_paid / $this->zakat_due) * 100);
    }

    public function isFullyPaid(): bool
    {
        return $this->zakat_remaining <= 0 && $this->zakat_due > 0;
    }
}