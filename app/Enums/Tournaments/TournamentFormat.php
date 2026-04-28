<?php

namespace App\Enums\Tournaments;

enum TournamentFormat: string
{
    case League = 'league';
    case LeaguePlayoffs = 'league_playoffs';
    case GroupsPlayoffs = 'groups_playoffs';
    case Playoffs = 'playoffs';

    public function label(): string
    {
        return match ($this) {
            self::League => 'Liga',
            self::LeaguePlayoffs => 'Liga + playoffs',
            self::GroupsPlayoffs => 'Fase de grupos + playoffs',
            self::Playoffs => 'Playoffs',
        };
    }

    public function hasPlayoffs(): bool
    {
        return $this !== self::League;
    }

    public function hasGroups(): bool
    {
        return $this === self::GroupsPlayoffs;
    }

    public function hasRegularPhase(): bool
    {
        return in_array($this, [self::LeaguePlayoffs, self::GroupsPlayoffs], true);
    }

    /**
     * @return array<int, array{value:string, label:string, has_playoffs:bool, has_groups:bool, has_regular_phase:bool}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $format) => [
                'value' => $format->value,
                'label' => $format->label(),
                'has_playoffs' => $format->hasPlayoffs(),
                'has_groups' => $format->hasGroups(),
                'has_regular_phase' => $format->hasRegularPhase(),
            ])
            ->all();
    }
}
