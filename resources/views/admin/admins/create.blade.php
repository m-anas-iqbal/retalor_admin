@extends('admin.layouts.app')

@section('content')
    <div class="topbar">
        <h1 class="title">Create Admin User</h1>
        <a class="button" href="{{ route('admin.admins.index') }}">Back</a>
    </div>

    <section class="panel">
        <form class="form" method="post" action="{{ route('admin.admins.store') }}">
            @csrf
            @include('admin.admins.partials.form', ['admin' => null])
            <button class="button primary" type="submit">Create Admin User</button>
        </form>
    </section>
@endsection
