<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('outbox_messages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('event_type', 160);
            $table->string('aggregate_type', 160);
            $table->string('aggregate_id', 191);
            $table->json('payload');
            $table->timestampTz('occurred_at');
            $table->string('status', 24)->index();
            $table->timestampTz('available_at')->index();
            $table->timestampTz('claimed_at')->nullable();
            $table->string('claimed_by', 160)->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestampsTz();

            $table->index(['status', 'available_at'], 'outbox_pending_dispatch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
    }
};
