<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

type MatchPlayer = { id: number; name: string; number: number; goals: number };
type MatchScorer = { player_id: number; goals: number };
type MatchTeam = { id: number | null; name: string | null; badge: string | null; players: MatchPlayer[] };
type MatchDetail = {
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
type ScorerFormItem = { player_id: string; goals: string };

const props = defineProps<{
    tournament: { id: number; name: string; code: number; can_manage: boolean };
    match: MatchDetail;
}>();

const page = usePage<{ flash: { success?: string | null } }>();
const hasResult = computed(() => props.match.home_score !== null && props.match.away_score !== null);

const form = useForm({
    home_score: props.match.home_score ?? '',
    away_score: props.match.away_score ?? '',
    home_scorers: buildScorerFormItems(props.match.home_scorers),
    away_scorers: buildScorerFormItems(props.match.away_scorers),
});

function buildScorerFormItems(scorers: MatchScorer[]): ScorerFormItem[] {
    return scorers.map((scorer) => ({
        player_id: String(scorer.player_id),
        goals: String(scorer.goals),
    }));
}

function formatDate(date: string | null) {
    if (!date) return 'Fecha pendiente';

    return new Date(date).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function teamInitials(name: string | null) {
    if (!name) return 'EQ';

    return name.split(' ').filter(Boolean).slice(0, 2).map((word) => word[0]).join('').toUpperCase();
}

function scoreLabel() {
    if (hasResult.value) {
        return `${props.match.home_score} - ${props.match.away_score}`;
    }

    return 'VS';
}

function sanitizeScorers(scorers: ScorerFormItem[]) {
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

function saveResult() {
    form
        .transform((data) => ({
            ...data,
            home_scorers: sanitizeScorers(data.home_scorers),
            away_scorers: sanitizeScorers(data.away_scorers),
        }))
        .patch(route('tournaments.matches.result', [props.tournament.id, props.match.id]), {
            preserveScroll: true,
        });
}
</script>

<template>
    <Head :title="`${tournament.name} - Partido`" />

    <AuthenticatedLayout>
        <div class="bg-slate-100 px-4 py-8 dark:bg-[#0f172a] lg:px-8">
            <div class="mx-auto max-w-5xl space-y-6">
                <div v-if="page.props.flash?.success" class="rounded-2xl border border-futbolix-green/30 bg-futbolix-green/10 px-4 py-3 text-sm text-futbolix-green">
                    {{ page.props.flash.success }}
                </div>

                <Link :href="route('tournaments.show', tournament.id)" class="text-sm font-semibold text-futbolix-green hover:text-futbolix-green-dark">
                    Volver al torneo
                </Link>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-futbolix-dark">
                    <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-futbolix-gold">
                                {{ match.matchday ? `Jornada ${match.matchday}` : 'Partido del torneo' }}
                            </p>
                            <h1 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">
                                {{ match.home_team.name || 'Local' }} vs {{ match.away_team.name || 'Visitante' }}
                            </h1>
                            <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                                {{ formatDate(match.scheduled_at) }} - {{ match.venue || 'Campo pendiente' }}
                            </p>
                        </div>

                        <span class="rounded-full px-3 py-1 text-sm font-semibold" :class="hasResult ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'">
                            {{ hasResult ? 'Resultado guardado' : 'Pendiente de resultado' }}
                        </span>
                    </div>

                    <div class="mt-8 grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-sm font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                <img v-if="match.home_team.badge" :src="match.home_team.badge" :alt="match.home_team.name || 'Local'" class="h-full w-full object-cover">
                                <span v-else>{{ teamInitials(match.home_team.name) }}</span>
                            </div>
                            <p class="truncate font-semibold text-slate-900 dark:text-white">{{ match.home_team.name || 'Local' }}</p>
                        </div>

                        <div class="rounded-xl bg-slate-100 px-5 py-3 text-xl font-bold text-slate-900 dark:bg-slate-800 dark:text-white">
                            {{ scoreLabel() }}
                        </div>

                        <div class="flex min-w-0 items-center justify-end gap-3 text-right">
                            <p class="truncate font-semibold text-slate-900 dark:text-white">{{ match.away_team.name || 'Visitante' }}</p>
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-sm font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                <img v-if="match.away_team.badge" :src="match.away_team.badge" :alt="match.away_team.name || 'Visitante'" class="h-full w-full object-cover">
                                <span v-else>{{ teamInitials(match.away_team.name) }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    v-if="tournament.can_manage && !hasResult"
                    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-futbolix-dark"
                >
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Anadir resultado</h2>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Guarda el marcador y reparte los goles entre los jugadores.
                    </p>

                    <form class="mt-6 space-y-5" @submit.prevent="saveResult">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Goles local</label>
                                <input v-model="form.home_score" type="number" min="0" class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                                <p v-if="form.errors.home_score" class="mt-2 text-sm text-red-500">{{ form.errors.home_score }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Goles visitante</label>
                                <input v-model="form.away_score" type="number" min="0" class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                                <p v-if="form.errors.away_score" class="mt-2 text-sm text-red-500">{{ form.errors.away_score }}</p>
                            </div>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Goleadores local</h3>
                                    <button type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800" @click="addScorer('home')">
                                        Anadir goleador
                                    </button>
                                </div>
                                <div class="mt-4 space-y-3">
                                    <div v-for="(scorer, index) in form.home_scorers" :key="`home-${index}`" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_100px_auto]">
                                        <select v-model="scorer.player_id" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                                            <option value="">Jugador</option>
                                            <option v-for="player in match.home_team.players" :key="player.id" :value="String(player.id)">{{ player.name }} - #{{ player.number }}</option>
                                        </select>
                                        <input v-model="scorer.goals" type="number" min="1" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                                        <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-500" @click="removeScorer('home', index)">Quitar</button>
                                    </div>
                                </div>
                                <p v-if="form.errors.home_scorers" class="mt-3 text-sm text-red-500">{{ form.errors.home_scorers }}</p>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Goleadores visitante</h3>
                                    <button type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800" @click="addScorer('away')">
                                        Anadir goleador
                                    </button>
                                </div>
                                <div class="mt-4 space-y-3">
                                    <div v-for="(scorer, index) in form.away_scorers" :key="`away-${index}`" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_100px_auto]">
                                        <select v-model="scorer.player_id" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                                            <option value="">Jugador</option>
                                            <option v-for="player in match.away_team.players" :key="player.id" :value="String(player.id)">{{ player.name }} - #{{ player.number }}</option>
                                        </select>
                                        <input v-model="scorer.goals" type="number" min="1" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                                        <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-500" @click="removeScorer('away', index)">Quitar</button>
                                    </div>
                                </div>
                                <p v-if="form.errors.away_scorers" class="mt-3 text-sm text-red-500">{{ form.errors.away_scorers }}</p>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <PrimaryButton :disabled="form.processing">Guardar resultado</PrimaryButton>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
