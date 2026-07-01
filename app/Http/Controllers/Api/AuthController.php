<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Models\ApiToken;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const PASSWORD_RESET_OTP_MINUTES = 10;

    private const PASSWORD_RESET_RESEND_SECONDS = 60;

    private const PASSWORD_RESET_MAX_ATTEMPTS = 5;

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return ApiResponse::success('Account created.', [
            'token_type' => 'Bearer',
            'access_token' => $this->createPlainToken($user, $data['device_name'] ?? 'api'),
            'user' => $user->load('shops'),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return ApiResponse::error('Invalid credentials.', 422);
        }

        return ApiResponse::success('Login successful.', [
            'token_type' => 'Bearer',
            'access_token' => $this->createPlainToken($user, $credentials['device_name'] ?? 'api'),
            'user' => $user->load('shops'),
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->validated('email');
        $userExists = User::where('email', $email)->exists();
        $data = [
            'expires_in_minutes' => self::PASSWORD_RESET_OTP_MINUTES,
        ];

        if (! $userExists) {
            return ApiResponse::success('If this email exists, a password reset OTP has been generated.', $data);
        }

        $existingReset = DB::table('password_reset_tokens')->where('email', $email)->first();

        if ($existingReset && now()->parse($existingReset->created_at)->gt(now()->subSeconds(self::PASSWORD_RESET_RESEND_SECONDS))) {
            return ApiResponse::error('Please wait before requesting another OTP.', 429, [
                'retry_after_seconds' => self::PASSWORD_RESET_RESEND_SECONDS,
            ]);
        }

        $otp = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert([
            'email' => $email,
        ], [
            'token' => Hash::make($otp),
            'attempts' => 0,
            'created_at' => now(),
            'expires_at' => now()->addMinutes(self::PASSWORD_RESET_OTP_MINUTES),
        ]);

        if (app()->isLocal() || app()->runningUnitTests()) {
            $data['otp'] = $otp;
        }

        return ApiResponse::success('If this email exists, a password reset OTP has been generated.', $data);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $reset = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        if (! $reset) {
            return ApiResponse::error('Invalid OTP.', 422);
        }

        if ($reset->expires_at && now()->parse($reset->expires_at)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

            return ApiResponse::error('OTP expired.', 422);
        }

        if ((int) $reset->attempts >= self::PASSWORD_RESET_MAX_ATTEMPTS) {
            return ApiResponse::error('Too many invalid OTP attempts. Please request a new OTP.', 429);
        }

        if (! Hash::check($data['otp'], $reset->token)) {
            DB::table('password_reset_tokens')->where('email', $data['email'])->increment('attempts');

            return ApiResponse::error('Invalid OTP.', 422);
        }

        $user = User::where('email', $data['email'])->firstOrFail();
        $user->update([
            'password' => $data['password'],
        ]);

        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
        ApiToken::whereHas('user', fn ($query) => $query->where('email', $data['email']))->delete();

        return ApiResponse::success('Password reset successfully.');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            return ApiResponse::error('Current password is incorrect.', 422);
        }

        $user->update([
            'password' => $data['password'],
        ]);

        $currentToken = $request->attributes->get('api_token');
        $user->tokens()->when($currentToken, fn ($query) => $query->whereKeyNot($currentToken->id))->delete();

        return ApiResponse::success('Password changed successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success('User profile fetched.', [
            'user' => $request->user()->load('shops'),
        ]);
    }

    public function updateMe(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return ApiResponse::success('Profile updated successfully.', [
            'user' => $user->fresh('shops'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('api_token')?->delete();

        return ApiResponse::success('Logged out.');
    }

    private function createPlainToken(User $user, string $name): string
    {
        $plainToken = Str::random(64);

        ApiToken::create([
            'user_id' => $user->id,
            'name' => $name,
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(30),
        ]);

        return $plainToken;
    }
}


