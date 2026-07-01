<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopEmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_shop_employees(): void
    {
        [$owner, $shop, $token] = $this->shopOwnerWithToken();
        $this->createSubscription($shop, 5);

        $employee = $this->withToken($token)->postJson("/api/shops/{$shop->id}/employees", [
            'name' => 'Counter Staff',
            'email' => 'staff@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'staff',
            'status' => 'active',
        ])->assertCreated()
            ->assertJsonPath('data.employee.pivot.role', 'staff')
            ->json('data.employee');

        $this->assertDatabaseHas('shop_user', [
            'shop_id' => $shop->id,
            'user_id' => $employee['id'],
            'role' => 'staff',
            'status' => 'active',
        ]);

        $this->withToken($token)->getJson("/api/shops/{$shop->id}/employees")
            ->assertOk()
            ->assertJsonPath('data.summary.total_users', 2)
            ->assertJsonPath('data.summary.seat_limit', 5);

        $this->withToken($token)->patchJson("/api/shops/{$shop->id}/employees/{$employee['id']}", [
            'role' => 'manager',
            'status' => 'inactive',
        ])->assertOk()
            ->assertJsonPath('data.employee.pivot.role', 'manager')
            ->assertJsonPath('data.employee.pivot.status', 'inactive');

        $this->withToken($token)->deleteJson("/api/shops/{$shop->id}/employees/{$employee['id']}")
            ->assertOk();

        $this->assertDatabaseMissing('shop_user', [
            'shop_id' => $shop->id,
            'user_id' => $employee['id'],
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $employee['id'],
            'email' => 'staff@example.com',
        ]);
    }

    public function test_plan_user_limit_is_enforced_for_employees(): void
    {
        [$owner, $shop, $token] = $this->shopOwnerWithToken();
        $this->createSubscription($shop, 2);

        $this->withToken($token)->postJson("/api/shops/{$shop->id}/employees", [
            'name' => 'First Staff',
            'email' => 'first@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'staff',
        ])->assertCreated();

        $this->withToken($token)->postJson("/api/shops/{$shop->id}/employees", [
            'name' => 'Second Staff',
            'email' => 'second@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'staff',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Your plan user limit has been reached.');
    }

    public function test_non_owner_cannot_manage_employees(): void
    {
        [$owner, $shop, $token] = $this->shopOwnerWithToken();
        $staff = User::factory()->create([
            'password' => 'password123',
        ]);
        $shop->users()->attach($staff->id, ['role' => 'staff', 'status' => 'active']);

        $login = $this->postJson('/api/login', [
            'email' => $staff->email,
            'password' => 'password123',
        ])->assertOk();

        $this->withToken($login->json('data.access_token'))->postJson("/api/shops/{$shop->id}/employees", [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'staff',
        ])->assertStatus(403)
            ->assertJsonPath('message', 'Only the shop owner can manage employees.');
    }

    /**
     * @return array{0: User, 1: Shop, 2: string}
     */
    private function shopOwnerWithToken(): array
    {
        $owner = User::factory()->create([
            'password' => 'password123',
        ]);

        $shop = Shop::create([
            'owner_user_id' => $owner->id,
            'name' => 'Test Shop',
            'slug' => 'test-shop',
        ]);

        $shop->users()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);

        $login = $this->postJson('/api/login', [
            'email' => $owner->email,
            'password' => 'password123',
        ])->assertOk();

        return [$owner, $shop, $login->json('data.access_token')];
    }

    private function createSubscription(Shop $shop, int $maxUsers): void
    {
        $plan = Plan::create([
            'name' => 'Employee Plan '.$maxUsers,
            'slug' => 'employee-plan-'.$maxUsers,
            'price' => 1000,
            'duration_days' => 30,
            'max_shops' => 1,
            'max_users' => $maxUsers,
            'is_active' => true,
        ]);

        Subscription::create([
            'shop_id' => $shop->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'price' => $plan->price,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(30)->toDateString(),
        ]);
    }
}
