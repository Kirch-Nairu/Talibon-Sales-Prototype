<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_key', 190)->unique();
            $table->string('event_type', 64)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('scope', 32)->default('department')->index();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_domain', 64)->index();
            $table->string('source_type', 160)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('priority', 32)->default('normal')->index();
            $table->timestampTz('starts_at')->index();
            $table->timestampTz('ends_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->string('location')->nullable();
            $table->string('action_url', 1024)->nullable();
            $table->string('status', 32)->default('scheduled')->index();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['department_id', 'starts_at']);
            $table->index(['user_id', 'starts_at']);
            $table->index(['source_domain', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
