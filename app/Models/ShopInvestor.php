<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopInvestor extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'user_id',
        'name',
        'email',
        'payout_type',
        'payout_value',
        'status',
        'notes',
    ];

    /**
     * @return BelongsTo<Shop, $this>
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ShopInvestorDailyEarning, $this>
     */
    public function dailyEarnings(): HasMany
    {
        return $this->hasMany(ShopInvestorDailyEarning::class);
    }
}
