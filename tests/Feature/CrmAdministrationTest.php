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

    public function test_administration_dashboard_lists_users_and_teams_first(): void
    {
        $this->seed(AdminUserSeeder::class);
        $admin = User::where('email', 'admin@open9.dev')->sole();

        $this->actingAs($admin)
            ->get(route('dashboard', $admin->currentTeam->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('adminUsers', 1)
                ->where('adminUsers.0.email', 'admin@open9.dev')
                ->where('adminUsers.0.roles.0', 'super-admin')
                ->has('adminTeams', 1)
                ->where('adminTeams.0.owner_name', 'Administrador'));
    }

    public function test_super_admin_can_reset_a_user_password_from_administration(): void
    {
        $this->seed(AdminUserSeeder::class);
        $admin = User::where('email', 'admin@open9.dev')->sole();

        $this->actingAs($admin)->post(route('crm-users.store'), [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'secret-1234',
            'password_confirmation' => 'secret-1234',
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'ana@example.com')->sole();

        $this->put(route('crm-users.password', $user), [
            'password' => 'nueva-clave-5678',
            'password_confirmation' => 'nueva-clave-5678',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertTrue(password_verify('nueva-clave-5678', $user->fresh()->password));

        $this->actingAs($user)
            ->put(route('crm-users.password', $admin), [
                'password' => 'no-permitido',
                'password_confirmation' => 'no-permitido',
            ])
            ->assertForbidden();
    }
}
