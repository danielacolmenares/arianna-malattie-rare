<?php

namespace Tests\Feature;

use App\Models\Pathology;
use Database\Seeders\PathologySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PathologyImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_pathologies_are_imported_idempotently(): void
    {
        $this->seed(PathologySeeder::class);
        $this->seed(PathologySeeder::class);

        $this->assertDatabaseCount('pathologies', 2);

        $pathology = Pathology::query()->where('source_id', 'PAT-RB0070')->firstOrFail();

        $this->assertSame('Sindrome del nevo basocellulare', $pathology->official_name);
        $this->assertSame('NBCCS', $pathology->synonyms[1]['valore']);
        $this->assertFalse($pathology->workflow['pubblicabile']);
    }
}
