<?php

namespace App\Console\Commands;

use App\Models\Api\Competition;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncStandings extends Command
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
    protected $signature = 'app:sync-standings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza la posicion de cada equipo en la clasificacion general';

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

        $updatedStandings = 0;

        foreach ($competitions as $competition) {
            $response = Http::withHeaders([
                'X-Auth-Token' => config('services.football_data.api_key'),
            ])->baseUrl(config('services.football_data.base_url'))
                ->get("competitions/{$competition->external_id}/standings");

            if ($response->failed()) {
                $this->warn("No se pudo obtener la clasificacion de {$competition->name} ({$competition->external_id})");

                continue;
            }

            $table = collect($response->json('standings', []))
                ->first(fn (array $standing) => ($standing['type'] ?? null) === 'TOTAL'
                    && empty($standing['group']));

            if (! is_array($table) || ! isset($table['table']) || ! is_array($table['table'])) {
                $this->warn("La competicion {$competition->name} ({$competition->external_id}) no tiene una clasificacion general valida.");

                continue;
            }

            $teamsByExternalId = $competition->teams()
                ->get(['teams.id', 'teams.external_id'])
                ->keyBy(fn ($team) => (string) $team->external_id);

            foreach ($table['table'] as $entry) {
                $teamExternalId = (string) ($entry['team']['id'] ?? '');

                if ($teamExternalId === '' || ! isset($teamsByExternalId[$teamExternalId])) {
                    if ($teamExternalId !== '') {
                        $this->warn("El equipo externo {$teamExternalId} no esta asociado a {$competition->name}. Se omite.");
                    }

                    continue;
                }

                $competition->teams()->updateExistingPivot(
                    $teamsByExternalId[$teamExternalId]->id,
                    [
                        'standing' => $entry['position'] ?? null,
                        'points' => $entry['points'] ?? null,
                        'won' => $entry['won'] ?? null,
                        'draw' => $entry['draw'] ?? null,
                        'lost' => $entry['lost'] ?? null,
                        'goal_difference' => $entry['goalDifference'] ?? null,
                    ]
                );

                $updatedStandings++;
            }
        }

        $this->info("Clasificaciones sincronizadas/actualizadas: {$updatedStandings}");

        return self::SUCCESS;
    }
}
