@extends('admin.layouts.app')

@section('title', config('brand.name').' Dashboard')

@section('content')
    <section class="dashboard-hero">
        <div>
            <p class="eyebrow">Shop Management</p>
            <h1 class="dashboard-title">Welcome back, {{ auth('admin')->user()?->name }}</h1>
            <p class="dashboard-copy">Manage shop customers, API access, and admin accounts from one place.</p>
        </div>
        <div class="hero-actions">
            <a class="button primary" href="{{ route('admin.api-users.create') }}">Add API User</a>
            <a class="button" href="{{ route('admin.admins.create') }}">Add Admin</a>
        </div>
    </section>

    <section class="stats-grid" aria-label="Dashboard stats">
        <article class="stat-card accent-green">
            <div class="stat-icon">U</div>
            <div>
                <span>API Users</span>
                <strong>{{ number_format($usersCount) }}</strong>
                <p>Shop app customer accounts</p>
            </div>
        </article>

        <article class="stat-card accent-amber">
            <div class="stat-icon">A</div>
            <div>
                <span>Admin Users</span>
                <strong>{{ number_format($adminsCount) }}</strong>
                <p>Dashboard access accounts</p>
            </div>
        </article>

        <article class="stat-card accent-slate">
            <div class="stat-icon">T</div>
            <div>
                <span>API Tokens</span>
                <strong>{{ number_format($tokensCount) }}</strong>
                <p>Active mobile/API sessions</p>
            </div>
        </article>
    </section>

    <section class="dashboard-grid">
        <div class="panel table-panel">
            <div class="panel-header">
                <div>
                    <p class="eyebrow">Recent Activity</p>
                    <h2 class="title title-sm">Recent API Users</h2>
                </div>
                <a class="button" href="{{ route('admin.api-users.index') }}">View All</a>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentUsers as $user)
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <span class="avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    <strong>{{ $user->name }}</strong>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td><span class="badge">API User</span></td>
                            <td>{{ $user->created_at?->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <strong>No API users yet</strong>
                                    <span>Create your first shop API user to get started.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <aside class="quick-panel">
            <div class="panel-header compact">
                <div>
                    <p class="eyebrow">Shortcuts</p>
                    <h2 class="title title-sm">Quick Actions</h2>
                </div>
            </div>

            <a class="quick-link" href="{{ route('admin.api-users.create') }}">
                <span>Create API user</span>
                <small>Add a new shop customer account</small>
            </a>
            <a class="quick-link" href="{{ route('admin.admins.index') }}">
                <span>Manage admins</span>
                <small>Control dashboard access</small>
            </a>
            <a class="quick-link" href="{{ url('/api/documentation') }}" target="_blank">
                <span>Open API docs</span>
                <small>Swagger documentation</small>
            </a>
        </aside>
    </section>
@endsection