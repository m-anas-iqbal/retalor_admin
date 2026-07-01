@extends('admin.layouts.app')

@section('content')
    <div class="topbar">
        <h1 class="title">Create Plan</h1>
    </div>

    <section class="panel">
        <form method="post" action="{{ route('admin.plans.store') }}">
            @csrf
            @include('admin.plans.partials.form')

            <div class="actions mt-4">
                <button class="button primary" type="submit">Save Plan</button>
            </div>
        </form>
    </section>
@endsection
