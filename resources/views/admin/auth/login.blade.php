@extends('admin.layouts.auth')

@section('title', config('brand.name').' Admin Login')

@section('content')
    <section class="auth-card">
        <div class="auth-brand">
            <img src="{{ asset(config('brand.logo')) }}" alt="{{ config('brand.name') }}">
        </div>

        <div class="auth-heading">
            <span>Shop Management</span>
            <h1>Admin Login</h1>
            <p>Sign in to manage shop users, admin accounts, and API access.</p>
        </div>

        @if (session('status'))
            <div class="notice ok">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="notice error">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ route('admin.login.store') }}">
            @csrf
            <label>
                Email address
                <input type="email" name="email" value="{{ old('email') }}" placeholder="admin@example.com" required autofocus>
            </label>
            <label>
                Password
                <input type="password" name="password" placeholder="Enter password" required>
            </label>
            <div class="auth-row">
                <label class="check-label">
                    <input type="checkbox" name="remember" value="1"> Remember me
                </label>
                <a href="{{ route('admin.password.forgot') }}">Forgot password?</a>
            </div>
            <button class="auth-button" type="submit">Sign in</button>
        </form>

        <p class="hint">Default admin: admin@example.com / password</p>
    </section>
@endsection