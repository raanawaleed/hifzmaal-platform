<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFamilyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            'currency' => ['required', 'string', 'size:3', 'in:PKR,USD,EUR,GBP,SAR,AED,INR,BDT'],
            'locale' => ['required', 'string', 'in:en,ur,hi,bn'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'name.required' => 'Family name is required.',
            'name.max' => 'Family name cannot exceed 255 characters.',
            'currency.required' => 'Currency is required.',
            'currency.in' => 'Invalid currency selected.',
            'locale.required' => 'Language is required.',
            'locale.in' => 'Invalid language selected.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'family name',
            'currency' => 'currency',
            'locale' => 'language',
        ];
    }
}
