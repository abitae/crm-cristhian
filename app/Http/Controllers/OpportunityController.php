<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Opportunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OpportunityController extends Controller
{
    public function update(Request $request, string $current_team, Opportunity $opportunity): RedirectResponse
    {
        abort_unless($opportunity->team_id === $request->user()->current_team_id, 403);
        $opportunity->update($request->validate(['stage' => ['required', 'in:Prospección,Calificación,Propuesta,Negociación,Ganada']]));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Etapa actualizada.'),
        ]);

        return back();
    }

    public function store(Request $request, string $current_team, Client $client): RedirectResponse
    {
        abort_unless($client->team_id === $request->user()->current_team_id, 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'between:0,999999999999.99'],
            'stage' => ['required', 'in:Prospección,Calificación,Propuesta,Negociación,Ganada'],
        ]);
        $opportunity = new Opportunity($data);
        $opportunity->client()->associate($client);
        $opportunity->company = $client->company ?? $client->name;
        $opportunity->team_id = $request->user()->current_team_id;
        $opportunity->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Oportunidad creada.'),
        ]);

        return back();
    }
}
