<?php

use App\Enums\DraftStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pathology_drafts', function (Blueprint $table): void {
            $table->string('status')->default(DraftStatus::Draft->value)->after('keywords');
            $table->text('last_note')->nullable()->after('status');
            $table->foreignId('status_changed_by')->nullable()->after('last_note')->constrained('users')->nullOnDelete();
            $table->timestamp('status_changed_at')->nullable()->after('status_changed_by');
        });
    }

    public function down(): void
    {
        Schema::table('pathology_drafts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('status_changed_by');
            $table->dropColumn(['status', 'last_note', 'status_changed_at']);
        });
    }
};
