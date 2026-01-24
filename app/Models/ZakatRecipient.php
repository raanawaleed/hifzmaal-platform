<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ZakatRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'name',
        'contact',
        'category',
        'address',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ZakatPayment::class, 'recipient_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function getTotalReceived(): float
    {
        return $this->payments()->sum('amount');
    }

    public function getLastPaymentDate(): ?string
    {
        return $this->payments()->latest('payment_date')->value('payment_date');
    }

    public function getCategoryLabel(): string
    {
        return match ($this->category) {
            'fuqara' => 'The Poor (الفقراء)',
            'masakin' => 'The Needy (المساكين)',
            'amilin' => 'Zakat Administrators (العاملين عليها)',
            'muallaf' => 'New Muslims (المؤلفة قلوبهم)',
            'riqab' => 'Freeing Slaves (في الرقاب)',
            'gharimin' => 'Those in Debt (الغارمين)',
            'fisabilillah' => 'In the Cause of Allah (في سبيل الله)',
            'ibnus_sabil' => 'Stranded Travelers (ابن السبيل)',
            default => 'Unknown',
        };
    }
}
