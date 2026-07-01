<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStockLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'product_id',
        'user_id',
        'type',
        'previous_stock_quantity',
        'new_stock_quantity',
        'quantity_delta',
        'previous_purchase_price',
        'new_purchase_price',
        'previous_sale_price',
        'new_sale_price',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'previous_stock_quantity' => 'integer',
            'new_stock_quantity' => 'integer',
            'quantity_delta' => 'integer',
            'previous_purchase_price' => 'decimal:2',
            'new_purchase_price' => 'decimal:2',
            'previous_sale_price' => 'decimal:2',
            'new_sale_price' => 'decimal:2',
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
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
