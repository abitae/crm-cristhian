<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrmUserController extends Controller
{
    public function store(Request $request, CreateNewUser $createUser): RedirectResponse
    {
        $input = $request->only(['name', 'email', 'password', 'password_confirmation']);
        $input['email'] = Str::lower($request->string('email')->toString());

        DB::transaction(function () use ($createUser, $input): void {
            $user = $createUser->create($input);
            $user->assignRole('crm-user');
            $user->markEmailAsVerified();
        });

        return back();
    }
}
