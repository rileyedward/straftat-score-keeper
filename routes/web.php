<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\TournamentController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('passcode')->group(function (): void {
    Route::get('/', HomeController::class)->name('home');

    Route::get('/tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
    Route::get('/tournaments/create', [TournamentController::class, 'create'])->name('tournaments.create');
    Route::post('/tournaments', [TournamentController::class, 'store'])->name('tournaments.store');
    Route::get('/tournaments/{tournament}', [TournamentController::class, 'show'])->name('tournaments.show');
    Route::post('/tournaments/{tournament}/end', [TournamentController::class, 'end'])->name('tournaments.end');
    Route::get('/tournaments/{tournament}/results', [TournamentController::class, 'results'])->name('tournaments.results');

    Route::post('/tournaments/{tournament}/matches', [MatchController::class, 'store'])->name('matches.store');
    Route::patch('/matches/{match}', [MatchController::class, 'update'])->name('matches.update');
    Route::delete('/matches/{match}', [MatchController::class, 'destroy'])->name('matches.destroy');

    Route::get('/api/players/search', [PlayerController::class, 'search'])->name('players.search');
    Route::post('/players', [PlayerController::class, 'store'])->name('players.store');
    Route::get('/players/{player}', [PlayerController::class, 'show'])->name('players.show');
    Route::patch('/players/{player}', [PlayerController::class, 'update'])->name('players.update');
    Route::post('/players/{player}/image', [PlayerController::class, 'uploadImage'])->name('players.image');
});
