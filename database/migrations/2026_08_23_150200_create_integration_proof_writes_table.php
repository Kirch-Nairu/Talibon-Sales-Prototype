<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('integration_proof_writes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('integration_client_id')->constrained('integration_clients')->restrictOnDelete();
            $table->foreignId('integration_client_credential_id')->constrained('integration_client_credentials')->restrictOnDelete();
            $table->string('operation', 160);
            $table->string('value', 160);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_proof_writes');
    }
};
