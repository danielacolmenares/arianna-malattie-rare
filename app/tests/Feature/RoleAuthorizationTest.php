<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DemoUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_the_four_expected_roles(): void
    {
        config()->set('demo.password', 'demo-password');
        config()->set('demo.users', [
            ['name' => 'Amministratrice', 'email' => 'admin@test.local', 'role' => UserRole::Administrator],
            ['name' => 'Redattore', 'email' => 'editor@test.local', 'role' => UserRole::Editor],
            ['name' => 'Validatore', 'email' => 'validator@test.local', 'role' => UserRole::Validator],
            ['name' => 'Direttore', 'email' => 'director@test.local', 'role' => UserRole::Director],
        ]);

        $this->seed(DemoUsersSeeder::class);

        $this->assertDatabaseCount('users', 4);
        foreach (UserRole::cases() as $role) {
            $this->assertDatabaseHas('users', ['role' => $role->value]);
        }
    }

    public function test_administrator_can_open_every_reserved_function(): void
    {
        $user = User::factory()->create(['role' => UserRole::Administrator]);

        foreach ($this->reservedRoutes() as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }

    public function test_each_specialist_can_open_only_the_assigned_function(): void
    {
        $matrix = [
            UserRole::Editor->value => 'admin.editor',
            UserRole::Validator->value => 'admin.validator',
            UserRole::Director->value => 'admin.director',
        ];

        foreach ($matrix as $role => $allowedRoute) {
            $user = User::factory()->create(['role' => $role]);

            foreach ($this->reservedRoutes() as $route) {
                $response = $this->actingAs($user)->get(route($route));
                $route === $allowedRoute ? $response->assertOk() : $response->assertForbidden();
            }
        }
    }

    public function test_dashboard_shows_the_authenticated_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::Validator]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Validatore')
            ->assertSee('Consentito')
            ->assertSee('Non consentito');
    }

    /** @return list<string> */
    private function reservedRoutes(): array
    {
        return ['admin.editor', 'admin.validator', 'admin.director', 'admin.users'];
    }
}
