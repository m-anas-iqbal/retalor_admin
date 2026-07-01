@extends('admin.layouts.app')

@section('content')
    <div class="topbar">
        <h1 class="title">Review Subscription Payment</h1>
    </div>

    <section class="panel">
        <div class="grid two">
            <div class="field">
                <span>Shop</span>
                <strong>{{ $payment->shop->name }}</strong>
            </div>
            <div class="field">
                <span>Plan</span>
                <strong>{{ $payment->plan->name }}</strong>
            </div>
            <div class="field">
                <span>Method</span>
                <strong>{{ strtoupper($payment->payment_method) }}</strong>
            </div>
            <div class="field">
                <span>Status</span>
                <strong>{{ ucfirst($payment->status) }}</strong>
            </div>
            <div class="field">
                <span>Amount</span>
                <strong>PKR {{ number_format((float) $payment->amount, 2) }}</strong>
            </div>
            <div class="field">
                <span>Reference</span>
                <strong>{{ $payment->reference_no ?: '-' }}</strong>
            </div>
        </div>

        <label class="field">
            <span>Shopkeeper Note</span>
            <textarea rows="4" readonly>{{ $payment->notes }}</textarea>
        </label>

        @if ($payment->screenshot_path)
            <div class="actions mt-4">
                <a class="button" href="{{ route('admin.subscription-payments.download', $payment) }}">Download IBFT Screenshot</a>
            </div>
        @endif

        <div class="grid two mt-4">
            <form method="post" action="{{ route('admin.subscription-payments.approve', $payment) }}">
                @csrf
                <label class="field">
                    <span>Admin Note</span>
                    <textarea name="admin_note" rows="4">{{ old('admin_note', $payment->admin_note) }}</textarea>
                </label>
                <button class="button primary" type="submit">Approve and Activate</button>
            </form>

            <form method="post" action="{{ route('admin.subscription-payments.reject', $payment) }}">
                @csrf
                <label class="field">
                    <span>Rejection Reason</span>
                    <textarea name="admin_note" rows="4" required>{{ old('admin_note', $payment->admin_note) }}</textarea>
                </label>
                <button class="button danger" type="submit">Reject Payment</button>
            </form>
        </div>
    </section>
@endsection
