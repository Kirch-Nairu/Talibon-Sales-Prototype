<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('operational_items', function (Blueprint $table): void {
            $table->id();
            $table->string('item_type', 32)->index();
            $table->string('reference_no', 64)->unique();
            $table->string('title');
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->foreignId('responsible_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('status', 48)->index();
            $table->string('priority', 24)->default('normal')->index();
            $table->date('target_date')->nullable()->index();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->decimal('allocated_amount', 16, 2)->nullable();
            $table->decimal('utilized_amount', 16, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestampsTz();
            $table->index(['item_type', 'status']);
            $table->index(['department_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_items');
    }
};
