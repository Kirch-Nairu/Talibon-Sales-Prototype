<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('travel_orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('reference_number', 80)->unique();
            $table->date('issuance_date')->index();
            $table->text('purpose');
            $table->string('destination');
            $table->foreignId('department_id')->constrained('departments');
            $table->date('travel_start_date');
            $table->date('travel_end_date');
            $table->string('status', 32)->default('approved');
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('status_updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['department_id', 'status']);
        });

        Schema::create('travel_order_employee', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('travel_order_id')->constrained('travel_orders')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees');
            $table->timestampsTz();

            $table->unique(['travel_order_id', 'employee_id']);
            $table->index('employee_id');
        });

        Schema::create('travel_order_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('travel_order_id')->constrained('travel_orders')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 64);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->text('remarks')->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampTz('created_at');

            $table->index(['travel_order_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_order_events');
        Schema::dropIfExists('travel_order_employee');
        Schema::dropIfExists('travel_orders');
    }
};
