<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pathologies', function (Blueprint $table) {
            $table->id();
            $table->string('source_id')->unique();
            $table->string('slug')->unique();
            $table->string('schema_version', 20);
            $table->string('official_name');
            $table->text('short_description')->nullable();
            $table->json('synonyms');
            $table->json('colloquial_terms');
            $table->json('keywords');
            $table->json('codes');
            $table->json('classifications');
            $table->json('operational_units');
            $table->json('access_path')->nullable();
            $table->json('sources');
            $table->json('workflow');
            $table->json('quality');
            $table->json('source_payload');
            $table->timestamps();

            $table->index('official_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pathologies');
    }
};
