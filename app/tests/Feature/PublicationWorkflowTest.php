<?php

namespace Tests\Feature;

use App\Enums\DraftStatus;
use App\Enums\UserRole;
use App\Models\Pathology;
use App\Models\PathologyDraft;
use App\Models\User;
use Database\Seeders\PathologySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PathologySeeder::class);
    }

    public function test_authorized_draft_can_be_published_and_replaces_public_editable_fields(): void
    {
        [$pathology, $draft] = $this->draft(DraftStatus::Authorized);
        $director = User::factory()->create(['role' => UserRole::Director]);

        $this->actingAs($director)->post(route('admin.pathologies.transition', $pathology), [
            'action' => 'publish',
            'note' => 'Versione verificata per la pubblicazione.',
        ])->assertRedirect(route('admin.pathologies.workflow', $pathology));

        $pathology->refresh();
        $this->assertSame(DraftStatus::Published, $draft->fresh()->status);
        $this->assertSame('Nome pubblico aggiornato', $pathology->official_name);
        $this->assertSame('Descrizione pubblicata.', $pathology->short_description);
        $this->assertSame('Sinonimo pubblicato', $pathology->synonyms[0]['valore']);
        $this->assertSame('published', $pathology->publication_status);
        $this->assertNotNull($pathology->published_at);
        $this->assertDatabaseHas('workflow_events', [
            'pathology_id' => $pathology->id,
            'user_id' => $director->id,
            'action' => 'publish',
            'previous_status' => DraftStatus::Authorized->value,
            'next_status' => DraftStatus::Published->value,
        ]);

        $this->get(route('pathologies.show', $pathology))
            ->assertOk()
            ->assertSee('Nome pubblico aggiornato')
            ->assertSee('Sinonimo pubblicato');
    }

    public function test_publication_is_rejected_before_authorization_and_for_wrong_role(): void
    {
        [$pathology, $draft] = $this->draft(DraftStatus::AwaitingAuthorization);
        $director = User::factory()->create(['role' => UserRole::Director]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($director)->post(route('admin.pathologies.transition', $pathology), [
            'action' => 'publish',
        ])->assertSessionHasErrors('action');

        $draft->update(['status' => DraftStatus::Authorized]);
        $this->actingAs($editor)->post(route('admin.pathologies.transition', $pathology), [
            'action' => 'publish',
        ])->assertForbidden();

        $this->assertSame(DraftStatus::Authorized, $draft->fresh()->status);
    }

    public function test_withdrawal_requires_a_note_and_removes_the_pathology_from_public_pages(): void
    {
        [$pathology, $draft] = $this->draft(DraftStatus::Published);
        $director = User::factory()->create(['role' => UserRole::Director]);

        $this->actingAs($director)
            ->from(route('admin.pathologies.workflow', $pathology))
            ->post(route('admin.pathologies.transition', $pathology), ['action' => 'withdraw'])
            ->assertRedirect(route('admin.pathologies.workflow', $pathology))
            ->assertSessionHasErrors('note');

        $this->actingAs($director)->post(route('admin.pathologies.transition', $pathology), [
            'action' => 'withdraw',
            'note' => 'Contenuto ritirato per aggiornamento.',
        ])->assertRedirect(route('admin.pathologies.workflow', $pathology));

        $pathology->refresh();
        $this->assertSame(DraftStatus::Withdrawn, $draft->fresh()->status);
        $this->assertSame('withdrawn', $pathology->publication_status);
        $this->assertNotNull($pathology->withdrawn_at);
        $this->get('/')->assertOk()->assertDontSee($pathology->official_name);
        $this->get(route('pathologies.show', $pathology))->assertNotFound();
        $this->get(route('pathologies.qr', $pathology))->assertNotFound();
        $this->getJson('/ricerca/suggerimenti?q=pubblicato')->assertJsonCount(0, 'suggestions');
        $this->assertDatabaseHas('workflow_events', [
            'pathology_id' => $pathology->id,
            'action' => 'withdraw',
            'note' => 'Contenuto ritirato per aggiornamento.',
        ]);
    }

    /** @return array{Pathology, PathologyDraft} */
    private function draft(DraftStatus $status): array
    {
        $pathology = Pathology::query()->where('slug', 'sindrome-di-kallmann')->firstOrFail();
        $draft = $pathology->draft()->create([
            'official_name' => 'Nome pubblico aggiornato',
            'short_description' => 'Descrizione pubblicata.',
            'synonyms' => ['Sinonimo pubblicato'],
            'colloquial_terms' => ['Termine pubblico'],
            'keywords' => ['Parola pubblica'],
            'status' => $status,
        ]);

        return [$pathology, $draft];
    }
}
