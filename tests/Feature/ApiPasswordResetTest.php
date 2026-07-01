<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_get_bearer_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'device_name' => 'mobile',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data' => ['token_type', 'access_token', 'user']]);

        $this->assertDatabaseHas(User::class, [
            'email' => 'new@example.com',
        ]);
    }

    public function test_user_can_reset_password_with_generated_otp(): void
    {
        User::factory()->create([
            'email' => 'reset@example.com',
            'password' => 'old-password',
        ]);

        $forgot = $this->postJson('/api/forgot-password', [
            'email' => 'reset@example.com',
        ]);

        $forgot->assertOk()->assertJsonStructure(['success', 'message', 'data' => ['expires_in_minutes', 'otp']]);

        $this->postJson('/api/reset-password', [
            'email' => 'reset@example.com',
            'otp' => $forgot->json('data.otp'),
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk()->assertJsonPath('message', 'Password reset successfully.');

        $this->postJson('/api/login', [
            'email' => 'reset@example.com',
            'password' => 'new-password',
        ])->assertOk()->assertJsonStructure(['data' => ['access_token']]);
    }

    public function test_password_reset_otp_expires(): void
    {
        User::factory()->create([
            'email' => 'expired@example.com',
        ]);

        $forgot = $this->postJson('/api/forgot-password', [
            'email' => 'expired@example.com',
        ]);

        $this->travel(11)->minutes();

        $this->postJson('/api/reset-password', [
            'email' => 'expired@example.com',
            'otp' => $forgot->json('data.otp'),
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertStatus(422)->assertJsonPath('message', 'OTP expired.');
    }

    public function test_password_reset_otp_has_attempt_limit(): void
    {
        User::factory()->create([
            'email' => 'attempts@example.com',
        ]);

        $this->postJson('/api/forgot-password', [
            'email' => 'attempts@example.com',
        ])->assertOk();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/reset-password', [
                'email' => 'attempts@example.com',
                'otp' => '111111',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/reset-password', [
            'email' => 'attempts@example.com',
            'otp' => '111111',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertStatus(429);

        $this->assertSame(5, DB::table('password_reset_tokens')->where('email', 'attempts@example.com')->value('attempts'));
    }

    public function test_forgot_password_has_resend_cooldown(): void
    {
        User::factory()->create([
            'email' => 'cooldown@example.com',
        ]);

        $this->postJson('/api/forgot-password', [
            'email' => 'cooldown@example.com',
        ])->assertOk();

        $this->postJson('/api/forgot-password', [
            'email' => 'cooldown@example.com',
        ])->assertStatus(429)->assertJsonStructure(['success', 'message', 'errors' => ['retry_after_seconds']]);
    }

    public function test_authenticated_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'email' => 'change@example.com',
            'password' => 'old-password',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'change@example.com',
            'password' => 'old-password',
        ]);

        $this->withToken($login->json('data.access_token'))
            ->postJson('/api/change-password', [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Password changed successfully.');

        $this->assertSame(1, ApiToken::where('user_id', $user->id)->count());

        $this->postJson('/api/login', [
            'email' => 'change@example.com',
            'password' => 'new-password',
        ])->assertOk();
    }
}
