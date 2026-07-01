<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Expense;
use App\Models\Plan;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_seeder_skips_when_disabled(): void
    {
        config(['retalors.demo_data_seed' => false]);

        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseMissing('plans', ['slug' => 'demo-pro']);
        $this->assertDatabaseMissing('shops', ['slug' => 'demo-retalors-shop']);
    }

    public function test_demo_data_seeder_populates_testing_dataset_when_enabled(): void
    {
        config(['retalors.demo_data_seed' => true]);

        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseHas('admins', ['email' => 'demo-admin@retalors.test']);
        $this->assertDatabaseHas('users', ['email' => 'demo-owner@retalors.test']);
        $this->assertDatabaseHas('plans', ['slug' => 'demo-pro']);
        $this->assertDatabaseHas('shops', ['slug' => 'demo-retalors-shop']);
        $this->assertDatabaseHas('subscriptions', ['status' => 'active']);
        $this->assertDatabaseHas('expenses', ['description' => 'Demo electricity bill']);
        $this->assertDatabaseHas('sales', ['start_time' => '09:00:00']);

        $this->assertTrue(Admin::where('email', 'demo-admin@retalors.test')->exists());
        $this->assertTrue(User::where('email', 'demo-owner@retalors.test')->exists());
        $this->assertTrue(Plan::where('slug', 'demo-pro')->exists());
        $this->assertTrue(Shop::where('slug', 'demo-retalors-shop')->exists());
        $this->assertTrue(Subscription::where('status', 'active')->exists());
        $this->assertTrue(Expense::where('description', 'Demo electricity bill')->exists());
        $this->assertTrue(Sale::where('start_time', '09:00:00')->exists());
    }
}
