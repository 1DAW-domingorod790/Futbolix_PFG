<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

type BracketTeam = { id: number; name: string; badge: string | null } | null;
type PlayoffMatchDetail = {
    id: number;
    round_name: string | null;
    round_number: number;
    position: number;
    status: string;
    home_score: number | null;
    away_score: number | null;
    home_team: BracketTeam;
    away_team: BracketTeam;
    winner_team: BracketTeam;
};

const props = defineProps<{
    tournament: { id: number; name: string; code: number; can_manage: boolean };
    match: PlayoffMatchDetail;
}>();

const page = usePage<{ flash: { success?: string | null } }>();
const hasResult = computed(() => props.match.home_score !== null && props.match.away_score !== null);
const form = useForm({
    home_score: props.match.home_score ?? '',
    away_score: props.match.away_score ?? '',
});

function teamName(team: BracketTeam, fallback: string) {
    return team?.name ?? fallback;
}

function teamInitials(team: BracketTeam) {
    if (!team?.name) return 'EQ';

    return team.name.split(' ').filter(Boolean).slice(0, 2).map((word) => word[0]).join('').toUpperCase();
}

function scoreLabel() {
    if (hasResult.value) {
        return `${props.match.home_score} - ${props.match.away_score}`;
    }

    return 'VS';
}

function saveResult() {
    form.patch(route('tournaments.playoffs.matches.result', [props.tournament.id, props.match.id]), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`${tournament.name} - Playoffs`" />

    <AuthenticatedLayout>
        <div class="bg-slate-100 px-4 py-8 dark:bg-[#0f172a] lg:px-8">
            <div class="mx-auto max-w-4xl space-y-6">
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
                                {{ match.round_name || `Ronda ${match.round_number}` }}
                            </p>
                            <h1 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">
                                {{ teamName(match.home_team, 'Pendiente') }} vs {{ teamName(match.away_team, 'Pendiente') }}
                            </h1>
                            <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                                Eliminatoria {{ match.position }} del cuadro de playoffs.
                            </p>
                        </div>

                        <span class="rounded-full px-3 py-1 text-sm font-semibold" :class="hasResult ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'">
                            {{ hasResult ? 'Resultado guardado' : 'Pendiente de resultado' }}
                        </span>
                    </div>

                    <div class="mt-8 grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-sm font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                <img v-if="match.home_team?.badge" :src="match.home_team.badge" :alt="match.home_team.name" class="h-full w-full object-cover">
                                <span v-else>{{ teamInitials(match.home_team) }}</span>
                            </div>
                            <p class="truncate font-semibold text-slate-900 dark:text-white">{{ teamName(match.home_team, 'Pendiente') }}</p>
                        </div>

                        <div class="rounded-xl bg-slate-100 px-5 py-3 text-xl font-bold text-slate-900 dark:bg-slate-800 dark:text-white">
                            {{ scoreLabel() }}
                        </div>

                        <div class="flex min-w-0 items-center justify-end gap-3 text-right">
                            <p class="truncate font-semibold text-slate-900 dark:text-white">{{ teamName(match.away_team, 'Pendiente') }}</p>
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-sm font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                <img v-if="match.away_team?.badge" :src="match.away_team.badge" :alt="match.away_team.name" class="h-full w-full object-cover">
                                <span v-else>{{ teamInitials(match.away_team) }}</span>
                            </div>
                        </div>
                    </div>

                    <p v-if="match.winner_team" class="mt-6 rounded-lg bg-futbolix-green/10 px-4 py-3 text-sm font-semibold text-futbolix-green">
                        Ganador: {{ match.winner_team.name }}
                    </p>
                </section>

                <section
                    v-if="tournament.can_manage && !hasResult"
                    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-futbolix-dark"
                >
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Anadir resultado</h2>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        En playoffs no se permiten empates; el ganador avanzara a la siguiente ronda si existe.
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

                        <div class="flex justify-end">
                            <PrimaryButton :disabled="form.processing">Guardar resultado</PrimaryButton>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
