<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_investor_daily_earnings', function (Blueprint $table): void {
            $table->decimal('operating_expenses', 12, 2)->default(0)->after('total_sales');
            $table->decimal('investor_expenses', 12, 2)->default(0)->after('operating_expenses');
            $table->decimal('profit_before_investor_payout', 12, 2)->default(0)->after('investor_expenses');
        });
    }

    public function down(): void
    {
        Schema::table('shop_investor_daily_earnings', function (Blueprint $table): void {
            $table->dropColumn(['operating_expenses', 'investor_expenses', 'profit_before_investor_payout']);
        });
    }
};
