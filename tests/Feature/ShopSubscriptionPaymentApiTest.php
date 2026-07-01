<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShopSubscriptionPaymentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_shopkeeper_can_register_with_cash_subscription_payment(): void
    {
        $plan = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 2500,
            'duration_days' => 30,
            'max_shops' => 1,
            'max_users' => 5,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/shops/register', [
            'shop' => [
                'name' => 'Cash Shop',
                'slug' => 'cash-shop',
            ],
            'owner' => [
                'name' => 'Cash Owner',
                'email' => 'cash-owner@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ],
            'plan_id' => $plan->id,
            'payment_method' => 'cash',
            'payment_reference' => 'CASH-COUNTER',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subscription.status', 'pending')
            ->assertJsonPath('data.payment.payment_method', 'cash')
            ->assertJsonPath('data.payment.status', 'pending');

        $this->assertDatabaseHas('subscription_payments', [
            'payment_method' => 'cash',
            'status' => 'pending',
        ]);
    }

    public function test_shopkeeper_can_submit_ibft_screenshot_payment(): void
    {
        Storage::fake('public');

        $plan = Plan::create([
            'name' => 'IBFT Plan',
            'slug' => 'ibft-plan',
            'price' => 4500,
            'duration_days' => 30,
            'max_shops' => 1,
            'max_users' => 5,
            'is_active' => true,
        ]);

        $response = $this->post('/api/shops/register', [
            'shop' => [
                'name' => 'IBFT Shop',
                'slug' => 'ibft-shop',
            ],
            'owner' => [
                'name' => 'IBFT Owner',
                'email' => 'ibft-owner@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ],
            'plan_id' => $plan->id,
            'payment_method' => 'ibft',
            'payment_reference' => 'IBFT-9988',
            'payment_screenshot' => UploadedFile::fake()->image('ibft-proof.jpg'),
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.payment.payment_method', 'ibft')
            ->assertJsonPath('data.payment.status', 'pending');

        $this->assertDatabaseHas('subscription_payments', [
            'payment_method' => 'ibft',
            'reference_no' => 'IBFT-9988',
        ]);

        Storage::disk('public')->assertExists('subscription-payments/'.$response->json('data.payment.screenshot_file'));
    }
}
