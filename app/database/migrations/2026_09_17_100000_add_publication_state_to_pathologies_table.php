<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pathologies', function (Blueprint $table): void {
            $table->string('publication_status')->default('published')->after('source_payload')->index();
            $table->timestamp('published_at')->nullable()->after('publication_status');
            $table->timestamp('withdrawn_at')->nullable()->after('published_at');
        });

        DB::table('pathologies')->whereNull('published_at')->update(['published_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('pathologies', function (Blueprint $table): void {
            $table->dropIndex(['publication_status']);
            $table->dropColumn(['publication_status', 'published_at', 'withdrawn_at']);
        });
    }
};
