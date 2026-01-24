<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'family_id',
        'name',
        'type',
        'currency',
        'balance',
        'initial_balance',
        'account_number',
        'bank_name',
        'is_active',
        'include_in_zakat',
        'description',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'initial_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'include_in_zakat' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Account $account) {
            if (is_null($account->balance)) {
                $account->balance = $account->initial_balance;
            }
        });
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function transferTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'transfer_to_account_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function savingsGoals(): HasMany
    {
        return $this->hasMany(SavingsGoal::class);
    }

    public function updateBalance(float $amount, string $type): void
    {
        if ($type === 'income' || $type === 'transfer_in') {
            $this->increment('balance', $amount);
        } else {
            $this->decrement('balance', $amount);
        }
    }

    public function getTotalIncome(): float
    {
        return $this->transactions()
            ->where('type', 'income')
            ->where('status', 'approved')
            ->sum('amount');
    }

    public function getTotalExpense(): float
    {
        return $this->transactions()
            ->where('type', 'expense')
            ->where('status', 'approved')
            ->sum('amount');
    }
}