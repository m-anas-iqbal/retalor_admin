<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (! config('retalors.demo_data_seed')) {
            return;
        }

        DB::transaction(function (): void {
            $admin = Admin::updateOrCreate(
                ['email' => 'demo-admin@retalors.test'],
                [
                    'name' => 'Demo Admin',
                    'password' => 'password',
                ],
            );

            $owner = User::updateOrCreate(
                ['email' => 'demo-owner@retalors.test'],
                [
                    'name' => 'Demo Owner',
                    'password' => 'password',
                ],
            );

            $staff = User::updateOrCreate(
                ['email' => 'demo-staff@retalors.test'],
                [
                    'name' => 'Demo Staff',
                    'password' => 'password',
                ],
            );

            $plan = Plan::updateOrCreate(
                ['slug' => 'demo-pro'],
                [
                    'name' => 'Demo Pro',
                    'description' => 'Demo plan for testing all shop modules.',
                    'price' => 2500,
                    'duration_days' => 30,
                    'max_shops' => 3,
                    'max_users' => 10,
                    'is_active' => true,
                ],
            );

            $shop = Shop::updateOrCreate(
                ['slug' => 'demo-retalors-shop'],
                [
                    'owner_user_id' => $owner->id,
                    'name' => 'Demo Retalors Shop',
                    'email' => 'shop@retalors.test',
                    'phone' => '03001234567',
                    'address' => 'Main Market',
                    'city' => 'Lahore',
                    'status' => 'active',
                ],
            );

            $shop->users()->syncWithoutDetaching([
                $owner->id => ['role' => 'owner', 'status' => 'active'],
                $staff->id => ['role' => 'staff', 'status' => 'active'],
            ]);

            $shop->users()->updateExistingPivot($owner->id, ['role' => 'owner', 'status' => 'active']);
            $shop->users()->updateExistingPivot($staff->id, ['role' => 'staff', 'status' => 'active']);

            $subscription = Subscription::updateOrCreate(
                ['shop_id' => $shop->id, 'plan_id' => $plan->id],
                [
                    'status' => 'active',
                    'price' => $plan->price,
                    'starts_at' => now()->subDays(5)->toDateString(),
                    'ends_at' => now()->addDays(25)->toDateString(),
                    'subscribed_at' => now()->subDays(5),
                    'notes' => 'Demo subscription seeded for testing.',
                ],
            );

            $category = Category::updateOrCreate(
                ['shop_id' => $shop->id, 'slug' => 'beverages'],
                [
                    'name' => 'Beverages',
                    'status' => 'active',
                ],
            );

            Product::updateOrCreate(
                ['shop_id' => $shop->id, 'slug' => 'demo-water-bottle'],
                [
                    'category_id' => $category->id,
                    'name' => 'Demo Water Bottle',
                    'sku' => 'WATER-001',
                    'description' => 'Demo product for inventory testing.',
                    'purchase_price' => 30,
                    'sale_price' => 45,
                    'stock_quantity' => 120,
                    'last_purchase_price' => 28,
                    'last_sale_price' => 40,
                    'last_stock_quantity' => 100,
                    'status' => 'active',
                ],
            );

            $expenseType = ExpenseType::updateOrCreate(
                ['shop_id' => $shop->id, 'slug' => 'utilities'],
                [
                    'name' => 'Utilities',
                    'status' => 'active',
                ],
            );

            Expense::updateOrCreate(
                [
                    'shop_id' => $shop->id,
                    'expense_type_id' => $expenseType->id,
                    'expense_date' => today()->toDateString(),
                    'amount' => 1200,
                    'description' => 'Demo electricity bill',
                ],
                [
                    'user_id' => $owner->id,
                ],
            );

            Sale::updateOrCreate(
                [
                    'shop_id' => $shop->id,
                    'sale_date' => today()->toDateString(),
                    'start_time' => '09:00:00',
                    'end_time' => '10:00:00',
                    'sales' => 6500,
                ],
                [
                    'user_id' => $owner->id,
                ],
            );

            Sale::updateOrCreate(
                [
                    'shop_id' => $shop->id,
                    'sale_date' => today()->toDateString(),
                    'start_time' => '10:00:00',
                    'end_time' => '11:00:00',
                    'sales' => 4300,
                ],
                [
                    'user_id' => $staff->id,
                ],
            );

            $this->command?->info(sprintf(
                'Demo data seeded for %s, %s and shop %s.',
                $admin->email,
                $owner->email,
                $shop->slug,
            ));
        });
    }
}
