<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CrmUserController extends Controller
{
    use PasswordValidationRules;

    public function store(Request $request, CreateNewUser $createUser): RedirectResponse
    {
        $input = $request->only(['name', 'email', 'password', 'password_confirmation']);
        $input['email'] = Str::lower($request->string('email')->toString());

        DB::transaction(function () use ($createUser, $input): void {
            $user = $createUser->create($input);
            $user->assignRole('crm-user');
            $user->markEmailAsVerified();
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Usuario creado. Ya puede iniciar sesión.'),
        ]);

        return back();
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $input = $request->only(['password', 'password_confirmation']);

        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => $input['password'],
            'remember_token' => Str::random(60),
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Contraseña actualizada para :name.', ['name' => $user->name]),
        ]);

        return back();
    }
}
