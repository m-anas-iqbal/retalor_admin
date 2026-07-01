<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreExpenseRequest;
use App\Http\Requests\Api\StoreExpenseTypeRequest;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Shop;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExpenseController extends Controller
{
    public function types(Request $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        return ApiResponse::success('Expense types fetched.', [
            'expense_types' => $shop->expenseTypes()->latest()->paginate(20),
        ]);
    }

    public function storeType(StoreExpenseTypeRequest $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        $data = $request->validated();
        $slug = $this->uniqueTypeSlug($shop, $data['name']);

        $type = $shop->expenseTypes()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'status' => $data['status'] ?? 'active',
        ]);

        return ApiResponse::success('Expense type created.', [
            'expense_type' => $type,
        ], 201);
    }

    public function index(Request $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        $expenses = $shop->expenses();

        return ApiResponse::success('Expenses fetched.', [
            'summary' => [
                'today_total' => (float) (clone $expenses)->whereDate('expense_date', today())->sum('amount'),
                'month_total' => (float) (clone $expenses)->whereYear('expense_date', today()->year)->whereMonth('expense_date', today()->month)->sum('amount'),
                'overall_total' => (float) (clone $expenses)->sum('amount'),
                'total_entries' => (clone $expenses)->count(),
            ],
            'expenses' => $shop->expenses()->with(['type', 'user:id,name,email'])->latest('expense_date')->latest('id')->paginate(20),
        ]);
    }

    public function store(StoreExpenseRequest $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        $data = $request->validated();

        if (! empty($data['expense_type_id']) && ! $shop->expenseTypes()->whereKey($data['expense_type_id'])->exists()) {
            return ApiResponse::error('Expense type does not belong to this shop.', 422);
        }

        $expense = $shop->expenses()->create([
            'expense_type_id' => $data['expense_type_id'] ?? null,
            'user_id' => $request->user()->id,
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'description' => $data['description'] ?? null,
        ]);

        return ApiResponse::success('Expense added.', [
            'expense' => $expense->load(['type', 'user:id,name,email']),
        ], 201);
    }

    private function canAccessShop(Request $request, Shop $shop): bool
    {
        return $shop->users()->whereKey($request->user()->id)->exists();
    }

    private function uniqueTypeSlug(Shop $shop, string $name): string
    {
        $base = Str::slug($name) ?: 'expense-type';
        $slug = $base;
        $counter = 2;

        while ($shop->expenseTypes()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
