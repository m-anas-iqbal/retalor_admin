<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ForgotPasswordRequest;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const RESET_OTP_MINUTES = 10;

    private const RESET_RESEND_SECONDS = 60;

    private const RESET_MAX_ATTEMPTS = 5;

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if (! Auth::guard('admin')->attempt($request->validated(), $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'The provided credentials are incorrect.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function showForgotPassword(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function forgotPassword(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = $request->validated('email');
        $adminExists = Admin::where('email', $email)->exists();

        if (! $adminExists) {
            return redirect()
                ->route('admin.password.reset')
                ->with('status', 'If this email exists, a reset OTP has been generated.')
                ->withInput(['email' => $email]);
        }

        $existingReset = DB::table('admin_password_reset_otps')->where('email', $email)->first();

        if ($existingReset && now()->parse($existingReset->created_at)->gt(now()->subSeconds(self::RESET_RESEND_SECONDS))) {
            return back()
                ->withErrors(['email' => 'Please wait before requesting another OTP.'])
                ->onlyInput('email');
        }

        $otp = (string) random_int(100000, 999999);

        DB::table('admin_password_reset_otps')->updateOrInsert([
            'email' => $email,
        ], [
            'otp' => Hash::make($otp),
            'attempts' => 0,
            'created_at' => now(),
            'expires_at' => now()->addMinutes(self::RESET_OTP_MINUTES),
        ]);

        return redirect()
            ->route('admin.password.reset')
            ->with('status', 'OTP generated. It will expire in '.self::RESET_OTP_MINUTES.' minutes.')
            ->with('otp', app()->isLocal() || app()->runningUnitTests() ? $otp : null)
            ->withInput(['email' => $email]);
    }

    public function showResetPassword(): View
    {
        return view('admin.auth.reset-password');
    }

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $reset = DB::table('admin_password_reset_otps')->where('email', $data['email'])->first();

        if (! $reset) {
            return back()->withErrors(['otp' => 'Invalid OTP.'])->withInput($request->except('password', 'password_confirmation'));
        }

        if ($reset->expires_at && now()->parse($reset->expires_at)->isPast()) {
            DB::table('admin_password_reset_otps')->where('email', $data['email'])->delete();

            return back()->withErrors(['otp' => 'OTP expired. Please request a new OTP.'])->withInput($request->except('password', 'password_confirmation'));
        }

        if ((int) $reset->attempts >= self::RESET_MAX_ATTEMPTS) {
            return back()->withErrors(['otp' => 'Too many invalid OTP attempts. Please request a new OTP.'])->withInput($request->except('password', 'password_confirmation'));
        }

        if (! Hash::check($data['otp'], $reset->otp)) {
            DB::table('admin_password_reset_otps')->where('email', $data['email'])->increment('attempts');

            return back()->withErrors(['otp' => 'Invalid OTP.'])->withInput($request->except('password', 'password_confirmation'));
        }

        $admin = Admin::where('email', $data['email'])->firstOrFail();
        $admin->update([
            'password' => $data['password'],
        ]);

        DB::table('admin_password_reset_otps')->where('email', $data['email'])->delete();

        return redirect()->route('admin.login')->with('status', 'Password reset successfully. You can now login.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
