<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
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

const showFlash = ref(!!page.props.flash?.success);
let flashTimer: ReturnType<typeof setTimeout> | null = null;

watch(() => page.props.flash?.success, (val) => {
    if (val) {
        showFlash.value = true;
        if (flashTimer) clearTimeout(flashTimer);
        flashTimer = setTimeout(() => { showFlash.value = false; }, 4000);
    }
}, { immediate: true });

const csvForm = useForm({ csv: null as File | null });

function onCsvImport(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    csvForm.csv = file;
    csvForm.post(route('tournaments.import-tournament-csv'), {
        forceFormData: true,
        onSuccess: () => { csvForm.reset(); (e.target as HTMLInputElement).value = ''; },
    });
}
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

                        <div class="flex gap-2">
                            <label class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-futbolix-green/50 bg-futbolix-green/10 px-5 py-3 text-sm font-semibold text-futbolix-green transition hover:bg-futbolix-green/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                {{ csvForm.processing ? 'Importando...' : 'Importar CSV' }}
                                <input type="file" accept=".csv" class="hidden" :disabled="csvForm.processing" @change="onCsvImport" />
                            </label>
                            <Link
                                :href="route('tournaments.create')"
                                class="inline-flex items-center justify-center rounded-lg bg-futbolix-green px-5 py-3 text-sm font-semibold text-white transition hover:bg-futbolix-green-dark"
                            >
                                Crear torneo
                            </Link>
                        </div>
                    </div>
                </section>

                <div
                    v-if="showFlash && page.props.flash?.success"
                    class="rounded-2xl border border-futbolix-green/30 bg-futbolix-green/10 px-4 py-3 text-sm text-futbolix-green"
                >
                    {{ page.props.flash.success }}
                </div>

                <div
                    v-if="(page.props.flash as any)?.error"
                    class="rounded-2xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-700 dark:bg-red-900/20 dark:text-red-400"
                >
                    {{ (page.props.flash as any).error }}
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
