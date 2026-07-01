<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateShopRequest;
use App\Models\Shop;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function update(UpdateShopRequest $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        if (! $this->canManageShop($request, $shop)) {
            return ApiResponse::error('Only the shop owner can edit shop details.', 403);
        }

        $shop->update($request->validated());

        return ApiResponse::success('Shop updated successfully.', [
            'shop' => $shop->fresh(['owner']),
        ]);
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
