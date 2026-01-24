<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ZakatPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'zakat_calculation_id',
        'family_id',
        'recipient_id',
        'transaction_id',
        'amount',
        'payment_date',
        'type',
        'recipient_name',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function zakatCalculation(): BelongsTo
    {
        return $this->belongsTo(ZakatCalculation::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(ZakatRecipient::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function scopeZakat(Builder $query): Builder
    {
        return $query->where('type', 'zakat');
    }

    public function scopeSadaqah(Builder $query): Builder
    {
        return $query->where('type', 'sadaqah');
    }

    public function scopeFitrah(Builder $query): Builder
    {
        return $query->where('type', 'fitrah');
    }

    public function scopeForYear(Builder $query, int $hijriYear): Builder
    {
        return $query->whereHas('zakatCalculation', function ($q) use ($hijriYear) {
            $q->where('hijri_year', $hijriYear);
        });
    }
}
