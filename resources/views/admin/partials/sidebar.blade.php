<aside class="sidebar">
    <a class="brand" href="{{ route('admin.dashboard') }}" aria-label="{{ config('brand.name') }} dashboard">
        <img src="{{ asset(config('brand.logo_light')) }}" alt="{{ config('brand.name') }}">
    </a>

    <nav class="nav" aria-label="Admin navigation">
        <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
        <a class="{{ request()->routeIs('admin.admins.*') ? 'active' : '' }}" href="{{ route('admin.admins.index') }}">Admin Users</a>
        <a class="{{ request()->routeIs('admin.api-users.*') ? 'active' : '' }}" href="{{ route('admin.api-users.index') }}">API Users</a>
        <a class="{{ request()->routeIs('admin.plans.*') ? 'active' : '' }}" href="{{ route('admin.plans.index') }}">Plans</a>
        <a class="{{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}" href="{{ route('admin.subscriptions.index') }}">Subscriptions</a>
        <a class="{{ request()->routeIs('admin.subscription-payments.*') ? 'active' : '' }}" href="{{ route('admin.subscription-payments.index') }}">Payment Reviews</a>
        <a href="{{ url('/api/documentation') }}" target="_blank">Swagger API Docs</a>
        <form method="post" action="{{ route('admin.logout') }}">
            @csrf
            <button class="logout" type="submit">Logout</button>
        </form>
    </nav>
</aside>