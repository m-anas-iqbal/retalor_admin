<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table): void {
            if (! Schema::hasColumn('password_reset_tokens', 'attempts')) {
                $table->unsignedTinyInteger('attempts')->default(0)->after('token');
            }

            if (! Schema::hasColumn('password_reset_tokens', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('created_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table): void {
            if (Schema::hasColumn('password_reset_tokens', 'expires_at')) {
                $table->dropColumn('expires_at');
            }

            if (Schema::hasColumn('password_reset_tokens', 'attempts')) {
                $table->dropColumn('attempts');
            }
        });
    }
};
