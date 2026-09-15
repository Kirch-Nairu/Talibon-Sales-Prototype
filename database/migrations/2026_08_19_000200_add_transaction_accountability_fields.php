<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->foreignId('assigned_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestampTz('received_at')->nullable()->index();
            $table->timestampTz('due_at')->nullable()->index();
            $table->timestampTz('completed_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assigned_employee_id');
            $table->dropColumn(['received_at', 'due_at', 'completed_at']);
        });
    }
};
