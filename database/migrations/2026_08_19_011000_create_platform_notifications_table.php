<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('platform_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('event_key', 190);
            $table->string('source_domain', 64)->index();
            $table->string('source_type', 160)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('priority', 32)->default('info')->index();
            $table->string('title');
            $table->text('message');
            $table->string('action_url', 1024)->nullable();
            $table->boolean('requires_acknowledgement')->default(false);
            $table->timestampTz('read_at')->nullable()->index();
            $table->timestampTz('acknowledged_at')->nullable()->index();
            $table->timestampTz('expires_at')->nullable()->index();
            $table->timestampsTz();

            $table->unique(['user_id', 'event_key']);
            $table->index(['source_domain', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_notifications');
    }
};
