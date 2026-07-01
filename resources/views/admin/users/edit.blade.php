@extends('admin.layouts.app')

@section('content')
    <div class="topbar">
        <h1 class="title">Edit API User</h1>
        <a class="button" href="{{ route('admin.api-users.index') }}">Back</a>
    </div>

    <section class="panel">
        <form class="form" method="post" action="{{ route('admin.api-users.update', $user) }}">
            @csrf
            @method('put')
            @include('admin.users.partials.form', ['user' => $user])
            <button class="button primary" type="submit">Save Changes</button>
        </form>
    </section>
@endsection
