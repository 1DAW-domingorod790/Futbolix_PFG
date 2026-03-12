<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    games: {
        type: Array,
        default: () => [],
    },
});

const totalGames = computed(() => props.games.length);
const finishedGames = computed(() => props.games.filter((game) => game.status === 'FINISHED').length);
const scheduledGames = computed(() => props.games.filter((game) => game.status === 'SCHEDULED').length);

function formatDate(date) {
    if (!date) {
        return 'Sin fecha';
    }

    return new Date(date).toLocaleString('es-ES', {
        dateStyle: 'short',
        timeStyle: 'short',
    });
}

function formatScore(game) {
    if (game.home_score === null || game.away_score === null) {
        return 'Pendiente';
    }

    return `${game.home_score} - ${game.away_score}`;
}
</script>

<template>
    <Head title="Partidos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-white">
                Partidos
            </h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="rounded-2xl border border-slate-700 bg-futbolix-dark p-6">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-2xl">
                            <p class="text-sm font-medium uppercase tracking-[0.2em] text-futbolix-green">
                                API futbol
                            </p>
                            <h1 class="mt-2 text-3xl font-bold text-white">
                                Calendario de partidos
                            </h1>
                            <p class="mt-3 text-sm text-slate-400">
                                Consulta rápida de los encuentros cargados con sus equipos, competición y estado.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-xl border border-slate-700 bg-slate-900/70 p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-400">Total</p>
                                <p class="mt-2 text-2xl font-semibold text-white">{{ totalGames }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-700 bg-slate-900/70 p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-400">Finalizados</p>
                                <p class="mt-2 text-2xl font-semibold text-white">{{ finishedGames }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-700 bg-slate-900/70 p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-400">Programados</p>
                                <p class="mt-2 text-2xl font-semibold text-white">{{ scheduledGames }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-700 bg-futbolix-dark">
                    <div class="border-b border-slate-700 px-6 py-4">
                        <h3 class="text-lg font-semibold text-white">Listado</h3>
                    </div>

                    <div v-if="games.length" class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm text-slate-300">
                            <thead class="bg-slate-900/80 text-xs uppercase tracking-wide text-slate-400">
                                <tr>
                                    <th class="px-6 py-4">Competición</th>
                                    <th class="px-6 py-4">Partido</th>
                                    <th class="px-6 py-4">Resultado</th>
                                    <th class="px-6 py-4">Fecha</th>
                                    <th class="px-6 py-4">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="game in games"
                                    :key="game.id"
                                    class="border-t border-slate-700 transition hover:bg-slate-900/50"
                                >
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-white">{{ game.competition?.name || 'Sin competición' }}</p>
                                        <p class="text-xs text-slate-500">ID externo: {{ game.external_id }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-white">
                                            {{ game.home_team?.name || game.homeTeam?.name || 'Local' }}
                                            vs
                                            {{ game.away_team?.name || game.awayTeam?.name || 'Visitante' }}
                                        </p>
                                    </td>
                                    <td class="px-6 py-4">{{ formatScore(game) }}</td>
                                    <td class="px-6 py-4">{{ formatDate(game.utc_date) }}</td>
                                    <td class="px-6 py-4">
                                        <span class="rounded-full bg-slate-800 px-3 py-1 text-xs font-semibold text-slate-200">
                                            {{ game.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="px-6 py-12 text-center text-sm text-slate-400">
                        No hay partidos disponibles.
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
