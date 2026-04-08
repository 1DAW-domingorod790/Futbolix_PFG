<script setup lang="ts">
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

const formatter = new Intl.DateTimeFormat('es-ES', {
    dateStyle: 'medium',
});

function formatDate(value: string): string {
    return formatter.format(new Date(value));
}
</script>

<template>
    <div class="grid gap-4">
        <article
            v-for="tournament in tournaments"
            :key="tournament.id"
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-futbolix-green/40 dark:border-slate-700 dark:bg-futbolix-dark"
        >
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
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
                    </div>

                    <p class="max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                        {{ tournament.description || 'Sin descripcion por ahora.' }}
                    </p>
                </div>

                <div class="shrink-0 rounded-xl bg-slate-100 px-4 py-3 text-sm text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    <span class="block text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Creado
                    </span>
                    <span class="mt-1 block font-semibold">{{ formatDate(tournament.created_at) }}</span>
                </div>
            </div>
        </article>
    </div>
</template>
