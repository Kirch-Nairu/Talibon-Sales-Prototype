<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('integration_clients', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('requests_per_minute')->default(60);
            $table->string('contact_name', 160)->nullable();
            $table->string('contact_email', 254)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('integration_client_credentials', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('integration_client_id')->constrained('integration_clients')->cascadeOnDelete();
            $table->char('secret_hash', 64);
            $table->json('scopes');
            $table->timestampTz('issued_at')->useCurrent();
            $table->timestampTz('expires_at')->nullable()->index();
            $table->timestampTz('revoked_at')->nullable()->index();
            $table->timestampTz('last_used_at')->nullable();
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['integration_client_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_client_credentials');
        Schema::dropIfExists('integration_clients');
    }
};
