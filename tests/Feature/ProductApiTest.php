<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_user_can_create_category_product_and_update_stock_with_logs(): void
    {
        [$user, $shop, $token] = $this->shopUserWithToken();

        $category = $this->withToken($token)->postJson("/api/shops/{$shop->id}/categories", [
            'name' => 'Beverages',
        ])->assertCreated()->json('data.category');

        $product = $this->withToken($token)->postJson("/api/shops/{$shop->id}/products", [
            'category_id' => $category['id'],
            'name' => 'Milk Pack',
            'sku' => 'MILK-001',
            'purchase_price' => 120,
            'sale_price' => 150,
            'stock_quantity' => 10,
            'note' => 'Opening stock',
        ])->assertCreated()
            ->assertJsonPath('data.product.stock_quantity', 10)
            ->json('data.product');

        $this->assertDatabaseHas('product_stock_logs', [
            'shop_id' => $shop->id,
            'product_id' => $product['id'],
            'user_id' => $user->id,
            'type' => 'create',
            'previous_stock_quantity' => 0,
            'new_stock_quantity' => 10,
            'quantity_delta' => 10,
        ]);

        $this->withToken($token)->postJson("/api/shops/{$shop->id}/products/{$product['id']}/stock", [
            'purchase_price' => 125,
            'sale_price' => 160,
            'stock_quantity' => 18,
            'note' => 'Purchased new stock',
        ])->assertOk()
            ->assertJsonPath('data.product.stock_quantity', 18)
            ->assertJsonPath('data.product.last_stock_quantity', 10)
            ->assertJsonPath('data.latest_log.quantity_delta', 8);

        $this->assertDatabaseHas('product_stock_logs', [
            'shop_id' => $shop->id,
            'product_id' => $product['id'],
            'type' => 'stock_update',
            'previous_stock_quantity' => 10,
            'new_stock_quantity' => 18,
            'quantity_delta' => 8,
        ]);
    }

    public function test_product_detail_report_returns_summary_and_logs(): void
    {
        [, $shop, $token] = $this->shopUserWithToken();

        $product = $this->withToken($token)->postJson("/api/shops/{$shop->id}/products", [
            'name' => 'Rice Bag',
            'purchase_price' => 1000,
            'sale_price' => 1200,
            'stock_quantity' => 3,
        ])->assertCreated()->json('data.product');

        $this->withToken($token)->getJson("/api/shops/{$shop->id}/products/{$product['id']}")
            ->assertOk()
            ->assertJsonPath('data.product.name', 'Rice Bag')
            ->assertJsonStructure(['data' => ['stock_logs']]);

        $this->withToken($token)->getJson("/api/shops/{$shop->id}/products/report")
            ->assertOk()
            ->assertJsonPath('data.summary.total_products', 1)
            ->assertJsonPath('data.summary.total_stock_quantity', 3)
            ->assertJsonPath('data.summary.low_stock_products', 1);
    }

    public function test_shop_user_can_import_and_export_products_csv(): void
    {
        [$user, $shop, $token] = $this->shopUserWithToken();

        $csv = implode("\n", [
            'category,name,sku,description,purchase_price,sale_price,stock_quantity,status,note',
            'Beverages,Milk Pack,MILK-001,Family pack,120,150,10,active,Opening stock',
            'Snacks,Potato Chips,CHIPS-101,Salted chips,60,90,25,active,Imported row',
        ]);

        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

        $this->withToken($token)->post("/api/shops/{$shop->id}/products/import", [
            'csv_file' => $file,
        ], [
            'Accept' => 'application/json',
        ])->assertOk()
            ->assertJsonPath('data.created_count', 2)
            ->assertJsonPath('data.updated_count', 0)
            ->assertJsonPath('data.failed_count', 0);

        $this->assertDatabaseHas('categories', [
            'shop_id' => $shop->id,
            'name' => 'Beverages',
        ]);

        $this->assertDatabaseHas('products', [
            'shop_id' => $shop->id,
            'sku' => 'MILK-001',
            'stock_quantity' => 10,
        ]);

        $this->assertDatabaseHas('product_stock_logs', [
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'type' => 'import_create',
            'new_stock_quantity' => 10,
        ]);

        $export = $this->withToken($token)->getJson("/api/shops/{$shop->id}/products/export")
            ->assertOk()
            ->assertJsonPath('data.total_rows', 2)
            ->json('data.csv_content');

        $this->assertStringContainsString('category,name,sku,description,purchase_price,sale_price,stock_quantity,status,note', $export);
        $this->assertStringContainsString('Milk Pack', $export);

        $example = $this->withToken($token)->getJson("/api/shops/{$shop->id}/products/example-csv")
            ->assertOk()
            ->json('data.csv_content');

        $this->assertStringContainsString('products-import-example.csv', $this->withToken($token)->getJson("/api/shops/{$shop->id}/products/example-csv")->json('data.file_name'));
        $this->assertStringContainsString('Potato Chips', $example);
    }

    /**
     * @return array{0: User, 1: Shop, 2: string}
     */
    private function shopUserWithToken(): array
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $shop = Shop::create([
            'owner_user_id' => $user->id,
            'name' => 'Test Shop',
            'slug' => 'test-shop',
        ]);

        $shop->users()->attach($user->id, ['role' => 'owner', 'status' => 'active']);

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        return [$user, $shop, $login->json('data.access_token')];
    }
}
