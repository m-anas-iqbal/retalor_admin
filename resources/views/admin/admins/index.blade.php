@extends('admin.layouts.app')

@section('content')
    <div class="topbar">
        <h1 class="title">Admin Users</h1>
        <a class="button primary" href="{{ route('admin.admins.create') }}">Add Admin User</a>
    </div>

    <section class="panel">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Created</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($admins as $admin)
                    <tr>
                        <td>{{ $admin->name }}</td>
                        <td>{{ $admin->email }}</td>
                        <td>{{ $admin->created_at?->format('M d, Y') }}</td>
                        <td>
                            <div class="actions">
                                <a class="button" href="{{ route('admin.admins.edit', $admin) }}">Edit</a>
                                <form method="post" action="{{ route('admin.admins.destroy', $admin) }}">
                                    @csrf
                                    @method('delete')
                                    <button class="button danger" type="submit" onclick="return confirm('Delete this admin user?')">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">No admin users found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination-wrap">{{ $admins->links() }}</div>
    </section>
@endsection
