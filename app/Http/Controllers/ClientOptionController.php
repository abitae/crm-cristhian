<?php

namespace App\Http\Controllers;

use App\Models\ClientOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientOptionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $userId = $request->user()->id;
        $option = new ClientOption($request->validate([
            'kind' => ['required', Rule::in(['tag', 'status'])],
            'name' => ['required', 'string', 'max:60', Rule::unique('client_options')->where('user_id', $userId)->where('kind', $request->string('kind')->toString())],
        ], ['name.unique' => 'Ya existe una opción con ese nombre.']));
        $option->user_id = $userId;
        $option->save();

        return back();
    }
}
