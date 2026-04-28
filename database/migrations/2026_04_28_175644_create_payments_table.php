<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('external_id')->nullable()->index(); // Mercado Pago ID
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('BRL');
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            $table->string('payment_method')->default('pix');
            $table->string('plan_type'); // personal, family
            $table->text('qr_code')->nullable();
            $table->text('qr_code_base64')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
