<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPlanSubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_plan_from_admin_panel(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post('/admin/plans', [
                'name' => 'Business',
                'price' => 4999,
                'duration_days' => 30,
                'max_shops' => 3,
                'max_users' => 12,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.plans.index'));

        $this->assertDatabaseHas('plans', [
            'name' => 'Business',
            'slug' => 'business',
            'duration_days' => 30,
            'max_shops' => 3,
            'max_users' => 12,
        ]);
    }

    public function test_admin_can_create_subscription_from_admin_panel(): void
    {
        $admin = Admin::factory()->create();
        $owner = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 1999,
            'duration_days' => 30,
            'max_shops' => 1,
            'max_users' => 5,
            'is_active' => true,
        ]);
        $shop = Shop::create([
            'owner_user_id' => $owner->id,
            'name' => 'City Mart',
            'slug' => 'city-mart',
            'status' => 'active',
        ]);

        $shop->users()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);

        $this->actingAs($admin, 'admin')
            ->post('/admin/subscriptions', [
                'shop_id' => $shop->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => '2026-06-25',
                'ends_at' => '2026-07-25',
            ])
            ->assertRedirect(route('admin.subscriptions.index'));

        $this->assertDatabaseHas('subscriptions', [
            'shop_id' => $shop->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
    }
}
