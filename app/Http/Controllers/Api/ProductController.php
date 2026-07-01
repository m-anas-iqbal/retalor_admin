<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ImportProductsRequest;
use App\Http\Requests\Api\StoreProductRequest;
use App\Http\Requests\Api\UpdateProductRequest;
use App\Http\Requests\Api\UpdateProductStockRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * @var list<string>
     */
    private const IMPORT_COLUMNS = [
        'category',
        'name',
        'sku',
        'description',
        'purchase_price',
        'sale_price',
        'stock_quantity',
        'status',
        'note',
    ];

    public function index(Request $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        return ApiResponse::success('Products fetched.', [
            'products' => $shop->products()->with('category')->latest()->paginate(20),
        ]);
    }

    public function store(StoreProductRequest $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        $data = $request->validated();

        if (! empty($data['category_id']) && ! $shop->categories()->whereKey($data['category_id'])->exists()) {
            return ApiResponse::error('Category does not belong to this shop.', 422);
        }

        $slug = $data['slug'] ?? $this->uniqueSlug($shop, $data['name']);

        if ($shop->products()->where('slug', $slug)->exists()) {
            return ApiResponse::error('Product slug already exists for this shop.', 422);
        }

        if (! empty($data['sku']) && $shop->products()->where('sku', $data['sku'])->exists()) {
            return ApiResponse::error('Product SKU already exists for this shop.', 422);
        }

        $product = DB::transaction(function () use ($request, $shop, $data, $slug): Product {
            $product = $shop->products()->create([
                'category_id' => $data['category_id'] ?? null,
                'name' => $data['name'],
                'slug' => $slug,
                'sku' => $data['sku'] ?? null,
                'description' => $data['description'] ?? null,
                'purchase_price' => $data['purchase_price'],
                'sale_price' => $data['sale_price'],
                'stock_quantity' => $data['stock_quantity'],
                'last_purchase_price' => null,
                'last_sale_price' => null,
                'last_stock_quantity' => null,
                'status' => $data['status'] ?? 'active',
            ]);

            $this->logStockChange($request, $shop, $product, [
                'previous_stock_quantity' => 0,
                'new_stock_quantity' => $product->stock_quantity,
                'previous_purchase_price' => null,
                'new_purchase_price' => $product->purchase_price,
                'previous_sale_price' => null,
                'new_sale_price' => $product->sale_price,
                'type' => 'create',
                'note' => $data['note'] ?? 'Initial product stock',
            ]);

            return $product;
        });

        return ApiResponse::success('Product created.', [
            'product' => $product->load(['category', 'stockLogs' => fn ($query) => $query->latest('id')->take(5)]),
        ], 201);
    }

    public function show(Request $request, Shop $shop, Product $product): JsonResponse
    {
        if (! $this->canAccessProduct($request, $shop, $product)) {
            return ApiResponse::error('You cannot access this product.', 403);
        }

        return ApiResponse::success('Product details fetched.', [
            'product' => $product->load('category'),
            'stock_logs' => $product->stockLogs()->with('user:id,name,email')->latest('id')->paginate(20),
        ]);
    }

    public function update(UpdateProductRequest $request, Shop $shop, Product $product): JsonResponse
    {
        if (! $this->canAccessProduct($request, $shop, $product)) {
            return ApiResponse::error('You cannot access this product.', 403);
        }

        $data = $request->validated();

        if (! empty($data['category_id']) && ! $shop->categories()->whereKey($data['category_id'])->exists()) {
            return ApiResponse::error('Category does not belong to this shop.', 422);
        }

        $slug = $data['slug'] ?? $product->slug;

        if ($shop->products()->where('slug', $slug)->whereKeyNot($product->id)->exists()) {
            return ApiResponse::error('Product slug already exists for this shop.', 422);
        }

        if (! empty($data['sku']) && $shop->products()->where('sku', $data['sku'])->whereKeyNot($product->id)->exists()) {
            return ApiResponse::error('Product SKU already exists for this shop.', 422);
        }

        $product->update([
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'slug' => $slug,
            'sku' => $data['sku'] ?? null,
            'description' => $data['description'] ?? null,
            'purchase_price' => $data['purchase_price'],
            'sale_price' => $data['sale_price'],
            'status' => $data['status'] ?? $product->status,
        ]);

        return ApiResponse::success('Product updated.', [
            'product' => $product->fresh()->load('category'),
        ]);
    }
    public function updateStock(UpdateProductStockRequest $request, Shop $shop, Product $product): JsonResponse
    {
        if (! $this->canAccessProduct($request, $shop, $product)) {
            return ApiResponse::error('You cannot access this product.', 403);
        }

        $data = $request->validated();

        DB::transaction(function () use ($request, $shop, $product, $data): void {
            $previousStock = $product->stock_quantity;
            $previousPurchasePrice = $product->purchase_price;
            $previousSalePrice = $product->sale_price;

            $product->update([
                'last_stock_quantity' => $previousStock,
                'last_purchase_price' => $previousPurchasePrice,
                'last_sale_price' => $previousSalePrice,
                'stock_quantity' => $data['stock_quantity'],
                'purchase_price' => $data['purchase_price'],
                'sale_price' => $data['sale_price'],
            ]);

            $this->logStockChange($request, $shop, $product->fresh(), [
                'previous_stock_quantity' => $previousStock,
                'new_stock_quantity' => $data['stock_quantity'],
                'previous_purchase_price' => $previousPurchasePrice,
                'new_purchase_price' => $data['purchase_price'],
                'previous_sale_price' => $previousSalePrice,
                'new_sale_price' => $data['sale_price'],
                'type' => 'stock_update',
                'note' => $data['note'] ?? null,
            ]);
        });

        return ApiResponse::success('Product stock updated.', [
            'product' => $product->fresh()->load('category'),
            'latest_log' => $product->stockLogs()->latest('id')->first(),
        ]);
    }

    public function report(Request $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        $products = $shop->products();

        return ApiResponse::success('Product report fetched.', [
            'summary' => [
                'total_products' => (clone $products)->count(),
                'total_stock_quantity' => (int) (clone $products)->sum('stock_quantity'),
                'total_purchase_value' => (float) (clone $products)->selectRaw('COALESCE(SUM(stock_quantity * purchase_price), 0) as total')->value('total'),
                'total_sale_value' => (float) (clone $products)->selectRaw('COALESCE(SUM(stock_quantity * sale_price), 0) as total')->value('total'),
                'low_stock_products' => (clone $products)->where('stock_quantity', '<=', 5)->count(),
            ],
            'products' => $shop->products()->with('category')->orderBy('stock_quantity')->paginate(20),
        ]);
    }

    public function export(Request $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        $rows = [$this->csvHeader()];

        foreach ($shop->products()->with('category')->orderBy('name')->get() as $product) {
            $rows[] = [
                $product->category?->name ?? '',
                $product->name,
                $product->sku ?? '',
                $product->description ?? '',
                (string) $product->purchase_price,
                (string) $product->sale_price,
                (string) $product->stock_quantity,
                $product->status,
                '',
            ];
        }

        return ApiResponse::success('Products exported.', [
            'file_name' => sprintf('%s-products-%s.csv', $shop->slug, now()->format('YmdHis')),
            'csv_content' => $this->buildCsv($rows),
            'total_rows' => max(count($rows) - 1, 0),
        ]);
    }

    public function exampleCsv(Request $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        return ApiResponse::success('Example CSV fetched.', [
            'file_name' => 'products-import-example.csv',
            'csv_content' => $this->buildCsv([
                $this->csvHeader(),
                ['Beverages', 'Milk Pack', 'MILK-001', 'Family pack', '120', '150', '10', 'active', 'Opening stock'],
                ['Snacks', 'Potato Chips', 'CHIPS-101', 'Salted chips', '60', '90', '25', 'active', 'Imported opening stock'],
            ]),
        ]);
    }

    public function import(ImportProductsRequest $request, Shop $shop): JsonResponse
    {
        if (! $this->canAccessShop($request, $shop)) {
            return ApiResponse::error('You cannot access this shop.', 403);
        }

        $file = $request->file('csv_file');

        if ($file === null || ! $file->isValid()) {
            return ApiResponse::error('Valid CSV file is required.', 422);
        }

        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            return ApiResponse::error('Unable to read CSV file.', 422);
        }

        $header = fgetcsv($handle) ?: [];
        $normalizedHeader = array_map(fn ($value) => Str::of((string) $value)->trim()->lower()->value(), $header);

        if ($normalizedHeader !== self::IMPORT_COLUMNS) {
            fclose($handle);

            return ApiResponse::error('CSV header is invalid. Download the example CSV and follow the same columns.', 422);
        }

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $rowData = array_combine(self::IMPORT_COLUMNS, array_pad($row, count(self::IMPORT_COLUMNS), ''));

            if ($rowData === false) {
                $failed++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => 'Unable to map CSV row.',
                ];
                continue;
            }

            try {
                $result = DB::transaction(function () use ($request, $shop, $rowData) {
                    return $this->importRow($request, $shop, $rowData);
                });

                if ($result === 'created') {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (ValidationException $exception) {
                $failed++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => $exception->validator->errors()->first(),
                ];
            } catch (\Throwable $exception) {
                $failed++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => 'Import failed for this row.',
                ];
            }
        }

        fclose($handle);

        return ApiResponse::success('Products imported.', [
            'created_count' => $created,
            'updated_count' => $updated,
            'failed_count' => $failed,
            'errors' => $errors,
        ]);
    }

    private function canAccessShop(Request $request, Shop $shop): bool
    {
        return $shop->users()->whereKey($request->user()->id)->exists();
    }

    private function canAccessProduct(Request $request, Shop $shop, Product $product): bool
    {
        return $product->shop_id === $shop->id && $this->canAccessShop($request, $shop);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function logStockChange(Request $request, Shop $shop, Product $product, array $data): void
    {
        $product->stockLogs()->create([
            'shop_id' => $shop->id,
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'previous_stock_quantity' => $data['previous_stock_quantity'],
            'new_stock_quantity' => $data['new_stock_quantity'],
            'quantity_delta' => $data['new_stock_quantity'] - $data['previous_stock_quantity'],
            'previous_purchase_price' => $data['previous_purchase_price'],
            'new_purchase_price' => $data['new_purchase_price'],
            'previous_sale_price' => $data['previous_sale_price'],
            'new_sale_price' => $data['new_sale_price'],
            'note' => $data['note'],
        ]);
    }

    private function uniqueSlug(Shop $shop, string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $counter = 2;

        while ($shop->products()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function uniqueCategorySlug(Shop $shop, string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $counter = 2;

        while ($shop->categories()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @return list<string>
     */
    private function csvHeader(): array
    {
        return self::IMPORT_COLUMNS;
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function buildCsv(array $rows): string
    {
        $stream = fopen('php://temp', 'rb+');

        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);
        $content = stream_get_contents($stream) ?: '';
        fclose($stream);

        return $content;
    }

    /**
     * @param  list<string>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function importRow(Request $request, Shop $shop, array $row): string
    {
        $payload = [
            'category' => trim($row['category']),
            'name' => trim($row['name']),
            'sku' => trim($row['sku']),
            'description' => trim($row['description']),
            'purchase_price' => trim($row['purchase_price']),
            'sale_price' => trim($row['sale_price']),
            'stock_quantity' => trim($row['stock_quantity']),
            'status' => trim($row['status']) !== '' ? trim($row['status']) : 'active',
            'note' => trim($row['note']) !== '' ? trim($row['note']) : 'Imported stock update',
        ];

        $validator = Validator::make($payload, [
            'category' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $category = $this->resolveImportCategory($shop, $payload['category']);
        $product = $this->resolveImportProduct($shop, $payload['name'], $payload['sku']);

        if ($product !== null) {
            $previousStock = $product->stock_quantity;
            $previousPurchasePrice = $product->purchase_price;
            $previousSalePrice = $product->sale_price;

            $product->update([
                'category_id' => $category?->id,
                'name' => $payload['name'],
                'sku' => $payload['sku'] !== '' ? $payload['sku'] : null,
                'description' => $payload['description'] !== '' ? $payload['description'] : null,
                'status' => $payload['status'],
                'last_stock_quantity' => $previousStock,
                'last_purchase_price' => $previousPurchasePrice,
                'last_sale_price' => $previousSalePrice,
                'stock_quantity' => (int) $payload['stock_quantity'],
                'purchase_price' => (float) $payload['purchase_price'],
                'sale_price' => (float) $payload['sale_price'],
            ]);

            $this->logStockChange($request, $shop, $product->fresh(), [
                'previous_stock_quantity' => $previousStock,
                'new_stock_quantity' => (int) $payload['stock_quantity'],
                'previous_purchase_price' => $previousPurchasePrice,
                'new_purchase_price' => (float) $payload['purchase_price'],
                'previous_sale_price' => $previousSalePrice,
                'new_sale_price' => (float) $payload['sale_price'],
                'type' => 'import_update',
                'note' => $payload['note'],
            ]);

            return 'updated';
        }

        $product = $shop->products()->create([
            'category_id' => $category?->id,
            'name' => $payload['name'],
            'slug' => $this->uniqueSlug($shop, $payload['name']),
            'sku' => $payload['sku'] !== '' ? $payload['sku'] : null,
            'description' => $payload['description'] !== '' ? $payload['description'] : null,
            'purchase_price' => (float) $payload['purchase_price'],
            'sale_price' => (float) $payload['sale_price'],
            'stock_quantity' => (int) $payload['stock_quantity'],
            'status' => $payload['status'],
        ]);

        $this->logStockChange($request, $shop, $product, [
            'previous_stock_quantity' => 0,
            'new_stock_quantity' => (int) $payload['stock_quantity'],
            'previous_purchase_price' => null,
            'new_purchase_price' => (float) $payload['purchase_price'],
            'previous_sale_price' => null,
            'new_sale_price' => (float) $payload['sale_price'],
            'type' => 'import_create',
            'note' => $payload['note'],
        ]);

        return 'created';
    }

    private function resolveImportCategory(Shop $shop, string $categoryName): ?Category
    {
        if ($categoryName === '') {
            return null;
        }

        $category = $shop->categories()->whereRaw('LOWER(name) = ?', [Str::lower($categoryName)])->first();

        if ($category !== null) {
            return $category;
        }

        return $shop->categories()->create([
            'name' => $categoryName,
            'slug' => $this->uniqueCategorySlug($shop, $categoryName),
            'status' => 'active',
        ]);
    }

    private function resolveImportProduct(Shop $shop, string $name, string $sku): ?Product
    {
        if ($sku !== '') {
            $product = $shop->products()->where('sku', $sku)->first();

            if ($product !== null) {
                return $product;
            }
        }

        return $shop->products()->whereRaw('LOWER(name) = ?', [Str::lower($name)])->first();
    }
}
