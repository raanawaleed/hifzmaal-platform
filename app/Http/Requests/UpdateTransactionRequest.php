<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('transaction'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'account_id' => ['sometimes', 'exists:accounts,id'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'type' => ['sometimes', 'in:income,expense,transfer'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'date' => ['sometimes', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipts' => ['nullable', 'array', 'max:5'],
            'receipts.*' => ['file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'account_id.exists' => 'Selected account does not exist.',
            'category_id.exists' => 'Selected category does not exist.',
            'amount.min' => 'Amount must be greater than 0.',
            'date.before_or_equal' => 'Transaction date cannot be in the future.',
            'receipts.max' => 'You can upload maximum 5 receipts.',
            'receipts.*.mimes' => 'Receipts must be JPEG, PNG or PDF files.',
            'receipts.*.max' => 'Each receipt must not exceed 5MB.',
        ];
    }
}
