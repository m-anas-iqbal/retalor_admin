<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreShopEmployeeRequest;
use App\Http\Requests\Api\UpdateShopEmployeeRequest;
use App\Models\Shop;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ShopEmployeeController extends Controller
{
    public function index(Request $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        $employees = $shop->users()->latest('shop_user.id')->get();
        $seatLimit = $this->seatLimit($shop);

        return ApiResponse::success('Employees fetched.', [
            'employees' => $employees->map(fn (User $employee) => $this->transformEmployee($employee, $shop))->values(),
            'summary' => [
                'total_users' => $employees->count(),
                'active_users' => $employees->where('pivot.status', 'active')->count(),
                'seat_limit' => $seatLimit,
                'seats_remaining' => $seatLimit === null ? null : max($seatLimit - $employees->count(), 0),
            ],
        ]);
    }

    public function store(StoreShopEmployeeRequest $request, Shop $shop): JsonResponse
    {
        if (! $this->canManageEmployees($request, $shop)) {
            return ApiResponse::error('Only the shop owner can manage employees.', 403);
        }

        $data = $request->validated();
        $seatLimit = $this->seatLimit($shop);
        $currentUsers = $shop->users()->count();

        if ($seatLimit !== null && $currentUsers >= $seatLimit) {
            return ApiResponse::error('Your plan user limit has been reached.', 422, [
                'seat_limit' => $seatLimit,
            ]);
        }

        $employee = DB::transaction(function () use ($shop, $data): User {
            $employee = User::where('email', $data['email'])->first();

            if ($employee !== null && $shop->users()->whereKey($employee->id)->exists()) {
                abort(response()->json(ApiResponse::error('This user is already assigned to the shop.', 422)->getData(true), 422));
            }

            if ($employee === null) {
                if (empty($data['password'])) {
                    abort(response()->json(ApiResponse::error('Password is required for a new employee account.', 422)->getData(true), 422));
                }

                $employee = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                ]);
            }

            $shop->users()->attach($employee->id, [
                'role' => $data['role'],
                'status' => $data['status'] ?? 'active',
            ]);

            return $employee->fresh(['shops']);
        });

        return ApiResponse::success('Employee added to shop.', [
            'employee' => $this->transformEmployee($employee, $shop),
        ], 201);
    }

    public function update(UpdateShopEmployeeRequest $request, Shop $shop, User $employee): JsonResponse
    {
        if (! $this->canManageEmployees($request, $shop)) {
            return ApiResponse::error('Only the shop owner can manage employees.', 403);
        }

        if (! $shop->users()->whereKey($employee->id)->exists()) {
            return ApiResponse::error('Employee does not belong to this shop.', 404);
        }

        if ($employee->id === $shop->owner_user_id) {
            return ApiResponse::error('Owner membership cannot be changed from the employee module.', 422);
        }

        $data = $request->validated();

        $shop->users()->updateExistingPivot($employee->id, $data);

        return ApiResponse::success('Employee updated.', [
            'employee' => $this->transformEmployee($employee->fresh(['shops']), $shop),
        ]);
    }

    public function destroy(Request $request, Shop $shop, User $employee): JsonResponse
    {
        if (! $this->canManageEmployees($request, $shop)) {
            return ApiResponse::error('Only the shop owner can manage employees.', 403);
        }

        if (! $shop->users()->whereKey($employee->id)->exists()) {
            return ApiResponse::error('Employee does not belong to this shop.', 404);
        }

        if ($employee->id === $shop->owner_user_id) {
            return ApiResponse::error('Owner cannot be removed from the shop.', 422);
        }

        $shop->users()->detach($employee->id);

        return ApiResponse::success('Employee removed from shop.');
    }

    private function canAccessShop(Request $request, Shop $shop): bool
    {
        return $shop->users()->whereKey($request->user()->id)->exists();
    }

    private function canManageEmployees(Request $request, Shop $shop): bool
    {
        return (int) $request->user()->id === (int) $shop->owner_user_id;
    }

    private function seatLimit(Shop $shop): ?int
    {
        $subscription = $shop->subscriptions()->with('plan')->latest('id')->first();

        return $subscription?->plan?->max_users;
    }

    private function transformEmployee(User $employee, Shop $shop): array
    {
        $shopMembership = $employee->shops->firstWhere('id', $shop->id);
        $pivot = $shopMembership?->pivot;

        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'is_owner' => (int) $employee->id === (int) $shop->owner_user_id,
            'pivot' => [
                'role' => $pivot?->role ?? ((int) $employee->id === (int) $shop->owner_user_id ? 'owner' : null),
                'status' => $pivot?->status ?? 'active',
            ],
        ];
    }
}
