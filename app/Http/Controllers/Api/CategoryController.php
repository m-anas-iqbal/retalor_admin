<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCategoryRequest;
use App\Http\Requests\Api\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Shop;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        return ApiResponse::success('Categories fetched.', [
            'categories' => $shop->categories()->latest()->paginate(20),
        ]);
    }

    public function store(StoreCategoryRequest $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        $data = $request->validated();
        $slug = $data['slug'] ?? Str::slug($data['name']);

        if ($shop->categories()->where('slug', $slug)->exists()) {
            return ApiResponse::error('Category slug already exists for this shop.', 422);
        }

        $category = $shop->categories()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'status' => $data['status'] ?? 'active',
        ]);

        return ApiResponse::success('Category created.', [
            'category' => $category,
        ], 201);
    }

    public function update(UpdateCategoryRequest $request, Shop $shop, Category $category): JsonResponse
    {
        if (! $this->canAccessCategory($request, $shop, $category)) {
            return ApiResponse::error('You cannot access this category.', 403);
        }

        $data = $request->validated();
        $slug = $data['slug'] ?? $category->slug;

        if ($shop->categories()->where('slug', $slug)->whereKeyNot($category->id)->exists()) {
            return ApiResponse::error('Category slug already exists for this shop.', 422);
        }

        $category->update([
            'name' => $data['name'],
            'slug' => $slug,
            'status' => $data['status'] ?? $category->status,
        ]);

        return ApiResponse::success('Category updated.', [
            'category' => $category->fresh(),
        ]);
    }

    private function canAccessShop(Request $request, Shop $shop): bool
    {
        return $shop->users()->whereKey($request->user()->id)->exists();
    }

    private function canAccessCategory(Request $request, Shop $shop, Category $category): bool
    {
        return $category->shop_id === $shop->id && $this->canAccessShop($request, $shop);
    }
}