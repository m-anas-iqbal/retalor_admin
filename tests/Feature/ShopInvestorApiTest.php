<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\ShopInvestor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopInvestorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_owner_can_manage_investors(): void
    {
        $owner = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'password',
        ]);

        $shop = Shop::create([
            'owner_user_id' => $owner->id,
            'name' => 'Demo Shop',
            'slug' => 'demo-shop',
            'status' => 'active',
        ]);

        $shop->users()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);

        $login = $this->postJson('/api/login', [
            'email' => 'owner@example.com',
            'password' => 'password',
        ]);

        $login->assertOk();

        $create = $this->withToken($login->json('data.access_token'))
            ->postJson('/api/shops/'.$shop->id.'/investors', [
                'name' => 'Ali Investor',
                'email' => 'ali@example.com',
                'payout_type' => 'percentage',
                'payout_value' => 15,
                'status' => 'active',
                'notes' => 'Main partner',
            ]);

        $create->assertCreated()
            ->assertJsonPath('message', 'Investor added.')
            ->assertJsonPath('data.investor.name', 'Ali Investor')
            ->assertJsonPath('data.investor.payout_type', 'percentage');

        $investorId = $create->json('data.investor.id');

        $this->withToken($login->json('data.access_token'))
            ->putJson('/api/shops/'.$shop->id.'/investors/'.$investorId, [
                'name' => 'Ali Updated',
                'payout_type' => 'fixed_amount',
                'payout_value' => 250000,
                'status' => 'inactive',
            ])
            ->assertOk()
            ->assertJsonPath('data.investor.name', 'Ali Updated')
            ->assertJsonPath('data.investor.payout_type', 'fixed_amount');

        $this->withToken($login->json('data.access_token'))
            ->getJson('/api/shops/'.$shop->id.'/investors')
            ->assertOk()
            ->assertJsonCount(1, 'data.investors');

        $this->withToken($login->json('data.access_token'))
            ->deleteJson('/api/shops/'.$shop->id.'/investors/'.$investorId)
            ->assertOk()
            ->assertJsonPath('message', 'Investor removed.');

        $this->assertDatabaseMissing('shop_investors', [
            'id' => $investorId,
        ]);
    }

    public function test_percentage_investor_requires_valid_range(): void
    {
        $owner = User::factory()->create([
            'email' => 'owner2@example.com',
            'password' => 'password',
        ]);

        $shop = Shop::create([
            'owner_user_id' => $owner->id,
            'name' => 'Demo Shop 2',
            'slug' => 'demo-shop-2',
            'status' => 'active',
        ]);

        $shop->users()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);

        $login = $this->postJson('/api/login', [
            'email' => 'owner2@example.com',
            'password' => 'password',
        ]);

        $login->assertOk();

        $this->withToken($login->json('data.access_token'))
            ->postJson('/api/shops/'.$shop->id.'/investors', [
                'name' => 'Bad Investor',
                'payout_type' => 'percentage',
                'payout_value' => 150,
            ])
            ->assertStatus(422);
    }
}
