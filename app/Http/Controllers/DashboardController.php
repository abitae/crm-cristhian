<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Models\ClientOption;
use App\Models\Opportunity;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $email = strtolower($user->email);
        $canAdminister = $user->hasRole('super-admin')
            && ($user->can('crm.users.create') || $user->can('crm.teams.create'));

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        return Inertia::render('Dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'clientOptions' => ClientOption::query()->where('user_id', $user->id)->orderBy('name')->get(['id', 'kind', 'name']),
            'opportunities' => Opportunity::query()->with('client:id,name')->where('team_id', $user->current_team_id)->latest('id')->get(),
            'adminUsers' => $canAdminister ? $this->adminUsers() : [],
            'adminTeams' => $canAdminister ? $this->adminTeams() : [],
        ]);
    }

    /**
     * @return list<array{id: int, name: string, email: string, roles: list<string>, teams_count: int, created_at: string|null}>
     */
    private function adminUsers(): array
    {
        return User::query()
            ->with('roles:id,name')
            ->withCount('teams')
            ->latest('id')
            ->get(['id', 'name', 'email', 'created_at'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->values()->all(),
                'teams_count' => $user->teams_count,
                'created_at' => $user->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, slug: string, is_personal: bool, members_count: int, owner_name: string|null, created_at: string|null}>
     */
    private function adminTeams(): array
    {
        return Team::query()
            ->withCount('members')
            ->with(['members' => fn ($query) => $query
                ->wherePivot('role', TeamRole::Owner->value)
                ->select('users.id', 'users.name')])
            ->latest('id')
            ->get(['id', 'name', 'slug', 'is_personal', 'created_at'])
            ->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'is_personal' => $team->is_personal,
                'members_count' => $team->members_count,
                'owner_name' => $team->members->first()?->name,
                'created_at' => $team->created_at?->toIso8601String(),
            ])
            ->all();
    }
}
