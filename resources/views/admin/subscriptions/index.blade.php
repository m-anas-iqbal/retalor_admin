@extends('admin.layouts.app')

@section('content')
    <div class="topbar">
        <h1 class="title">Subscriptions</h1>
        <a class="button primary" href="{{ route('admin.subscriptions.create') }}">Add Subscription</a>
    </div>

    <section class="panel">
        <table>
            <thead>
                <tr>
                    <th>Shop</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Price</th>
                    <th>Period</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subscriptions as $subscription)
                    <tr>
                        <td>{{ $subscription->shop->name }}</td>
                        <td>{{ $subscription->plan->name }}</td>
                        <td>{{ ucfirst($subscription->status) }}</td>
                        <td>PKR {{ number_format((float) $subscription->price, 2) }}</td>
                        <td>
                            {{ $subscription->starts_at?->format('Y-m-d') ?? '-' }}
                            to
                            {{ $subscription->ends_at?->format('Y-m-d') ?? '-' }}
                        </td>
                        <td>
                            <div class="actions">
                                <a class="button" href="{{ route('admin.subscriptions.edit', $subscription) }}">Edit</a>
                                <form method="post" action="{{ route('admin.subscriptions.destroy', $subscription) }}">
                                    @csrf
                                    @method('delete')
                                    <button class="button danger" type="submit" onclick="return confirm('Delete this subscription?')">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No subscriptions found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination-wrap">{{ $subscriptions->links() }}</div>
    </section>
@endsection
