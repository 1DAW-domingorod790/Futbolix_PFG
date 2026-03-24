<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import MatchPredictionCard from '@/Components/MatchPredictionCard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

type Team = {
    id: number | string;
    name?: string | null;
    shortname?: string | null;
    tla?: string | null;
    crest?: string | null;
    founded?: number | null;
    venue?: string | null;
};

type Competition = {
    id: number | string;
    name?: string | null;
    code?: string | null;
    type?: string | null;
    emblem?: string | null;
    startDate?: string | null;
    endDate?: string | null;
    currentMatchDay?: number | null;
    lastUpdated?: string | null;
    updated_at?: string | null;
};

type Game = {
    id: number | string;
    matchday?: number | null;
    utc_date?: string | null;
    status?: string | null;
    home_score?: number | null;
    away_score?: number | null;
    competition?: Competition | null;
    home_team?: Team | null;
    away_team?: Team | null;
    homeTeam?: Team | null;
    awayTeam?: Team | null;
};

type TeamSummary = {
    played: number;
    wins: number;
    draws: number;
    losses: number;
    goals_for: number;
    goals_against: number;
    goal_difference: number;
};

const props = defineProps<{
    game: Game;
    recentGames: {
        home: Game[];
        away: Game[];
    };
    headToHeadGames: Game[];
    matchdayGames: Game[];
    teamSummaries: {
        home: TeamSummary;
        away: TeamSummary;
    };
}>();

const homeTeam = computed(() => props.game.homeTeam || props.game.home_team || null);
const awayTeam = computed(() => props.game.awayTeam || props.game.away_team || null);
const competition = computed(() => props.game.competition || null);
const shouldShowPrediction = computed(() => {
    const status = props.game.status;

    return Boolean(
        competition.value
        && status
        && ['TIMED', 'SCHEDULED', 'PROGRAMMED'].includes(status),
    );
});

const pageTitle = computed(() =>
    `${teamName(homeTeam.value, 'Local')} vs ${teamName(awayTeam.value, 'Visitante')}`,
);

const heroScore = computed(() => ({
    home: props.game.home_score ?? '-',
    away: props.game.away_score ?? '-',
}));

const summaryMetrics = [
    {
        key: 'played',
        label: 'PJ',
    },
    {
        key: 'wins',
        label: 'PG',
    },
    {
        key: 'draws',
        label: 'PE',
    },
    {
        key: 'losses',
        label: 'PP',
    },
    {
        key: 'goal_difference',
        label: 'DG',
    },
] as const;

function teamName(team?: Team | null, fallback = 'Equipo') {
    return team?.shortname || team?.name || fallback;
}

function teamInitials(team?: Team | null) {
    const baseName = team?.tla || team?.shortname || team?.name;

    if (!baseName) {
        return 'EQ';
    }

    return baseName
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 3);
}

function competitionInitials(name?: string | null) {
    if (!name) {
        return 'CP';
    }

    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0])
        .join('')
        .toUpperCase();
}

function competitionTypeLabel(type?: string | null) {
    if (!type) {
        return 'Formato sin definir';
    }

    if (type === 'LEAGUE') {
        return 'Liga';
    }

    if (type === 'CUP') {
        return 'Copa';
    }

    return type;
}

function matchStatusLabel(status?: string | null) {
    if (!status) {
        return 'Pendiente';
    }

    if (status === 'FINISHED') {
        return 'Finalizado';
    }

    if (status === 'IN_PLAY') {
        return 'En directo';
    }

    if (status === 'TIMED') {
        return 'Programado';
    }

    if (status === 'POSTPONED') {
        return 'Aplazado';
    }

    return status;
}

function matchStatusTone(status?: string | null) {
    if (status === 'FINISHED') {
        return 'border-[#d9b100] bg-[#ffd84d] text-[#4a3900]';
    }

    if (status === 'IN_PLAY') {
        return 'border-[#ff9c7d] bg-[#ff5c35] text-white';
    }

    if (status === 'TIMED') {
        return 'border-[#5ca1ff] bg-[#0051b2] text-white';
    }

    return 'border-[#c8d5e6] bg-white text-[#00357b]';
}

