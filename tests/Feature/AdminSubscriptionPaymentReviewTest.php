<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriptionPaymentReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_ibft_payment_and_activate_subscription(): void
    {
        $admin = Admin::factory()->create();
        $owner = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Growth',
            'slug' => 'growth',
            'price' => 3500,
            'duration_days' => 30,
            'max_shops' => 1,
            'max_users' => 5,
            'is_active' => true,
        ]);
        $shop = Shop::create([
            'owner_user_id' => $owner->id,
            'name' => 'Review Shop',
            'slug' => 'review-shop',
            'status' => 'active',
        ]);
        $subscription = Subscription::create([
            'shop_id' => $shop->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'price' => $plan->price,
        ]);
        $payment = SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'shop_id' => $shop->id,
            'plan_id' => $plan->id,
            'user_id' => $owner->id,
            'payment_method' => 'ibft',
            'status' => 'pending',
            'amount' => $plan->price,
            'reference_no' => 'IBFT-123',
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.subscription-payments.approve', $payment), [
                'admin_note' => 'Bank transfer verified.',
            ])
            ->assertRedirect(route('admin.subscription-payments.index'));

        $this->assertDatabaseHas('subscription_payments', [
            'id' => $payment->id,
            'status' => 'approved',
            'reviewed_by_admin_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'active',
        ]);
    }
}
