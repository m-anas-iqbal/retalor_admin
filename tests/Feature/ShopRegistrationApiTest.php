<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopRegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_registration_creates_shop_owner_relation_and_token(): void
    {
        $response = $this->postJson('/api/shops/register', [
            'shop' => [
                'name' => 'City Mart',
                'slug' => 'city-mart',
                'email' => 'shop@example.com',
                'phone' => '03001234567',
                'address' => 'Main Market',
                'city' => 'Lahore',
            ],
            'owner' => [
                'name' => 'Shop Owner',
                'email' => 'owner@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ],
            'device_name' => 'mobile',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.shop.name', 'City Mart')
            ->assertJsonPath('data.owner.email', 'owner@example.com')
            ->assertJsonStructure(['data' => ['access_token', 'token_type', 'shop', 'owner']]);

        $shop = Shop::where('slug', 'city-mart')->firstOrFail();
        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $this->assertSame($owner->id, $shop->owner_user_id);
        $this->assertDatabaseHas('shop_user', [
            'shop_id' => $shop->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
    }

    public function test_one_shop_can_have_multiple_users(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()->create();
        $shop = Shop::create([
            'owner_user_id' => $owner->id,
            'name' => 'Multi User Shop',
            'slug' => 'multi-user-shop',
        ]);

        $shop->users()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);
        $shop->users()->attach($staff->id, ['role' => 'staff', 'status' => 'active']);

        $this->assertCount(2, $shop->fresh()->users);
        $this->assertCount(1, $staff->fresh()->shops);
    }
}
