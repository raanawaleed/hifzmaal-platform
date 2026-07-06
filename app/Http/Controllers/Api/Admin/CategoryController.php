<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends ApiController
{
    /**
     * System categories only (family_id = null); families manage their own
     * custom categories through the family-scoped endpoints.
     */
    public function index(): JsonResponse
    {
        $categories = Category::whereNull('family_id')
            ->where('is_system', true)
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateCategory($request);

        $category = Category::create($validated + [
            'family_id' => null,
            'is_system' => true,
        ]);

        return response()->json([
            'message' => 'System category created.',
            'data' => $category,
        ], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $this->ensureSystemCategory($category);

        $category->update($this->validateCategory($request, $category));

        return response()->json([
            'message' => 'System category updated.',
            'data' => $category->fresh(),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->ensureSystemCategory($category);

        // The transactions FK is RESTRICT: deleting an in-use category is
        // rejected by the exception handler with a 422 "in use" response.
        $category->delete();

        return response()->json(['message' => 'System category deleted.']);
    }

    protected function validateCategory(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => [
                $category ? 'sometimes' : 'required',
                'string', 'max:255',
                Rule::unique('categories', 'name')
                    ->whereNull('family_id')
                    ->ignore($category?->id),
            ],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'type' => [$category ? 'sometimes' : 'required', 'in:income,expense'],
            'icon' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_halal' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);
    }

    protected function ensureSystemCategory(Category $category): void
    {
        abort_unless($category->is_system && $category->family_id === null, 404);
    }
}
