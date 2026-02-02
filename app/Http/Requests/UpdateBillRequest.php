<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bill'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'due_date' => ['sometimes', 'date'],
            'reminder_days' => ['sometimes', 'integer', 'min:1', 'max:30'],
            'auto_pay' => ['sometimes', 'boolean'],
            'is_recurring' => ['sometimes', 'boolean'],
            'provider' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Bill amount must be greater than 0.',
            'reminder_days.max' => 'Reminder days cannot exceed 30.',
        ];
    }
}
