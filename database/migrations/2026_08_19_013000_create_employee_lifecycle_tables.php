<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('onboarding_cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained('employees')->cascadeOnDelete();
            $table->string('status', 40)->default('in_progress')->index();
            $table->string('appointment_reference', 160)->nullable();
            $table->foreignId('target_department_id')->constrained('departments')->restrictOnDelete();
            $table->string('target_position_title');
            $table->foreignId('supervisor_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('planned_start_date')->nullable()->index();
            $table->foreignId('started_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('started_at');
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('onboarding_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('onboarding_case_id')->constrained('onboarding_cases')->cascadeOnDelete();
            $table->string('task_key', 80);
            $table->string('title');
            $table->string('category', 64)->index();
            $table->foreignId('owner_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->boolean('is_required')->default(true)->index();
            $table->string('status', 40)->default('pending')->index();
            $table->timestampTz('due_at')->nullable()->index();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['onboarding_case_id', 'task_key']);
        });

        Schema::create('employee_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('movement_type', 64)->index();
            $table->date('effective_date')->index();
            $table->foreignId('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('to_department_id')->constrained('departments')->restrictOnDelete();
            $table->string('from_position_title')->nullable();
            $table->string('to_position_title')->nullable();
            $table->foreignId('previous_supervisor_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('new_supervisor_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('status', 40)->default('applied')->index();
            $table->foreignId('initiated_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('applied_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_movement_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_movement_id')->constrained('employee_movements')->cascadeOnDelete();
            $table->string('task_key', 80);
            $table->string('title');
            $table->foreignId('owner_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->boolean('is_required')->default(true)->index();
            $table->string('status', 40)->default('pending')->index();
            $table->timestampTz('due_at')->nullable()->index();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['employee_movement_id', 'task_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_movement_tasks');
        Schema::dropIfExists('employee_movements');
        Schema::dropIfExists('onboarding_tasks');
        Schema::dropIfExists('onboarding_cases');
    }
};
