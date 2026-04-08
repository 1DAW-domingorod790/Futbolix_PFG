<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tournaments\Tournament;
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
                'description' => 'Torneo local de prueba para el desarrollo inicial del modulo.',
                'admin_id' => $admin->id,
            ]
        );

        $team = TournamentTeam::updateOrCreate(
            ['code' => 111111],
            [
                'name' => 'Musculitos FC',
                'badge' => null,
                'tournament_id' => $tournament->id,
            ]
        );

        TournamentPlayer::updateOrCreate(
            ['dni' => '85644963P'],
            [
                'name' => 'Carmelix Villarreal',
                'number' => 8,
                'age' => null,
                'team_id' => $team->id,
            ]
        );

        TournamentPlayer::updateOrCreate(
            ['dni' => '79044463B'],
            [
                'name' => 'Domingix Rodriguez',
                'number' => 10,
                'age' => 20,
                'team_id' => $team->id,
            ]
        );
    }
}
