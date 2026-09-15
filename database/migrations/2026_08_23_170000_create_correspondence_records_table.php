<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('correspondence_records', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('external_reference_no', 64)->unique();
            $table->string('source', 64);
            $table->string('channel', 64)->nullable();
            $table->string('sender_name');
            $table->string('sender_organization')->nullable();
            $table->json('sender_contact')->nullable();
            $table->string('subject');
            $table->text('summary')->nullable();
            $table->timestampTz('received_at')->index();
            $table->string('received_source_identity', 128)->nullable();
            $table->foreignId('receiving_integration_client_id')->nullable()->constrained('integration_clients')->restrictOnDelete();
            $table->foreignId('receiving_integration_client_credential_id')->nullable()->constrained('integration_client_credentials')->restrictOnDelete();
            $table->foreignId('receiving_department_id')->nullable()->constrained('departments')->restrictOnDelete();
            $table->foreignId('registered_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('registered_at')->nullable();
            $table->string('municipal_reference_no', 64)->nullable()->unique();
            $table->string('classification', 32)->nullable()->index();
            $table->timestampTz('classified_at')->nullable();
            $table->foreignId('classified_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('originating_external_reference', 128)->nullable()->index();
            $table->string('lifecycle_state', 32)->index();
            $table->foreignId('workflow_transaction_id')->nullable()->unique()->constrained('transactions')->restrictOnDelete();
            $table->timestampsTz();

            $table->index(['receiving_integration_client_id', 'lifecycle_state']);
            $table->index(['receiving_department_id', 'lifecycle_state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correspondence_records');
    }
};
