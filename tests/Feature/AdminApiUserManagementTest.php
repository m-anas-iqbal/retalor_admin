<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_api_user_from_admin_panel(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post('/admin/api-users', [
                'name' => 'API Customer',
                'email' => 'customer@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('admin.api-users.index'));

        $this->assertDatabaseHas(User::class, [
            'name' => 'API Customer',
            'email' => 'customer@example.com',
        ]);
    }
}
