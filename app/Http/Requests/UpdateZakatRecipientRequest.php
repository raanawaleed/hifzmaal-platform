<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateZakatRecipientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('family'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'category' => ['sometimes', 'in:fuqara,masakin,amilin,muallaf,riqab,gharimin,fisabilillah,ibnus_sabil'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Recipient name cannot exceed 255 characters.',
            'category.in' => 'Invalid Zakat recipient category.',
            'address.max' => 'Address cannot exceed 500 characters.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }
}