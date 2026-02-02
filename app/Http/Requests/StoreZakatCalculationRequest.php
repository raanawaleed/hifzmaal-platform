<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreZakatCalculationRequest extends FormRequest
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
            'hijri_year' => ['required', 'integer', 'min:1400', 'max:1500'],
            'cash_in_hand' => ['required', 'numeric', 'min:0'],
            'cash_in_bank' => ['required', 'numeric', 'min:0'],
            'gold_value' => ['nullable', 'numeric', 'min:0'],
            'silver_value' => ['nullable', 'numeric', 'min:0'],
            'business_inventory' => ['nullable', 'numeric', 'min:0'],
            'investments' => ['nullable', 'numeric', 'min:0'],
            'loans_receivable' => ['nullable', 'numeric', 'min:0'],
            'other_assets' => ['nullable', 'numeric', 'min:0'],
            'debts' => ['nullable', 'numeric', 'min:0'],
            'nisab_type' => ['required', 'in:gold,silver'],
            'asset_details' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'hijri_year.required' => 'Hijri year is required.',
            'hijri_year.min' => 'Invalid Hijri year.',
            'hijri_year.max' => 'Invalid Hijri year.',
            'cash_in_hand.required' => 'Cash in hand amount is required.',
            'cash_in_bank.required' => 'Cash in bank amount is required.',
            'nisab_type.required' => 'Nisab type (gold or silver) is required.',
            'nisab_type.in' => 'Nisab type must be either gold or silver.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Set defaults for optional fields
        $defaults = [
            'gold_value' => 0,
            'silver_value' => 0,
            'business_inventory' => 0,
            'investments' => 0,
            'loans_receivable' => 0,
            'other_assets' => 0,
            'debts' => 0,
        ];

        foreach ($defaults as $key => $value) {
            if ($this->{$key} === null) {
                $this->merge([$key => $value]);
            }
        }
    }
}
