<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentPlayer;
use App\Models\Tournaments\TournamentTeam;

class TournamentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tournament::create([
            'code' => '111111',
            'name' => 'Liga Fútbol 7 - Dos Hermanas',
            'admin_id' => 3,
        ]);

        TournamentTeam::create([
            'code' => '111111',
            'name' => 'Musculitos FC',
            'tournament_id' => 1,
        ]);

        TournamentPlayer::create([
            'dni' => '85644963P',
            'name' => 'Carmelix Villarreal',
            'number' => 8,
            'team_id' => 1,
        ]);

        TournamentPlayer::create([
            'dni' => '79044463B',
            'name' => 'Domingix Rodriguez',
            'number' => 10,
            'age' => 20,
            'team_id' => 1,
        ]);
    }
}
