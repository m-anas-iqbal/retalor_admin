<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_investor_daily_earnings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_investor_id')->constrained('shop_investors')->cascadeOnDelete();
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('report_date');
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->decimal('total_expenses', 12, 2)->default(0);
            $table->decimal('net_profit', 12, 2)->default(0);
            $table->string('payout_type');
            $table->decimal('payout_value', 12, 2)->default(0);
            $table->decimal('payout_amount', 12, 2)->default(0);
            $table->string('status')->default('generated');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['shop_investor_id', 'report_date']);
            $table->index(['shop_id', 'report_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_investor_daily_earnings');
    }
};
