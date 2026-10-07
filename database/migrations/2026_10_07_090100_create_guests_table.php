<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table): void {
            $table->id();
            $table->string('guest_code', 24)->unique();
            $table->string('full_name', 160)->index();
            $table->string('gender', 20)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('phone_normalized', 40)->nullable()->index();
            $table->string('email')->nullable();
            $table->string('email_normalized')->nullable()->index();
            $table->string('identity_type', 30)->nullable();
            $table->string('identity_number', 100)->nullable();
            $table->string('identity_number_normalized', 100)->nullable()->index();
            $table->string('nationality', 80)->nullable();
            $table->text('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_blacklisted')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
