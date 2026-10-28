<?php

namespace Tests\Feature;

use App\Enums\DraftStatus;
use App\Enums\UserRole;
use App\Models\Pathology;
use App\Models\User;
use Database\Seeders\PathologySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PathologySeeder::class);
    }

    public function test_saving_a_draft_records_the_user_role_and_status(): void
    {
        $pathology = Pathology::query()->firstOrFail();
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($editor)->put(route('admin.pathologies.update', $pathology), [
            'official_name' => 'Nome in bozza',
            'short_description' => 'Descrizione in bozza',
            'synonyms' => '',
            'colloquial_terms' => '',
            'keywords' => '',
        ])->assertRedirect();

        $this->assertDatabaseHas('workflow_events', [
            'pathology_id' => $pathology->id,
            'user_id' => $editor->id,
            'user_role' => UserRole::Editor->value,
            'action' => 'draft_saved',
            'previous_status' => DraftStatus::Draft->value,
            'next_status' => DraftStatus::Draft->value,
        ]);
    }

    public function test_each_transition_is_recorded_in_chronological_history(): void
    {
        $pathology = Pathology::query()->firstOrFail();
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $validator = User::factory()->create(['role' => UserRole::Validator]);
        $draft = $pathology->draft()->create([
            'official_name' => $pathology->official_name,
            'synonyms' => [],
            'colloquial_terms' => [],
            'keywords' => [],
        ]);

        $this->actingAs($editor)->post(route('admin.pathologies.transition', $pathology), ['action' => 'submit'])->assertRedirect();
        $this->actingAs($validator)->post(route('admin.pathologies.transition', $pathology), [
            'action' => 'return_validation',
            'note' => 'Aggiungere una fonte.',
        ])->assertRedirect();

        $events = $pathology->workflowEvents()->oldest('created_at')->oldest('id')->get();
        $this->assertSame(['submit', 'return_validation'], $events->pluck('action')->all());
        $this->assertSame(DraftStatus::Draft, $events[0]->previous_status);
        $this->assertSame(DraftStatus::InValidation, $events[0]->next_status);
        $this->assertSame('Aggiungere una fonte.', $events[1]->note);
        $this->assertSame(DraftStatus::Draft, $draft->fresh()->status);
    }

    public function test_history_requires_authentication_and_is_visible_to_every_back_office_role(): void
    {
        $pathology = Pathology::query()->firstOrFail();

        $this->get(route('admin.pathologies.workflow', $pathology))->assertRedirect(route('login'));

        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)
                ->get(route('admin.pathologies.workflow', $pathology))
                ->assertOk()
                ->assertSee('Storico delle operazioni');
        }
    }
}
