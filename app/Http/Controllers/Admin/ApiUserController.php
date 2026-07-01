<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreApiUserRequest;
use App\Http\Requests\Admin\UpdateApiUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApiUserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(StoreApiUserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()->route('admin.api-users.index')->with('status', 'API user created.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateApiUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (! $data['password']) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.api-users.index')->with('status', 'API user updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()->route('admin.api-users.index')->with('status', 'API user deleted.');
    }
}
