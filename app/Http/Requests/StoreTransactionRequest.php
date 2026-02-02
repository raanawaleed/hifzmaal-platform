<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('family'));
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'exists:accounts,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'type' => ['required', 'in:income,expense,transfer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'size:3'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'transfer_to_account_id' => ['required_if:type,transfer', 'exists:accounts,id', 'different:account_id'],
            'is_recurring' => ['boolean'],
            'recurring_frequency' => ['required_if:is_recurring,true', 'in:daily,weekly,monthly,yearly'],
            'recurring_end_date' => ['nullable', 'date', 'after:date'],
            'receipts' => ['nullable', 'array', 'max:5'],
            'receipts.*' => ['file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'account_id.required' => 'Please select an account.',
            'account_id.exists' => 'Selected account does not exist.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'Selected category does not exist.',
            'type.required' => 'Transaction type is required.',
            'type.in' => 'Invalid transaction type.',
            'amount.required' => 'Amount is required.',
            'amount.min' => 'Amount must be greater than 0.',
            'date.required' => 'Transaction date is required.',
            'date.before_or_equal' => 'Transaction date cannot be in the future.',
            'transfer_to_account_id.required_if' => 'Please select destination account for transfer.',
            'transfer_to_account_id.different' => 'Source and destination accounts must be different.',
            'recurring_frequency.required_if' => 'Please select recurring frequency.',
            'receipts.max' => 'You can upload maximum 5 receipts.',
            'receipts.*.mimes' => 'Receipts must be JPEG, PNG or PDF files.',
            'receipts.*.max' => 'Each receipt must not exceed 5MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'account_id' => 'account',
            'category_id' => 'category',
            'transfer_to_account_id' => 'destination account',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->is_recurring === null) {
            $this->merge(['is_recurring' => false]);
        }
    }
}
