<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Family;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends ApiController
{
    /**
     * @OA\Get(
     *     path="/api/families/{family}/categories",
     *     summary="Get all categories for a family",
     *     tags={"Categories"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by type",
     *         @OA\Schema(type="string", enum={"income", "expense"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items()
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/families/{family}/categories",
     *     summary="Create a new category",
     *     tags={"Categories"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "type"},
     *             @OA\Property(property="name", type="string", example="Custom Category"),
     *             @OA\Property(property="name_ur", type="string", example="اپنی مرضی کی قسم"),
     *             @OA\Property(property="type", type="string", enum={"income", "expense"}, example="expense"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true),
     *             @OA\Property(property="icon", type="string", example="star"),
     *             @OA\Property(property="color", type="string", example="#FF5733"),
     *             @OA\Property(property="is_halal", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreCategoryRequest $request, Family $family): JsonResponse
    {
        $this->authorize('update', $family);

        $category = $family->categories()->create(array_merge($request->validated(), [
            'is_system' => false,
            'sort_order' => Category::where('family_id', $family->id)->max('sort_order') + 1,
        ]));

        return response()->json([
            'message' => 'Category created successfully',
            'data' => new CategoryResource($category),
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/families/{family}/categories/{category}",
     *     summary="Update category",
     *     tags={"Categories"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="icon", type="string"),
     *             @OA\Property(property="color", type="string"),
     *             @OA\Property(property="is_halal", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully"
     *     ),
     *     @OA\Response(response=403, description="Cannot modify system categories")
     * )
     */
    public function update(UpdateCategoryRequest $request, Family $family, Category $category): JsonResponse
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

        $category->update($request->validated());

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/families/{family}/categories/{category}",
     *     summary="Delete category",
     *     tags={"Categories"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="family",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category deleted successfully"
     *     ),
     *     @OA\Response(response=403, description="Cannot delete system categories")
     * )
     */
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