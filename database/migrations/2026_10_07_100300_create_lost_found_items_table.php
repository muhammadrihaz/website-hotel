<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lost_found_items', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('item_name', 160);
            $table->text('description')->nullable();
            $table->string('found_location', 160);
            $table->date('found_date')->index();
            $table->foreignId('found_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('photo')->nullable();
            $table->string('storage_location', 160)->nullable();
            $table->string('status', 20)->default('FOUND')->index();
            $table->text('notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'found_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_found_items');
    }
};
