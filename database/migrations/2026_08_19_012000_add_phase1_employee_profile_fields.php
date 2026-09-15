<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->foreignId('supervisor_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('employment_type', 64)->nullable()->index();
            $table->date('appointment_date')->nullable();
            $table->date('employment_start_date')->nullable();
            $table->date('contract_end_date')->nullable()->index();
            $table->date('date_of_birth')->nullable();
            $table->text('personal_email')->nullable();
            $table->text('home_address')->nullable();
            $table->text('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relationship', 80)->nullable();
            $table->text('emergency_contact_phone')->nullable();
            $table->text('gsis_number')->nullable();
            $table->text('philhealth_number')->nullable();
            $table->text('pagibig_number')->nullable();
            $table->text('tin_number')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('supervisor_employee_id');
            $table->dropColumn([
                'employment_type',
                'appointment_date',
                'employment_start_date',
                'contract_end_date',
                'date_of_birth',
                'personal_email',
                'home_address',
                'emergency_contact_name',
                'emergency_contact_relationship',
                'emergency_contact_phone',
                'gsis_number',
                'philhealth_number',
                'pagibig_number',
                'tin_number',
            ]);
        });
    }
};
