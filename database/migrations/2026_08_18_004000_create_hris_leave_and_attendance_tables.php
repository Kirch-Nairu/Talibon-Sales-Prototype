<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 24)->unique();
            $table->string('name');
            $table->boolean('tracks_balance')->default(true);
            $table->string('entitlement_label')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('leave_credit_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $table->decimal('balance', 10, 3)->default(0);
            $table->timestamps();
            $table->unique(['employee_id', 'leave_type_id']);
        });

        Schema::create('leave_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('units', 8, 3);
            $table->text('reason')->nullable();
            $table->string('status', 24)->default('pending')->index();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestampsTz();
            $table->index(['employee_id', 'status']);
        });

        Schema::create('leave_credit_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('leave_credit_account_id')->constrained('leave_credit_accounts')->cascadeOnDelete();
            $table->decimal('amount', 10, 3);
            $table->string('entry_type', 32);
            $table->string('source_type', 80)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent()->index();
        });

        Schema::create('attendance_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->timestampTz('occurred_at')->index();
            $table->string('event_type', 24)->index();
            $table->string('source', 80)->default('manual_import');
            $table->string('external_reference')->nullable()->index();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['employee_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
        Schema::dropIfExists('leave_credit_transactions');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_credit_accounts');
        Schema::dropIfExists('leave_types');
    }
};
