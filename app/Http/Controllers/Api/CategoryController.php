<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Family;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(Request $request, Family $family): AnonymousResourceCollection
    {
        $this->authorize('view', $family);

        $query = Category::where(function($q) use ($family) {
            $q->where('family_id', $family->id)
              ->orWhereNull('family_id');
        });

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $categories = $query->ordered()->get();

        return CategoryResource::collection($categories);
    }

    public function store(Request $request, Family $family): JsonResponse
    {
        $this->authorize('update', $family);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_ur' => 'nullable|string|max:255',
            'type' => 'required|in:income,expense',
            'parent_id' => 'nullable|exists:categories,id',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'is_halal' => 'boolean',
        ]);

        $category = $family->categories()->create(array_merge($validated, [
            'is_system' => false,
            'sort_order' => Category::where('family_id', $family->id)->max('sort_order') + 1,
        ]));

        return response()->json([
            'message' => 'Category created successfully',
            'data' => new CategoryResource($category),
        ], 201);
    }

    public function update(Request $request, Family $family, Category $category): JsonResponse
    {
        $this->authorize('update', $family);

        if ($category->is_system) {
            return response()->json([
                'message' => 'System categories cannot be modified',
            ], 403);
        }

        if ($category->family_id !== $family->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'name_ur' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'is_halal' => 'boolean',
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => new CategoryResource($category),
        ]);
    }

    public function destroy(Family $family, Category $category): JsonResponse
    {
        $this->authorize('delete', $family);

        if ($category->is_system) {
            return response()->json([
                'message' => 'System categories cannot be deleted',
            ], 403);
        }

        if ($category->family_id !== $family->id) {
            abort(404);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }
}