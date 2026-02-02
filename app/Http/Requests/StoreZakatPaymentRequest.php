<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreZakatPaymentRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['nullable', 'date'],
            'type' => ['required', 'in:zakat,sadaqah,fitrah'],
            'recipient_id' => ['nullable', 'exists:zakat_recipients,id'],
            'recipient_name' => ['required_without:recipient_id', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Payment amount must be greater than 0.',
            'recipient_name.required_without' => 'Recipient name is required when recipient is not selected.',
        ];
    }
}
