<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFamilyRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255', 'min:2'],
            'currency' => ['sometimes', 'string', 'size:3', 'in:PKR,USD,EUR,GBP,SAR,AED,INR,BDT'],
            'locale' => ['sometimes', 'string', 'in:en,ur,hi,bn'],
            'settings' => ['sometimes', 'array'],
            'settings.theme' => ['sometimes', 'in:light,dark'],
            'settings.notifications_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.min' => 'Family name must be at least 2 characters.',
            'currency.size' => 'Currency must be 3 characters.',
            'currency.in' => 'Selected currency is not supported.',
            'locale.in' => 'Selected language is not supported.',
        ];
    }
}
