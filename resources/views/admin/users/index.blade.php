@extends('admin.layouts.app')

@section('content')
    <div class="topbar">
        <h1 class="title">API Users</h1>
        <a class="button primary" href="{{ route('admin.api-users.create') }}">Add API User</a>
    </div>

    <section class="panel">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>API User</td>
                        <td>
                            <div class="actions">
                                <a class="button" href="{{ route('admin.api-users.edit', $user) }}">Edit</a>
                                <form method="post" action="{{ route('admin.api-users.destroy', $user) }}">
                                    @csrf
                                    @method('delete')
                                    <button class="button danger" type="submit" onclick="return confirm('Delete this user?')">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination-wrap">{{ $users->links() }}</div>
    </section>
@endsection
