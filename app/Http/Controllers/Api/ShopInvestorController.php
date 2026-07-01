<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreShopInvestorRequest;
use App\Http\Requests\Api\UpdateShopInvestorRequest;
use App\Models\Shop;
use App\Models\ShopInvestor;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopInvestorController extends Controller
{
    public function index(Request $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        return ApiResponse::success('Investors fetched.', [
            'investors' => $shop->investors()->latest('id')->get(),
        ]);
    }

    public function store(StoreShopInvestorRequest $request, Shop $shop): JsonResponse
    {
        if (! $this->canManageShop($request, $shop)) {
            return ApiResponse::error('Only the shop owner can manage investors.', 403);
        }

        $investor = $shop->investors()->create($request->validated());

        return ApiResponse::success('Investor added.', [
            'investor' => $investor,
        ], 201);
    }

    public function update(UpdateShopInvestorRequest $request, Shop $shop, ShopInvestor $investor): JsonResponse
    {
        if ($investor->shop_id !== $shop->id) {
            return ApiResponse::error('Investor does not belong to this shop.', 404);
        }

        if (! $this->canManageShop($request, $shop)) {
            return ApiResponse::error('Only the shop owner can manage investors.', 403);
        }

        $investor->update($request->validated());

        return ApiResponse::success('Investor updated.', [
            'investor' => $investor->fresh(),
        ]);
    }

    public function destroy(Request $request, Shop $shop, ShopInvestor $investor): JsonResponse
    {
        if ($investor->shop_id !== $shop->id) {
            return ApiResponse::error('Investor does not belong to this shop.', 404);
        }

        if (! $this->canManageShop($request, $shop)) {
            return ApiResponse::error('Only the shop owner can manage investors.', 403);
        }

        $investor->delete();

        return ApiResponse::success('Investor removed.');
    }

    private function canAccessShop(Request $request, Shop $shop): bool
    {
        return $shop->users()->whereKey($request->user()->id)->exists();
    }

    private function canManageShop(Request $request, Shop $shop): bool
    {
        return (int) $request->user()->id === (int) $shop->owner_user_id;
    }
}
