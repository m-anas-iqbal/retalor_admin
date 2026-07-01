@php($subscription = $subscription ?? null)

<div class="grid two">
    <label class="field">
        <span>Shop</span>
        <select name="shop_id" required>
            <option value="">Select shop</option>
            @foreach ($shops as $shop)
                <option value="{{ $shop->id }}" @selected((string) old('shop_id', $subscription?->shop_id) === (string) $shop->id)>
                    {{ $shop->name }}
                </option>
            @endforeach
        </select>
    </label>

    <label class="field">
        <span>Plan</span>
        <select name="plan_id" required>
            <option value="">Select plan</option>
            @foreach ($plans as $plan)
                <option value="{{ $plan->id }}" @selected((string) old('plan_id', $subscription?->plan_id) === (string) $plan->id)>
                    {{ $plan->name }} - PKR {{ number_format((float) $plan->price, 2) }}
                </option>
            @endforeach
        </select>
    </label>
</div>

<div class="grid two">
    <label class="field">
        <span>Status</span>
        <select name="status" required>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $subscription?->status ?? 'pending') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>

    <label class="field">
        <span>Price</span>
        <input name="price" type="number" min="0" step="0.01" value="{{ old('price', $subscription?->price) }}" placeholder="leave empty to use plan price">
    </label>
</div>

<div class="grid two">
    <label class="field">
        <span>Starts At</span>
        <input name="starts_at" type="date" value="{{ old('starts_at', $subscription?->starts_at?->format('Y-m-d')) }}">
    </label>

    <label class="field">
        <span>Ends At</span>
        <input name="ends_at" type="date" value="{{ old('ends_at', $subscription?->ends_at?->format('Y-m-d')) }}">
    </label>
</div>

<div class="grid two">
    <label class="field">
        <span>Trial Ends At</span>
        <input name="trial_ends_at" type="date" value="{{ old('trial_ends_at', $subscription?->trial_ends_at?->format('Y-m-d')) }}">
    </label>

    <label class="field">
        <span>Subscribed At</span>
        <input name="subscribed_at" type="date" value="{{ old('subscribed_at', $subscription?->subscribed_at?->format('Y-m-d')) }}">
    </label>
</div>

<label class="field">
    <span>Notes</span>
    <textarea name="notes" rows="4">{{ old('notes', $subscription?->notes) }}</textarea>
</label>
