<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('family'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'type' => ['required', 'in:cash,bank,wallet,savings,investment'],
            'currency' => ['nullable', 'string', 'size:3', 'in:PKR,USD,EUR,GBP,SAR,AED,INR,BDT'],
            'initial_balance' => ['required', 'numeric', 'min:0'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255', 'required_if:type,bank'],
            'include_in_zakat' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Account name is required.',
            'name.min' => 'Account name must be at least 2 characters.',
            'name.max' => 'Account name cannot exceed 255 characters.',
            'type.required' => 'Account type is required.',
            'type.in' => 'Invalid account type. Must be cash, bank, wallet, savings, or investment.',
            'currency.size' => 'Currency code must be exactly 3 characters.',
            'currency.in' => 'Selected currency is not supported.',
            'initial_balance.required' => 'Initial balance is required.',
            'initial_balance.min' => 'Initial balance cannot be negative.',
            'bank_name.required_if' => 'Bank name is required for bank accounts.',
            'account_number.max' => 'Account number cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'account name',
            'type' => 'account type',
            'initial_balance' => 'initial balance',
            'bank_name' => 'bank name',
            'account_number' => 'account number',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->include_in_zakat === null) {
            $this->merge(['include_in_zakat' => true]);
        }
    }
}