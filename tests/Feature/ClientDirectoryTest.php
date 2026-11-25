<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Client;
use App\Models\ClientOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_kanban_moves_only_the_viewers_status_and_preserves_tags(): void
    {
        $owner = User::factory()->create();
        $team = $owner->currentTeam;
        $member = User::factory()->create();
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);
        $optionUrl = route('client-options.store', $team->slug);
        $this->actingAs($owner)->post($optionUrl, ['kind' => 'status', 'name' => 'Activo']);
        $ownerStatus = ClientOption::query()->sole();
        $this->post(route('clients.store', $team->slug), ['name' => 'Luna', 'tags' => [], 'status' => $ownerStatus->id]);
        $client = Client::query()->sole();
        $this->actingAs($member)->post($optionUrl, ['kind' => 'tag', 'name' => 'VIP']);
        $this->post($optionUrl, ['kind' => 'status', 'name' => 'En contacto']);
        $tag = ClientOption::where('kind', 'tag')->sole();
        $status = ClientOption::where('user_id', $member->id)->where('kind', 'status')->sole();
        $client->options()->attach($tag->id);
        $unassigned = route('clients.index', ['current_team' => $team->slug, 'status' => 'none']);
        $move = route('clients.status', [$team->slug, $client]);
        $this->getJson($unassigned)->assertJsonPath('total', 1);
        $this->patch($move, ['status' => $ownerStatus->id])->assertSessionHasErrors('status');
        $this->patch($move, ['status' => $status->id])->assertSessionHasNoErrors();
        $this->getJson($unassigned)->assertJsonPath('total', 0);
        $this->getJson(route('clients.index', ['current_team' => $team->slug, 'status' => $status->id, 'tag' => $tag->id]))->assertJsonPath('data.0.id', $client->id);
        $this->patch($move, ['status' => null])->assertSessionHasNoErrors();
        $this->getJson($unassigned)->assertJsonCount(1, 'data.0.options')->assertJsonPath('data.0.options.0.id', $tag->id);
        $this->assertTrue($client->options()->whereKey($ownerStatus)->exists());
        $other = User::factory()->create();
        $this->actingAs($other)->patch(route('clients.status', [$other->currentTeam->slug, $client]), ['status' => null])->assertForbidden();
    }

    public function test_filtered_directory_is_paginated_and_scoped_to_the_team(): void
    {
        $member = User::factory()->create();
        $team = $member->currentTeam;
        $this->actingAs($member)->post(route('client-options.store', $team->slug), ['kind' => 'tag', 'name' => 'VIP'])->assertSessionHasNoErrors();
        $this->post(route('client-options.store', $team->slug), ['kind' => 'status', 'name' => 'Activo'])->assertSessionHasNoErrors();
        $tag = ClientOption::where('kind', 'tag')->sole();
        $status = ClientOption::where('kind', 'status')->sole();
        $payload = ['company' => 'Norte', 'tags' => [$tag->id], 'status' => $status->id];
        foreach (range(1, 10) as $number) {
            $this->post(route('clients.store', $team->slug), [...$payload, 'name' => "Cliente {$number}"])->assertSessionHasNoErrors();
        }
        $this->post(route('clients.store', $team->slug), [...$payload, 'name' => 'Sin etiqueta', 'tags' => []]);
        $this->post(route('clients.store', $team->slug), [...$payload, 'name' => 'Sin estado', 'status' => null]);
        $this->post(route('clients.store', $team->slug), [...$payload, 'name' => 'Otro', 'company' => 'Sur']);
        $query = ['current_team' => $team->slug, 'search' => 'Norte', 'status' => $status->id, 'tag' => $tag->id, 'page' => 2];
        $this->getJson(route('clients.index', $query))->assertOk()->assertJsonPath('total', 10)
            ->assertJsonCount(2, 'data')->assertJsonPath('data.0.name', 'Cliente 2');

        $other = User::factory()->create();
        $this->actingAs($other)->getJson(route('clients.index', $other->currentTeam->slug))->assertJsonPath('total', 0);
        $this->getJson(route('clients.index', $team->slug))->assertForbidden();
    }

    public function test_teammates_can_classify_the_same_client_without_sharing_personal_options(): void
    {
        $owner = User::factory()->create();
        $team = $owner->currentTeam;
        $member = User::factory()->create();
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);
        $this->actingAs($owner)->post(route('client-options.store', $team->slug), ['kind' => 'tag', 'name' => 'VIP']);
        $tag = ClientOption::query()->sole();
        $this->post(route('clients.store', $team->slug), ['name' => 'Cliente', 'tags' => [$tag->id]]);
        $client = Client::query()->sole();
        $url = route('clients.update', [$team->slug, $client]);
        $this->actingAs($member)->getJson(route('clients.index', $team->slug))->assertJsonCount(0, 'data.0.options');
        $this->put($url, ['name' => 'Cliente', 'tags' => [$tag->id]])->assertSessionHasErrors('tags.0');
        $this->post(route('client-options.store', $team->slug), ['kind' => 'tag', 'name' => 'VIP'])->assertSessionHasNoErrors();
        $this->post(route('client-options.store', $team->slug), ['kind' => 'tag', 'name' => 'VIP'])->assertSessionHasErrors('name');
        $personal = ClientOption::where('user_id', $member->id)->sole();
        $this->put($url, ['name' => 'Cliente actualizado', 'tags' => [$personal->id]])->assertSessionHasNoErrors();
        $this->actingAs($owner)->getJson(route('clients.index', $team->slug))->assertJsonCount(1, 'data.0.options')->assertJsonPath('data.0.options.0.id', $tag->id);
    }
}
