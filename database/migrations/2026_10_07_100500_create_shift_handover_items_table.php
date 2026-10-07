<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_handover_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shift_handover_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('category', 40)->index();
            $table->text('description');
            $table->string('priority', 20)->default('NORMAL')->index();
            $table->string('status', 20)->default('OPEN')->index();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['shift_handover_id', 'status'], 'shift_handover_item_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_handover_items');
    }
};
