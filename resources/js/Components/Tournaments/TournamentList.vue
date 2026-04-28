<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

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
    is_public?: boolean;
    logo_url?: string | null;
    admin?: {
        id: number;
        name: string;
    } | null;
};

withDefaults(defineProps<{
    tournaments: TournamentListItem[];
    showAdmin?: boolean;
}>(), {
    showAdmin: false,
});

const formatter = new Intl.DateTimeFormat('es-ES', {
    dateStyle: 'medium',
});

function formatDate(value: string): string {
    return formatter.format(new Date(value));
}
</script>

<template>
    <div class="grid gap-4">
        <Link
            v-for="tournament in tournaments"
            :key="tournament.id"
            :href="route('tournaments.show', tournament.id)"
            class="block rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-futbolix-green/40 hover:shadow-[0_14px_30px_rgba(22,163,74,0.18)] dark:border-slate-700 dark:bg-futbolix-dark"
        >
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex min-w-0 gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-slate-100 dark:bg-slate-800">
                        <img
                            :src="tournament.logo_url || '/tournament-avatar.png'"
                            :alt="tournament.name"
                            class="h-full w-full object-cover"
                        >
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                                    {{ tournament.name }}
                                </h2>
                                <span
                                    class="inline-flex items-center rounded-full bg-futbolix-gold/15 px-2.5 py-1 text-xs font-medium text-futbolix-gold"
                                >
                                    Codigo {{ tournament.code }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-futbolix-green/10 px-2.5 py-1 text-xs font-medium text-futbolix-green">
                                    {{ tournament.format.label }}
                                </span>
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="tournament.is_public
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                                        : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200'"
                                >
                                    {{ tournament.is_public ? 'Visible' : 'Oculto' }}
                                </span>
                            </div>

                            <p class="max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                                {{ tournament.description || 'Sin descripcion por ahora.' }}
                            </p>

                            <p v-if="showAdmin && tournament.admin?.name" class="text-sm text-slate-500 dark:text-slate-400">
                                Administrador: {{ tournament.admin.name }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 rounded-xl bg-slate-100 px-4 py-3 text-sm text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    <span class="block text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Creado
                    </span>
                    <span class="mt-1 block font-semibold">{{ formatDate(tournament.created_at) }}</span>
                </div>
            </div>
        </Link>
    </div>
</template>
