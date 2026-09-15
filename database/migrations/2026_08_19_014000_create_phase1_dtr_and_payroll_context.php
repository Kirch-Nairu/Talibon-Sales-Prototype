<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dtr_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('label');
            $table->date('period_start')->index();
            $table->date('period_end')->index();
            $table->string('status', 32)->default('generated')->index();
            $table->timestampTz('generated_at')->nullable();
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('locked_at')->nullable();
            $table->foreignId('locked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['period_start', 'period_end']);
        });

        Schema::create('dtr_daily_summaries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dtr_period_id')->constrained('dtr_periods')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('work_date')->index();
            $table->timestampTz('first_in_at')->nullable();
            $table->timestampTz('last_out_at')->nullable();
            $table->unsignedInteger('raw_event_count')->default(0);
            $table->string('leave_status', 32)->nullable();
            $table->string('source_status', 32)->index();
            $table->timestampTz('generated_at');
            $table->timestampsTz();
            $table->unique(['dtr_period_id', 'employee_id', 'work_date'], 'dtr_daily_employee_date_unique');
            $table->index(['employee_id', 'work_date']);
        });

        Schema::table('payroll_periods', function (Blueprint $table): void {
            $table->foreignId('dtr_period_id')->nullable()->after('id')->constrained('dtr_periods')->nullOnDelete();
            $table->string('calculation_mode', 64)->default('prototype')->after('status');
            $table->text('source_notes')->nullable()->after('calculation_mode');
        });

        Schema::table('payroll_entries', function (Blueprint $table): void {
            $table->unsignedInteger('dtr_days_with_logs')->default(0)->after('employee_id');
            $table->unsignedInteger('dtr_complete_days')->default(0)->after('dtr_days_with_logs');
            $table->unsignedInteger('dtr_partial_days')->default(0)->after('dtr_complete_days');
            $table->decimal('approved_leave_units', 10, 3)->default(0)->after('dtr_partial_days');
            $table->string('dtr_snapshot_status', 32)->default('not_linked')->after('approved_leave_units');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table): void {
            $table->dropColumn(['dtr_days_with_logs', 'dtr_complete_days', 'dtr_partial_days', 'approved_leave_units', 'dtr_snapshot_status']);
        });

        Schema::table('payroll_periods', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('dtr_period_id');
            $table->dropColumn(['calculation_mode', 'source_notes']);
        });

        Schema::dropIfExists('dtr_daily_summaries');
        Schema::dropIfExists('dtr_periods');
    }
};
