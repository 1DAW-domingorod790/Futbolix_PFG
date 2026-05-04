<?php

namespace App\Console\Commands;

use App\Models\Api\Competition;
use App\Models\Api\Game;
use App\Models\Api\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SyncGames extends Command
{
    public array $competitionIds = [
        2014, // LaLiga
        2021, // Premier League
        2019, // Serie A
        2002, // Bundesliga
        2015, // Ligue 1
        2000, // FIFA World Cup
        2001, // UEFA Champions League
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-games';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cada 10 minutos, los partidos se actualizan con los datos recibidos mediante GET';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        DB::connection()->getPdo()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);

        $syncedGames = 0;

        foreach ($this->competitionIds as $competitionId) {
            $response = Http::withoutVerifying()->withHeaders([
                'X-Auth-Token' => config('services.football_data.api_key'),
            ])->baseUrl(config('services.football_data.base_url'))
                ->get("competitions/{$competitionId}/matches");

            if ($response->failed()) {
                $this->warn("No se pudieron obtener los partidos de la competicion con ID {$competitionId}");
                continue;
            }

            $games = $response->json('matches', []);

            foreach ($games as $g) {
                $competition = Competition::updateOrCreate(
                    ['external_id' => $g['competition']['id']],
                    [
                        'name' => $g['competition']['name'],
                        'code' => $g['competition']['code'] ?? null,
                        'type' => $g['competition']['type'] ?? null,
                        'emblem' => $g['competition']['emblem'] ?? null,
                        'startDate' => $g['season']['startDate'] ?? null,
                        'endDate' => $g['season']['endDate'] ?? null,
                        'lastUpdated' => $g['season']['lastUpdated'] ?? null,
                        'currentMatchDay' => $g['season']['currentMatchday'] ?? null,
                    ]
                );

                if ($g['homeTeam'] === null || $g['awayTeam'] === null || !isset($g['homeTeam']['id']) || !isset($g['awayTeam']['id'])) {
                    $this->warn("Partido con ID {$g['id']} tiene datos de equipo faltantes. Se omite.");
                    continue;
                }

                $homeTeam = Team::updateOrCreate(
                    ['external_id' => $g['homeTeam']['id']],
                    [
                        'name' => $g['homeTeam']['name'],
                        'shortname' => $g['homeTeam']['shortName'] ?? null,
                        'tla' => $g['homeTeam']['tla'] ?? null,
                        'crest' => $g['homeTeam']['crest'] ?? null,
                    ]
                );

                $awayTeam = Team::updateOrCreate(
                    ['external_id' => $g['awayTeam']['id']],
                    [
                        'name' => $g['awayTeam']['name'],
                        'shortname' => $g['awayTeam']['shortName'] ?? null,
                        'tla' => $g['awayTeam']['tla'] ?? null,
                        'crest' => $g['awayTeam']['crest'] ?? null,
                    ]
                );

                $competition->teams()->syncWithoutDetaching([$homeTeam->id, $awayTeam->id]);

                Game::updateOrCreate(
                    ['external_id' => $g['id']],
                    [
                        'competition_id' => $competition->id,
                        'home_team_id' => $homeTeam->id,
                        'away_team_id' => $awayTeam->id,
                        'matchday' => $g['matchday'] ?? null,
                        'home_score' => $g['score']['fullTime']['home'] ?? null,
                        'away_score' => $g['score']['fullTime']['away'] ?? null,
                        'matchday' => $g['matchday'] ?? null,
                        'stage' => $g['stage'] ?? null,
                        'utc_date' => $g['utcDate'],
                        'status' => $g['status'],
                    ]
                );

                $syncedGames++;
            }
        }

        $this->info("Partidos sincronizados: {$syncedGames}");

        return self::SUCCESS;
    }
}
