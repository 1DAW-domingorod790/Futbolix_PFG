<?php

use Illuminate\Support\Facades\Http;

it('returns a normalized match prediction without touching the database', function () {
    config()->set('services.groq.api_key', 'test-groq-key');

    Http::fake([
        'https://api.groq.com/openai/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'home_win' => 45,
                            'draw' => 30,
                            'away_win' => 20,
                            'home_goals' => 2,
                            'away_goals' => 1,
                        ]),
                    ],
                ],
            ],
        ]),
    ]);

    $this->withoutMiddleware();

    $response = $this->postJson('/api/predictions/match', [
        'match' => [
            'id' => 10,
            'utc_date' => '2026-03-20T20:00:00Z',
            'matchday' => 28,
            'status' => 'TIMED',
            'home_team' => [
                'name' => 'Real Sociedad',
                'shortname' => 'Real Sociedad',
            ],
            'away_team' => [
                'name' => 'Valencia CF',
                'shortname' => 'Valencia',
            ],
        ],
        'competition' => [
            'name' => 'La Liga',
            'type' => 'LEAGUE',
            'currentMatchDay' => 28,
        ],
    ]);

    $response
        ->assertOk()
        ->assertJson([
            'home_win' => 50,
            'draw' => 30,
            'away_win' => 20,
            'home_goals' => 2,
            'away_goals' => 1,
        ]);

    expect($response->json('home_win') + $response->json('draw') + $response->json('away_win'))->toBe(100);
    expect($response->json('home_goals'))->toBe(2);
    expect($response->json('away_goals'))->toBe(1);
});

it('returns an upstream error when groq responds with invalid json without touching the database', function () {
    config()->set('services.groq.api_key', 'test-groq-key');

    Http::fake([
        'https://api.groq.com/openai/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'esto no es json',
                    ],
                ],
            ],
        ]),
    ]);

    $this->withoutMiddleware();

    $response = $this->postJson('/api/predictions/match', [
        'match' => [
            'home_team' => ['name' => 'Equipo local'],
            'away_team' => ['name' => 'Equipo visitante'],
        ],
        'competition' => [
            'name' => 'Copa',
        ],
    ]);

    $response
        ->assertStatus(502)
        ->assertJsonStructure(['message']);
});
