<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateZakatCalculationRequest extends FormRequest
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
            'cash_in_hand' => ['sometimes', 'numeric', 'min:0'],
            'cash_in_bank' => ['sometimes', 'numeric', 'min:0'],
            'gold_value' => ['sometimes', 'numeric', 'min:0'],
            'silver_value' => ['sometimes', 'numeric', 'min:0'],
            'business_inventory' => ['sometimes', 'numeric', 'min:0'],
            'investments' => ['sometimes', 'numeric', 'min:0'],
            'loans_receivable' => ['sometimes', 'numeric', 'min:0'],
            'other_assets' => ['sometimes', 'numeric', 'min:0'],
            'debts' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
