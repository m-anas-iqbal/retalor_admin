<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubscriptionRequest;
use App\Http\Requests\Admin\UpdateSubscriptionRequest;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(): View
    {
        return view('admin.subscriptions.index', [
            'subscriptions' => Subscription::with(['shop', 'plan'])->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.subscriptions.create', [
            'plans' => Plan::query()->where('is_active', true)->orderBy('name')->get(),
            'shops' => Shop::query()->orderBy('name')->get(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function store(StoreSubscriptionRequest $request): RedirectResponse
    {
        $data = $this->normalize($request->validated());

        Subscription::create($data);

        return redirect()->route('admin.subscriptions.index')->with('status', 'Subscription created.');
    }

    public function edit(Subscription $subscription): View
    {
        return view('admin.subscriptions.edit', [
            'subscription' => $subscription,
            'plans' => Plan::query()->orderBy('name')->get(),
            'shops' => Shop::query()->orderBy('name')->get(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(UpdateSubscriptionRequest $request, Subscription $subscription): RedirectResponse
    {
        $subscription->update($this->normalize($request->validated()));

        return redirect()->route('admin.subscriptions.index')->with('status', 'Subscription updated.');
    }

    public function destroy(Subscription $subscription): RedirectResponse
    {
        $subscription->delete();

        return redirect()->route('admin.subscriptions.index')->with('status', 'Subscription deleted.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $plan = Plan::findOrFail($data['plan_id']);

        $data['price'] = $data['price'] ?? $plan->price;
        $data['subscribed_at'] = $data['subscribed_at'] ?? now();

        return $data;
    }

    /**
     * @return array<string, string>
     */
    private function statuses(): array
    {
        return [
            'pending' => 'Pending',
            'trial' => 'Trial',
            'active' => 'Active',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
        ];
    }
}
