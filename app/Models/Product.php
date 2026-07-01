<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'purchase_price',
        'sale_price',
        'stock_quantity',
        'last_purchase_price',
        'last_sale_price',
        'last_stock_quantity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'last_purchase_price' => 'decimal:2',
            'last_sale_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'last_stock_quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Shop, $this>
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<ProductStockLog, $this>
     */
    public function stockLogs(): HasMany
    {
        return $this->hasMany(ProductStockLog::class);
    }
}
