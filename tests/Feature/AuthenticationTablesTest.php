<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthenticationTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_and_api_users_use_separate_tables(): void
    {
        $this->assertTrue(Schema::hasTable('admins'));
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('api_tokens'));
        $this->assertFalse(Schema::hasColumn('users', 'is_admin'));
    }
}
