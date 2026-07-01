<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GenerateInvestorDailyReportRequest;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Shop;
use App\Models\ShopInvestorDailyEarning;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopInvestorReportController extends Controller
{
    private const INVESTOR_EXPENSE_SLUG = 'investor-payout';

    private const INVESTOR_EXPENSE_NAME = 'Investor Payout';

    public function index(Request $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $reportQuery = ShopInvestorDailyEarning::query()->where('shop_id', $shop->id);
        $filteredReports = $this->applyDateFilters(clone $reportQuery, $data);
        $reports = $this->applyDateFilters($reportQuery, $data)
            ->with(['investor:id,shop_id,name,payout_type,payout_value,status', 'generatedBy:id,name,email'])
            ->latest('report_date')
            ->latest('id')
            ->paginate(20);

        $expenseTypeId = $this->investorExpenseTypeId($shop);
        $expenseQuery = Expense::query()->where('shop_id', $shop->id);
        $filteredExpenseQuery = $this->applyExpenseDateFilters(clone $expenseQuery, $data);
        $filteredOperatingExpenses = $this->operatingExpensesQuery(clone $expenseQuery, $expenseTypeId, $data);
        $filteredInvestorExpenses = $this->investorExpensesQuery(clone $expenseQuery, $expenseTypeId, $data);
        $filteredSales = $shop->sales()
            ->when($data['date_from'] ?? null, fn ($builder, $dateFrom) => $builder->whereDate('sale_date', '>=', $dateFrom))
            ->when($data['date_to'] ?? null, fn ($builder, $dateTo) => $builder->whereDate('sale_date', '<=', $dateTo));

        $dailyTotals = $filteredReports
            ->get()
            ->groupBy('report_date')
            ->map(fn ($group) => $group->first())
            ->values();

        return ApiResponse::success('Investor reports fetched.', [
            'filters' => [
                'date_from' => $data['date_from'] ?? null,
                'date_to' => $data['date_to'] ?? null,
            ],
            'summary' => [
                'total_reports' => (int) $reports->total(),
                'total_sales' => (float) $filteredSales->sum('sales'),
                'operating_expenses' => (float) $filteredOperatingExpenses->sum('amount'),
                'investor_expenses' => (float) $filteredInvestorExpenses->sum('amount'),
                'total_expenses' => (float) $filteredExpenseQuery->sum('amount'),
                'profit_before_investor_payout' => (float) $dailyTotals->sum('profit_before_investor_payout'),
                'total_net_profit' => (float) $dailyTotals->sum('net_profit'),
                'total_payout' => (float) $filteredReports->sum('payout_amount'),
            ],
            'reports' => $reports,
        ]);
    }

    public function generate(GenerateInvestorDailyReportRequest $request, Shop $shop): JsonResponse
    {
        if (! $this->canManageShop($request, $shop)) {
            return ApiResponse::error('Only the shop owner can generate investor reports.', 403);
        }

        $data = $request->validated();
        $reportDate = $data['report_date'];
        $salesTotal = (float) $shop->sales()->whereDate('sale_date', $reportDate)->sum('sales');
        $expenseTypeId = $this->investorExpenseTypeId($shop);
        $operatingExpenses = (float) Expense::query()
            ->where('shop_id', $shop->id)
            ->whereDate('expense_date', $reportDate)
            ->where(function ($query) use ($expenseTypeId): void {
                $query->whereNull('expense_type_id');
                if ($expenseTypeId !== null) {
                    $query->orWhere('expense_type_id', '!=', $expenseTypeId);
                }
            })
            ->sum('amount');
        $profitBeforeInvestor = $salesTotal - $operatingExpenses;
        $investors = $shop->investors()->where('status', 'active')->get();

        $payouts = $investors->mapWithKeys(function ($investor) use ($profitBeforeInvestor): array {
            $payoutValue = (float) $investor->payout_value;
            $payoutAmount = $investor->payout_type === 'percentage'
                ? round(max($profitBeforeInvestor, 0) * ($payoutValue / 100), 2)
                : round($payoutValue, 2);

            return [$investor->id => [
                'investor' => $investor,
                'payout_value' => $payoutValue,
                'payout_amount' => $payoutAmount,
            ]];
        });

        $investorExpenseTotal = (float) $payouts->sum(fn (array $item) => $item['payout_amount']);
        $totalExpenses = $operatingExpenses + $investorExpenseTotal;
        $netProfit = $salesTotal - $totalExpenses;
        $records = [];

        DB::transaction(function () use ($payouts, $request, $shop, $reportDate, $salesTotal, $operatingExpenses, $profitBeforeInvestor, $investorExpenseTotal, $totalExpenses, $netProfit, &$records, $data): void {
            $investorExpenseType = ExpenseType::query()->firstOrCreate(
                [
                    'shop_id' => $shop->id,
                    'slug' => self::INVESTOR_EXPENSE_SLUG,
                ],
                [
                    'name' => self::INVESTOR_EXPENSE_NAME,
                    'status' => 'active',
                ],
            );

            foreach ($payouts as $investorId => $payoutData) {
                $investor = $payoutData['investor'];
                $payoutValue = $payoutData['payout_value'];
                $payoutAmount = $payoutData['payout_amount'];

                Expense::updateOrCreate(
                    [
                        'shop_id' => $shop->id,
                        'expense_type_id' => $investorExpenseType->id,
                        'expense_date' => $reportDate,
                        'description' => 'Investor payout #'.$investor->id.': '.$investor->name,
                    ],
                    [
                        'user_id' => $request->user()->id,
                        'amount' => $payoutAmount,
                    ]
                );

                $record = ShopInvestorDailyEarning::updateOrCreate(
                    [
                        'shop_investor_id' => $investor->id,
                        'report_date' => $reportDate,
                    ],
                    [
                        'shop_id' => $shop->id,
                        'generated_by_user_id' => $request->user()->id,
                        'total_sales' => $salesTotal,
                        'operating_expenses' => $operatingExpenses,
                        'investor_expenses' => $investorExpenseTotal,
                        'profit_before_investor_payout' => $profitBeforeInvestor,
                        'total_expenses' => $totalExpenses,
                        'net_profit' => $netProfit,
                        'payout_type' => $investor->payout_type,
                        'payout_value' => $payoutValue,
                        'payout_amount' => $payoutAmount,
                        'status' => 'generated',
                        'notes' => $data['notes'] ?? null,
                    ]
                );

                $records[] = $record->load(['investor:id,shop_id,name,payout_type,payout_value,status', 'generatedBy:id,name,email']);
            }
        });

        return ApiResponse::success('Daily investor report generated.', [
            'report_date' => $reportDate,
            'summary' => [
                'total_sales' => $salesTotal,
                'operating_expenses' => $operatingExpenses,
                'investor_expenses' => $investorExpenseTotal,
                'total_expenses' => $totalExpenses,
                'profit_before_investor_payout' => $profitBeforeInvestor,
                'net_profit' => $netProfit,
                'investor_count' => $investors->count(),
                'total_payout' => $investorExpenseTotal,
            ],
            'reports' => $records,
        ], 201);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyDateFilters(mixed $query, array $filters): mixed
    {
        return $query
            ->when($filters['date_from'] ?? null, fn ($builder, $dateFrom) => $builder->whereDate('report_date', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn ($builder, $dateTo) => $builder->whereDate('report_date', '<=', $dateTo));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyExpenseDateFilters(mixed $query, array $filters): mixed
    {
        return $query
            ->when($filters['date_from'] ?? null, fn ($builder, $dateFrom) => $builder->whereDate('expense_date', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn ($builder, $dateTo) => $builder->whereDate('expense_date', '<=', $dateTo));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function operatingExpensesQuery(mixed $query, ?int $investorExpenseTypeId, array $filters): mixed
    {
        return $this->applyExpenseDateFilters($query, $filters)
            ->where(function ($builder) use ($investorExpenseTypeId): void {
                $builder->whereNull('expense_type_id');
                if ($investorExpenseTypeId !== null) {
                    $builder->orWhere('expense_type_id', '!=', $investorExpenseTypeId);
                }
            });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function investorExpensesQuery(mixed $query, ?int $investorExpenseTypeId, array $filters): mixed
    {
        if ($investorExpenseTypeId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $this->applyExpenseDateFilters($query, $filters)
            ->where('expense_type_id', $investorExpenseTypeId);
    }

    private function investorExpenseTypeId(Shop $shop): ?int
    {
        return ExpenseType::query()
            ->where('shop_id', $shop->id)
            ->where('slug', self::INVESTOR_EXPENSE_SLUG)
            ->value('id');
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