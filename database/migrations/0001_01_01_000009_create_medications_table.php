<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->string('name');
            $table->string('dosage')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('scheduled_time')->nullable();
            $table->boolean('reminder_enabled')->default(false);
            $table->boolean('repeat_reminder_every_five_minutes')->default(false);
            $table->unsignedSmallInteger('reminder_minutes_before')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'uuid']);
            $table->index(['tenant_id', 'profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
