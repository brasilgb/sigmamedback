<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('medication_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('medication_id')->constrained('medications')->cascadeOnDelete();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->timestamp('taken_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'uuid']);
            $table->index(['tenant_id', 'taken_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_logs');
    }
};
