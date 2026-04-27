<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weight_readings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->decimal('weight', 8, 2);
            $table->decimal('height', 6, 2)->nullable();
            $table->string('unit')->default('kg');
            $table->timestamp('measured_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'uuid']);
            $table->index(['tenant_id', 'measured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weight_readings');
    }
};
