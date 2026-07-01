@extends('admin.layouts.app')

@section('content')
    <div class="topbar">
        <h1 class="title">Create Subscription</h1>
    </div>

    <section class="panel">
        <form method="post" action="{{ route('admin.subscriptions.store') }}">
            @csrf
            @include('admin.subscriptions.partials.form')

            <div class="actions mt-4">
                <button class="button primary" type="submit">Save Subscription</button>
            </div>
        </form>
    </section>
@endsection
