<?php

use App\Models\Role;
use App\Models\Tournaments\Tournament;
use App\Models\User;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);
});

test('guests cannot access the tournaments module', function () {
    $this->get(route('tournaments.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users only see their own tournaments', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ownTournament = Tournament::create([
        'code' => 111111,
        'name' => 'Torneo del barrio',
        'description' => 'Competicion entre equipos locales.',
        'admin_id' => $user->id,
    ]);

    Tournament::create([
        'code' => 222222,
        'name' => 'Torneo privado de otro usuario',
        'description' => 'No deberia aparecer en el listado.',
        'admin_id' => $otherUser->id,
    ]);

    $this->actingAs($user)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
        ])
        ->get(route('tournaments.index'))
        ->assertOk()
        ->assertJsonFragment([
            'name' => $ownTournament->name,
        ])
        ->assertJsonMissing([
            'name' => 'Torneo privado de otro usuario',
        ]);
});

test('authenticated users can create tournaments', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('tournaments.store'), [
        'name' => 'Liga Futbolix Primavera',
        'description' => 'Primera version del torneo autogestionado.',
    ]);

    $response->assertRedirect(route('tournaments.index'));

    $this->assertDatabaseHas('tournaments', [
        'name' => 'Liga Futbolix Primavera',
        'description' => 'Primera version del torneo autogestionado.',
        'admin_id' => $user->id,
    ]);
});

test('the tournament name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('tournaments.store'), [
        'name' => '',
        'description' => 'Sin nombre no deberia guardarse.',
    ])->assertSessionHasErrors('name');
});
