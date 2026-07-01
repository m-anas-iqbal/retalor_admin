<?php

namespace Tests\Feature;

use App\Models\ExpenseType;
use App\Models\Shop;
use App\Models\ShopInvestor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopInvestorReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_generate_daily_investor_reports(): void
    {
        $owner = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'password',
        ]);

        $shop = Shop::create([
            'owner_user_id' => $owner->id,
            'name' => 'Daily Shop',
            'slug' => 'daily-shop',
            'status' => 'active',
        ]);

        $shop->users()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);

        $percentageInvestor = ShopInvestor::create([
            'shop_id' => $shop->id,
            'name' => 'Percent Partner',
            'payout_type' => 'percentage',
            'payout_value' => 20,
            'status' => 'active',
        ]);

        $fixedInvestor = ShopInvestor::create([
            'shop_id' => $shop->id,
            'name' => 'Fixed Partner',
            'payout_type' => 'fixed_amount',
            'payout_value' => 500,
            'status' => 'active',
        ]);

        $shop->sales()->createMany([
            ['user_id' => $owner->id, 'sale_date' => '2026-06-29', 'start_time' => '09:00:00', 'end_time' => '10:00:00', 'sales' => 700],
            ['user_id' => $owner->id, 'sale_date' => '2026-06-29', 'start_time' => '10:00:00', 'end_time' => '11:00:00', 'sales' => 300],
        ]);

        $shop->expenses()->create([
            'user_id' => $owner->id,
            'amount' => 200,
            'expense_date' => '2026-06-29',
            'description' => 'Daily expense',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'owner@example.com',
            'password' => 'password',
        ]);

        $login->assertOk();

        $this->withToken($login->json('data.access_token'))
            ->postJson('/api/shops/'.$shop->id.'/investor-reports/generate', [
                'report_date' => '2026-06-29',
            ])
            ->assertCreated()
            ->assertJsonPath('data.summary.total_sales', 1000)
            ->assertJsonPath('data.summary.operating_expenses', 200)
            ->assertJsonPath('data.summary.profit_before_investor_payout', 800)
            ->assertJsonPath('data.summary.investor_expenses', 660)
            ->assertJsonPath('data.summary.total_expenses', 860)
            ->assertJsonPath('data.summary.net_profit', 140)
            ->assertJsonPath('data.summary.investor_count', 2)
            ->assertJsonPath('data.summary.total_payout', 660);

        $this->assertDatabaseHas('shop_investor_daily_earnings', [
            'shop_investor_id' => $percentageInvestor->id,
            'report_date' => '2026-06-29',
            'operating_expenses' => 200,
            'investor_expenses' => 660,
            'profit_before_investor_payout' => 800,
            'payout_amount' => 160,
            'net_profit' => 140,
        ]);

        $this->assertDatabaseHas('shop_investor_daily_earnings', [
            'shop_investor_id' => $fixedInvestor->id,
            'report_date' => '2026-06-29',
            'operating_expenses' => 200,
            'investor_expenses' => 660,
            'profit_before_investor_payout' => 800,
            'payout_amount' => 500,
            'net_profit' => 140,
        ]);

        $investorExpenseType = ExpenseType::query()->where('shop_id', $shop->id)->where('slug', 'investor-payout')->firstOrFail();
        $this->assertDatabaseHas('expenses', [
            'shop_id' => $shop->id,
            'expense_type_id' => $investorExpenseType->id,
            'expense_date' => '2026-06-29',
            'amount' => 160,
            'description' => 'Investor payout #'.$percentageInvestor->id.': '.$percentageInvestor->name,
        ]);
        $this->assertDatabaseHas('expenses', [
            'shop_id' => $shop->id,
            'expense_type_id' => $investorExpenseType->id,
            'expense_date' => '2026-06-29',
            'amount' => 500,
            'description' => 'Investor payout #'.$fixedInvestor->id.': '.$fixedInvestor->name,
        ]);
    }

    public function test_owner_can_filter_generated_reports_by_date(): void
    {
        $owner = User::factory()->create([
            'email' => 'owner2@example.com',
            'password' => 'password',
        ]);

        $shop = Shop::create([
            'owner_user_id' => $owner->id,
            'name' => 'Filter Shop',
            'slug' => 'filter-shop',
            'status' => 'active',
        ]);

        $shop->users()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);

        ShopInvestor::create([
            'shop_id' => $shop->id,
            'name' => 'Filter Partner',
            'payout_type' => 'percentage',
            'payout_value' => 10,
            'status' => 'active',
        ]);

        $shop->investors()->create([
            'name' => 'Old Partner',
            'payout_type' => 'fixed_amount',
            'payout_value' => 100,
            'status' => 'active',
        ]);

        $shop->sales()->create([
            'user_id' => $owner->id,
            'sale_date' => '2026-06-29',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'sales' => 1000,
        ]);

        $shop->expenses()->create([
            'user_id' => $owner->id,
            'amount' => 100,
            'expense_date' => '2026-06-29',
            'description' => 'Expense',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'owner2@example.com',
            'password' => 'password',
        ]);

        $this->withToken($login->json('data.access_token'))
            ->postJson('/api/shops/'.$shop->id.'/investor-reports/generate', [
                'report_date' => '2026-06-29',
            ])
            ->assertCreated();

        $this->withToken($login->json('data.access_token'))
            ->getJson('/api/shops/'.$shop->id.'/investor-reports?date_from=2026-06-29&date_to=2026-06-29')
            ->assertOk()
            ->assertJsonPath('data.summary.total_reports', 2)
            ->assertJsonCount(2, 'data.reports.data');
    }
}