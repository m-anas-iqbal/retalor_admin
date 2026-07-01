<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreUserRequest;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success('Users fetched.', [
            'users' => User::latest()->paginate(15),
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return ApiResponse::success('User created.', [
            'user' => $user,
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        return ApiResponse::success('User fetched.', [
            'user' => $user,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());

        return ApiResponse::success('User updated.', [
            'user' => $user,
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return ApiResponse::error('You cannot delete your own account.', 422);
        }

        $user->delete();

        return ApiResponse::success('User deleted.');
    }
}
