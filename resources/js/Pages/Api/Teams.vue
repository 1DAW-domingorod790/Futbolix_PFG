<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    teams: {
        type: Array,
        default: () => [],
    },
});

const totalTeams = computed(() => props.teams.length);
const teamsWithVenue = computed(() => props.teams.filter((team) => team.venue).length);
const teamsWithFoundationYear = computed(() => props.teams.filter((team) => team.founded).length);
</script>

<template>
    <Head title="Equipos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-white">
                Equipos
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
                                Equipos registrados
                            </h1>
                            <p class="mt-3 text-sm text-slate-400">
                                Vista simple de los clubes guardados, con información básica y su estadio.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-xl border border-slate-700 bg-slate-900/70 p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-400">Total</p>
                                <p class="mt-2 text-2xl font-semibold text-white">{{ totalTeams }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-700 bg-slate-900/70 p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-400">Con estadio</p>
                                <p class="mt-2 text-2xl font-semibold text-white">{{ teamsWithVenue }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-700 bg-slate-900/70 p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-400">Con fundación</p>
                                <p class="mt-2 text-2xl font-semibold text-white">{{ teamsWithFoundationYear }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="team in teams"
                        :key="team.id"
                        class="rounded-2xl border border-slate-700 bg-futbolix-dark p-5"
                    >
                        <div class="flex items-start gap-4">
                            <img
                                v-if="team.crest"
                                :src="team.crest"
                                :alt="team.name"
                                class="h-14 w-14 rounded-xl bg-white object-contain p-2"
                            />
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate text-lg font-semibold text-white">{{ team.name }}</h3>
                                <p class="mt-1 text-sm text-slate-400">
                                    {{ team.shortname || 'Sin nombre corto' }}
                                </p>
                            </div>
                            <span class="rounded-full bg-futbolix-green/15 px-3 py-1 text-xs font-semibold text-futbolix-green">
                                {{ team.tla || 'N/D' }}
                            </span>
                        </div>

                        <dl class="mt-5 space-y-3 text-sm text-slate-300">
                            <div class="flex items-center justify-between gap-4 border-t border-slate-700 pt-3">
                                <dt class="text-slate-500">Fundación</dt>
                                <dd>{{ team.founded || 'N/D' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4 border-t border-slate-700 pt-3">
                                <dt class="text-slate-500">Estadio</dt>
                                <dd class="text-right">{{ team.venue || 'N/D' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4 border-t border-slate-700 pt-3">
                                <dt class="text-slate-500">ID externo</dt>
                                <dd>{{ team.external_id }}</dd>
                            </div>
                        </dl>
                    </article>

                    <div
                        v-if="!teams.length"
                        class="col-span-full rounded-2xl border border-dashed border-slate-700 bg-futbolix-dark px-6 py-12 text-center text-sm text-slate-400"
                    >
                        No hay equipos disponibles.
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
