<?php

namespace App\Console\Commands;

use App\Models\Api\Competition;
use App\Models\Api\Game;
use App\Models\Api\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
        DB::connection()->getPdo()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);

        $syncedCompetitions = 0;

        foreach ($this->competitionIds as $competitionId) {
            $response = Http::withoutVerifying()->withHeaders([
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

        $this->info("Competiciones sincronizadas: {$syncedCompetitions}");

        return self::SUCCESS;
    }
}
