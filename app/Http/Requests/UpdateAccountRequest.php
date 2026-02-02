<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('family'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
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
            'type.required' => 'Account type is required.',
            'type.in' => 'Invalid account type selected.',
            'initial_balance.required' => 'Initial balance is required.',
            'initial_balance.min' => 'Initial balance cannot be negative.',
            'bank_name.required_if' => 'Bank name is required for bank accounts.',
        ];
    }
}
