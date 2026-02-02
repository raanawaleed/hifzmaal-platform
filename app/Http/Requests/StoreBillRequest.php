<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillRequest extends FormRequest
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
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:electricity,gas,water,internet,mobile,rent,school_fees,other'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['required', 'date'],
            'frequency' => ['required', 'in:monthly,quarterly,yearly'],
            'is_recurring' => ['boolean'],
            'auto_pay' => ['boolean'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'provider' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'split_members' => ['nullable', 'array'],
            'split_members.*' => ['exists:family_members,id'],
            'reminder_days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Please select a category.',
            'name.required' => 'Bill name is required.',
            'type.required' => 'Bill type is required.',
            'type.in' => 'Invalid bill type selected.',
            'amount.required' => 'Bill amount is required.',
            'amount.min' => 'Amount must be greater than 0.',
            'due_date.required' => 'Due date is required.',
            'frequency.required' => 'Billing frequency is required.',
            'split_members.*.exists' => 'One or more selected family members do not exist.',
            'reminder_days.max' => 'Reminder days cannot exceed 30.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->is_recurring === null) {
            $this->merge(['is_recurring' => true]);
        }

        if ($this->auto_pay === null) {
            $this->merge(['auto_pay' => false]);
        }

        if ($this->reminder_days === null) {
            $this->merge(['reminder_days' => 3]);
        }
    }
}
