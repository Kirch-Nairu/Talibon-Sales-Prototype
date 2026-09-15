<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('correspondence_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('correspondence_record_id')->constrained('correspondence_records')->restrictOnDelete();
            $table->string('event', 64)->index();
            $table->string('previous_lifecycle_state', 32)->nullable();
            $table->string('new_lifecycle_state', 32);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('integration_client_actor_id')->nullable()->constrained('integration_clients')->restrictOnDelete();
            $table->foreignId('office_department_id')->nullable()->constrained('departments')->restrictOnDelete();
            $table->text('remarks')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('correlation_id')->nullable()->index();
            $table->timestampTz('occurred_at')->index();

            $table->index(['correspondence_record_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correspondence_events');
    }
};
