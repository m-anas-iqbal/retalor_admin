@extends('admin.layouts.app')

@section('content')
    <div class="topbar">
        <h1 class="title">Subscription Payments</h1>
    </div>

    <section class="panel">
        <table>
            <thead>
                <tr>
                    <th>Shop</th>
                    <th>Plan</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Proof</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td>{{ $payment->shop->name }}</td>
                        <td>{{ $payment->plan->name }}</td>
                        <td>{{ strtoupper($payment->payment_method) }}</td>
                        <td>{{ ucfirst($payment->status) }}</td>
                        <td>PKR {{ number_format((float) $payment->amount, 2) }}</td>
                        <td>{{ $payment->screenshot_path ? 'Uploaded' : 'N/A' }}</td>
                        <td>
                            <div class="actions">
                                <a class="button" href="{{ route('admin.subscription-payments.edit', $payment) }}">Review</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No subscription payments found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination-wrap">{{ $payments->links() }}</div>
    </section>
@endsection
