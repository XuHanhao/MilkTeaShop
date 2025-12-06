<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->with('optionValues');
        
        if ($request->has('category_id') && $request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        
        if ($request->boolean('only_active', true)) {
            $query->where('status', 'active');
        }
        
        $products = $query->orderBy('sort_order')
            ->paginate($request->integer('per_page', 15));

        return $this->success($products);
    }

    public function show(Product $product): JsonResponse
    {
        return $this->success($product->load('optionValues'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateProduct($request);

        $product = null;
        DB::transaction(function () use (&$product, $validated, $request) {
            $product = Product::create([
                'category_id' => $validated['category_id'] ?? null,
                'name' => $validated['name'],
                'slug' => $validated['slug'] ?? Str::slug($validated['name']).'-'.Str::random(6),
                'image' => $validated['image'] ?? null,
                'description' => $validated['description'] ?? null,
                'base_price' => $validated['base_price'],
                'stock' => $validated['stock'] ?? 0,
                'status' => $validated['status'] ?? 'draft',
                'options' => $validated['options'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            $this->syncOptions($product, $request->input('option_values', []));
        });

        return $this->success($product->load('optionValues'), 'Product created successfully');
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $this->validateProduct($request, $product->id);

        DB::transaction(function () use ($product, $validated, $request): void {
            $product->update([
                'category_id' => $validated['category_id'] ?? $product->category_id,
                'name' => $validated['name'] ?? $product->name,
                'slug' => $validated['slug'] ?? $product->slug,
                'image' => $validated['image'] ?? $product->image,
                'description' => $validated['description'] ?? $product->description,
                'base_price' => $validated['base_price'] ?? $product->base_price,
                'stock' => $validated['stock'] ?? $product->stock,
                'status' => $validated['status'] ?? $product->status,
                'options' => $validated['options'] ?? $product->options,
                'sort_order' => $validated['sort_order'] ?? $product->sort_order,
            ]);

            if ($request->has('option_values')) {
                $product->optionValues()->delete();
                $this->syncOptions($product, $request->input('option_values', []));
            }
        });

        return $this->success($product->refresh()->load('optionValues'), 'Product updated successfully');
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return $this->success(null, 'Product deleted successfully');
    }

    protected function validateProduct(Request $request, ?int $productId = null): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['sometimes', Rule::requiredIf($productId === null), 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'image' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'base_price' => ['sometimes', Rule::requiredIf($productId === null), 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['draft', 'active', 'inactive'])],
            'options' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'option_values' => ['nullable', 'array'],
            'option_values.*.type' => ['required_with:option_values', Rule::in(['sweetness', 'ice', 'size', 'addon'])],
            'option_values.*.label' => ['required_with:option_values', 'string', 'max:255'],
            'option_values.*.extra_price' => ['nullable', 'numeric', 'min:0'],
            'option_values.*.is_default' => ['nullable', 'boolean'],
            'option_values.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    protected function syncOptions(Product $product, array $options): void
    {
        foreach ($options as $option) {
            $product->optionValues()->create([
                'type' => $option['type'],
                'label' => $option['label'],
                'extra_price' => $option['extra_price'] ?? 0,
                'is_default' => $option['is_default'] ?? false,
                'sort_order' => $option['sort_order'] ?? 0,
            ]);
        }
    }
}

