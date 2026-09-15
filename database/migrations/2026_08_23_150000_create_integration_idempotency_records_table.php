<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('integration_idempotency_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_client_id')->constrained('integration_clients')->restrictOnDelete();
            $table->foreignId('integration_client_credential_id')->constrained('integration_client_credentials')->restrictOnDelete();
            $table->string('operation', 160);
            $table->char('idempotency_key_hash', 64);
            $table->char('request_fingerprint', 64);
            $table->string('status', 24)->index();
            $table->uuid('processing_token')->nullable();
            $table->unsignedInteger('execution_attempts')->default(1);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_body')->nullable();
            $table->timestampTz('started_at');
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->timestampsTz();

            $table->unique(
                ['integration_client_id', 'integration_client_credential_id', 'operation', 'idempotency_key_hash'],
                'integration_idempotency_scope_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_idempotency_records');
    }
};
