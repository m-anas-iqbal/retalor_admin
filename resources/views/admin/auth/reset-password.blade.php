@extends('admin.layouts.auth')

@section('title', config('brand.name').' Reset Password')

@section('content')
    <section class="auth-card">
        <div class="auth-brand">
            <img src="{{ asset(config('brand.logo')) }}" alt="{{ config('brand.name') }}">
        </div>

        <div class="auth-heading">
            <span>New Password</span>
            <h1>Reset password</h1>
            <p>Enter the 6-digit OTP and set a new password for your admin account.</p>
        </div>

        @if (session('status'))
            <div class="notice ok">{{ session('status') }}</div>
        @endif

        @if (session('otp'))
            <div class="otp-preview">Testing OTP: <strong>{{ session('otp') }}</strong></div>
        @endif

        @if ($errors->any())
            <div class="notice error">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ route('admin.password.update') }}">
            @csrf
            <label>
                Email address
                <input type="email" name="email" value="{{ old('email') }}" placeholder="admin@example.com" required autofocus>
            </label>
            <label>
                OTP code
                <input type="text" name="otp" value="{{ old('otp') }}" inputmode="numeric" maxlength="6" placeholder="123456" required>
            </label>
            <label>
                New password
                <input type="password" name="password" placeholder="New password" required>
            </label>
            <label>
                Confirm new password
                <input type="password" name="password_confirmation" placeholder="Confirm new password" required>
            </label>
            <button class="auth-button" type="submit">Reset Password</button>
        </form>

        <p class="auth-footer"><a href="{{ route('admin.password.forgot') }}">Request another OTP</a></p>
    </section>
@endsection