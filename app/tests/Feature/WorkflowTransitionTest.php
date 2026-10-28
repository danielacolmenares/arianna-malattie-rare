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

class WorkflowTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PathologySeeder::class);
    }

    public function test_draft_can_complete_validation_and_authorization_flow(): void
    {
        [$pathology, $draft] = $this->draft();
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $validator = User::factory()->create(['role' => UserRole::Validator]);
        $director = User::factory()->create(['role' => UserRole::Director]);

        $this->actingAs($editor)->post(route('admin.pathologies.transition', $pathology), [
            'action' => 'submit',
        ])->assertRedirect(route('admin.pathologies.workflow', $pathology));
        $this->assertSame(DraftStatus::InValidation, $draft->fresh()->status);

        $this->actingAs($validator)->post(route('admin.pathologies.transition', $pathology), [
            'action' => 'validate',
            'note' => 'Contenuti verificati.',
        ])->assertRedirect(route('admin.pathologies.workflow', $pathology));
        $this->assertSame(DraftStatus::AwaitingAuthorization, $draft->fresh()->status);

        $this->actingAs($director)->post(route('admin.pathologies.transition', $pathology), [
            'action' => 'authorize',
            'note' => 'Scheda autorizzata.',
        ])->assertRedirect(route('admin.pathologies.workflow', $pathology));

        $draft->refresh();
        $this->assertSame(DraftStatus::Authorized, $draft->status);
        $this->assertSame('Scheda autorizzata.', $draft->last_note);
        $this->assertSame($director->id, $draft->status_changed_by);
        $this->assertNotNull($draft->status_changed_at);
    }

    public function test_validator_can_return_a_draft_with_a_required_note(): void
    {
        [$pathology, $draft] = $this->draft(DraftStatus::InValidation);
        $validator = User::factory()->create(['role' => UserRole::Validator]);

        $this->actingAs($validator)->post(route('admin.pathologies.transition', $pathology), [
            'action' => 'return_validation',
            'note' => 'Integrare la fonte del sinonimo.',
        ])->assertRedirect(route('admin.pathologies.workflow', $pathology));

        $draft->refresh();
        $this->assertSame(DraftStatus::Draft, $draft->status);
        $this->assertSame('Integrare la fonte del sinonimo.', $draft->last_note);
    }

    public function test_return_without_a_note_is_rejected(): void
    {
        [$pathology, $draft] = $this->draft(DraftStatus::AwaitingAuthorization);
        $director = User::factory()->create(['role' => UserRole::Director]);

        $this->actingAs($director)
            ->from(route('admin.pathologies.workflow', $pathology))
            ->post(route('admin.pathologies.transition', $pathology), [
                'action' => 'return_authorization',
                'note' => '',
            ])->assertRedirect(route('admin.pathologies.workflow', $pathology))
            ->assertSessionHasErrors('note');

        $this->assertSame(DraftStatus::AwaitingAuthorization, $draft->fresh()->status);
    }

    public function test_roles_cannot_execute_another_roles_transition(): void
    {
        [$pathology, $draft] = $this->draft(DraftStatus::InValidation);
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($editor)->post(route('admin.pathologies.transition', $pathology), [
            'action' => 'validate',
        ])->assertForbidden();

        $this->assertSame(DraftStatus::InValidation, $draft->fresh()->status);
    }

    public function test_draft_cannot_be_edited_while_under_review(): void
    {
        [$pathology] = $this->draft(DraftStatus::InValidation);
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($editor)
            ->get(route('admin.pathologies.edit', $pathology))
            ->assertStatus(409);
    }

    /** @return array{Pathology, PathologyDraft} */
    private function draft(DraftStatus $status = DraftStatus::Draft): array
    {
        $pathology = Pathology::query()->firstOrFail();
        $draft = $pathology->draft()->create([
            'official_name' => $pathology->official_name,
            'short_description' => $pathology->short_description,
            'synonyms' => [],
            'colloquial_terms' => [],
            'keywords' => [],
            'status' => $status,
        ]);

        return [$pathology, $draft];
    }
}
