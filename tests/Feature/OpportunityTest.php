<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_can_create_and_progress_their_teams_opportunities(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('clients.store', $user->currentTeam->slug), ['name' => 'Ana', 'tags' => [], 'phone' => '+51 012 345', 'city' => 'Lima'])->assertSessionHasNoErrors();
        $client = Client::query()->sole();
        $this->getJson(route('clients.index', ['current_team' => $user->currentTeam->slug, 'search' => 'Lima']))
            ->assertJsonPath('data.0.phone', '+51 012 345')->assertJsonPath('data.0.city', 'Lima');
        $payload = ['name' => 'Contratación', 'amount' => 4500, 'stage' => 'Propuesta'];
        $this->post(route('opportunities.store', [$user->currentTeam->slug, $client]), $payload)->assertSessionHasNoErrors();
        $record = Opportunity::query()->sole();
        $this->assertEquals($user->current_team_id, $record->team_id);
        $this->assertTrue($record->client->is($client));
        $this->patch(route('opportunities.update', [$user->currentTeam->slug, $record]), ['stage' => 'Ganada'])->assertSessionHasNoErrors();
        $this->assertEquals('Ganada', $record->fresh()->stage);

        $other = User::factory()->create();
        $this->actingAs($other)->post(route('opportunities.store', [$other->currentTeam->slug, $client]), $payload)->assertForbidden();
        $this->actingAs($other)->patch(route('opportunities.update', [$other->currentTeam->slug, $record]), ['stage' => 'Propuesta'])->assertForbidden();
        $this->assertEquals('Ganada', $record->fresh()->stage);
    }
}
