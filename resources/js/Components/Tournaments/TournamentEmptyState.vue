<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    createHref: string;
}>();
</script>

<template>
    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm dark:border-slate-700 dark:bg-futbolix-dark">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-futbolix-gold/15">
            <svg class="h-8 w-8 text-futbolix-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
                />
            </svg>
        </div>

        <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Aun no has creado ningun torneo</h2>
        <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
            Empieza creando tu primer torneo para tener una base sobre la que anadir equipos, jornadas,
            clasificaciones y partidos en las siguientes iteraciones.
        </p>

        <Link
            :href="createHref"
            class="mt-6 inline-flex items-center rounded-lg bg-futbolix-green px-4 py-2 text-sm font-semibold text-white transition hover:bg-futbolix-green-dark"
        >
            Crear torneo
        </Link>
    </div>
</template>
