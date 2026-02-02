<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'period' => ['required', 'in:weekly,monthly,yearly'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'alert_threshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'Selected category does not exist.',
            'name.required' => 'Budget name is required.',
            'amount.required' => 'Budget amount is required.',
            'amount.min' => 'Budget amount must be greater than 0.',
            'period.required' => 'Budget period is required.',
            'period.in' => 'Invalid budget period selected.',
            'start_date.required' => 'Start date is required.',
            'end_date.required' => 'End date is required.',
            'end_date.after' => 'End date must be after start date.',
            'alert_threshold.max' => 'Alert threshold cannot exceed 100%.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->is_active === null) {
            $this->merge(['is_active' => true]);
        }

        if ($this->alert_threshold === null) {
            $this->merge(['alert_threshold' => 80]);
        }
    }
}
