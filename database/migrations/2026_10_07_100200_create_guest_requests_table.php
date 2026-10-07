<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('request_number', 32)->unique();
            $table->foreignId('guest_id')->constrained()->restrictOnDelete();
            $table->foreignId('reservation_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->string('category', 40)->index();
            $table->text('description');
            $table->string('priority', 20)->default('NORMAL')->index();
            $table->string('assigned_department', 30)->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('OPEN')->index();
            $table->timestamp('requested_at');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'status']);
            $table->index(['reservation_id', 'status']);
            $table->index(['requested_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_requests');
    }
};
