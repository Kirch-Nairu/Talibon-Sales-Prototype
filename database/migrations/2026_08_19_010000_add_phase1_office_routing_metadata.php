<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table): void {
            $table->string('branch', 32)->default('executive')->index();
            $table->string('office_type', 32)->default('department')->index();
            $table->foreignId('parent_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->boolean('is_routable')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(100)->index();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_department_id');
            $table->dropColumn(['branch', 'office_type', 'is_routable', 'sort_order']);
        });
    }
};
