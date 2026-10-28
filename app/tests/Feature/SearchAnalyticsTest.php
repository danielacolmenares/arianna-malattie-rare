<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SearchEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reserved_dashboard_shows_aggregated_search_indicators(): void
    {
        SearchEvent::query()->create(['query' => 'Malattia di Fabry', 'normalized_query' => 'malattia di fabry', 'result_count' => 0]);
        SearchEvent::query()->create(['query' => 'Malattia di Fabry', 'normalized_query' => 'malattia di fabry', 'result_count' => 0]);
        SearchEvent::query()->create(['query' => 'Kallmann', 'normalized_query' => 'kallmann', 'result_count' => 1]);

        $user = User::factory()->create(['role' => UserRole::Administrator]);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Osservatorio delle ricerche')
            ->assertSee('Malattia di Fabry')
            ->assertSee('Piano editoriale prioritizzato')
            ->assertSee('70/100');
    }
}
