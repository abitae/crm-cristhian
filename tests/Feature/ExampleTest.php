<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_must_log_in_before_entering_the_crm(): void
    {
        $response = $this->get(route('home'));

        $response->assertRedirectToRoute('login');
    }

    public function test_signed_in_users_enter_their_team_dashboard_from_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('home'))
            ->assertRedirectToRoute('dashboard', ['current_team' => $user->currentTeam->slug]);
    }
}
