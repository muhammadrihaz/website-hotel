<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number', 20)->unique();
            $table->unsignedSmallInteger('floor')->index();
            $table->foreignId('room_type_id')->constrained()->restrictOnDelete();
            $table->string('occupancy_status', 20)->default('VACANT')->index();
            $table->string('housekeeping_status', 20)->default('READY')->index();
            $table->string('operational_status', 20)->default('AVAILABLE')->index();
            $table->decimal('base_rate', 15, 2)->default(0);
            $table->unsignedTinyInteger('capacity_adult')->default(2);
            $table->unsignedTinyInteger('capacity_child')->default(0);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['room_type_id', 'is_active']);
            $table->index(['occupancy_status', 'housekeeping_status', 'operational_status'], 'rooms_board_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
