<?php

namespace App\Console\Commands;

use App\Models\Api\Competition;
use App\Models\Api\Game;
use App\Models\Api\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncCompetitions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-competitions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza las competiciones desde football-data.org';

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
     * Execute the console command.
     */
    public function handle(): int
    {
        $syncedCompetitions = $this->syncCompetitions();
        $syncedTeams = $this->syncTeams();
        $syncedGames = $this->syncGames();

        $this->info("Competiciones sincronizadas: {$syncedCompetitions}");
        $this->info("Equipos sincronizados/actualizados: {$syncedTeams}");
        $this->info("Partidos sincronizados: {$syncedGames}");

        return self::SUCCESS;
    }

    private function syncCompetitions(): int
    {
        $syncedCompetitions = 0;

        foreach ($this->competitionIds as $competitionId) {
            $response = Http::withHeaders([
                'X-Auth-Token' => config('services.football_data.api_key'),
            ])->baseUrl(config('services.football_data.base_url'))
                ->get("competitions/{$competitionId}");

            if ($response->failed()) {
                $this->error("No se pudo sincronizar la competicion con ID {$competitionId}");
                $this->error("Respuesta: {$response->body()}");
                continue;
            }

            $competitionData = $response->json();

            if ($competitionData && $competitionData['name'] && $competitionData['name'] === 'Primera Division') {
                $competitionData['name'] = 'LaLiga';
            }

            Competition::updateOrCreate(
                ['external_id' => $competitionData['id']],
                [
                    'name' => $competitionData['name'],
                    'code' => $competitionData['code'] ?? null,
                    'type' => $competitionData['type'] ?? null,
                    'emblem' => $competitionData['emblem'] ?? null,
                    'startDate' => $competitionData['currentSeason']['startDate'] ?? null,
                    'endDate' => $competitionData['currentSeason']['endDate'] ?? null,
                    'lastUpdated' => $competitionData['currentSeason']['lastUpdated'] ?? null,
                    'currentMatchDay' => $competitionData['currentSeason']['currentMatchday'] ?? null,
                ]
            );

            $syncedCompetitions++;
        }

        return $syncedCompetitions;
    }

    private function syncTeams(): int
    {
        $syncedTeams = 0;

        foreach ($this->competitionIds as $competitionId) {
            $competition = Competition::query()
                ->where('external_id', $competitionId)
                ->first();

            if (! $competition) {
                $this->warn("No existe la competición con external_id {$competitionId} en base de datos.");

                continue;
            }

            $responseTeams = Http::withHeaders([
                'X-Auth-Token' => config('services.football_data.api_key'),
            ])->baseUrl(config('services.football_data.base_url'))
                ->get("competitions/{$competitionId}/teams");

            if ($responseTeams->failed()) {
                $this->error("No se pudieron obtener los equipos de la competición con ID {$competitionId}");
                $this->error("Respuesta: {$responseTeams->body()}");
                continue;
            }

            $teams = $responseTeams->json('teams', []);
            $teamIds = [];

            foreach ($teams as $teamData) {
                $team = Team::updateOrCreate(
                    ['external_id' => $teamData['id']],
                    [
                        'name' => $teamData['name'],
                        'shortname' => $teamData['shortName'] ?? null,
                        'tla' => $teamData['tla'] ?? null,
                        'crest' => $teamData['crest'] ?? null,
                        'founded' => $teamData['founded'] ?? null,
                        'venue' => $teamData['venue'] ?? null,
                        'lastUpdated' => $teamData['lastUpdated'] ?? null,
                    ]
                );

                $teamIds[] = $team->id;
                $syncedTeams++;
            }

            if ($teamIds !== []) {
                $competition->teams()->syncWithoutDetaching($teamIds);
            }
        }

        return $syncedTeams;
    }

    private function syncGames(): int
    {
        $syncedGames = 0;

        foreach ($this->competitionIds as $competitionId) {
            $responseGames = Http::withHeaders([
                'X-Auth-Token' => config('services.football_data.api_key'),
            ])->baseUrl(config('services.football_data.base_url'))
                ->get("competitions/{$competitionId}/matches");

            if ($responseGames->failed()) {
                $this->error("No se pudieron obtener los partidos de la competicion con ID {$competitionId}");
                $this->error("Respuesta: {$responseGames->body()}");
                continue;
            }

            $games = $responseGames->json('matches', []);

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
                        'utc_date' => $g['utcDate'],
                        'status' => $g['status'],
                    ]
                );

                $syncedGames++;
            }
        }

        return $syncedGames;
    }
}
