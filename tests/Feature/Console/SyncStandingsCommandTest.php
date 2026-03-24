<?php

use App\Models\Api\Competition;
use App\Models\Api\Team;
use Illuminate\Support\Facades\Http;

it('syncs standings from the total standings table for linked teams', function () {
    config()->set('services.football_data.api_key', 'test-football-key');

    $competition = Competition::create([
        'external_id' => 2014,
        'name' => 'LaLiga',
        'type' => 'LEAGUE',
    ]);

    $teamOne = Team::create([
        'external_id' => 86,
        'name' => 'Real Madrid CF',
    ]);

    $teamTwo = Team::create([
        'external_id' => 81,
        'name' => 'FC Barcelona',
    ]);

    $competition->teams()->attach([
        $teamOne->id,
        $teamTwo->id,
    ]);

    Http::fake([
        'https://api.football-data.org/v4/competitions/2014/standings' => Http::response([
            'standings' => [
                [
                    'type' => 'HOME',
                    'group' => null,
                    'table' => [
                        [
                            'position' => 99,
                            'team' => ['id' => 86],
                        ],
                    ],
                ],
                [
                    'type' => 'TOTAL',
                    'group' => null,
                    'table' => [
                        [
                            'position' => 1,
                            'points' => 72,
                            'won' => 22,
                            'draw' => 6,
                            'lost' => 2,
                            'goalDifference' => 38,
                            'team' => ['id' => 86],
                        ],
                        [
                            'position' => 2,
                            'points' => 67,
                            'won' => 21,
                            'draw' => 4,
                            'lost' => 5,
                            'goalDifference' => 29,
                            'team' => ['id' => 81],
                        ],
                    ],
                ],
            ],
        ]),
        '*' => Http::response(['standings' => []]),
    ]);

    $this->artisan('app:sync-standings')
        ->expectsOutput('Clasificaciones sincronizadas/actualizadas: 2')
        ->assertExitCode(0);

    $this->assertDatabaseHas('competition_team', [
        'competition_id' => $competition->id,
        'team_id' => $teamOne->id,
        'standing' => 1,
        'points' => 72,
        'won' => 22,
        'draw' => 6,
        'lost' => 2,
        'goal_difference' => 38,
    ]);

    $this->assertDatabaseHas('competition_team', [
        'competition_id' => $competition->id,
        'team_id' => $teamTwo->id,
        'standing' => 2,
        'points' => 67,
        'won' => 21,
        'draw' => 4,
        'lost' => 5,
        'goal_difference' => 29,
    ]);
});

it('continues when one competition fails and skips teams not linked in the pivot', function () {
    config()->set('services.football_data.api_key', 'test-football-key');

    $laliga = Competition::create([
        'external_id' => 2014,
        'name' => 'LaLiga',
        'type' => 'LEAGUE',
    ]);

    $premierLeague = Competition::create([
        'external_id' => 2021,
        'name' => 'Premier League',
        'type' => 'LEAGUE',
    ]);

    $linkedTeam = Team::create([
        'external_id' => 86,
        'name' => 'Real Madrid CF',
    ]);

    $unlinkedTeam = Team::create([
        'external_id' => 64,
        'name' => 'Liverpool FC',
    ]);

    $laliga->teams()->attach($linkedTeam->id);

    Http::fake([
        'https://api.football-data.org/v4/competitions/2014/standings' => Http::response([
            'standings' => [
                [
                    'type' => 'TOTAL',
                    'group' => null,
                    'table' => [
                        [
                            'position' => 1,
                            'points' => 70,
                            'won' => 21,
                            'draw' => 7,
                            'lost' => 1,
                            'goalDifference' => 41,
                            'team' => ['id' => 86],
                        ],
                        [
                            'position' => 3,
                            'team' => ['id' => 64],
                        ],
                    ],
                ],
            ],
        ]),
        'https://api.football-data.org/v4/competitions/2021/standings' => Http::response([], 404),
        '*' => Http::response(['standings' => []]),
    ]);

    $this->artisan('app:sync-standings')
        ->expectsOutput("El equipo externo 64 no esta asociado a LaLiga. Se omite.")
        ->expectsOutput('No se pudo obtener la clasificacion de Premier League (2021)')
        ->expectsOutput('Clasificaciones sincronizadas/actualizadas: 1')
        ->assertExitCode(0);

    $this->assertDatabaseHas('competition_team', [
        'competition_id' => $laliga->id,
        'team_id' => $linkedTeam->id,
        'standing' => 1,
        'points' => 70,
        'won' => 21,
        'draw' => 7,
        'lost' => 1,
        'goal_difference' => 41,
    ]);

    $this->assertDatabaseHas('teams', [
        'id' => $unlinkedTeam->id,
        'external_id' => 64,
        'name' => 'Liverpool FC',
    ]);

    $this->assertDatabaseMissing('competition_team', [
        'competition_id' => $laliga->id,
        'team_id' => $unlinkedTeam->id,
    ]);
});

it('does not break when a competition has no linked teams or no ungrouped total standings table', function () {
    config()->set('services.football_data.api_key', 'test-football-key');

    $worldCup = Competition::create([
        'external_id' => 2000,
        'name' => 'FIFA World Cup',
        'type' => 'CUP',
    ]);

    Http::fake([
        'https://api.football-data.org/v4/competitions/2000/standings' => Http::response([
            'standings' => [
                [
                    'type' => 'TOTAL',
                    'group' => 'GROUP_A',
                    'table' => [
                        [
                            'position' => 1,
                            'team' => ['id' => 1],
                        ],
                    ],
                ],
            ],
        ]),
        '*' => Http::response(['standings' => []]),
    ]);

    $this->artisan('app:sync-standings')
        ->expectsOutput('La competicion FIFA World Cup (2000) no tiene una clasificacion general valida.')
        ->expectsOutput('Clasificaciones sincronizadas/actualizadas: 0')
        ->assertExitCode(0);

    expect($worldCup->teams)->toHaveCount(0);
});
