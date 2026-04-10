<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

type MatchTeam = {
    id: number | null;
    name: string | null;
    badge: string | null;
};

type MatchItem = {
    id: number;
    matchday: number | null;
    scheduled_at: string | null;
    venue: string | null;
    status: string | null;
    home_score: number | null;
    away_score: number | null;
    home_team: MatchTeam;
    away_team: MatchTeam;
};

const props = defineProps<{
    tournamentId: number;
    match: MatchItem;
    canManage: boolean;
}>();

const form = useForm({
    home_score: props.match.home_score ?? '',
    away_score: props.match.away_score ?? '',
});

function formatDate(date: string | null) {
    if (!date) {
        return 'Fecha pendiente';
    }

    return new Date(date).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
}

function formatTime(date: string | null) {
    if (!date) {
        return '--:--';
    }

    return new Date(date).toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function teamInitials(name: string | null) {
    if (!name) {
        return 'EQ';
    }

    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0])
        .join('')
        .toUpperCase();
}

function statusLabel(status: string | null) {
    if (status === 'FINISHED') {
        return 'Finalizado';
    }

    if (status === 'IN_PLAY') {
        return 'En juego';
    }

    return 'Programado';
}

function statusClasses(status: string | null) {
    if (status === 'FINISHED') {
        return 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300';
    }

    if (status === 'IN_PLAY') {
        return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
    }

    return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300';
}

function scoreLabel() {
    if (props.match.status === 'FINISHED' || props.match.status === 'IN_PLAY') {
        return `${props.match.home_score ?? 0} - ${props.match.away_score ?? 0}`;
    }

    return 'VS';
}

function saveResult() {
    form.patch(route('tournaments.matches.result', [props.tournamentId, props.match.id]), {
        preserveScroll: true,
    });
}
</script>

<template>
    <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ formatDate(match.scheduled_at) }} - {{ formatTime(match.scheduled_at) }}
            </p>
            <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusClasses(match.status)">
                {{ statusLabel(match.status) }}
            </span>
        </div>

        <div class="mt-5 grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-sm font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <img
                        v-if="match.home_team.badge"
                        :src="match.home_team.badge"
                        :alt="match.home_team.name || 'Equipo local'"
                        class="h-full w-full object-cover"
                    >
                    <span v-else>{{ teamInitials(match.home_team.name) }}</span>
                </div>
                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                    {{ match.home_team.name || 'Equipo local' }}
                </p>
            </div>

            <div class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-bold text-slate-900 dark:bg-slate-800 dark:text-white">
                {{ scoreLabel() }}
            </div>

            <div class="flex min-w-0 items-center justify-end gap-3 text-right">
                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                    {{ match.away_team.name || 'Equipo visitante' }}
                </p>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-sm font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <img
                        v-if="match.away_team.badge"
                        :src="match.away_team.badge"
                        :alt="match.away_team.name || 'Equipo visitante'"
                        class="h-full w-full object-cover"
                    >
                    <span v-else>{{ teamInitials(match.away_team.name) }}</span>
                </div>
            </div>
        </div>

        <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">
            Campo: {{ match.venue || 'Pendiente de confirmar' }}
        </p>

        <form v-if="canManage" class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60" @submit.prevent="saveResult">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Guardar resultado</h3>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                        Goles local
                    </label>
                    <input
                        v-model="form.home_score"
                        type="number"
                        min="0"
                        class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                    >
                    <p v-if="form.errors.home_score" class="mt-2 text-sm text-red-500">{{ form.errors.home_score }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                        Goles visitante
                    </label>
                    <input
                        v-model="form.away_score"
                        type="number"
                        min="0"
                        class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                    >
                    <p v-if="form.errors.away_score" class="mt-2 text-sm text-red-500">{{ form.errors.away_score }}</p>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center rounded-lg bg-futbolix-green px-4 py-2 text-sm font-semibold text-white transition hover:bg-futbolix-green-dark disabled:cursor-not-allowed disabled:opacity-60"
                >
                    Guardar resultado
                </button>
            </div>
        </form>
    </article>
</template>
