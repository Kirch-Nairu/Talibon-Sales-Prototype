<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('correspondence_records', function (Blueprint $table): void {
            $table->foreignId('routed_by_user_id')->nullable()->after('classified_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('routed_at')->nullable()->after('routed_by_user_id');
            $table->foreignId('action_started_by_user_id')->nullable()->after('routed_at')->constrained('users')->restrictOnDelete();
            $table->timestampTz('action_started_at')->nullable()->after('action_started_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('correspondence_records', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('action_started_by_user_id');
            $table->dropColumn('action_started_at');
            $table->dropConstrainedForeignId('routed_by_user_id');
            $table->dropColumn('routed_at');
        });
    }
};
