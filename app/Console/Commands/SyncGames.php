<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Api\Game;
use Illuminate\Support\Facades\Http;

class SyncGames extends Command
{
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
    public function handle()
    {
        $response = Http::withHeaders([
            'X-Auth-Token' => config('services.football_data.api_key')
        ])->baseUrl(config('services.football_data.base_url'))
        ->get('matches');


        if ($response->failed()) {
            $this->error('No se pudo conectar con la API');
            return;
        }

        $games = $response->json()['matches'];

        foreach ($games as $g) {
            Game::updateOrCreate(
                ['external_id' => $g['id']],
                [
                    'home_team'       => $g['homeTeam']['shortName'] ?? $g['homeTeam']['name'],
                    'home_team_logo'  => $g['homeTeam']['crest'],
                    'away_team'       => $g['awayTeam']['shortName'] ?? $g['awayTeam']['name'],
                    'away_team_logo'  => $g['awayTeam']['crest'],
                    'home_score'      => $g['score']['fullTime']['home'],
                    'away_score'      => $g['score']['fullTime']['away'],
                    'utc_date'        => $g['utcDate'],
                    'status'          => $g['status'],
                ]
            );
        }
    }
}
