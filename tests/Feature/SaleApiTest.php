<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_user_can_create_daily_sale_entry(): void
    {
        [$user, $shop, $token] = $this->shopUserWithToken();

        $sale = $this->withToken($token)->postJson("/api/shops/{$shop->id}/sales", [
            'sale_date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'sales' => 5000,
        ])->assertCreated()
            ->assertJsonPath('data.sale.sales', '5000.00')
            ->assertJsonPath('data.sale.start_time', '09:00')
            ->json('data.sale');

        $this->assertDatabaseHas('sales', [
            'id' => $sale['id'],
            'shop_id' => $shop->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_sales_report_returns_daily_and_hourly_entries(): void
    {
        [, $shop, $token] = $this->shopUserWithToken();

        $this->withToken($token)->postJson("/api/shops/{$shop->id}/sales", [
            'sale_date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'sales' => 5000,
        ])->assertCreated();

        $this->withToken($token)->postJson("/api/shops/{$shop->id}/sales", [
            'sale_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'sales' => 3000,
        ])->assertCreated();

        $this->withToken($token)->getJson("/api/shops/{$shop->id}/sales")
            ->assertOk()
            ->assertJsonPath('data.summary.today_total', 8000)
            ->assertJsonPath('data.summary.total_entries', 2)
            ->assertJsonCount(2, 'data.sales.data');
    }

    public function test_sales_report_can_be_filtered_by_date_range(): void
    {
        [, $shop, $token] = $this->shopUserWithToken();

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $this->withToken($token)->postJson("/api/shops/{$shop->id}/sales", [
            'sale_date' => $today,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'sales' => 5000,
        ])->assertCreated();

        $this->withToken($token)->postJson("/api/shops/{$shop->id}/sales", [
            'sale_date' => $yesterday,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'sales' => 2000,
        ])->assertCreated();

        $this->withToken($token)->getJson("/api/shops/{$shop->id}/sales?date_from={$today}&date_to={$today}")
            ->assertOk()
            ->assertJsonPath('data.filters.date_from', $today)
            ->assertJsonPath('data.filters.date_to', $today)
            ->assertJsonPath('data.summary.filtered_total', 5000)
            ->assertJsonPath('data.summary.total_entries', 1)
            ->assertJsonCount(1, 'data.sales.data');
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
