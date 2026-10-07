<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_handovers', function (Blueprint $table): void {
            $table->id();
            $table->date('shift_date')->index();
            $table->string('shift_type', 20)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('handover_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('DRAFT')->index();
            $table->timestamp('handed_over_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index(['shift_date', 'shift_type', 'status'], 'shift_handover_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_handovers');
    }
};
