<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

type MatchTeam = {
    id: number | null;
    name: string | null;
    badge: string | null;
    players: MatchPlayer[];
};

type MatchPlayer = {
    id: number;
    name: string;
    number: number;
    goals: number;
};

type MatchScorer = {
    player_id: number;
    goals: number;
};

type MatchItem = {
    id: number;
    matchday: number | null;
    scheduled_at: string | null;
    venue: string | null;
    status: string | null;
    home_score: number | null;
    away_score: number | null;
    home_scorers: MatchScorer[];
    away_scorers: MatchScorer[];
    home_team: MatchTeam;
    away_team: MatchTeam;
};

type MatchScorerFormItem = {
    player_id: string;
    goals: string;
};

const props = defineProps<{
    tournamentId: number;
    match: MatchItem;
    canManage: boolean;
}>();

const form = useForm({
    home_score: props.match.home_score ?? '',
    away_score: props.match.away_score ?? '',
    home_scorers: buildScorerFormItems(props.match.home_scorers),
    away_scorers: buildScorerFormItems(props.match.away_scorers),
});
const showResultForm = ref(false);

function buildScorerFormItems(scorers: MatchScorer[]): MatchScorerFormItem[] {
    return scorers.map((scorer) => ({
        player_id: String(scorer.player_id),
        goals: String(scorer.goals),
    }));
}

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

const resultActionLabel = computed(() =>
    props.match.home_score !== null && props.match.away_score !== null
        ? 'Editar resultado'
        : 'Guardar resultado',
);

function syncFormWithMatch() {
    form.home_score = props.match.home_score ?? '';
    form.away_score = props.match.away_score ?? '';
    form.home_scorers = buildScorerFormItems(props.match.home_scorers);
    form.away_scorers = buildScorerFormItems(props.match.away_scorers);
}

function openResultForm() {
    syncFormWithMatch();
    form.clearErrors();
    showResultForm.value = true;
}

function closeResultForm() {
    showResultForm.value = false;
    syncFormWithMatch();
    form.clearErrors();
}

function saveResult() {
    form
        .transform((data) => ({
            ...data,
            home_scorers: sanitizeScorers(data.home_scorers),
            away_scorers: sanitizeScorers(data.away_scorers),
        }))
        .patch(route('tournaments.matches.result', [props.tournamentId, props.match.id]), {
            preserveScroll: true,
            onSuccess: () => closeResultForm(),
        });
}

function sanitizeScorers(scorers: MatchScorerFormItem[]) {
    return scorers
        .filter((scorer) => scorer.player_id !== '' && scorer.goals !== '')
        .map((scorer) => ({
            player_id: Number(scorer.player_id),
            goals: Number(scorer.goals),
        }));
}

function addScorer(team: 'home' | 'away') {
    const scorer = { player_id: '', goals: '1' };

    if (team === 'home') {
        form.home_scorers.push(scorer);
        return;
    }

    form.away_scorers.push(scorer);
}

function removeScorer(team: 'home' | 'away', index: number) {
    if (team === 'home') {
        form.home_scorers.splice(index, 1);
        return;
    }

    form.away_scorers.splice(index, 1);
}

