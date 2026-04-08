<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import TournamentEmptyState from '@/Components/Tournaments/TournamentEmptyState.vue';
import TournamentList from '@/Components/Tournaments/TournamentList.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { route } from 'ziggy-js';

type TournamentListItem = {
    id: number;
    code: number;
    name: string;
    description: string | null;
    created_at: string;
};

defineProps<{
    tournaments: TournamentListItem[];
}>();

const page = usePage<{
    flash: {
        success?: string | null;
    };
}>();
</script>

<template>
    <Head title="Mis torneos" />

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
                                Mis torneos
                            </h1>
                            <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                Crea y organiza tus propias competiciones. Esta primera version ya te permite
                                registrar torneos y verlos en un listado claro listo para seguir creciendo.
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
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Listado de torneos</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ tournaments.length }} torneo{{ tournaments.length === 1 ? '' : 's' }} creado{{ tournaments.length === 1 ? '' : 's' }}.
                        </p>
                    </div>

                    <TournamentEmptyState
                        v-if="tournaments.length === 0"
                        :create-href="route('tournaments.create')"
                    />

                    <TournamentList v-else :tournaments="tournaments" />
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
