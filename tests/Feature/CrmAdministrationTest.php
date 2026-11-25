<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_provision_a_user_with_an_isolated_workspace(): void
    {
        $this->seed(AdminUserSeeder::class);
        $admin = User::where('email', 'admin@open9.dev')->sole();
        $this->actingAs($admin)->post(route('crm-users.store'), [
            'name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'secret-1234', 'password_confirmation' => 'secret-1234',
            'role' => 'super-admin', 'current_team_id' => $admin->current_team_id,
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'ana@example.com')->sole();
        $this->assertAuthenticatedAs($admin);
        $this->assertTrue($user->hasExactRoles(['crm-user']));
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue($user->ownsTeam($user->currentTeam));
        $this->assertNotEquals($admin->current_team_id, $user->current_team_id);
        $this->actingAs($user)->get(route('dashboard', $user->currentTeam->slug))->assertOk();
        $this->get(route('dashboard', $admin->currentTeam->slug))->assertForbidden();
        $this->getJson(route('clients.index', $admin->currentTeam->slug))->assertForbidden();
        $this->post(route('crm-users.store'), ['name' => 'Otro'])->assertForbidden();
        $this->post(route('teams.store'), ['name' => 'Otro'])->assertForbidden();
        $this->assertDatabaseCount('users', 2);
    }
}
