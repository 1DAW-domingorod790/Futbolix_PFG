<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    competitions: {
        type: Array,
        default: () => [],
    },
});

const totalCompetitions = computed(() => props.competitions.length);
const competitionsWithMatchday = computed(() => props.competitions.filter((competition) => competition.currentMatchDay).length);
const competitionsWithDates = computed(() => props.competitions.filter((competition) => competition.startDate || competition.endDate).length);

function formatDate(date) {
    if (!date) {
        return 'Sin fecha';
    }

    return new Date(date).toLocaleDateString('es-ES');
}
</script>

<template>
    <Head title="Competiciones" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-white">
                Competiciones
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
                                Competiciones disponibles
                            </h1>
                            <p class="mt-3 text-sm text-slate-400">
                                Vista rápida de las ligas y torneos almacenados en la aplicación.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-xl border border-slate-700 bg-slate-900/70 p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-400">Total</p>
                                <p class="mt-2 text-2xl font-semibold text-white">{{ totalCompetitions }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-700 bg-slate-900/70 p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-400">Con jornada</p>
                                <p class="mt-2 text-2xl font-semibold text-white">{{ competitionsWithMatchday }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-700 bg-slate-900/70 p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-400">Con fechas</p>
                                <p class="mt-2 text-2xl font-semibold text-white">{{ competitionsWithDates }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-700 bg-futbolix-dark">
                    <div class="border-b border-slate-700 px-6 py-4">
                        <h3 class="text-lg font-semibold text-white">Listado</h3>
                    </div>

                    <div v-if="competitions.length" class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm text-slate-300">
                            <thead class="bg-slate-900/80 text-xs uppercase tracking-wide text-slate-400">
                                <tr>
                                    <th class="px-6 py-4">Competición</th>
                                    <th class="px-6 py-4">Código</th>
                                    <th class="px-6 py-4">Tipo</th>
                                    <th class="px-6 py-4">Jornada actual</th>
                                    <th class="px-6 py-4">Fechas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="competition in competitions"
                                    :key="competition.id"
                                    class="border-t border-slate-700 transition hover:bg-slate-900/50"
                                >
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <img
                                                v-if="competition.emblem"
                                                :src="competition.emblem"
                                                :alt="competition.name"
                                                class="h-10 w-10 rounded-full bg-white object-contain p-1"
                                            />
                                            <div>
                                                <p class="font-medium text-white">{{ competition.name }}</p>
                                                <p class="text-xs text-slate-500">ID externo: {{ competition.external_id }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">{{ competition.code || 'N/D' }}</td>
                                    <td class="px-6 py-4">{{ competition.type || 'N/D' }}</td>
                                    <td class="px-6 py-4">{{ competition.currentMatchDay || 'N/D' }}</td>
                                    <td class="px-6 py-4">
                                        {{ formatDate(competition.startDate) }} - {{ formatDate(competition.endDate) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="px-6 py-12 text-center text-sm text-slate-400">
                        No hay competiciones disponibles.
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
