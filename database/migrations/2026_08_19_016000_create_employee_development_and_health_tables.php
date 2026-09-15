<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('performance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('period_start')->index();
            $table->date('period_end')->index();
            $table->foreignId('evaluator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('rating', 8, 3)->nullable();
            $table->string('rating_scale', 80)->nullable();
            $table->string('status', 40)->default('recorded')->index();
            $table->text('summary')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->index(['employee_id', 'period_end']);
        });

        Schema::create('employee_development_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('record_type', 48)->index();
            $table->string('title');
            $table->string('provider')->nullable();
            $table->string('reference_no', 180)->nullable();
            $table->date('attained_at')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->string('status', 40)->default('active')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->index(['employee_id', 'record_type']);
        });

        Schema::create('employee_health_access_grants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->cascadeOnDelete();
            $table->boolean('can_view')->default(true);
            $table->boolean('can_manage')->default(false);
            $table->text('purpose');
            $table->foreignId('granted_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('granted_at');
            $table->timestampTz('expires_at')->nullable()->index();
            $table->timestampTz('revoked_at')->nullable()->index();
            $table->foreignId('revoked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['user_id', 'employee_id', 'revoked_at'], 'health_grant_lookup_idx');
        });

        Schema::create('employee_health_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('record_type', 64)->index();
            $table->string('title');
            $table->date('issued_at')->nullable();
            $table->date('valid_until')->nullable()->index();
            $table->string('status', 40)->default('active')->index();
            $table->text('summary')->nullable();
            $table->text('restriction_notes')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->index(['employee_id', 'record_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_health_records');
        Schema::dropIfExists('employee_health_access_grants');
        Schema::dropIfExists('employee_development_records');
        Schema::dropIfExists('performance_records');
    }
};
