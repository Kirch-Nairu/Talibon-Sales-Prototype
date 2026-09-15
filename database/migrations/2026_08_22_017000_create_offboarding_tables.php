<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offboarding_cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('separation_type', 40);
            $table->date('effective_date');
            $table->string('status', 32)->default('in_progress');
            $table->text('reason')->nullable();
            $table->foreignId('initiated_by_user_id')->constrained('users');
            $table->timestamp('initiated_at');
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('account_deactivated_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'status']);
        });

        Schema::create('offboarding_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('offboarding_case_id')->constrained()->cascadeOnDelete();
            $table->string('task_key', 80);
            $table->string('title');
            $table->foreignId('owner_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->boolean('is_required')->default(true);
            $table->string('status', 32)->default('pending');
            $table->timestamp('due_at')->nullable();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['offboarding_case_id', 'task_key']);
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->date('separation_date')->nullable()->after('contract_end_date');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropColumn('separation_date');
        });
        Schema::dropIfExists('offboarding_tasks');
        Schema::dropIfExists('offboarding_cases');
    }
};
