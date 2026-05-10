<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Sleep;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:sync-all', function () {
    $commands = [
        'app:sync-competitions',
        'app:sync-teams',
        'app:sync-games',
        'app:sync-standings',
    ];

    foreach ($commands as $index => $command) {
        $this->info("Ejecutando {$command}...");
        $this->call($command);

        if ($index < count($commands) - 1) {
            Sleep::for(90)->seconds();
        }
    }

    $this->info('Sincronizacion completa.');
})->purpose('Sincroniza competiciones, equipos, partidos y clasificaciones');

Schedule::command('app:sync-all')
    ->everyThirtyMinutes()
    ->withoutOverlapping();
