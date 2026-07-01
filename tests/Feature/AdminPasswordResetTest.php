<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reset_password_with_otp(): void
    {
        Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'old-password',
        ]);

        $forgot = $this->post('/admin/forgot-password', [
            'email' => 'admin@example.com',
        ]);

        $forgot->assertRedirect(route('admin.password.reset'));
        $otp = session('otp');

        $this->post('/admin/reset-password', [
            'email' => 'admin@example.com',
            'otp' => $otp,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('admin.login'));

        $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'new-password',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_reset_otp_attempts_are_limited(): void
    {
        Admin::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $this->post('/admin/forgot-password', [
            'email' => 'admin@example.com',
        ])->assertRedirect(route('admin.password.reset'));

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/reset-password', [
                'email' => 'admin@example.com',
                'otp' => '111111',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertSessionHasErrors('otp');
        }

        $this->post('/admin/reset-password', [
            'email' => 'admin@example.com',
            'otp' => '111111',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('otp');

        $this->assertSame(5, DB::table('admin_password_reset_otps')->where('email', 'admin@example.com')->value('attempts'));
    }
}
