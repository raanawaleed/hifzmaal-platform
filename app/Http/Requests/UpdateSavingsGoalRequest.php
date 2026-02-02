<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSavingsGoalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('savingsGoal'));
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
            'target_amount' => ['sometimes', 'numeric', 'min:0.01'],
            'monthly_contribution' => ['sometimes', 'numeric', 'min:0'],
            'target_date' => ['sometimes', 'date', 'after:today'],
            'is_active' => ['sometimes', 'boolean'],
            'auto_contribute' => ['sometimes', 'boolean'],
            'contribution_day' => ['sometimes', 'integer', 'min:1', 'max:28'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_amount.min' => 'Target amount must be greater than 0.',
            'target_date.after' => 'Target date must be in the future.',
            'contribution_day.max' => 'Contribution day cannot exceed 28.',
        ];
    }
}
