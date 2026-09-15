<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table): void {
            $table->id();
            $table->string('property_number', 120)->unique();
            $table->string('qr_value', 180)->unique();
            $table->string('category', 120)->index();
            $table->string('description');
            $table->string('serial_number', 180)->nullable()->index();
            $table->date('acquisition_date')->nullable();
            $table->decimal('acquisition_cost', 14, 2)->nullable();
            $table->string('funding_source', 180)->nullable();
            $table->string('supplier', 180)->nullable();
            $table->date('warranty_until')->nullable()->index();
            $table->foreignId('current_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('physical_location', 180)->nullable();
            $table->foreignId('accountable_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('par_reference', 160)->nullable();
            $table->string('ics_reference', 160)->nullable();
            $table->string('condition', 64)->default('good')->index();
            $table->string('status', 64)->default('available')->index();
            $table->string('reconciliation_status', 64)->default('unreconciled')->index();
            $table->timestamps();
        });

        Schema::create('asset_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->string('assignment_type', 64)->index();
            $table->string('reference_no', 160)->nullable();
            $table->string('condition_at_issue', 64)->nullable();
            $table->string('condition_at_return', 64)->nullable();
            $table->timestampTz('assigned_at');
            $table->timestampTz('returned_at')->nullable()->index();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('asset_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 64)->index();
            $table->foreignId('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('to_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('from_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('to_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_events');
        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('assets');
    }
};
