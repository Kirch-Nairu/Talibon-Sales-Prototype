<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('memoranda', function (Blueprint $table): void {
            $table->id();
            $table->string('memo_number', 80)->unique();
            $table->string('title');
            $table->longText('body');
            $table->foreignId('issued_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('issued_by_department_id')->constrained('departments')->restrictOnDelete();
            $table->string('audience_type', 32)->default('all');
            $table->boolean('requires_acknowledgement')->default(false);
            $table->string('classification', 32)->default('internal');
            $table->string('status', 24)->default('published')->index();
            $table->timestampTz('published_at')->nullable()->index();
            $table->timestampTz('expires_at')->nullable()->index();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memoranda');
    }
};
