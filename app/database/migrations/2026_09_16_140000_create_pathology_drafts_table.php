<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pathology_drafts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pathology_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('official_name');
            $table->text('short_description')->nullable();
            $table->json('synonyms');
            $table->json('colloquial_terms');
            $table->json('keywords');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pathology_drafts');
    }
};
