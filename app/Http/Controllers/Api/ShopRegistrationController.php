<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterShopRequest;
use App\Models\ApiToken;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShopRegistrationController extends Controller
{
    public function store(RegisterShopRequest $request): JsonResponse
    {
        $data = $request->validated();
        $plan = Plan::query()->where('is_active', true)->findOrFail($data['plan_id']);

        $result = DB::transaction(function () use ($request, $data, $plan): array {
            $owner = User::create([
                'name' => $data['owner']['name'],
                'email' => $data['owner']['email'],
                'password' => $data['owner']['password'],
            ]);

            $shopName = $data['shop']['name'];
            $shop = Shop::create([
                'owner_user_id' => $owner->id,
                'name' => $shopName,
                'slug' => $data['shop']['slug'] ?? $this->uniqueSlug($shopName),
                'email' => $data['shop']['email'] ?? null,
                'phone' => $data['shop']['phone'] ?? null,
                'address' => $data['shop']['address'] ?? null,
                'city' => $data['shop']['city'] ?? null,
                'status' => 'active',
            ]);

            $shop->users()->attach($owner->id, [
                'role' => 'owner',
                'status' => 'active',
            ]);

            $subscription = Subscription::create([
                'shop_id' => $shop->id,
                'plan_id' => $plan->id,
                'status' => 'pending',
                'price' => $plan->price,
                'notes' => $data['payment_notes'] ?? null,
            ]);

            $screenshotPath = null;

            if ($request->hasFile('payment_screenshot')) {
                $screenshotPath = $request->file('payment_screenshot')->store('subscription-payments', 'public');
            }

            $payment = SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'shop_id' => $shop->id,
                'plan_id' => $plan->id,
                'user_id' => $owner->id,
                'payment_method' => $data['payment_method'],
                'status' => 'pending',
                'amount' => $plan->price,
                'reference_no' => $data['payment_reference'] ?? null,
                'screenshot_path' => $screenshotPath,
                'notes' => $data['payment_notes'] ?? null,
            ]);

            $plainToken = $this->createPlainToken($owner, $data['device_name'] ?? 'shop-registration');

            return [
                'shop' => $shop->load('owner'),
                'owner' => $owner->load('shops'),
                'subscription' => $subscription->load('plan'),
                'payment' => $this->transformPayment($payment),
                'token_type' => 'Bearer',
                'access_token' => $plainToken,
            ];
        });

        return ApiResponse::success('Shop registered and subscription request submitted.', $result, 201);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'shop';
        $slug = $base;
        $counter = 2;

        while (Shop::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function createPlainToken(User $user, string $name): string
    {
        $plainToken = Str::random(64);

        ApiToken::create([
            'user_id' => $user->id,
            'name' => $name,
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(30),
        ]);

        return $plainToken;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformPayment(SubscriptionPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'payment_method' => $payment->payment_method,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'reference_no' => $payment->reference_no,
            'screenshot_file' => $payment->screenshot_path ? basename($payment->screenshot_path) : null,
            'screenshot_url' => $payment->screenshot_path ? Storage::disk('public')->url($payment->screenshot_path) : null,
            'notes' => $payment->notes,
        ];
    }
}
