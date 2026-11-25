<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientHistoryController extends Controller
{
    public function index(Request $request, string $current_team, Client $client): JsonResponse
    {
        abort_unless($client->team_id === $request->user()->current_team_id, 403);

        $entries = $client->histories()
            ->with(['user:id,name'])
            ->limit(50)
            ->get()
            ->map(fn ($entry) => [
                'id' => $entry->id,
                'type' => $entry->type,
                'body' => $entry->body,
                'meta' => $entry->meta,
                'created_at' => $entry->created_at?->toIso8601String(),
                'user' => [
                    'id' => $entry->user?->id,
                    'name' => $entry->user?->name ?? 'Usuario',
                ],
            ]);

        return response()->json(['data' => $entries]);
    }

    public function store(Request $request, string $current_team, Client $client): JsonResponse
    {
        abort_unless($client->team_id === $request->user()->current_team_id, 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $entry = $client->recordHistory(
            $request->user(),
            'note',
            trim($data['body']),
        );

        $entry->load('user:id,name');

        return response()->json([
            'data' => [
                'id' => $entry->id,
                'type' => $entry->type,
                'body' => $entry->body,
                'meta' => $entry->meta,
                'created_at' => $entry->created_at?->toIso8601String(),
                'user' => [
                    'id' => $entry->user?->id,
                    'name' => $entry->user?->name ?? 'Usuario',
                ],
            ],
        ], 201);
    }
}
