<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('legislative_records', function (Blueprint $table): void {
            $table->id();
            $table->string('record_type', 40)->index();
            $table->string('record_number', 100)->unique();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->date('approved_at')->nullable()->index();
            $table->unsignedSmallInteger('year')->index();
            $table->string('status', 32)->default('active')->index();
            $table->string('issuing_body')->default('Sangguniang Bayan');
            $table->text('keywords')->nullable();
            $table->string('source_file_name')->nullable();
            $table->string('source_path')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->index(['record_type', 'year', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legislative_records');
    }
};
