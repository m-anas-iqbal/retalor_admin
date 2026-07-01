<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopInvestorDailyEarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'shop_investor_id',
        'generated_by_user_id',
        'report_date',
        'total_sales',
        'operating_expenses',
        'investor_expenses',
        'profit_before_investor_payout',
        'total_expenses',
        'net_profit',
        'payout_type',
        'payout_value',
        'payout_amount',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'total_sales' => 'decimal:2',
            'operating_expenses' => 'decimal:2',
            'investor_expenses' => 'decimal:2',
            'profit_before_investor_payout' => 'decimal:2',
            'total_expenses' => 'decimal:2',
            'net_profit' => 'decimal:2',
            'payout_value' => 'decimal:2',
            'payout_amount' => 'decimal:2',
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
     * @return BelongsTo<ShopInvestor, $this>
     */
    public function investor(): BelongsTo
    {
        return $this->belongsTo(ShopInvestor::class, 'shop_investor_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }
}
