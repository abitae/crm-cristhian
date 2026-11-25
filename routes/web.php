<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientHistoryController;
use App\Http\Controllers\ClientOptionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request): RedirectResponse {
    $team = $request->user()->currentTeam ?? $request->user()->personalTeam();
    abort_unless($team, 403);

    return to_route('dashboard', ['current_team' => $team->slug]);
})->middleware('auth')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::resource('clients', ClientController::class)->only(['index', 'store', 'update']);
        Route::patch('clients/{client}/status', [ClientController::class, 'updateStatus'])->name('clients.status');
        Route::get('clients/{client}/histories', [ClientHistoryController::class, 'index'])->name('clients.histories.index');
        Route::post('clients/{client}/histories', [ClientHistoryController::class, 'store'])->name('clients.histories.store');
        Route::post('client-options', [ClientOptionController::class, 'store'])->name('client-options.store');
        Route::post('clients/{client}/opportunities', [OpportunityController::class, 'store'])->name('opportunities.store');
        Route::patch('opportunities/{opportunity}', [OpportunityController::class, 'update'])->name('opportunities.update');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
