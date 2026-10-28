<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Pathology;
use App\Models\User;
use Database\Seeders\PathologySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DraftEditingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PathologySeeder::class);
    }

    public function test_editor_and_administrator_can_open_the_draft_form(): void
    {
        $pathology = Pathology::query()->firstOrFail();

        foreach ([UserRole::Editor, UserRole::Administrator] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('admin.pathologies.edit', $pathology))
                ->assertOk()
                ->assertSee('Salva bozza')
                ->assertSee('non cambiano la scheda pubblica');
        }
    }

    public function test_validator_and_director_cannot_open_or_save_a_draft(): void
    {
        $pathology = Pathology::query()->firstOrFail();

        foreach ([UserRole::Validator, UserRole::Director] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('admin.pathologies.edit', $pathology))
                ->assertForbidden();

            $this->actingAs($user)
                ->put(route('admin.pathologies.update', $pathology), $this->draftData())
                ->assertForbidden();
        }
    }

    public function test_editor_can_save_a_separate_draft_without_changing_public_data(): void
    {
        $pathology = Pathology::query()->where('slug', 'sindrome-di-kallmann')->firstOrFail();
        $publicName = $pathology->official_name;
        $user = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($user)
            ->put(route('admin.pathologies.update', $pathology), $this->draftData())
            ->assertRedirect(route('admin.pathologies.edit', $pathology))
            ->assertSessionHas('status', 'Bozza salvata. La scheda pubblica non è stata modificata.');

        $this->assertDatabaseHas('pathology_drafts', [
            'pathology_id' => $pathology->id,
            'official_name' => 'Nome aggiornato in bozza',
            'updated_by' => $user->id,
        ]);
        $this->assertSame($publicName, $pathology->fresh()->official_name);

        $this->get(route('pathologies.show', $pathology))
            ->assertOk()
            ->assertSee($publicName)
            ->assertDontSee('Nome aggiornato in bozza');
    }

    public function test_saved_draft_is_loaded_back_into_the_form(): void
    {
        $pathology = Pathology::query()->firstOrFail();
        $user = User::factory()->create(['role' => UserRole::Administrator]);

        $this->actingAs($user)->put(route('admin.pathologies.update', $pathology), $this->draftData());

        $this->actingAs($user)
            ->get(route('admin.pathologies.edit', $pathology))
            ->assertOk()
            ->assertSee('Nome aggiornato in bozza')
            ->assertSee('Sinonimo uno')
            ->assertSee('Parola chiave');
    }

    public function test_official_name_is_required_when_saving_a_draft(): void
    {
        $pathology = Pathology::query()->firstOrFail();
        $user = User::factory()->create(['role' => UserRole::Editor]);

        $data = $this->draftData();
        $data['official_name'] = '';

        $this->actingAs($user)
            ->from(route('admin.pathologies.edit', $pathology))
            ->put(route('admin.pathologies.update', $pathology), $data)
            ->assertRedirect(route('admin.pathologies.edit', $pathology))
            ->assertSessionHasErrors('official_name');

        $this->assertDatabaseCount('pathology_drafts', 0);
    }

    /** @return array<string, string> */
    private function draftData(): array
    {
        return [
            'official_name' => 'Nome aggiornato in bozza',
            'short_description' => 'Descrizione aggiornata e ancora non pubblicata.',
            'synonyms' => "Sinonimo uno\nSinonimo due",
            'colloquial_terms' => 'Termine comune',
            'keywords' => 'Parola chiave',
        ];
    }
}
