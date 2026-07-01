<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('brand.name').' Admin')</title>
    <link rel="icon" href="{{ asset(config('brand.favicon')) }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('assets/admin/app.css') }}">
</head>
<body>
<div class="shell">
    @include('admin.partials.sidebar')

    <main class="main">
        @include('admin.partials.alerts')
        @yield('content')
    </main>
</div>
</body>
</html>