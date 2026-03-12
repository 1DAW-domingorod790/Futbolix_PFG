<?php

namespace App\Console\Commands;

use App\Models\Api\Competition;
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
        2014,
        2019,
        2002,
        2015,
        2021,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $syncedCompetitions = 0;

        foreach ($this->competitionIds as $competitionId) {
            $response = Http::withHeaders([
                'X-Auth-Token' => config('services.football_data.api_key'),
            ])->baseUrl(config('services.football_data.base_url'))
                ->get("competitions/{$competitionId}");

            if ($response->failed()) {
                $this->error("No se pudo sincronizar la competicion con ID {$competitionId}");

                continue;
            }

            $competitionData = $response->json();

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
