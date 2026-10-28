<?php

namespace Tests\Feature;

use Database\Seeders\PathologySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PathologyPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PathologySeeder::class);
    }

    public function test_home_lists_the_sample_pathologies(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Arianna')
            ->assertSee('Sindrome di Kallmann')
            ->assertSee('Sindrome del nevo basocellulare')
            ->assertSee('aria-autocomplete="list"', false);
    }

    public function test_search_matches_a_synonym(): void
    {
        $this->get('/?q=NBCCS')
            ->assertOk()
            ->assertSee('Sindrome del nevo basocellulare')
            ->assertDontSee('Sindrome di Kallmann');
    }

    public function test_suggestions_return_matching_pathologies(): void
    {
        $this->getJson('/ricerca/suggerimenti?q=Gorlin')
            ->assertOk()
            ->assertJsonPath('suggestions.0.name', 'Sindrome del nevo basocellulare')
            ->assertJsonPath('suggestions.0.code', 'PAT-RB0070')
            ->assertJsonPath('suggestions.0.url', route('pathologies.show', 'sindrome-del-nevo-basocellulare'));
    }

    public function test_short_queries_do_not_return_suggestions(): void
    {
        $this->getJson('/ricerca/suggerimenti?q=K')
            ->assertOk()
            ->assertExactJson(['suggestions' => []]);
    }

    public function test_voice_recording_is_transcribed_locally(): void
    {
        Http::fake([
            'http://whisper:8080/inference' => Http::response(['text' => 'Sto cercando Kallmann.']),
        ]);

        $this->postJson('/ricerca/vocale', [
            'audio' => UploadedFile::fake()->create('ricerca-vocale.webm', 100, 'audio/webm'),
        ])->assertOk()->assertExactJson([
            'transcript' => 'Sto cercando Kallmann.',
            'query' => 'Kallmann',
        ]);

        Http::assertSent(fn ($request) => $request->url() === 'http://whisper:8080/inference'
            && collect($request->data())->contains(fn (array $part): bool => ($part['name'] ?? null) === 'prompt'
                && str_contains((string) ($part['contents'] ?? ''), 'Sindrome di Kallmann')));
    }

    public function test_voice_search_rejects_a_missing_recording(): void
    {
        $this->postJson('/ricerca/vocale')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('audio');
    }

    public function test_pathology_detail_is_available_by_slug(): void
    {
        $this->get('/patologie/sindrome-di-kallmann')
            ->assertOk()
            ->assertSee('Ipogonadismo con anosmia')
            ->assertSee('UOC Endocrinologia')
            ->assertSee('Condividi la scheda')
            ->assertSee('Scarica QR in SVG');
    }

    public function test_pathology_qr_is_generated_as_svg(): void
    {
        $response = $this->get('/patologie/sindrome-di-kallmann/qr.svg')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8');

        $this->assertStringContainsString('<svg', $response->getContent());
        $this->assertGreaterThan(1000, strlen($response->getContent()));
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_pathology_qr_can_be_downloaded(): void
    {
        $this->get('/patologie/sindrome-di-kallmann/qr.svg?download=1')
            ->assertOk()
            ->assertDownload('arianna-sindrome-di-kallmann.svg');
    }
}
