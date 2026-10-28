<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_events', function (Blueprint $table): void {
            $table->id();
            $table->string('query', 200);
            $table->string('normalized_query', 200)->index();
            $table->unsignedInteger('result_count');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_events');
    }
};
