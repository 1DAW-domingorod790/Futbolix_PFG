<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Ai\AiConversationController;
use App\Http\Controllers\Ai\FutbolixAiPageController;
use App\Http\Controllers\Api\MatchPredictionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\PasswordController as SettingsPasswordController;
use App\Http\Controllers\Settings\ProfileController as SettingsProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Api\CompetitionController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Tournaments\PlayoffController;
use App\Http\Controllers\Tournaments\TournamentController;
use App\Http\Controllers\Ai\AiPlanPageController;
use App\Http\Controllers\Ai\UpgradePlanController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
    Route::patch('/admin/users/{user}', [AdminController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [AdminController::class, 'destroy'])->name('admin.users.destroy');
    Route::post('/admin/users', [AdminController::class, 'store'])->name('admin.users.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/settings/profile', [SettingsProfileController::class, 'edit'])->name('settings.profile.edit');
    Route::patch('/settings/profile', [SettingsProfileController::class, 'update'])->name('settings.profile.update');
    Route::delete('/settings/profile', [SettingsProfileController::class, 'destroy'])->name('settings.profile.destroy');

    Route::get('/settings/password', [SettingsPasswordController::class, 'edit'])->name('settings.password.edit');
    Route::put('/settings/password', [SettingsPasswordController::class, 'update'])->name('settings.password.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/futbolix-ai/plans', AiPlanPageController::class)->name('futbolix-ai.plans');
    Route::post('/futbolix-ai/upgrade', UpgradePlanController::class)->name('futbolix-ai.upgrade');
    Route::get('/futbolix-ai', FutbolixAiPageController::class)->name('futbolix-ai.index');
    Route::get('/api/futbolix-ai/conversations', [AiConversationController::class, 'index'])->name('futbolix-ai.conversations.index');
    Route::post('/api/futbolix-ai/conversations', [AiConversationController::class, 'store'])->name('futbolix-ai.conversations.store');
    Route::get('/api/futbolix-ai/conversations/{conversation}', [AiConversationController::class, 'show'])->name('futbolix-ai.conversations.show');
    Route::post('/api/futbolix-ai/conversations/{conversation}/messages', [AiConversationController::class, 'send'])->name('futbolix-ai.conversations.messages.store');
    Route::delete('/api/futbolix-ai/conversations/{conversation}', [AiConversationController::class, 'destroy'])->name('futbolix-ai.conversations.destroy');
    Route::get('/api/futbolix-ai/credits', [AiConversationController::class, 'credits'])->name('futbolix-ai.credits');

    Route::get('/tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
    Route::get('/tournaments/create', [TournamentController::class, 'create'])->name('tournaments.create');
    Route::get('/tournaments/{tournament}', [TournamentController::class, 'show'])->name('tournaments.show');
    Route::get('/tournaments/{tournament}/playoffs', [PlayoffController::class, 'show'])->name('tournaments.playoffs.show');
    Route::get('/tournaments/{tournament}/matches/{match}', [TournamentController::class, 'showMatch'])->name('tournaments.matches.show');
    Route::get('/tournaments/{tournament}/playoffs/matches/{match}', [PlayoffController::class, 'showMatch'])->name('tournaments.playoffs.matches.show');
    Route::get('/tournaments/{tournament}/teams/{team}', [TournamentController::class, 'showTeam'])->name('tournaments.teams.show');
    Route::get('/tournament-teams/{team}/badge', [TournamentController::class, 'showTeamBadge'])->name('tournaments.teams.badge');
    Route::post('/tournaments', [TournamentController::class, 'store'])->name('tournaments.store');
    Route::post('/tournaments/import-csv', [TournamentController::class, 'importTournamentCsv'])->name('tournaments.import-tournament-csv');
    Route::patch('/tournaments/{tournament}', [TournamentController::class, 'update'])->name('tournaments.update');
    Route::delete('/tournaments/{tournament}', [TournamentController::class, 'destroy'])->name('tournaments.destroy');
    Route::patch('/tournaments/{tournament}/teams/{team}', [TournamentController::class, 'updateTeam'])->name('tournaments.teams.update');
    Route::post('/tournaments/{tournament}/teams', [TournamentController::class, 'storeTeam'])->name('tournaments.teams.store');
    Route::post('/tournaments/{tournament}/matches', [TournamentController::class, 'storeMatch'])->name('tournaments.matches.store');
    Route::post('/tournaments/{tournament}/playoffs/generate', [PlayoffController::class, 'generate'])->name('tournaments.playoffs.generate');
    Route::post('/tournaments/{tournament}/playoffs/draw', [PlayoffController::class, 'draw'])->name('tournaments.playoffs.draw');
    Route::post('/tournaments/{tournament}/playoffs/manual', [PlayoffController::class, 'manual'])->name('tournaments.playoffs.manual');
    Route::patch('/tournaments/{tournament}/playoffs/matches/{match}/result', [PlayoffController::class, 'updateResult'])->name('tournaments.playoffs.matches.result');
    Route::post('/tournaments/{tournament}/teams/{team}/players', [TournamentController::class, 'storePlayer'])->name('tournaments.teams.players.store');
    Route::patch('/tournaments/{tournament}/matches/{match}/result', [TournamentController::class, 'updateMatchResult'])->name('tournaments.matches.result');
    Route::post('/tournaments/{tournament}/import-csv', [TournamentController::class, 'importCsv'])->name('tournaments.import-csv');
    Route::get('/tournaments/{tournament}/export-csv', [TournamentController::class, 'exportCsv'])->name('tournaments.export-csv');

    Route::post('/api/predictions/match', [MatchPredictionController::class, 'store'])
        ->name('predictions.match');

    Route::get('/matches', [GameController::class, 'index'])->name('matches.index');
    Route::get('/matches/{id}', [GameController::class, 'show'])->name('matches.show');

    Route::get('/competitions', [CompetitionController::class, 'index'])->name('competitions.index');
    Route::get('/competitions/{id}', [CompetitionController::class, 'show'])->name('competitions.show');

    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::get('/teams/{id}', [TeamController::class, 'show'])->name('teams.show');
});

require __DIR__.'/auth.php';
