<?php

namespace Database\Seeders;

use App\Enums\Tournaments\TournamentFormat;
use App\Models\Role;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentMatch;
use App\Models\Tournaments\TournamentPlayer;
use App\Models\Tournaments\TournamentTeam;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TournamentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRoleId = Role::query()->where('name', 'admin')->value('id')
            ?? Role::firstOrCreate(['name' => 'admin'])->id;

        $admin = User::firstOrCreate(
            ['email' => 'admin@futbolix.test'],
            [
                'name' => 'Administrador Futbolix',
                'role_id' => $adminRoleId,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $tournament = Tournament::updateOrCreate(
            ['code' => 111111],
            [
                'name' => 'Liga Futbol 7 - Dos Hermanas',
                'description' => 'Torneo local de prueba para el desarrollo inicial del modulo con partidos, clasificacion y goleadores.',
                'format' => TournamentFormat::League->value,
                'playoff_teams_count' => null,
                'groups_count' => null,
                'regular_phase_matchdays_count' => null,
                'current_matchday' => null,
                'playoff_bracket_generated_at' => null,
                'is_public' => false,
                'admin_id' => $admin->id,
            ]
        );

        $teams = collect([
            [
                'code' => 111111,
                'name' => 'Musculitos FC',
                'position' => 1,
                'played' => 1,
                'won' => 1,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 3,
                'goals_against' => 1,
                'goal_difference' => 2,
                'points' => 3,
                'players' => [
                    ['dni' => '85644963P', 'name' => 'Carmelix Villarreal', 'number' => 8, 'age' => 24, 'goals' => 2],
                    ['dni' => '79044463B', 'name' => 'Domingix Rodriguez', 'number' => 10, 'age' => 20, 'goals' => 1],
                    ['dni' => '51234567L', 'name' => 'Jose Mari Rojas', 'number' => 1, 'age' => 29, 'goals' => 0],
                ],
            ],
            [
                'code' => 111112,
                'name' => 'Atletico Montequinto',
                'position' => 2,
                'played' => 1,
                'won' => 0,
                'drawn' => 1,
                'lost' => 0,
                'goals_for' => 2,
                'goals_against' => 2,
                'goal_difference' => 0,
                'points' => 1,
                'players' => [
                    ['dni' => '62345678M', 'name' => 'Adrian Campos', 'number' => 9, 'age' => 22, 'goals' => 1],
                    ['dni' => '63456789N', 'name' => 'Pedro Luque', 'number' => 6, 'age' => 27, 'goals' => 1],
                    ['dni' => '64567890Q', 'name' => 'Ruben Mena', 'number' => 13, 'age' => 31, 'goals' => 0],
                ],
            ],
            [
                'code' => 111113,
                'name' => 'La Motilla United',
                'position' => 3,
                'played' => 1,
                'won' => 0,
                'drawn' => 1,
                'lost' => 0,
                'goals_for' => 2,
                'goals_against' => 2,
                'goal_difference' => 0,
                'points' => 1,
                'players' => [
                    ['dni' => '65678901R', 'name' => 'Antonio Moya', 'number' => 11, 'age' => 23, 'goals' => 1],
                    ['dni' => '66789012S', 'name' => 'Fran Pulido', 'number' => 7, 'age' => 25, 'goals' => 1],
                    ['dni' => '67890123T', 'name' => 'Miguel Torres', 'number' => 4, 'age' => 28, 'goals' => 0],
                ],
            ],
            [
                'code' => 111114,
                'name' => 'Los Naranjos CF',
                'position' => 4,
                'played' => 1,
                'won' => 0,
                'drawn' => 0,
                'lost' => 1,
                'goals_for' => 1,
                'goals_against' => 3,
                'goal_difference' => -2,
                'points' => 0,
                'players' => [
                    ['dni' => '68901234V', 'name' => 'Sergio Vela', 'number' => 17, 'age' => 21, 'goals' => 1],
                    ['dni' => '69012345W', 'name' => 'Ismael Rey', 'number' => 5, 'age' => 30, 'goals' => 0],
                    ['dni' => '70123456X', 'name' => 'David Prieto', 'number' => 12, 'age' => 26, 'goals' => 0],
                ],
            ],
        ])->mapWithKeys(function (array $teamData) use ($tournament) {
            $team = TournamentTeam::updateOrCreate(
                ['code' => $teamData['code']],
                [
                    'name' => $teamData['name'],
                    'badge' => null,
                    'tournament_id' => $tournament->id,
                    'position' => $teamData['position'],
                    'played' => $teamData['played'],
                    'won' => $teamData['won'],
                    'drawn' => $teamData['drawn'],
                    'lost' => $teamData['lost'],
                    'goals_for' => $teamData['goals_for'],
                    'goals_against' => $teamData['goals_against'],
                    'goal_difference' => $teamData['goal_difference'],
                    'points' => $teamData['points'],
                ]
            );

            foreach ($teamData['players'] as $playerData) {
                TournamentPlayer::updateOrCreate(
                    ['dni' => $playerData['dni']],
                    [
                        'name' => $playerData['name'],
                        'number' => $playerData['number'],
                        'age' => $playerData['age'],
                        'goals' => $playerData['goals'],
                        'team_id' => $team->id,
                    ]
                );
            }

            return [$teamData['code'] => $team];
        });

        $matches = [
            [
                'matchday' => 1,
                'scheduled_at' => '2026-04-06 20:00:00',
                'venue' => 'Complejo Deportivo Pepe Flores',
                'status' => 'FINISHED',
                'home_team_code' => 111111,
                'away_team_code' => 111114,
                'home_score' => 3,
                'away_score' => 1,
            ],
            [
                'matchday' => 1,
                'scheduled_at' => '2026-04-06 21:15:00',
                'venue' => 'Complejo Deportivo Pepe Flores',
                'status' => 'FINISHED',
                'home_team_code' => 111112,
                'away_team_code' => 111113,
                'home_score' => 2,
                'away_score' => 2,
            ],
            [
                'matchday' => 2,
                'scheduled_at' => '2026-04-13 20:00:00',
                'venue' => 'Campo Municipal Miguel Roman',
                'status' => 'SCHEDULED',
                'home_team_code' => 111114,
                'away_team_code' => 111112,
                'home_score' => null,
                'away_score' => null,
            ],
            [
                'matchday' => 2,
                'scheduled_at' => '2026-04-13 21:15:00',
                'venue' => 'Campo Municipal Miguel Roman',
                'status' => 'SCHEDULED',
                'home_team_code' => 111113,
                'away_team_code' => 111111,
                'home_score' => null,
                'away_score' => null,
            ],
            [
                'matchday' => 3,
                'scheduled_at' => '2026-04-20 20:00:00',
                'venue' => 'Complejo Deportivo Pepe Flores',
                'status' => 'SCHEDULED',
                'home_team_code' => 111111,
                'away_team_code' => 111112,
                'home_score' => null,
                'away_score' => null,
            ],
            [
                'matchday' => 3,
                'scheduled_at' => '2026-04-20 21:15:00',
                'venue' => 'Complejo Deportivo Pepe Flores',
                'status' => 'SCHEDULED',
                'home_team_code' => 111114,
                'away_team_code' => 111113,
                'home_score' => null,
                'away_score' => null,
            ],
        ];

        foreach ($matches as $matchData) {
            TournamentMatch::updateOrCreate(
                [
                    'tournament_id' => $tournament->id,
                    'matchday' => $matchData['matchday'],
                    'home_team_id' => $teams[$matchData['home_team_code']]->id,
                    'away_team_id' => $teams[$matchData['away_team_code']]->id,
                ],
                [
                    'scheduled_at' => $matchData['scheduled_at'],
                    'venue' => $matchData['venue'],
                    'status' => $matchData['status'],
                    'home_score' => $matchData['home_score'],
                    'away_score' => $matchData['away_score'],
                ]
            );
        }
    }
}
