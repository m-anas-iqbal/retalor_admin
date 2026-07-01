@extends('admin.layouts.app')

@section('content')
    <div class="topbar">
        <h1 class="title">Create API User</h1>
        <a class="button" href="{{ route('admin.api-users.index') }}">Back</a>
    </div>

    <section class="panel">
        <form class="form" method="post" action="{{ route('admin.api-users.store') }}">
            @csrf
            @include('admin.users.partials.form', ['user' => null])
            <button class="button primary" type="submit">Create API User</button>
        </form>
    </section>
@endsection
