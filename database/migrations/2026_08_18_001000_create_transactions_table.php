<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_no', 40)->nullable()->unique();
            $table->string('transaction_type', 80)->default('internal_request')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority', 24)->default('normal')->index();
            $table->foreignId('origin_department_id')->constrained('departments')->restrictOnDelete();
            $table->foreignId('current_department_id')->constrained('departments')->restrictOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('submitted')->index();
            $table->timestampTz('closed_at')->nullable();
            $table->timestampsTz();
            $table->index(['current_department_id', 'status']);
            $table->index(['origin_department_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
