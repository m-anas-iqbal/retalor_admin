<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSubscriptionPaymentRequest;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ShopSubscriptionController extends Controller
{
    public function show(Request $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        $subscription = $shop->subscriptions()
            ->with([
                'plan',
                'payments' => fn ($query) => $query->latest()->with('reviewer:id,name,email'),
            ])
            ->latest()
            ->first();

        if (! $subscription) {
            return ApiResponse::error('No subscription found for this shop.', 404);
        }

        return ApiResponse::success('Subscription fetched.', [
            'subscription' => $subscription,
        ]);
    }

    public function storePayment(StoreSubscriptionPaymentRequest $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        /** @var Subscription|null $subscription */
        $subscription = $shop->subscriptions()->latest()->first();

        if (! $subscription) {
            return ApiResponse::error('No subscription found for this shop.', 404);
        }

        $data = $request->validated();

        $payment = DB::transaction(function () use ($request, $shop, $subscription, $data): SubscriptionPayment {
            $path = null;

            if ($request->hasFile('payment_screenshot')) {
                $path = $request->file('payment_screenshot')->store('subscription-payments', 'public');
            }

            $subscription->update([
                'status' => 'pending',
                'notes' => $data['notes'] ?? $subscription->notes,
            ]);

            return SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'shop_id' => $shop->id,
                'plan_id' => $subscription->plan_id,
                'user_id' => $request->user()->id,
                'payment_method' => $data['payment_method'],
                'status' => 'pending',
                'amount' => $subscription->price,
                'reference_no' => $data['reference_no'] ?? null,
                'screenshot_path' => $path,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return ApiResponse::success('Subscription payment submitted.', [
            'payment' => $this->transformPayment($payment->fresh(['plan', 'reviewer'])),
            'subscription' => $subscription->fresh('plan'),
        ], 201);
    }

    private function canAccessShop(Request $request, Shop $shop): bool
    {
        return $shop->users()->whereKey($request->user()->id)->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformPayment(SubscriptionPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'subscription_id' => $payment->subscription_id,
            'shop_id' => $payment->shop_id,
            'plan_id' => $payment->plan_id,
            'payment_method' => $payment->payment_method,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'reference_no' => $payment->reference_no,
            'screenshot_url' => $payment->screenshot_path ? Storage::disk('public')->url($payment->screenshot_path) : null,
            'notes' => $payment->notes,
            'admin_note' => $payment->admin_note,
            'reviewed_at' => $payment->reviewed_at,
        ];
    }
}
