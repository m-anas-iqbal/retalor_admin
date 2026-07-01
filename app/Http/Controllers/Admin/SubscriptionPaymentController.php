<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SubscriptionPaymentController extends Controller
{
    public function index(): View
    {
        return view('admin.subscription-payments.index', [
            'payments' => SubscriptionPayment::with(['shop', 'plan', 'user', 'reviewer', 'subscription'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function edit(SubscriptionPayment $subscriptionPayment): View
    {
        return view('admin.subscription-payments.edit', [
            'payment' => $subscriptionPayment->load(['shop', 'plan', 'user', 'subscription']),
        ]);
    }

    public function approve(Request $request, SubscriptionPayment $subscriptionPayment): RedirectResponse
    {
        DB::transaction(function () use ($request, $subscriptionPayment): void {
            $subscriptionPayment->update([
                'status' => 'approved',
                'admin_note' => $request->string('admin_note')->toString() ?: null,
                'reviewed_by_admin_id' => $request->user('admin')->id,
                'reviewed_at' => now(),
            ]);

            $subscription = $subscriptionPayment->subscription;
            $startDate = $subscription->starts_at ?? now()->toDateString();

            $subscription->update([
                'status' => 'active',
                'starts_at' => $startDate,
                'ends_at' => now()->parse($startDate)->addDays($subscription->plan->duration_days)->toDateString(),
                'subscribed_at' => now(),
                'notes' => $request->string('admin_note')->toString() ?: $subscription->notes,
            ]);
        });

        return redirect()->route('admin.subscription-payments.index')->with('status', 'Payment approved and subscription activated.');
    }

    public function reject(Request $request, SubscriptionPayment $subscriptionPayment): RedirectResponse
    {
        $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $subscriptionPayment): void {
            $subscriptionPayment->update([
                'status' => 'rejected',
                'admin_note' => $request->string('admin_note')->toString(),
                'reviewed_by_admin_id' => $request->user('admin')->id,
                'reviewed_at' => now(),
            ]);

            $subscriptionPayment->subscription->update([
                'status' => 'cancelled',
                'notes' => $request->string('admin_note')->toString(),
            ]);
        });

        return redirect()->route('admin.subscription-payments.index')->with('status', 'Payment rejected.');
    }

    public function download(SubscriptionPayment $subscriptionPayment)
    {
        abort_if(! $subscriptionPayment->screenshot_path, 404);

        return Storage::disk('public')->download($subscriptionPayment->screenshot_path);
    }
}
