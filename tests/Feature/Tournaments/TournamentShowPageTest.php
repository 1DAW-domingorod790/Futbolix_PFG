<?php

use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentMatch;
use App\Models\Tournaments\TournamentPlayer;
use App\Models\Tournaments\TournamentTeam;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('tournament detail page can be rendered for the tournament owner', function () {
    $user = User::factory()->create();

    $tournament = Tournament::create([
        'code' => 222222,
        'name' => 'Liga de prueba',
        'description' => 'Torneo de prueba para la vista de detalle.',
        'admin_id' => $user->id,
    ]);

    $homeTeam = TournamentTeam::create([
        'code' => 222223,
        'name' => 'Equipo Azul',
        'tournament_id' => $tournament->id,
        'position' => 1,
        'played' => 1,
        'won' => 1,
        'drawn' => 0,
        'lost' => 0,
        'goals_for' => 2,
        'goals_against' => 0,
        'goal_difference' => 2,
        'points' => 3,
    ]);

    $awayTeam = TournamentTeam::create([
        'code' => 222224,
        'name' => 'Equipo Rojo',
        'tournament_id' => $tournament->id,
        'position' => 2,
        'played' => 1,
        'won' => 0,
        'drawn' => 0,
        'lost' => 1,
        'goals_for' => 0,
        'goals_against' => 2,
        'goal_difference' => -2,
        'points' => 0,
    ]);

    TournamentPlayer::create([
        'dni' => '12345678A',
        'name' => 'Carlos Gomez',
        'number' => 9,
        'birth_date' => '2003-05-12',
        'goals' => 2,
        'team_id' => $homeTeam->id,
    ]);

    TournamentMatch::create([
        'tournament_id' => $tournament->id,
        'matchday' => 1,
        'scheduled_at' => now()->addDay(),
        'venue' => 'Campo principal',
        'status' => 'SCHEDULED',
        'home_team_id' => $homeTeam->id,
        'away_team_id' => $awayTeam->id,
        'home_score' => null,
        'away_score' => null,
    ]);

    $this->actingAs($user)
        ->get(route('tournaments.show', $tournament))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tournaments/Show')
            ->where('tournament.name', 'Liga de prueba')
            ->where('tournament.summary.teams_count', 2)
            ->where('tournament.summary.matches_count', 1)
            ->has('tournament.matches', 1)
            ->where('tournament.top_scorers.0.name', 'Carlos Gomez')
        );
});

test('users cannot see tournaments they do not own', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $tournament = Tournament::create([
        'code' => 333333,
        'name' => 'Liga privada',
        'description' => null,
        'admin_id' => $owner->id,
    ]);

    $this->actingAs($otherUser)
        ->get(route('tournaments.show', $tournament))
        ->assertForbidden();
});

test('public tournaments can be seen by other authenticated users', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $tournament = Tournament::create([
        'code' => 444444,
        'name' => 'Liga publica',
        'description' => 'Visible para todos los usuarios.',
        'admin_id' => $owner->id,
        'is_public' => true,
    ]);

    $this->actingAs($otherUser)
        ->get(route('tournaments.show', $tournament))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tournaments/Show')
            ->where('tournament.name', 'Liga publica')
            ->where('tournament.can_manage', false)
            ->where('tournament.is_public', true)
        );
});

test('new tournaments are hidden by default', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('tournaments.store'), [
            'name' => 'Torneo privado',
            'description' => 'No debe ser visible al crearse.',
        ])
        ->assertRedirect();

    $tournament = Tournament::where('name', 'Torneo privado')->first();

    expect($tournament)->not->toBeNull();
    expect((bool) $tournament?->is_public)->toBeFalse();
});
