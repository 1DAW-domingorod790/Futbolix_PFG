<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

type BracketTeam = {
    id: number;
    name: string;
    badge: string | null;
} | null;

type BracketMatch = {
    id: number;
    round_number: number;
    position: number;
    status: string;
    home_score: number | null;
    away_score: number | null;
    home_team: BracketTeam;
    away_team: BracketTeam;
    winner_team: BracketTeam;
    next_match_id: number | null;
};

type BracketRound = {
    id: number;
    name: string;
    round_number: number;
    matches_count: number;
    matches: BracketMatch[];
};

defineProps<{
    tournamentId: number;
    rounds: BracketRound[];
    state: string;
    generatedAt: string | null;
}>();

function teamName(team: BracketTeam): string {
    return team?.name ?? 'Pendiente';
}

function statusLabel(status: string): string {
    if (status === 'ready') return 'Pendiente de resultado';
    if (status === 'finished') return 'Finalizado';

    return 'Ganador pendiente';
}

function scoreLabel(match: BracketMatch): string {
    if (match.home_score !== null && match.away_score !== null) {
        return `${match.home_score} - ${match.away_score}`;
    }

    return 'VS';
}

function stateLabel(state: string): string {
    if (state === 'generated') return 'Generado';
    if (state === 'in_progress') return 'En curso';
    if (state === 'finished') return 'Finalizado';

    return 'No generado';
}
</script>

<template>
    <section class="space-y-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-futbolix-gold">
                    Playoffs
                </p>
                <h2 class="mt-2 text-xl font-bold text-slate-900 dark:text-white">
                    Cuadro eliminatorio
                </h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Rondas y eliminatorias guardadas para conectar resultados y ganadores mas adelante.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <span class="rounded-full bg-futbolix-green/10 px-3 py-1 text-sm font-semibold text-futbolix-green">
                    {{ stateLabel(state) }}
                </span>
                <span
                    v-if="generatedAt"
                    class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                >
                    Generado
                </span>
            </div>
        </div>

        <div v-if="rounds.length" class="overflow-x-auto">
            <div class="flex min-w-max gap-6 pb-2">
                <div
                    v-for="round in rounds"
                    :key="round.id"
                    class="flex w-64 shrink-0 flex-col justify-center gap-4"
                >
                    <div>
                        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                            {{ round.name }}
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ round.matches_count }} eliminatoria{{ round.matches_count === 1 ? '' : 's' }}
                        </p>
                    </div>

                    <div class="flex flex-col justify-around gap-4">
                        <article
                            v-for="match in round.matches"
                            :key="match.id"
                            class="relative rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-900/50"
                        >
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    Partido {{ match.position }}
                                </span>
                                <span class="rounded-full bg-white px-2 py-1 text-xs font-medium text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                                    {{ statusLabel(match.status) }}
                                </span>
                            </div>

                            <div class="mb-3 text-center text-sm font-bold text-slate-900 dark:text-white">
                                {{ scoreLabel(match) }}
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center gap-2 rounded-md bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200">
                                    <img
                                        v-if="match.home_team?.badge"
                                        :src="match.home_team.badge"
                                        :alt="match.home_team.name"
                                        class="h-6 w-6 rounded-full object-cover"
                                    >
                                    <span class="truncate">{{ teamName(match.home_team) }}</span>
                                </div>
                                <div class="flex items-center gap-2 rounded-md bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200">
                                    <img
                                        v-if="match.away_team?.badge"
                                        :src="match.away_team.badge"
                                        :alt="match.away_team.name"
                                        class="h-6 w-6 rounded-full object-cover"
                                    >
                                    <span class="truncate">{{ teamName(match.away_team) }}</span>
                                </div>
                            </div>

                            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                                Ganador: {{ teamName(match.winner_team) }}
                            </p>

                            <Link
                                :href="route('tournaments.playoffs.matches.show', [tournamentId, match.id])"
                                class="mt-3 inline-flex w-full items-center justify-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                            >
                                Ver partido
                            </Link>
                        </article>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-else
            class="rounded-lg border border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500 dark:border-slate-600 dark:text-slate-400"
        >
            Todavia no hay eliminatorias creadas para este torneo.
        </div>
    </section>
</template>
