<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSaleRequest;
use App\Models\Shop;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $sales = $shop->sales();
        $filteredSales = $this->applyDateFilters(clone $sales, $data);

        return ApiResponse::success('Sales fetched.', [
            'filters' => [
                'date_from' => $data['date_from'] ?? null,
                'date_to' => $data['date_to'] ?? null,
            ],
            'summary' => [
                'today_total' => (float) (clone $sales)->whereDate('sale_date', today())->sum('sales'),
                'month_total' => (float) (clone $sales)->whereYear('sale_date', today()->year)->whereMonth('sale_date', today()->month)->sum('sales'),
                'overall_total' => (float) (clone $sales)->sum('sales'),
                'filtered_total' => (float) (clone $filteredSales)->sum('sales'),
                'total_entries' => (clone $filteredSales)->count(),
            ],
            'sales' => $this->applyDateFilters($shop->sales()->with('user:id,name,email'), $data)
                ->latest('sale_date')
                ->latest('start_time')
                ->paginate(20),
        ]);
    }

    public function store(StoreSaleRequest $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        $data = $request->validated();

        $sale = $shop->sales()->create([
            'user_id' => $request->user()->id,
            'sale_date' => $data['sale_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'sales' => $data['sales'],
        ]);

        return ApiResponse::success('Sale added.', [
            'sale' => $sale->load('user:id,name,email'),
        ], 201);
    }

    private function canAccessShop(Request $request, Shop $shop): bool
    {
        return $shop->users()->whereKey($request->user()->id)->exists();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyDateFilters(mixed $query, array $filters): mixed
    {
        return $query
            ->when($filters['date_from'] ?? null, fn ($builder, $dateFrom) => $builder->whereDate('sale_date', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn ($builder, $dateTo) => $builder->whereDate('sale_date', '<=', $dateTo));
    }
}
