<?php

namespace Database\Seeders;

use App\Actions\Teams\CreateTeam;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(CreateTeam $createTeam): void
    {
        DB::transaction(function () use ($createTeam): void {
            $admin = Role::findOrCreate('super-admin', 'web');
            $admin->givePermissionTo(
                Permission::findOrCreate('crm.users.create', 'web'),
                Permission::findOrCreate('crm.teams.create', 'web'),
            );
            Role::findOrCreate('crm-user', 'web');
            $user = User::firstOrCreate(
                ['email' => 'admin@open9.dev'],
                ['name' => 'Administrador', 'password' => 'admin123'],
            );

            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }
            $user->assignRole($admin);

            if (! $user->currentTeam) {
                $team = $user->personalTeam();
                if ($team) {
                    $user->switchTeam($team);
                } else {
                    $createTeam->handle($user, 'Open9', isPersonal: true);
                }
            }
        });
    }
}
