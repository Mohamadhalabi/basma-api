<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\Category;
use App\Models\Manufacturer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController
{
    /**
     * List products with filtering by category, manufacturer, attributes, search
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->where('is_active', true);

        // Search by title or SKU
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category')) {
            $categoryId = $request->input('category');
            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }

        // Filter by manufacturer
        if ($request->has('manufacturer')) {
            $manufacturerId = $request->input('manufacturer');
            $query->where('manufacturer_id', $manufacturerId);
        }

        // Filter by attributes (e.g., ?attributes[]=1&attributes[]=2)
        if ($request->has('attributes')) {
            $attributeIds = $request->input('attributes');
            $query->whereHas('attributeValues', function ($q) use ($attributeIds) {
                $q->whereIn('attribute_values.id', $attributeIds);
            }, '=', count($attributeIds)); // All attributes must match
        }

        // Price range filter (in halalas)
        if ($request->has('min_price')) {
            $query->where('default_price', '>=', $request->input('min_price'));
        }
        if ($request->has('max_price')) {
            $query->where('default_price', '<=', $request->input('max_price'));
        }

        // Pagination
        $perPage = $request->input('per_page', 12);
        $products = $query->with(['manufacturer', 'categories'])
            ->paginate($perPage);

        // Add gallery data to each product
        $products->getCollection()->transform(function ($product) {
            return $this->formatProductWithGallery($product);
        });

        return response()->json([
            'data' => $products->items(),
            'pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    /**
     * Get single product detail
     */
    public function show(Product $product): JsonResponse
    {
        if (!$product->is_active) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->load(['manufacturer', 'categories', 'attributeValues']);
        $formattedProduct = $this->formatProductWithGallery($product);

        return response()->json($formattedProduct);
    }

    /**
     * Get all categories (for filters)
     */
    public function categories(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->with('parent')
            ->get();

        return response()->json($categories);
    }

    /**
     * Get all manufacturers (for filters)
     */
    public function manufacturers(): JsonResponse
    {
        $manufacturers = Manufacturer::where('is_active', true)->get();

        return response()->json($manufacturers);
    }

    /**
     * Format product with gallery data
     */
    private function formatProductWithGallery(Product $product): array
    {
        // Get gallery images
        $gallery = $product->getMedia('gallery')->map(function ($media) {
            return [
                'thumb' => $media->getUrl('thumb'),
                'card' => $media->getUrl('card'),
                'full' => $media->getUrl('full'),
                'original' => $media->getUrl(),
            ];
        });

        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'title' => $product->title,
            'slug' => $product->slug,
            'description' => $product->description,
            'seo_title' => $product->seo_title,
            'seo_description' => $product->seo_description,
            'default_price' => $product->default_price,
            'vat_rate' => $product->vat_rate,
            'stock_quantity' => $product->stock_quantity,
            'is_low_stock' => $product->isLowStock(),
            'allow_backorder' => $product->allow_backorder,
            'manufacturer' => $product->manufacturer,
            'categories' => $product->categories,
            'attributes' => $product->attributeValues ?? [],
            'gallery' => $gallery->toArray(),
        ];
    }
}
