<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import TournamentEmptyState from '@/Components/Tournaments/TournamentEmptyState.vue';
import TournamentList from '@/Components/Tournaments/TournamentList.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

type TournamentListItem = {
    id: number;
    code: number;
    name: string;
    description: string | null;
    format: {
        value: string;
        label: string;
        has_playoffs: boolean;
        has_groups: boolean;
        has_regular_phase: boolean;
        playoff_teams_count: number | null;
        groups_count: number | null;
        regular_phase_matchdays_count: number | null;
    };
    created_at: string;
    is_public: boolean;
    logo_url: string | null;
    admin?: {
        id: number;
        name: string;
    } | null;
};

defineProps<{
    ownedTournaments: TournamentListItem[];
    publicTournaments: TournamentListItem[];
}>();

const page = usePage<{
    flash: {
        success?: string | null;
    };
}>();
</script>

<template>
    <Head title="Torneos" />

    <AuthenticatedLayout>
        <div class="bg-slate-100 px-4 py-8 dark:bg-[#0f172a] lg:px-8">
            <div class="mx-auto max-w-6xl space-y-6">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-futbolix-dark">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-futbolix-gold">
                                Modulo de torneos
                            </p>
                            <h1 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">
                                Torneos
                            </h1>
                            <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                Crea tus torneos, mantenlos ocultos mientras los preparas y decide cuando
                                hacerlos visibles para el resto de usuarios.
                            </p>
                        </div>

                        <Link
                            :href="route('tournaments.create')"
                            class="inline-flex items-center justify-center rounded-lg bg-futbolix-green px-5 py-3 text-sm font-semibold text-white transition hover:bg-futbolix-green-dark"
                        >
                            Crear torneo
                        </Link>
                    </div>
                </section>

                <div
                    v-if="page.props.flash?.success"
                    class="rounded-2xl border border-futbolix-green/30 bg-futbolix-green/10 px-4 py-3 text-sm text-futbolix-green"
                >
                    {{ page.props.flash.success }}
                </div>

                <section class="space-y-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Mis torneos</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ ownedTournaments.length }} torneo{{ ownedTournaments.length === 1 ? '' : 's' }} creado{{ ownedTournaments.length === 1 ? '' : 's' }}.
                        </p>
                    </div>

                    <TournamentEmptyState
                        v-if="ownedTournaments.length === 0"
                        :create-href="route('tournaments.create')"
                    />

                    <TournamentList v-else :tournaments="ownedTournaments" />
                </section>

                <section class="space-y-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Torneos visibles</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Torneos compartidos por otros administradores y accesibles para todos los usuarios.
                        </p>
                    </div>

                    <div
                        v-if="publicTournaments.length === 0"
                        class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm text-slate-500 shadow-sm dark:border-slate-700 dark:bg-futbolix-dark dark:text-slate-400"
                    >
                        Todavia no hay torneos publicos disponibles.
                    </div>

                    <TournamentList
                        v-else
                        :tournaments="publicTournaments"
                        show-admin
                    />
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
