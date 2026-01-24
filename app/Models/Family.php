<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Family extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'currency',
        'locale',
        'owner_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function savingsGoals(): HasMany
    {
        return $this->hasMany(SavingsGoal::class);
    }

    public function zakatCalculations(): HasMany
    {
        return $this->hasMany(ZakatCalculation::class);
    }

    public function zakatRecipients(): HasMany
    {
        return $this->hasMany(ZakatRecipient::class);
    }

    public function zakatPayments(): HasMany
    {
        return $this->hasMany(ZakatPayment::class);
    }

    public function getTotalBalance(): float
    {
        return $this->accounts()->where('is_active', true)->sum('balance');
    }

    public function getMonthlyIncome(int $month, int $year): float
    {
        return $this->transactions()
            ->where('type', 'income')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->sum('amount');
    }

    public function getMonthlyExpense(int $month, int $year): float
    {
        return $this->transactions()
            ->where('type', 'expense')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->sum('amount');
    }

    public function getActiveMembers(): int
    {
        return $this->members()->where('is_active', true)->count();
    }
}
