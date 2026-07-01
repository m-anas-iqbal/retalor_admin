@extends('admin.layouts.app')

@section('content')
    <div class="topbar">
        <h1 class="title">Edit Subscription</h1>
    </div>

    <section class="panel">
        <form method="post" action="{{ route('admin.subscriptions.update', $subscription) }}">
            @csrf
            @method('put')
            @include('admin.subscriptions.partials.form', ['subscription' => $subscription])

            <div class="actions mt-4">
                <button class="button primary" type="submit">Update Subscription</button>
            </div>
        </form>
    </section>
@endsection
