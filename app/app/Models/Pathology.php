<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;

#[Fillable([
    'source_id', 'slug', 'schema_version', 'official_name', 'short_description',
    'synonyms', 'colloquial_terms', 'keywords', 'codes', 'classifications',
    'operational_units', 'access_path', 'sources', 'workflow', 'quality', 'source_payload',
    'publication_status', 'published_at', 'withdrawn_at',
])]
class Pathology extends Model
{
    use HasFactory, Searchable;

    public function draft(): HasOne
    {
        return $this->hasOne(PathologyDraft::class);
    }

    public function workflowEvents(): HasMany
    {
        return $this->hasMany(WorkflowEvent::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function searchableAs(): string
    {
        return 'pathologies';
    }

    public function isPublished(): bool
    {
        return $this->publication_status === 'published';
    }

    public function shouldBeSearchable(): bool
    {
        return $this->isPublished();
    }

    /** @return array<string, mixed> */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->getScoutKey(),
            'source_id' => $this->source_id,
            'slug' => $this->slug,
            'official_name' => $this->official_name,
            'short_description' => $this->short_description,
            'synonyms' => collect($this->synonyms)->pluck('valore')->filter()->values()->all(),
            'colloquial_terms' => $this->colloquial_terms,
            'keywords' => $this->keywords,
            'codes' => collect($this->codes)->map(
                fn (array $code): string => trim($code['sistema'].' '.$code['valore'])
            )->all(),
            'classifications' => collect($this->classifications)->pluck('valore')->filter()->values()->all(),
            'operational_units' => collect($this->operational_units)->pluck('nome')->filter()->values()->all(),
            'workflow_status' => data_get($this->workflow, 'stato'),
            'publishable' => (bool) data_get($this->workflow, 'pubblicabile', false),
            'publication_status' => $this->publication_status,
        ];
    }

    protected function casts(): array
    {
        return [
            'synonyms' => 'array', 'colloquial_terms' => 'array', 'keywords' => 'array',
            'codes' => 'array', 'classifications' => 'array', 'operational_units' => 'array',
            'access_path' => 'array', 'sources' => 'array', 'workflow' => 'array',
            'quality' => 'array', 'source_payload' => 'array',
            'published_at' => 'datetime', 'withdrawn_at' => 'datetime',
        ];
    }
}
