<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('legislative_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('session_code', 80)->unique();
            $table->string('session_type', 40)->index();
            $table->string('title');
            $table->timestampTz('scheduled_at')->index();
            $table->string('location')->nullable();
            $table->string('status', 32)->default('scheduled')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
        });

        Schema::create('legislative_agenda_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legislative_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence_no');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('legislative_record_id')->nullable()->constrained('legislative_records')->nullOnDelete();
            $table->string('status', 32)->default('pending')->index();
            $table->string('disposition', 120)->nullable();
            $table->timestampsTz();
            $table->unique(['legislative_session_id', 'sequence_no'], 'legislative_agenda_sequence_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legislative_agenda_items');
        Schema::dropIfExists('legislative_sessions');
    }
};
