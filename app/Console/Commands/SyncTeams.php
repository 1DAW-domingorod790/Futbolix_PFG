<?php

namespace App\Console\Commands;

use App\Models\Api\Competition;
use App\Models\Api\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncTeams extends Command
{
    private array $competitionIds = [
        2014,
        2019,
        2002,
        2015,
        2021,
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-teams';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza los equipos y su relación con competiciones';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $competitions = Competition::query()
            ->whereIn('external_id', $this->competitionIds)
            ->get();

        if ($competitions->isEmpty()) {
            $this->warn('No hay competiciones en base de datos. Ejecuta antes app:sync-competitions.');

            return self::FAILURE;
        }

        $syncedTeams = 0;

        foreach ($competitions as $competition) {
            $response = Http::withHeaders([
                'X-Auth-Token' => config('services.football_data.api_key'),
            ])->baseUrl(config('services.football_data.base_url'))
                ->get("competitions/{$competition->external_id}/teams");

            if ($response->failed()) {
                $this->warn("No se pudieron obtener los equipos de la competición {$competition->name} ({$competition->external_id})");

                continue;
            }

            $teams = $response->json('teams', []);
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

        $this->info("Equipos sincronizados/actualizados: {$syncedTeams}");

        return self::SUCCESS;
    }
}
