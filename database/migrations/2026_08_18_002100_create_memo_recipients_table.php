<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('memo_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('memorandum_id')->constrained('memoranda')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestampTz('delivered_at')->useCurrent();
            $table->timestampTz('viewed_at')->nullable()->index();
            $table->timestampTz('acknowledged_at')->nullable()->index();
            $table->unique(['memorandum_id', 'user_id']);
            $table->index(['user_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memo_recipients');
    }
};
