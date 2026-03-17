<?php

use App\Http\Controllers\AdminController;
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
    Route::get('/tournaments', function () {
        return Inertia::render('Tournaments/Index');
    })->name('tournaments.index');

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
