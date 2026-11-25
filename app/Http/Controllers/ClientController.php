<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientOption;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $clients = Client::query()->where('team_id', $request->user()->current_team_id)
            ->with(['options' => fn ($q) => $q->where('user_id', $userId)->select('client_options.id', 'kind', 'name')]);
        if ($search = $request->string('search')->trim()->toString()) {
            $clients->where(fn ($q) => $q->whereLike('name', "%{$search}%")->orWhereLike('company', "%{$search}%")->orWhereLike('email', "%{$search}%")->orWhereLike('phone', "%{$search}%")->orWhereLike('city', "%{$search}%"));
        }
        foreach (['tag', 'status'] as $kind) {
            if ($id = $request->integer($kind)) {
                $clients->whereHas('options', fn ($q) => $q->whereKey($id)->where('user_id', $userId)->where('kind', $kind));
            }
        }
        if ($request->input('status') === 'none') {
            $clients->whereDoesntHave('options', fn ($q) => $q->where('user_id', $userId)->where('kind', 'status'));
        }

        return response()->json($clients->latest('id')->paginate(8));
    }

    public function store(Request $request): RedirectResponse
    {
        $client = new Client;
        $client->team_id = $request->user()->current_team_id;

        return $this->persist($request, $client, creating: true);
    }

    public function update(Request $request, string $current_team, Client $client): RedirectResponse
    {
        abort_unless($client->team_id === $request->user()->current_team_id, 403);

        return $this->persist($request, $client, creating: false);
    }

    private function persist(Request $request, Client $client, bool $creating): RedirectResponse
    {
        $user = $request->user();
        $userId = $user->id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'integer', Rule::exists('client_options', 'id')->where('user_id', $userId)->where('kind', 'status')],
            'tags' => ['present', 'array'],
            'tags.*' => ['integer', 'distinct', Rule::exists('client_options', 'id')->where('user_id', $userId)->where('kind', 'tag')],
        ]);

        DB::transaction(function () use ($client, $data, $user, $userId, $creating) {
            $previousStatusId = $creating
                ? null
                : $client->options()->where('user_id', $userId)->where('kind', 'status')->value('client_options.id');

            $client->fill(collect($data)->only(['name', 'company', 'email', 'phone', 'city'])->all())->save();
            $previous = $client->options()->where('user_id', $userId)->pluck('client_options.id');
            $client->options()->detach($previous);
            $client->options()->attach(array_filter([...$data['tags'], $data['status'] ?? null]));

            if ($creating) {
                $client->recordHistory($user, 'created', 'Cliente creado en el directorio.');
            }

            $this->recordStatusChange($client, $user, $previousStatusId, $data['status'] ?? null);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $creating ? __('Cliente creado.') : __('Cliente actualizado.'),
        ]);

        return back();
    }

    public function updateStatus(Request $request, string $current_team, Client $client): RedirectResponse
    {
        $user = $request->user();
        abort_unless($client->team_id === $user->current_team_id, 403);
        $data = $request->validate([
            'status' => ['present', 'nullable', 'integer', Rule::exists('client_options', 'id')->where('user_id', $user->id)->where('kind', 'status')],
        ]);
        DB::transaction(function () use ($client, $user, $data): void {
            Client::whereKey($client->id)->lockForUpdate()->firstOrFail();
            $previousStatusId = $client->options()->where('user_id', $user->id)->where('kind', 'status')->value('client_options.id');
            $previous = $client->options()->where('user_id', $user->id)->where('kind', 'status')->pluck('client_options.id');
            $client->options()->detach($previous);
            if ($data['status'] !== null) {
                $client->options()->attach($data['status']);
            }
            $this->recordStatusChange($client, $user, $previousStatusId, $data['status']);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Estado actualizado.'),
        ]);

        return back();
    }

    private function recordStatusChange(Client $client, User $user, int|string|null $fromId, int|string|null $toId): void
    {
        $from = $fromId !== null ? (int) $fromId : null;
        $to = $toId !== null ? (int) $toId : null;

        if ($from === $to) {
            return;
        }

        $labels = ClientOption::query()
            ->whereIn('id', array_filter([$from, $to]))
            ->pluck('name', 'id');

        $fromLabel = $from ? ($labels[$from] ?? 'Estado eliminado') : 'Sin estado';
        $toLabel = $to ? ($labels[$to] ?? 'Estado eliminado') : 'Sin estado';

        $client->recordHistory(
            $user,
            'status_change',
            "Estado actualizado: {$fromLabel} → {$toLabel}",
            [
                'from' => $fromLabel,
                'to' => $toLabel,
                'from_id' => $from,
                'to_id' => $to,
            ],
        );
    }
}
