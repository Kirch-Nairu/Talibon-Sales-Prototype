<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('label');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 32)->default('draft')->index();
            $table->timestampTz('processed_at')->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('released_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('payroll_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('basic_pay', 14, 2);
            $table->decimal('allowances', 14, 2)->default(0);
            $table->decimal('gross_pay', 14, 2);
            $table->decimal('gsis', 14, 2)->default(0);
            $table->decimal('philhealth', 14, 2)->default(0);
            $table->decimal('pagibig', 14, 2)->default(0);
            $table->decimal('withholding_tax', 14, 2)->default(0);
            $table->decimal('other_deductions', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2);
            $table->decimal('net_pay', 14, 2);
            $table->string('status', 32)->default('processed')->index();
            $table->timestampsTz();
            $table->unique(['payroll_period_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_entries');
        Schema::dropIfExists('payroll_periods');
    }
};
