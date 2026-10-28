<?php

namespace App\Models;

use App\Enums\DraftStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pathology_id', 'official_name', 'short_description', 'synonyms',
    'colloquial_terms', 'keywords', 'status', 'last_note', 'updated_by',
    'status_changed_by', 'status_changed_at',
])]
class PathologyDraft extends Model
{
    public function pathology(): BelongsTo
    {
        return $this->belongsTo(Pathology::class);
    }

    public function statusEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function casts(): array
    {
        return [
            'synonyms' => 'array',
            'colloquial_terms' => 'array',
            'keywords' => 'array',
            'status' => DraftStatus::class,
            'status_changed_at' => 'datetime',
        ];
    }
}
