@extends('admin.layouts.auth')

@section('title', config('brand.name').' Forgot Password')

@section('content')
    <section class="auth-card">
        <div class="auth-brand">
            <img src="{{ asset(config('brand.logo')) }}" alt="{{ config('brand.name') }}">
        </div>

        <div class="auth-heading">
            <span>Password Recovery</span>
            <h1>Forgot password?</h1>
            <p>Enter your admin email. We will generate a 6-digit OTP to reset your password.</p>
        </div>

        @if ($errors->any())
            <div class="notice error">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ route('admin.password.email') }}">
            @csrf
            <label>
                Email address
                <input type="email" name="email" value="{{ old('email') }}" placeholder="admin@example.com" required autofocus>
            </label>
            <button class="auth-button" type="submit">Send OTP</button>
        </form>

        <p class="auth-footer"><a href="{{ route('admin.login') }}">Back to login</a></p>
    </section>
@endsection