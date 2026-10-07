<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housekeeping_checklist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('housekeeping_task_id')->constrained()->cascadeOnDelete();
            $table->string('item_key', 50);
            $table->string('label', 100);
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_completed')->default(false);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['housekeeping_task_id', 'item_key'], 'housekeeping_task_item_unique');
            $table->index(['housekeeping_task_id', 'is_completed'], 'housekeeping_task_completion_index');
        });

        $items = [
            'bedsheet' => 'Bedsheet',
            'pillow_case' => 'Pillow Case',
            'bath_towel' => 'Bath Towel',
            'mineral_water' => 'Mineral Water',
            'soap' => 'Soap',
            'shampoo' => 'Shampoo',
            'tissue' => 'Tissue',
            'bathroom' => 'Bathroom',
            'floor' => 'Floor',
            'tv' => 'TV',
            'ac' => 'AC',
            'shower' => 'Shower',
            'trash_bin' => 'Trash Bin',
        ];

        $now = now();
        foreach (DB::table('housekeeping_tasks')->pluck('id') as $taskId) {
            foreach ($items as $key => $label) {
                DB::table('housekeeping_checklist_items')->insert([
                    'housekeeping_task_id' => $taskId,
                    'item_key' => $key,
                    'label' => $label,
                    'is_mandatory' => true,
                    'is_completed' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('housekeeping_checklist_items');
    }
};
