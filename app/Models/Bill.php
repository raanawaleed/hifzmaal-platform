<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Bill extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'family_id',
        'category_id',
        'name',
        'type',
        'amount',
        'average_amount',
        'due_date',
        'frequency',
        'is_recurring',
        'auto_pay',
        'account_id',
        'provider',
        'account_number',
        'split_members',
        'reminder_days',
        'status',
        'last_paid_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'average_amount' => 'decimal:2',
        'due_date' => 'date',
        'last_paid_date' => 'date',
        'is_recurring' => 'boolean',
        'auto_pay' => 'boolean',
        'split_members' => 'array',
        'reminder_days' => 'integer',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'overdue');
    }

    public function scopeUpcoming(Builder $query, int $days = 7): Builder
    {
        return $query->where('status', 'pending')
            ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    public function isDue(): bool
    {
        return $this->due_date->isToday() || $this->due_date->isPast();
    }

    public function isOverdue(): bool
    {
        return $this->due_date->isPast() && $this->status !== 'paid';
    }

    public function shouldRemind(): bool
    {
        $reminderDate = $this->due_date->copy()->subDays($this->reminder_days);
        return now()->greaterThanOrEqualTo($reminderDate) 
            && now()->lessThan($this->due_date)
            && $this->status === 'pending';
    }

    public function markAsPaid(?int $transactionId = null): void
    {
        $this->update([
            'status' => 'paid',
            'last_paid_date' => now(),
        ]);

        if ($this->is_recurring) {
            $this->generateNextBill();
        }
    }

    protected function generateNextBill(): void
    {
        $nextDueDate = match($this->frequency) {
            'monthly' => $this->due_date->copy()->addMonth(),
            'quarterly' => $this->due_date->copy()->addMonths(3),
            'yearly' => $this->due_date->copy()->addYear(),
        };

        static::create([
            'family_id' => $this->family_id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'type' => $this->type,
            'amount' => $this->amount,
            'average_amount' => $this->average_amount,
            'due_date' => $nextDueDate,
            'frequency' => $this->frequency,
            'is_recurring' => $this->is_recurring,
            'auto_pay' => $this->auto_pay,
            'account_id' => $this->account_id,
            'provider' => $this->provider,
            'account_number' => $this->account_number,
            'split_members' => $this->split_members,
            'reminder_days' => $this->reminder_days,
            'status' => 'pending',
        ]);
    }

    public function getDaysUntilDue(): int
    {
        return now()->diffInDays($this->due_date, false);
    }
}