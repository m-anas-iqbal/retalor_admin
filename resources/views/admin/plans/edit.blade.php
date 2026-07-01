@extends('admin.layouts.app')

@section('content')
    <div class="topbar">
        <h1 class="title">Edit Plan</h1>
    </div>

    <section class="panel">
        <form method="post" action="{{ route('admin.plans.update', $plan) }}">
            @csrf
            @method('put')
            @include('admin.plans.partials.form', ['plan' => $plan])

            <div class="actions mt-4">
                <button class="button primary" type="submit">Update Plan</button>
            </div>
        </form>
    </section>
@endsection