watch(
    () => [props.match.home_score, props.match.away_score],
    () => syncFormWithMatch(),
);
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
                <Link
                    v-if="match.home_team.id"
                    :href="route('tournaments.teams.show', [tournamentId, match.home_team.id])"
                    class="flex min-w-0 items-center gap-3 transition hover:opacity-80"
                >
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
                </Link>
                <template v-else>
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
                </template>
            </div>

            <div class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-bold text-slate-900 dark:bg-slate-800 dark:text-white">
                {{ scoreLabel() }}
            </div>

            <div class="flex min-w-0 items-center justify-end gap-3 text-right">
                <Link
                    v-if="match.away_team.id"
                    :href="route('tournaments.teams.show', [tournamentId, match.away_team.id])"
                    class="flex min-w-0 items-center justify-end gap-3 text-right transition hover:opacity-80"
                >
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
                </Link>
                <template v-else>
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
                </template>
            </div>
        </div>

        <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">
            Campo: {{ match.venue || 'Pendiente de confirmar' }}
        </p>

        <div v-if="canManage" class="mt-5 flex justify-end">
            <button
                type="button"
                class="inline-flex items-center rounded-lg bg-futbolix-green px-4 py-2 text-sm font-semibold text-white transition hover:bg-futbolix-green-dark"
                @click="openResultForm"
            >
                {{ resultActionLabel }}
            </button>
        </div>

        <div
            v-if="canManage && showResultForm"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 backdrop-blur-sm"
            @click.self="closeResultForm"
        >
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-futbolix-dark">
                <div class="mb-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-futbolix-gold">Marcador del torneo</p>
                    <h3 class="mt-2 text-xl font-bold text-slate-900 dark:text-white">
                        {{ match.home_team.name || 'Local' }} vs {{ match.away_team.name || 'Visitante' }}
                    </h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        {{ formatDate(match.scheduled_at) }} a las {{ formatTime(match.scheduled_at) }}
                    </p>
                </div>

                <form class="space-y-4" @submit.prevent="saveResult">
                    <div class="grid gap-4 sm:grid-cols-2">
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

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Goleadores local</h4>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Reparte los {{ form.home_score || 0 }} goles del equipo local.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
                                    @click="addScorer('home')"
                                >
                                    Anadir goleador
                                </button>
                            </div>

                            <div v-if="form.home_scorers.length" class="mt-4 space-y-3">
                                <div
                                    v-for="(scorer, index) in form.home_scorers"
                                    :key="`home-scorer-${index}`"
                                    class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_110px_auto]"
                                >
                                    <div>
                                        <select
                                            v-model="scorer.player_id"
                                            class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                        >
                                            <option value="">Selecciona un jugador</option>
                                            <option
                                                v-for="player in match.home_team.players"
                                                :key="`home-player-${player.id}`"
                                                :value="String(player.id)"
                                            >
                                                {{ player.name }} - #{{ player.number }}
                                            </option>
                                        </select>
                                        <p v-if="form.errors[`home_scorers.${index}.player_id`]" class="mt-2 text-sm text-red-500">
                                            {{ form.errors[`home_scorers.${index}.player_id`] }}
                                        </p>
                                    </div>

                                    <div>
                                        <input
                                            v-model="scorer.goals"
                                            type="number"
                                            min="1"
                                            class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                        >
                                        <p v-if="form.errors[`home_scorers.${index}.goals`]" class="mt-2 text-sm text-red-500">
                                            {{ form.errors[`home_scorers.${index}.goals`] }}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-500 transition hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10"
                                        @click="removeScorer('home', index)"
                                    >
                                        Quitar
                                    </button>
                                </div>
                            </div>

                            <p v-else class="mt-4 rounded-lg border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500 dark:border-slate-600 dark:text-slate-400">
                                Anade los jugadores que han marcado por el equipo local.
                            </p>

                            <p v-if="form.errors.home_scorers" class="mt-3 text-sm text-red-500">{{ form.errors.home_scorers }}</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Goleadores visitante</h4>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Reparte los {{ form.away_score || 0 }} goles del equipo visitante.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
                                    @click="addScorer('away')"
                                >
                                    Anadir goleador
                                </button>
                            </div>

                            <div v-if="form.away_scorers.length" class="mt-4 space-y-3">
                                <div
                                    v-for="(scorer, index) in form.away_scorers"
                                    :key="`away-scorer-${index}`"
                                    class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_110px_auto]"
                                >
                                    <div>
                                        <select
                                            v-model="scorer.player_id"
                                            class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                        >
                                            <option value="">Selecciona un jugador</option>
                                            <option
                                                v-for="player in match.away_team.players"
                                                :key="`away-player-${player.id}`"
                                                :value="String(player.id)"
                                            >
                                                {{ player.name }} - #{{ player.number }}
                                            </option>
                                        </select>
                                        <p v-if="form.errors[`away_scorers.${index}.player_id`]" class="mt-2 text-sm text-red-500">
                                            {{ form.errors[`away_scorers.${index}.player_id`] }}
                                        </p>
                                    </div>

                                    <div>
                                        <input
                                            v-model="scorer.goals"
                                            type="number"
                                            min="1"
                                            class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                        >
                                        <p v-if="form.errors[`away_scorers.${index}.goals`]" class="mt-2 text-sm text-red-500">
                                            {{ form.errors[`away_scorers.${index}.goals`] }}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-500 transition hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10"
                                        @click="removeScorer('away', index)"
                                    >
                                        Quitar
                                    </button>
                                </div>
                            </div>

                            <p v-else class="mt-4 rounded-lg border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500 dark:border-slate-600 dark:text-slate-400">
                                Anade los jugadores que han marcado por el equipo visitante.
                            </p>

                            <p v-if="form.errors.away_scorers" class="mt-3 text-sm text-red-500">{{ form.errors.away_scorers }}</p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
                            @click="closeResultForm"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center rounded-lg bg-futbolix-green px-4 py-2 text-sm font-semibold text-white transition hover:bg-futbolix-green-dark disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Guardar resultado
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </article>
</template>
