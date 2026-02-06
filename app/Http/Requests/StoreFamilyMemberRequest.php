<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFamilyMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manageMembers', $this->route('family'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('family_members')->where(function ($query) {
                    return $query->where('family_id', $this->route('family')->id);
                }),
            ],
            'relationship' => [
                'required',
                'in:owner,spouse,son,daughter,father,mother,brother,sister,dependent'
            ],
            'role' => [
                'required',
                'in:owner,editor,viewer,approver'
            ],
            'date_of_birth' => [
                'nullable',
                'date',
                'before:today',
                'after:1900-01-01'
            ],
            'spending_limit' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99'
            ],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Family member name is required.',
            'name.min' => 'Family member name must be at least 2 characters.',
            'name.max' => 'Family member name cannot exceed 255 characters.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already associated with another family member.',
            'relationship.required' => 'Please select the relationship.',
            'relationship.in' => 'Invalid relationship selected.',
            'role.required' => 'Please select a role for this family member.',
            'role.in' => 'Invalid role selected.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'date_of_birth.after' => 'Invalid date of birth.',
            'spending_limit.numeric' => 'Spending limit must be a number.',
            'spending_limit.min' => 'Spending limit cannot be negative.',
            'spending_limit.max' => 'Spending limit is too high.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'member name',
            'email' => 'email address',
            'relationship' => 'relationship',
            'role' => 'access role',
            'date_of_birth' => 'date of birth',
            'spending_limit' => 'spending limit',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if ($this->is_active === null) {
            $this->merge(['is_active' => true]);
        }

        // Trim whitespace from name and email
        if ($this->name) {
            $this->merge(['name' => trim($this->name)]);
        }

        if ($this->email) {
            $this->merge(['email' => trim(strtolower($this->email))]);
        }
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate that owner role can only be assigned to owner relationship
            if ($this->role === 'owner' && $this->relationship !== 'owner') {
                $validator->errors()->add(
                    'role',
                    'Owner role can only be assigned to the family owner.'
                );
            }

            // Validate spending limit is not set for owner
            if ($this->role === 'owner' && $this->spending_limit) {
                $validator->errors()->add(
                    'spending_limit',
                    'Spending limit cannot be set for family owner.'
                );
            }

            // Check if trying to add another owner
            $family = $this->route('family');
            if ($this->relationship === 'owner' || $this->role === 'owner') {
                $existingOwner = $family->members()
                    ->where('role', 'owner')
                    ->exists();
                
                if ($existingOwner) {
                    $validator->errors()->add(
                        'role',
                        'A family can only have one owner.'
                    );
                }
            }

            // Validate age if date of birth is provided
            if ($this->date_of_birth) {
                $age = \Carbon\Carbon::parse($this->date_of_birth)->age;
                
                // Children should be under 18
                if (in_array($this->relationship, ['son', 'daughter']) && $age >= 18) {
                    $validator->errors()->add(
                        'date_of_birth',
                        'For adult children, please use "dependent" relationship.'
                    );
                }

                // Parents should be reasonable age
                if (in_array($this->relationship, ['father', 'mother']) && $age < 18) {
                    $validator->errors()->add(
                        'date_of_birth',
                        'Invalid date of birth for parent.'
                    );
                }
            }
        });
    }
}