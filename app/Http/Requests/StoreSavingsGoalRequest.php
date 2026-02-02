<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavingsGoalRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:hajj,umrah,education,marriage,emergency,business,other'],
            'target_amount' => ['required', 'numeric', 'min:0.01'],
            'current_amount' => ['nullable', 'numeric', 'min:0', 'lte:target_amount'],
            'monthly_contribution' => ['nullable', 'numeric', 'min:0'],
            'target_date' => ['nullable', 'date', 'after:today'],
            'start_date' => ['nullable', 'date'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'dua_reminder' => ['nullable', 'string', 'max:500'],
            'auto_contribute' => ['boolean'],
            'contribution_day' => ['nullable', 'integer', 'min:1', 'max:28'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Goal name is required.',
            'type.required' => 'Goal type is required.',
            'type.in' => 'Invalid goal type selected.',
            'target_amount.required' => 'Target amount is required.',
            'target_amount.min' => 'Target amount must be greater than 0.',
            'current_amount.lte' => 'Current amount cannot exceed target amount.',
            'target_date.after' => 'Target date must be in the future.',
            'account_id.exists' => 'Selected account does not exist.',
            'contribution_day.max' => 'Contribution day must be between 1 and 28.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->auto_contribute === null) {
            $this->merge(['auto_contribute' => false]);
        }

        if ($this->is_active === null) {
            $this->merge(['is_active' => true]);
        }

        if ($this->start_date === null) {
            $this->merge(['start_date' => now()->format('Y-m-d')]);
        }
    }
}
