<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->renameColumn('deposit_amount', 'paid_amount');
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->decimal('security_deposit_amount', 15, 2)->default(0)->after('paid_amount');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn('security_deposit_amount');
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->renameColumn('paid_amount', 'deposit_amount');
        });
    }
};
