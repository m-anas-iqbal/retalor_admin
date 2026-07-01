@extends('admin.layouts.app')

@section('content')
    <div class="topbar">
        <h1 class="title">Edit Admin User</h1>
        <a class="button" href="{{ route('admin.admins.index') }}">Back</a>
    </div>

    <section class="panel">
        <form class="form" method="post" action="{{ route('admin.admins.update', $admin) }}">
            @csrf
            @method('put')
            @include('admin.admins.partials.form', ['admin' => $admin])
            <button class="button primary" type="submit">Save Changes</button>
        </form>
    </section>
@endsection