function formatDateTime(date?: string | null) {
    if (!date) {
        return 'Sin fecha';
    }

    return new Date(date).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatShortDate(date?: string | null) {
    if (!date) {
        return 'Fecha pendiente';
    }

    return new Date(date).toLocaleDateString('es-ES', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
    });
}

function formatTime(date?: string | null) {
    if (!date) {
        return '--:--';
    }

    return new Date(date).toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function isTeamHome(game: Game, teamId?: number | string | null) {
    if (!teamId) {
        return false;
    }

    return Number(game.homeTeam?.id ?? game.home_team?.id) === Number(teamId);
}

function teamGoals(game: Game, teamId?: number | string | null) {
    if (!teamId) {
        return null;
    }

    return isTeamHome(game, teamId) ? game.home_score : game.away_score;
}

function opponentGoals(game: Game, teamId?: number | string | null) {
    if (!teamId) {
        return null;
    }

    return isTeamHome(game, teamId) ? game.away_score : game.home_score;
}

function opponentTeam(game: Game, teamId?: number | string | null) {
    return isTeamHome(game, teamId)
        ? game.awayTeam || game.away_team || null
        : game.homeTeam || game.home_team || null;
}

function resultToken(game: Game, teamId?: number | string | null) {
    if (game.status !== 'FINISHED') {
        return 'PR';
    }

    const goalsFor = teamGoals(game, teamId);
    const goalsAgainst = opponentGoals(game, teamId);

    if (goalsFor === null || goalsAgainst === null) {
        return 'PR';
    }

    if ((goalsFor as number) > (goalsAgainst as number)) {
        return 'V';
    }

    if ((goalsFor as number) === (goalsAgainst as number)) {
        return 'E';
    }

    return 'D';
}

function resultTone(result: string) {
    if (result === 'V') {
        return 'border-[#4cbb87] bg-[#dff8ec] text-[#0f7a47]';
    }

    if (result === 'E') {
        return 'border-[#d9b100] bg-[#fff4b5] text-[#765b00]';
    }

    if (result === 'D') {
        return 'border-[#ffb2a0] bg-[#ffe1d9] text-[#b13e1a]';
    }

    return 'border-[#c8d5e6] bg-white text-[#4a6ea9]';
}

function gameStatus(game: Game) {
    if (game.status === 'FINISHED') {
        return `${game.home_score ?? 0} - ${game.away_score ?? 0}`;
    }

    if (game.status === 'IN_PLAY') {
        return 'En juego';
    }

    return formatTime(game.utc_date);
}

function summaryValue(summary: TeamSummary, key: (typeof summaryMetrics)[number]['key']) {
    const value = summary[key];

    if (key === 'goal_difference') {
        return value > 0 ? `+${value}` : `${value}`;
    }

    return `${value}`;
}
</script>

<template>
    <Head :title="pageTitle" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-black tracking-[0.22em] text-white/60 uppercase">
                        Centro del partido
                    </p>
                    <h2 class="text-xl leading-tight font-semibold text-white">
                        {{ pageTitle }}
                    </h2>
                    <p class="text-sm font-semibold text-white/75">
                        Vista detallada del encuentro con forma reciente, contexto de competición y resto de la jornada.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link
                        v-if="competition"
                        :href="route('competitions.show', competition.id)"
                        class="inline-flex items-center justify-center rounded-full border border-white/25 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/20"
                    >
                        Ver competición
                    </Link>
                    <Link
                        :href="route('matches.index')"
                        class="inline-flex items-center justify-center rounded-full border border-white/25 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/20"
                    >
                        Volver a partidos
                    </Link>
                </div>
            </div>
        </template>

        <div class="min-h-screen px-4 py-6 lg:px-6 lg:py-8">
            <div class="mx-auto flex w-full max-w-[1500px] flex-col gap-5">
                <section class="overflow-hidden rounded-[2rem] border-4 border-[#083b8d] bg-[#00357b] shadow-[0_18px_45px_rgba(8,59,141,0.18)]">
                    <div class="border-b border-[#0c4ea9] bg-[radial-gradient(circle_at_top,_rgba(255,212,0,0.14),_transparent_42%)] px-5 py-4 text-white lg:px-6">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white p-2 shadow-[0_10px_24px_rgba(255,255,255,0.18)]">
                                    <img
                                        v-if="competition?.emblem"
                                        :src="competition.emblem"
                                        :alt="competition.name || 'Competición'"
                                        class="h-full w-full object-contain"
                                    >
                                    <span v-else class="text-sm font-black text-[#00357b]">
                                        {{ competitionInitials(competition?.name) }}
                                    </span>
                                </div>

                                <div class="min-w-0">
                                    <p class="text-xs font-black tracking-[0.2em] text-[#d8e7ff] uppercase">
                                        {{ competitionTypeLabel(competition?.type) }}
                                    </p>
                                    <h1 class="truncate text-2xl font-black lg:text-3xl">
                                        {{ competition?.name || 'Sin competición' }}
                                    </h1>
                                    <p class="text-sm font-semibold text-[#d8e7ff]">
                                        {{ game.matchday ? `Jornada ${game.matchday}` : 'Partido sin jornada asignada' }}
                                    </p>
                                </div>
                            </div>

                            <span
                                class="inline-flex w-fit items-center rounded-full border px-4 py-2 text-xs font-black tracking-wide uppercase"
                                :class="matchStatusTone(game.status)"
                            >
                                {{ matchStatusLabel(game.status) }}
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-5 px-4 py-5 lg:grid-cols-[minmax(0,1fr)_280px_minmax(0,1fr)] lg:px-6 lg:py-6">
                        <article class="rounded-[1.75rem] border border-[#0c4ea9] bg-[#00479d] p-5 text-white">
                            <div class="flex items-center gap-4">
                                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-[1.5rem] bg-white p-3 shadow-[0_10px_24px_rgba(255,255,255,0.18)]">
                                    <img
                                        v-if="homeTeam?.crest"
                                        :src="homeTeam.crest"
                                        :alt="teamName(homeTeam, 'Local')"
                                        class="h-full w-full object-contain"
                                    >
                                    <span v-else class="text-xl font-black text-[#00357b]">
                                        {{ teamInitials(homeTeam) }}
                                    </span>
                                </div>

                                <div class="min-w-0">
                                    <p class="text-xs font-black tracking-[0.22em] text-[#d8e7ff] uppercase">
                                        Local
                                    </p>
                                    <h3 class="mt-2 line-clamp-2 text-2xl font-black leading-tight">
                                        {{ teamName(homeTeam, 'Equipo local') }}
                                    </h3>
                                    <p class="mt-2 text-sm font-semibold text-[#d8e7ff]">
                                        {{ homeTeam?.tla || 'Sin siglas' }}
                                        <span v-if="homeTeam?.founded">· Fundado en {{ homeTeam.founded }}</span>
                                    </p>
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[1.75rem] border border-[#0c4ea9] bg-[#03285f] p-5 text-center text-white shadow-[0_16px_30px_rgba(3,40,95,0.35)]">
                            <p class="text-xs font-black tracking-[0.22em] text-[#d8e7ff] uppercase">
                                Marcador
                            </p>

                            <div class="mt-4 flex items-end justify-center gap-3">
                                <span class="text-5xl font-black lg:text-6xl">
                                    {{ heroScore.home }}
                                </span>
                                <span class="pb-2 text-xl font-black text-[#ffd400]">-</span>
                                <span class="text-5xl font-black lg:text-6xl">
                                    {{ heroScore.away }}
                                </span>
                            </div>

                            <p class="mt-4 text-base font-black text-[#ffd400]">
                                {{ game.status === 'TIMED' ? formatTime(game.utc_date) : matchStatusLabel(game.status) }}
                            </p>
                            <p class="mt-1 text-sm font-semibold text-[#d8e7ff]">
                                {{ formatDateTime(game.utc_date) }}
                            </p>
                        </article>

                        <article class="rounded-[1.75rem] border border-[#0c4ea9] bg-[#00479d] p-5 text-white">
                            <div class="flex items-center justify-end gap-4 text-right">
                                <div class="min-w-0">
                                    <p class="text-xs font-black tracking-[0.22em] text-[#d8e7ff] uppercase">
                                        Visitante
                                    </p>
                                    <h3 class="mt-2 line-clamp-2 text-2xl font-black leading-tight">
                                        {{ teamName(awayTeam, 'Equipo visitante') }}
                                    </h3>
                                    <p class="mt-2 text-sm font-semibold text-[#d8e7ff]">
                                        {{ awayTeam?.tla || 'Sin siglas' }}
                                        <span v-if="awayTeam?.founded">· Fundado en {{ awayTeam.founded }}</span>
                                    </p>
                                </div>

                                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-[1.5rem] bg-white p-3 shadow-[0_10px_24px_rgba(255,255,255,0.18)]">
                                    <img
                                        v-if="awayTeam?.crest"
                                        :src="awayTeam.crest"
                                        :alt="teamName(awayTeam, 'Visitante')"
                                        class="h-full w-full object-contain"
                                    >
                                    <span v-else class="text-xl font-black text-[#00357b]">
                                        {{ teamInitials(awayTeam) }}
                                    </span>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <div v-if="shouldShowPrediction && competition">
                    <MatchPredictionCard :match="game" :competition="competition" />
                </div>

                <div class="grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_420px]">
                    <section class="space-y-5">
                        <section class="rounded-[2rem] border-4 border-[#083b8d] bg-[#eef1f5] p-4 shadow-[0_18px_45px_rgba(8,59,141,0.18)] lg:p-5">
                            <div class="mb-4 flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <h3 class="text-xl font-black tracking-wide text-[#00357b] uppercase">
                                        Forma reciente
                                    </h3>
                                    <p class="text-sm font-semibold text-[#4a6ea9]">
                                        Últimos cinco partidos registrados de cada equipo.
                                    </p>
                                </div>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-2">
                                <article class="rounded-[1.5rem] border-2 border-[#042b67] bg-[#00357b] p-4 text-white shadow-[0_8px_18px_rgba(3,34,82,0.2)]">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-black tracking-[0.18em] text-[#d8e7ff] uppercase">
                                                {{ teamName(homeTeam, 'Local') }}
                                            </p>
                                            <h4 class="mt-1 text-lg font-black">
                                                Balance en competición
                                            </h4>
                                        </div>
                                        <div class="flex gap-1">
                                            <span
                                                v-for="gameItem in recentGames.home"
                                                :key="`home-form-${gameItem.id}`"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-full border text-xs font-black"
                                                :class="resultTone(resultToken(gameItem, homeTeam?.id))"
                                            >
                                                {{ resultToken(gameItem, homeTeam?.id) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mt-4 grid grid-cols-5 gap-2">
                                        <div
                                            v-for="metric in summaryMetrics"
                                            :key="`home-${metric.key}`"
                                            class="rounded-2xl border border-[#0c4ea9] bg-[#00479d] px-3 py-3 text-center"
                                        >
                                            <p class="text-[11px] font-black tracking-wide text-[#d8e7ff] uppercase">
                                                {{ metric.label }}
                                            </p>
                                            <p class="mt-1 text-lg font-black">
                                                {{ summaryValue(teamSummaries.home, metric.key) }}
                                            </p>
                                        </div>
                                    </div>

                                    <div v-if="recentGames.home.length" class="mt-4 space-y-2">
                                        <article
                                            v-for="gameItem in recentGames.home"
                                            :key="`home-game-${gameItem.id}`"
                                            class="rounded-2xl border border-[#0c4ea9] bg-[#00479d] px-3 py-3"
                                        >
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-xs font-black tracking-wide text-[#d8e7ff] uppercase">
                                                        {{ gameItem.competition?.code || 'Partido' }} · {{ formatShortDate(gameItem.utc_date) }}
                                                    </p>
                                                    <p class="mt-1 truncate text-sm font-bold text-white">
                                                        vs {{ teamName(opponentTeam(gameItem, homeTeam?.id), 'Rival') }}
                                                    </p>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="inline-flex rounded-full border px-2.5 py-1 text-xs font-black"
                                                        :class="resultTone(resultToken(gameItem, homeTeam?.id))"
                                                    >
                                                        {{ resultToken(gameItem, homeTeam?.id) }}
                                                    </span>
                                                    <span class="text-sm font-black text-[#ffd400]">
                                                        {{ gameStatus(gameItem) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </article>
                                    </div>

                                    <div
                                        v-else
                                        class="mt-4 rounded-2xl border border-dashed border-[#5ca1ff] bg-[#00479d] px-4 py-8 text-center text-sm font-semibold text-[#d8e7ff]"
                                    >
                                        No hay historial reciente para este equipo.
                                    </div>
                                </article>

                                <article class="rounded-[1.5rem] border-2 border-[#042b67] bg-[#00357b] p-4 text-white shadow-[0_8px_18px_rgba(3,34,82,0.2)]">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-black tracking-[0.18em] text-[#d8e7ff] uppercase">
                                                {{ teamName(awayTeam, 'Visitante') }}
                                            </p>
                                            <h4 class="mt-1 text-lg font-black">
                                                Balance en competición
                                            </h4>
                                        </div>
                                        <div class="flex gap-1">
                                            <span
                                                v-for="gameItem in recentGames.away"
                                                :key="`away-form-${gameItem.id}`"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-full border text-xs font-black"
                                                :class="resultTone(resultToken(gameItem, awayTeam?.id))"
                                            >
                                                {{ resultToken(gameItem, awayTeam?.id) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mt-4 grid grid-cols-5 gap-2">
                                        <div
                                            v-for="metric in summaryMetrics"
                                            :key="`away-${metric.key}`"
                                            class="rounded-2xl border border-[#0c4ea9] bg-[#00479d] px-3 py-3 text-center"
                                        >
                                            <p class="text-[11px] font-black tracking-wide text-[#d8e7ff] uppercase">
                                                {{ metric.label }}
                                            </p>
                                            <p class="mt-1 text-lg font-black">
                                                {{ summaryValue(teamSummaries.away, metric.key) }}
                                            </p>
                                        </div>
                                    </div>

                                    <div v-if="recentGames.away.length" class="mt-4 space-y-2">
                                        <article
                                            v-for="gameItem in recentGames.away"
                                            :key="`away-game-${gameItem.id}`"
                                            class="rounded-2xl border border-[#0c4ea9] bg-[#00479d] px-3 py-3"
                                        >
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-xs font-black tracking-wide text-[#d8e7ff] uppercase">
                                                        {{ gameItem.competition?.code || 'Partido' }} · {{ formatShortDate(gameItem.utc_date) }}
                                                    </p>
                                                    <p class="mt-1 truncate text-sm font-bold text-white">
                                                        vs {{ teamName(opponentTeam(gameItem, awayTeam?.id), 'Rival') }}
                                                    </p>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="inline-flex rounded-full border px-2.5 py-1 text-xs font-black"
                                                        :class="resultTone(resultToken(gameItem, awayTeam?.id))"
                                                    >
                                                        {{ resultToken(gameItem, awayTeam?.id) }}
                                                    </span>
                                                    <span class="text-sm font-black text-[#ffd400]">
                                                        {{ gameStatus(gameItem) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </article>
                                    </div>

                                    <div
                                        v-else
                                        class="mt-4 rounded-2xl border border-dashed border-[#5ca1ff] bg-[#00479d] px-4 py-8 text-center text-sm font-semibold text-[#d8e7ff]"
                                    >
                                        No hay historial reciente para este equipo.
                                    </div>
                                </article>
                            </div>
                        </section>

                        <section class="rounded-[2rem] border-4 border-[#083b8d] bg-[#eef1f5] p-4 shadow-[0_18px_45px_rgba(8,59,141,0.18)] lg:p-5">
                            <div class="mb-4 flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <h3 class="text-xl font-black tracking-wide text-[#00357b] uppercase">
                                        Cara a cara
                                    </h3>
                                    <p class="text-sm font-semibold text-[#4a6ea9]">
                                        Últimos precedentes entre ambos clubes.
                                    </p>
                                </div>
                            </div>

                            <div v-if="headToHeadGames.length" class="space-y-3">
                                <article
                                    v-for="gameItem in headToHeadGames"
                                    :key="`h2h-${gameItem.id}`"
                                    class="rounded-[1.5rem] border-2 border-[#042b67] bg-[#00357b] px-4 py-4 text-white shadow-[0_8px_18px_rgba(3,34,82,0.18)]"
                                >
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <span class="rounded-full border border-[#5ca1ff] bg-[#0051b2] px-3 py-1 text-[11px] font-black tracking-wide text-white uppercase">
                                                {{ gameItem.competition?.code || 'Partido' }}
                                            </span>
                                            <p class="text-sm font-semibold text-[#d8e7ff]">
                                                {{ formatDateTime(gameItem.utc_date) }}
                                            </p>
                                        </div>

                                        <div class="grid flex-1 grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3 lg:max-w-[620px]">
                                            <p class="truncate text-right text-sm font-black">
                                                {{ teamName(gameItem.homeTeam || gameItem.home_team, 'Local') }}
                                            </p>
                                            <div class="rounded-full border-2 border-[#ffd400] bg-[#00357b] px-3 py-2 text-center text-sm font-black text-[#ffd400]">
                                                {{ gameStatus(gameItem) }}
                                            </div>
                                            <p class="truncate text-sm font-black">
                                                {{ teamName(gameItem.awayTeam || gameItem.away_team, 'Visitante') }}
                                            </p>
                                        </div>
                                    </div>
                                </article>
                            </div>

                            <div
                                v-else
                                class="rounded-2xl border-2 border-dashed border-[#5ca1ff] bg-white px-6 py-12 text-center text-sm font-semibold text-[#4a6ea9]"
                            >
                                No hay enfrentamientos previos registrados entre estos equipos.
                            </div>
                        </section>
                    </section>

                    <aside class="space-y-5">
                        <section class="rounded-[2rem] border-4 border-[#083b8d] bg-[#eef1f5] p-4 shadow-[0_18px_45px_rgba(8,59,141,0.18)] lg:p-5">
                            <div class="mb-4">
                                <h3 class="text-xl font-black tracking-wide text-[#00357b] uppercase">
                                    Esta jornada
                                </h3>
                                <p class="text-sm font-semibold text-[#4a6ea9]">
                                    {{ game.matchday ? `Partidos de la jornada ${game.matchday}` : 'Partidos relacionados en la competición' }}
                                </p>
                            </div>

                            <div v-if="matchdayGames.length" class="space-y-3">
                                <Link
                                    v-for="gameItem in matchdayGames"
                                    :key="`matchday-${gameItem.id}`"
                                    :href="route('matches.show', gameItem.id)"
                                    class="block rounded-[1.5rem] border-2 border-[#042b67] bg-[#00357b] px-4 py-4 text-white shadow-[0_8px_18px_rgba(3,34,82,0.18)] transition duration-150 hover:-translate-y-0.5 hover:shadow-[0_14px_26px_rgba(3,34,82,0.22)]"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-xs font-black tracking-[0.18em] text-[#d8e7ff] uppercase">
                                            {{ formatShortDate(gameItem.utc_date) }}
                                        </p>
                                        <span class="rounded-full border border-[#5ca1ff] bg-[#0051b2] px-2.5 py-1 text-[11px] font-black uppercase">
                                            {{ matchStatusLabel(gameItem.status) }}
                                        </span>
                                    </div>

                                    <div class="mt-3 grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3">
                                        <p class="truncate text-right text-sm font-black">
                                            {{ teamName(gameItem.homeTeam || gameItem.home_team, 'Local') }}
                                        </p>
                                        <div class="rounded-full border-2 border-[#ffd400] bg-[#00357b] px-3 py-2 text-center text-sm font-black text-[#ffd400]">
                                            {{ gameStatus(gameItem) }}
                                        </div>
                                        <p class="truncate text-sm font-black">
                                            {{ teamName(gameItem.awayTeam || gameItem.away_team, 'Visitante') }}
                                        </p>
                                    </div>
                                </Link>
                            </div>

                            <div
                                v-else
                                class="rounded-2xl border-2 border-dashed border-[#5ca1ff] bg-white px-5 py-10 text-center text-sm font-semibold text-[#4a6ea9]"
                            >
                                No hay más partidos disponibles en esta jornada.
                            </div>
                        </section>

                    </aside>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
