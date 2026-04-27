<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('blood_pressure_readings', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('glicose_readings', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('weight_readings', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('medications', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('medication_logs', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('blood_pressure_readings', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('glicose_readings', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('weight_readings', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('medications', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('medication_logs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
