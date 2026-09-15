<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_maintenance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('maintenance_type', 80);
            $table->string('status', 40)->default('open');
            $table->text('issue_description');
            $table->string('service_provider', 180)->nullable();
            $table->decimal('estimated_cost', 14, 2)->nullable();
            $table->decimal('actual_cost', 14, 2)->nullable();
            $table->date('started_on')->nullable();
            $table->date('completed_on')->nullable();
            $table->string('condition_before', 40)->nullable();
            $table->string('condition_after', 40)->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();
        });

        Schema::create('asset_inventory_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('session_code', 80)->unique();
            $table->string('title', 180);
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('status', 40)->default('open');
            $table->date('inventory_date');
            $table->text('notes')->nullable();
            $table->foreignId('started_by_user_id')->constrained('users');
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_inventory_scans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_inventory_session_id')->constrained('asset_inventory_sessions')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('scan_value', 180)->nullable();
            $table->string('observed_location', 180)->nullable();
            $table->string('observed_condition', 40)->nullable();
            $table->string('verification_status', 40)->default('verified');
            $table->text('remarks')->nullable();
            $table->foreignId('scanned_by_user_id')->constrained('users');
            $table->timestamp('scanned_at');
            $table->timestamps();
            $table->unique(['asset_inventory_session_id', 'asset_id'], 'asset_inventory_scan_unique');
        });

        Schema::create('asset_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('status', 40);
            $table->string('accounting_reference', 180)->nullable();
            $table->decimal('book_value', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('reconciled_by_user_id')->constrained('users');
            $table->timestamp('reconciled_at');
            $table->timestamps();
        });

        Schema::create('asset_disposals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('status', 40)->default('recommended');
            $table->string('method', 80)->nullable();
            $table->string('authority_reference', 180)->nullable();
            $table->text('reason');
            $table->decimal('proceeds', 14, 2)->nullable();
            $table->foreignId('recommended_by_user_id')->constrained('users');
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recommended_at');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
        Schema::dropIfExists('asset_reconciliations');
        Schema::dropIfExists('asset_inventory_scans');
        Schema::dropIfExists('asset_inventory_sessions');
        Schema::dropIfExists('asset_maintenance_records');
    }
};
