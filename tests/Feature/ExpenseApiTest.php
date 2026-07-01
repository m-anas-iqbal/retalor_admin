<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_user_can_create_expense_type_and_daily_expense(): void
    {
        [$user, $shop, $token] = $this->shopUserWithToken();

        $type = $this->withToken($token)->postJson("/api/shops/{$shop->id}/expense-types", [
            'name' => 'Electricity Bill',
        ])->assertCreated()->json('data.expense_type');

        $expense = $this->withToken($token)->postJson("/api/shops/{$shop->id}/expenses", [
            'expense_type_id' => $type['id'],
            'amount' => 2500,
            'expense_date' => now()->toDateString(),
            'description' => 'Daily light bill',
        ])->assertCreated()
            ->assertJsonPath('data.expense.amount', '2500.00')
            ->assertJsonPath('data.expense.description', 'Daily light bill')
            ->json('data.expense');

        $this->assertDatabaseHas('expenses', [
            'id' => $expense['id'],
            'shop_id' => $shop->id,
            'expense_type_id' => $type['id'],
            'user_id' => $user->id,
        ]);
    }

    public function test_expense_report_returns_summary_and_expenses(): void
    {
        [$user, $shop, $token] = $this->shopUserWithToken();

        $type = $this->withToken($token)->postJson("/api/shops/{$shop->id}/expense-types", [
            'name' => 'Rent',
        ])->assertCreated()->json('data.expense_type');

        $this->withToken($token)->postJson("/api/shops/{$shop->id}/expenses", [
            'expense_type_id' => $type['id'],
            'amount' => 1000,
            'expense_date' => now()->toDateString(),
        ])->assertCreated();

        $this->withToken($token)->postJson("/api/shops/{$shop->id}/expenses", [
            'expense_type_id' => $type['id'],
            'amount' => 500,
            'expense_date' => now()->subDay()->toDateString(),
            'description' => 'Old expense',
        ])->assertCreated();

        $this->withToken($token)->getJson("/api/shops/{$shop->id}/expenses")
            ->assertOk()
            ->assertJsonPath('data.summary.today_total', 1000)
            ->assertJsonPath('data.summary.overall_total', 1500)
            ->assertJsonPath('data.summary.total_entries', 2);
    }

    /**
     * @return array{0: User, 1: Shop, 2: string}
     */
    private function shopUserWithToken(): array
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $shop = Shop::create([
            'owner_user_id' => $user->id,
            'name' => 'Test Shop',
            'slug' => 'test-shop',
        ]);

        $shop->users()->attach($user->id, ['role' => 'owner', 'status' => 'active']);

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        return [$user, $shop, $login->json('data.access_token')];
    }
}
