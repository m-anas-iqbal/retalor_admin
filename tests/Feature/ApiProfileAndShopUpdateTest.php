<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiProfileAndShopUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'password' => 'password',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'old@example.com',
            'password' => 'password',
        ]);

        $login->assertOk();

        $this->withToken($login->json('data.access_token'))
            ->putJson('/api/me', [
                'name' => 'New Name',
                'email' => 'new@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Profile updated successfully.')
            ->assertJsonPath('data.user.name', 'New Name')
            ->assertJsonPath('data.user.email', 'new@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_shop_owner_can_update_shop_details(): void
    {
        $owner = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'password',
        ]);

        $shop = Shop::create([
            'owner_user_id' => $owner->id,
            'name' => 'Old Shop',
            'slug' => 'old-shop',
            'email' => 'shop@example.com',
            'phone' => '03000000000',
            'address' => 'Old Address',
            'city' => 'Old City',
            'status' => 'active',
        ]);

        $shop->users()->attach($owner->id, [
            'role' => 'owner',
            'status' => 'active',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'owner@example.com',
            'password' => 'password',
        ]);

        $login->assertOk();

        $this->withToken($login->json('data.access_token'))
            ->putJson('/api/shops/'.$shop->id, [
                'name' => 'Updated Shop',
                'slug' => 'updated-shop',
                'email' => 'updated@example.com',
                'phone' => '03111222333',
                'address' => 'New Address',
                'city' => 'Karachi',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Shop updated successfully.')
            ->assertJsonPath('data.shop.name', 'Updated Shop')
            ->assertJsonPath('data.shop.slug', 'updated-shop');

        $this->assertDatabaseHas('shops', [
            'id' => $shop->id,
            'name' => 'Updated Shop',
            'slug' => 'updated-shop',
            'email' => 'updated@example.com',
            'phone' => '03111222333',
            'address' => 'New Address',
            'city' => 'Karachi',
        ]);
    }
}
