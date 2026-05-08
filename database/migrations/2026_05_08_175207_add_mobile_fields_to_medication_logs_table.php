<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('medication_logs', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->after('profile_id');
            $table->string('status', 20)->default('taken')->after('taken_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medication_logs', function (Blueprint $table) {
            $table->dropColumn(['scheduled_at', 'status']);
        });
    }
};
