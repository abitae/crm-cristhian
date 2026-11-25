<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Client;
use App\Models\ClientHistory;
use App\Models\ClientOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_client_records_history_and_notes_can_be_saved(): void
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;

        $this->actingAs($user)
            ->post(route('clients.store', $team->slug), [
                'name' => 'Lucía Herrera',
                'company' => 'Studio Norte',
                'tags' => [],
                'status' => null,
            ])
            ->assertSessionHasNoErrors();

        $client = Client::query()->sole();

        $this->assertDatabaseHas('client_histories', [
            'client_id' => $client->id,
            'user_id' => $user->id,
            'type' => 'created',
        ]);

        $this->postJson(route('clients.histories.store', [$team->slug, $client]), [
            'body' => 'Llamada de seguimiento agendada para el viernes.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'note')
            ->assertJsonPath('data.body', 'Llamada de seguimiento agendada para el viernes.')
            ->assertJsonPath('data.user.name', $user->name);

        $this->getJson(route('clients.histories.index', [$team->slug, $client]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'note')
            ->assertJsonPath('data.1.type', 'created');
    }

    public function test_status_changes_are_recorded_in_client_history(): void
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;

        $this->actingAs($user)
            ->post(route('client-options.store', $team->slug), ['kind' => 'status', 'name' => 'Activo']);
        $this->post(route('client-options.store', $team->slug), ['kind' => 'status', 'name' => 'En seguimiento']);

        $activo = ClientOption::where('name', 'Activo')->sole();
        $seguimiento = ClientOption::where('name', 'En seguimiento')->sole();

        $this->post(route('clients.store', $team->slug), [
            'name' => 'Cliente',
            'tags' => [],
            'status' => $activo->id,
        ])->assertSessionHasNoErrors();

        $client = Client::query()->sole();

        $this->patch(route('clients.status', [$team->slug, $client]), [
            'status' => $seguimiento->id,
        ])->assertSessionHasNoErrors();

        $this->assertTrue(
            ClientHistory::query()
                ->where('client_id', $client->id)
                ->where('type', 'status_change')
                ->where('body', 'Estado actualizado: Activo → En seguimiento')
                ->exists()
        );
    }

    public function test_history_is_scoped_to_the_client_team(): void
    {
        $owner = User::factory()->create();
        $team = $owner->currentTeam;
        $member = User::factory()->create();
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $this->actingAs($owner)
            ->post(route('clients.store', $team->slug), ['name' => 'Cliente', 'tags' => []]);
        $client = Client::query()->sole();

        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson(route('clients.histories.index', [$outsider->currentTeam->slug, $client]))
            ->assertForbidden();

        $this->postJson(route('clients.histories.store', [$outsider->currentTeam->slug, $client]), [
            'body' => 'No debería guardarse',
        ])->assertForbidden();

        $this->actingAs($member)
            ->postJson(route('clients.histories.store', [$team->slug, $client]), [
                'body' => 'Nota del equipo',
            ])
            ->assertCreated();
    }
}
