<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        return view('admin.admins.index', [
            'admins' => Admin::latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.admins.create');
    }

    public function store(StoreAdminRequest $request): RedirectResponse
    {
        Admin::create($request->validated());

        return redirect()->route('admin.admins.index')->with('status', 'Admin user created.');
    }

    public function edit(Admin $admin): View
    {
        return view('admin.admins.edit', compact('admin'));
    }

    public function update(UpdateAdminRequest $request, Admin $admin): RedirectResponse
    {
        $data = $request->validated();

        if (! $data['password']) {
            unset($data['password']);
        }

        $admin->update($data);

        return redirect()->route('admin.admins.index')->with('status', 'Admin user updated.');
    }

    public function destroy(Request $request, Admin $admin): RedirectResponse
    {
        if ($request->user('admin')->is($admin)) {
            return back()->withErrors(['admin' => 'You cannot delete your own admin account.']);
        }

        $admin->delete();

        return redirect()->route('admin.admins.index')->with('status', 'Admin user deleted.');
    }
}
