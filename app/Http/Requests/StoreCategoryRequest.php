<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('family'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:income,expense'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'is_halal' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'type.required' => 'Category type is required.',
            'type.in' => 'Category type must be income or expense.',
            'parent_id.exists' => 'Selected parent category does not exist.',
            'color.regex' => 'Color must be a valid hex code (e.g., #FF5733).',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->is_halal === null) {
            $this->merge(['is_halal' => true]);
        }
    }
}