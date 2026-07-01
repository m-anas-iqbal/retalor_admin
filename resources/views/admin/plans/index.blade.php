@extends('admin.layouts.app')

@section('content')
    <div class="topbar">
        <h1 class="title">Plans</h1>
        <a class="button primary" href="{{ route('admin.plans.create') }}">Add Plan</a>
    </div>

    <section class="panel">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Duration</th>
                    <th>Limits</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr>
                        <td>
                            <strong>{{ $plan->name }}</strong>
                            <div class="muted">{{ $plan->slug }}</div>
                        </td>
                        <td>PKR {{ number_format((float) $plan->price, 2) }}</td>
                        <td>{{ $plan->duration_days }} days</td>
                        <td>{{ $plan->max_shops }} shops / {{ $plan->max_users }} users</td>
                        <td>{{ $plan->is_active ? 'Active' : 'Inactive' }}</td>
                        <td>
                            <div class="actions">
                                <a class="button" href="{{ route('admin.plans.edit', $plan) }}">Edit</a>
                                <form method="post" action="{{ route('admin.plans.destroy', $plan) }}">
                                    @csrf
                                    @method('delete')
                                    <button class="button danger" type="submit" onclick="return confirm('Delete this plan?')">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No plans found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination-wrap">{{ $plans->links() }}</div>
    </section>
@endsection
