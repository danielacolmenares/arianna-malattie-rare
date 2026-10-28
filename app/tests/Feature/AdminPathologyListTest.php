<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\PathologySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPathologyListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PathologySeeder::class);
    }

    public function test_guest_cannot_open_the_administrative_list(): void
    {
        $this->get(route('admin.pathologies.index'))
            ->assertRedirect(route('login'));
    }

    public function test_every_demo_role_can_open_the_administrative_list(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('admin.pathologies.index'))
                ->assertOk()
                ->assertSee($role->label());
        }
    }

    public function test_list_shows_name_status_identifier_and_last_update(): void
    {
        $user = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($user)
            ->get(route('admin.pathologies.index'))
            ->assertOk()
            ->assertSee('Sindrome di Kallmann')
            ->assertSee('Sindrome del nevo basocellulare')
            ->assertSee('PAT-RC0020')
            ->assertSee('bozza')
            ->assertSee('incompleta')
            ->assertSee('Ultima modifica')
            ->assertSee(route('pathologies.show', 'sindrome-di-kallmann'), false);
    }

    public function test_dashboard_links_to_the_administrative_list(): void
    {
        $user = User::factory()->create(['role' => UserRole::Director]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Vedi elenco schede')
            ->assertSee(route('admin.pathologies.index'), false);
    }
}
