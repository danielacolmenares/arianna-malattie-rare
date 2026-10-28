<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pathology_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_role');
            $table->string('action');
            $table->string('previous_status')->nullable();
            $table->string('next_status')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['pathology_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_events');
    }
};
