<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('mfa_secret')->nullable();
            $table->timestampTz('mfa_confirmed_at')->nullable();
            $table->json('mfa_recovery_codes')->nullable();
            $table->timestampTz('mfa_recovery_codes_generated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'mfa_secret',
                'mfa_confirmed_at',
                'mfa_recovery_codes',
                'mfa_recovery_codes_generated_at',
            ]);
        });
    }
};
