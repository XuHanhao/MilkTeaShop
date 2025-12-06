<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->with(['products' => fn ($query) => $query->where('status', 'active')->orderBy('sort_order')])
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        return $this->success($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $validated['status'] ?? 'active',
        ]);

        return $this->success($category, 'Category created successfully');
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug,'.$category->id],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $category->update([
            'name' => $validated['name'] ?? $category->name,
            'slug' => $validated['slug'] ?? $category->slug,
            'sort_order' => $validated['sort_order'] ?? $category->sort_order,
            'status' => $validated['status'] ?? $category->status,
        ]);

        return $this->success($category->refresh(), 'Category updated successfully');
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return $this->success(null, 'Category deleted successfully');
    }
}

